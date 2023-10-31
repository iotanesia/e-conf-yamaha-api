<?php

namespace App\Query;

use App\Constants\Constant;
use App\Models\RegularStokConfirmationOutstockNote AS Model;
use App\Models\RegularStokConfirmation;
use App\ApiHelper as Helper;
use App\Models\MstPart;
use App\Models\MstShipment;
use App\Models\RegularDeliveryPlan;
use App\Models\RegularDeliveryPlanBox;
use App\Models\RegularDeliveryPlanProspectContainer;
use App\Models\RegularDeliveryPlanProspectContainerCreation;
use App\Models\RegularDeliveryPlanSet;
use App\Models\RegularStokConfirmationHistory;
use App\Models\RegularStokConfirmationTemp;
use App\Models\RegularFixedQuantityConfirmation;
use App\Models\RegularFixedQuantityConfirmationBox;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Str;
use Barryvdh\DomPDF\Facade\Pdf;

class QueryStockConfirmationOutstockNote extends Model {
    const cast = 'regular-stock-confirmation-outstock-note';
    public static function storeOutStockNote($request,$is_transaction = true)
    {
        if($is_transaction) DB::beginTransaction();
        try {
            //update status outstock
            $stokTempUpdate = RegularStokConfirmationTemp::whereIn('qr_key',$request->id)->get();
            $id_stock_confirmation = [];
            foreach ($stokTempUpdate as $key => $value) {
                $id_stock_confirmation[] = $value->id_stock_confirmation;
                $update = RegularStokConfirmationTemp::where('id',$value->id)->first();
                $update->update(['status_outstock' => 3]);
            }

            RegularStokConfirmation::whereIn('id',$id_stock_confirmation)->get()->map(function ($item){
                    $item->status_outstock = 3;
                    $item->save();
                    return $item;
            });

            //update tracking
            foreach ($request->id as $id_params) {
                if (count(explode('-',$id_params)) > 1) {
                    $id = explode('-',$id_params)[0];
                    $total_item = explode('-',$id_params)[1];
    
                    $delivery_plan_box = RegularDeliveryPlanBox::find($id);
                    $box = RegularDeliveryPlanBox::where('id_regular_delivery_plan', $delivery_plan_box->refRegularDeliveryPlan->id)
                                                    ->whereNotNull('qrcode')
                                                    ->orderBy('qty_pcs_box', 'desc')
                                                    ->orderBy('id', 'asc')
                                                    ->get();

                    $qty_pcs_box = [];
                    $id_plan_box = [];
                    $id_box = [];
                    foreach ($box as $key => $val) {
                        if ($val->id === $delivery_plan_box->id) {
                            for ($i=0; $i < $total_item; $i++) { 
                                $qty_pcs_box[] = $box[$key+$i]->qty_pcs_box;
                                $id_plan_box[] = $box[$key+$i]->id;
                                $id_box[] = $box[$key+$i]->id_box;
                            }
                        }
                    }
    
                    $deliv_plan_set = RegularDeliveryPlanSet::where('id_delivery_plan', $delivery_plan_box->refRegularDeliveryPlan->id)->get()->pluck('item_no');
                    
                    $qty_pcs_box_res = array_sum($qty_pcs_box) / count($deliv_plan_set);
    
                    $stock_confirmation = $delivery_plan_box->refRegularDeliveryPlan->refRegularStockConfirmation;
                    $in_stock_wh = $stock_confirmation->in_wh;
                    $in_dc_total = $stock_confirmation->in_dc - $qty_pcs_box_res;
                    $in_wh_total = $in_stock_wh + $qty_pcs_box_res;
    
                    $stock_confirmation->in_dc = $in_dc_total;
                    $stock_confirmation->in_wh = $in_wh_total;
                    $stock_confirmation->save();
    
                } else {
                    $delivery_plan_box = RegularDeliveryPlanBox::find($id_params);
    
                    $stock_confirmation = $delivery_plan_box->refRegularDeliveryPlan->refRegularStockConfirmation;
                    $in_stock_wh = $stock_confirmation->in_wh;
                    $in_wh_total = $in_stock_wh + $delivery_plan_box->qty_pcs_box;
                    $in_dc_total = $stock_confirmation->in_dc - $delivery_plan_box->qty_pcs_box;

                    $stock_confirmation->in_dc = $in_dc_total;
                    $stock_confirmation->in_wh = $in_wh_total;
                    $stock_confirmation->save();
                }
            }

            //update ke fix quantity
            // if ($stock_confirmation->in_dc == 0 && $stock_confirmation->in_wh == $stock_confirmation->qty && $stock_confirmation->production == 0) {
                $stock_confirmation->status_instock = 3;
                $stock_confirmation->save();

                $fixed_quantity_confirmation = new RegularFixedQuantityConfirmation;
                $fixed_quantity_confirmation->id_regular_delivery_plan = $stock_confirmation->id_regular_delivery_plan;
                $fixed_quantity_confirmation->datasource = $fixed_quantity_confirmation->refRegularDeliveryPlan->datasource;
                $fixed_quantity_confirmation->code_consignee = $fixed_quantity_confirmation->refRegularDeliveryPlan->code_consignee;
                $fixed_quantity_confirmation->model = $fixed_quantity_confirmation->refRegularDeliveryPlan->model;
                $fixed_quantity_confirmation->item_no = $fixed_quantity_confirmation->refRegularDeliveryPlan->item_no;
                $fixed_quantity_confirmation->item_serial = $fixed_quantity_confirmation->refRegularDeliveryPlan->item_no == null ? null : $fixed_quantity_confirmation->refRegularDeliveryPlan->refPart->item_serial;
                $fixed_quantity_confirmation->disburse = $fixed_quantity_confirmation->refRegularDeliveryPlan->disburse;
                $fixed_quantity_confirmation->delivery = $fixed_quantity_confirmation->refRegularDeliveryPlan->delivery;
                $fixed_quantity_confirmation->qty = $fixed_quantity_confirmation->refRegularDeliveryPlan->qty;
                $fixed_quantity_confirmation->order_no = $fixed_quantity_confirmation->refRegularDeliveryPlan->order_no;
                $fixed_quantity_confirmation->cust_item_no = $fixed_quantity_confirmation->refRegularDeliveryPlan->cust_item_no;
                $fixed_quantity_confirmation->etd_ypmi = $fixed_quantity_confirmation->refRegularDeliveryPlan->etd_ypmi;
                $fixed_quantity_confirmation->etd_wh = $fixed_quantity_confirmation->refRegularDeliveryPlan->etd_wh;
                $fixed_quantity_confirmation->etd_jkt = $fixed_quantity_confirmation->refRegularDeliveryPlan->etd_jkt;
                $fixed_quantity_confirmation->in_dc = $stock_confirmation->in_dc;
                $fixed_quantity_confirmation->in_wh = $stock_confirmation->in_wh;
                $fixed_quantity_confirmation->production = $stock_confirmation->production;
                $fixed_quantity_confirmation->is_actual = 0;
                $fixed_quantity_confirmation->status = 1;
                $fixed_quantity_confirmation->save();

                foreach ($fixed_quantity_confirmation->refRegularDeliveryPlan->manyDeliveryPlanBox as $item_box) {
                    $fixed_quantity_confirmation_box = new RegularFixedQuantityConfirmationBox;
                    $fixed_quantity_confirmation_box->id_fixed_quantity_confirmation = $fixed_quantity_confirmation->id;
                    $fixed_quantity_confirmation_box->id_regular_delivery_plan = $fixed_quantity_confirmation->id_regular_delivery_plan;
                    $fixed_quantity_confirmation_box->id_regular_delivery_plan_box = $item_box->id;
                    $fixed_quantity_confirmation_box->id_box = $item_box->id_box;
                    $fixed_quantity_confirmation_box->id_proc = $item_box->id_proc;
                    $fixed_quantity_confirmation_box->qty_pcs_box = $item_box->qty_pcs_box;
                    $fixed_quantity_confirmation_box->lot_packing = $item_box->lot_packing;
                    $fixed_quantity_confirmation_box->packing_date = $item_box->packing_date;
                    $fixed_quantity_confirmation_box->qrcode = $item_box->qrcode;
                    $fixed_quantity_confirmation_box->is_labeling = $item_box->is_labeling;
                    $fixed_quantity_confirmation_box->save();
                }

            // }
            

            $lastData = Model::latest()->first();
            Helper::generateCodeLetter($lastData);
            $stokTemp = RegularStokConfirmationTemp::whereIn('qr_key', $request->id)->get()->pluck('id_stock_confirmation');
            $stokConfirmation = RegularStokConfirmation::whereIn('id',$stokTemp->toArray())->get();
            $idDeliveryPlan = $stokConfirmation->pluck('id_regular_delivery_plan')->toArray();
            $deliveryPlan = RegularDeliveryPlan::select(
                DB::raw("string_agg(DISTINCT b.nick_name::character varying, ',') as code_consignee"),
                DB::raw("string_agg(DISTINCT regular_delivery_plan.id_prospect_container_creation::character varying, ',') as id_prospect_container_creation")
            )
            ->whereIn('regular_delivery_plan.id',$idDeliveryPlan)
            ->join('mst_consignee as b','b.code','regular_delivery_plan.code_consignee')
            ->get();

            $dataSend =  $deliveryPlan->transform(function($item) use($lastData,$request,$stokTemp){
                $creation = RegularDeliveryPlanProspectContainerCreation::where('id', $item->id_prospect_container_creation)->first();
                $lsp = $creation == null ? null : $creation->refMstLsp->name;
                $truck = $creation == null ? null : $creation->refMstTypeDelivery->name;
                return [
                    'shipper'=>MstShipment::where('is_active',Constant::IS_ACTIVE)->first()->shipment ?? null,
                    'yth'=> $request->yth ?? $lsp,
                    'consignee'=> $request->username ?? $item->code_consignee,
                    'no_letters'=>Helper::generateCodeLetter($lastData),
                    'delivery_date'=>Carbon::now()->format('Y-m-d'),
                    'truck_type'=>$truck,
                    'truck_no' => $request->truck_no ?? null,
                    'id_stock_confirmation' =>$stokTemp->toArray()
                ];
            });

            $dataSendDetail = RegularStokConfirmation::select(
                DB::raw("string_agg(DISTINCT regular_stock_confirmation.id::character varying, ',') as id_stock_confirmation"),
                DB::raw("string_agg(DISTINCT a.item_no::character varying, ',') as item_no"),
                DB::raw("string_agg(DISTINCT a.order_no::character varying, ',') as order_no"),
                DB::raw("string_agg(DISTINCT a.id_prospect_container::character varying, ',') as id_prospect_container"),
                DB::raw("SUM(CAST(regular_stock_confirmation.in_wh as INT)) as qty")
            )
            ->whereIn('regular_stock_confirmation.id',$stokTemp->toArray())
            ->join('regular_delivery_plan as a','a.id','regular_stock_confirmation.id_regular_delivery_plan')
            ->groupBy('a.id')
            ->get();

            $dataSendDetail->transform(function($item){
                $prospect = RegularDeliveryPlanProspectContainer::where('id', $item->id_prospect_container)->first();
                return [
                    'id_stock_confirmation' => $item->id_stock_confirmation,
                    'item_no' => $item->item_no,
                    'order_no' => $item->order_no,
                    'qty' => $item->qty,
                    'no_packing' => $prospect == null ? null : $prospect->no_packing
                ];
            });

            if(isset($dataSend[0])) {
                $insert = Model::create($dataSend[0]);
                foreach ($dataSendDetail as $value) {
                    $insert->manyRegularStockConfirmationOutstockNoteDetail()->createMany(self::getParamDetail($value,$insert));
                }
            }
            if($is_transaction) DB::commit();
            return ['items'=>$dataSend];
        } catch (\Throwable $th) {
            if($is_transaction) DB::rollBack();
            throw $th;
        }
    }
    
    public static function getParamDetail($params,$data) 
    {
        $res[] = [
            'item_no' => $params['item_no'],
            'order_no' => $params['order_no'],
            'qty' => $params['qty'],
            'no_packing' => $params['no_packing'],
            'id_stock_confirmation_outstock_note' => $data->id,
            'id_stock_confirmation' => $params['id_stock_confirmation']
        ];

        return $res;
    }

    public static function downloadOutStockNote($request,$pathToFile,$filename)
    {
        // try {
            $stokTemp = RegularStokConfirmationTemp::whereIn('qr_key',$request->id)->first();
            $data = Model::whereJsonContains('id_stock_confirmation',[$stokTemp->id_stock_confirmation])->orderBy('id','desc')->first();
            
            $words = explode(' ', $data->shipper);
            $data->shipperFirstWords = str_replace("JL.", " ",implode(' ', array_slice($words, 0, 13)));
            $data->shipperLastWords = str_replace("LTD", " ",implode(' ', array_slice($words, -23)));

            if ($data->manyRegularStockConfirmationOutstockNoteDetail[0]->item_no == null) {
                $data->item_no = RegularDeliveryPlanSet::where('id_delivery_plan', $stokTemp->id_regular_delivery_plan)->pluck('item_no');
                $data->description = MstPart::whereIn('item_no', $data->item_no)->pluck('description');
            }

            Pdf::loadView('pdf.stock-confirmation.outstock.delivery_note',[
              'data' => $data
            ])
            ->save($pathToFile)
            ->setPaper('A4','potrait')
            ->download($filename);
        //   } catch (\Throwable $th) {
        //       return Helper::setErrorResponse($th);
        //   }
    }
}
