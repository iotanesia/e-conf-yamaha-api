<?php

namespace App\Query;

use App\Constants\Constant;
use App\Models\RegularStokConfirmationOutstockNote AS Model;
use App\Models\RegularStokConfirmation;
use App\ApiHelper as Helper;
use App\Models\MstBox;
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
use App\Models\RegularStokConfirmationOutstockNoteDetail;
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
                // if (count(explode('-',$id_params)) > 1) {
                //     $id = explode('-',$id_params)[0];
                //     $total_item = explode('-',$id_params)[1];
    
                //     $delivery_plan_box = RegularDeliveryPlanBox::find($id);
                //     $box = RegularDeliveryPlanBox::where('id_regular_delivery_plan', $delivery_plan_box->refRegularDeliveryPlan->id)
                //                                     ->whereNotNull('qrcode')
                //                                     ->orderBy('qty_pcs_box', 'desc')
                //                                     ->orderBy('id', 'asc')
                //                                     ->get();

                //     $qty_pcs_box = [];
                //     $id_plan_box = [];
                //     $id_box = [];
                //     foreach ($box as $key => $val) {
                //         if ($val->id === $delivery_plan_box->id) {
                //             for ($i=0; $i < $total_item; $i++) { 
                //                 $qty_pcs_box[] = $box[$key+$i]->qty_pcs_box;
                //                 $id_plan_box[] = $box[$key+$i]->id;
                //                 $id_box[] = $box[$key+$i]->id_box;
                //             }
                //         }
                //     }
    
                //     $deliv_plan_set = RegularDeliveryPlanSet::where('id_delivery_plan', $delivery_plan_box->refRegularDeliveryPlan->id)->get()->pluck('item_no');
                    
                //     $qty_pcs_box_res = array_sum($qty_pcs_box) / count($deliv_plan_set);
    
                //     $stock_confirmation = $delivery_plan_box->refRegularDeliveryPlan->refRegularStockConfirmation;
                //     $in_stock_wh = $stock_confirmation->in_wh;
                //     $in_dc_total = $stock_confirmation->in_dc - $qty_pcs_box_res;
                //     $in_wh_total = $in_stock_wh + $qty_pcs_box_res;
    
                //     $stock_confirmation->in_dc = $in_dc_total;
                //     $stock_confirmation->in_wh = $in_wh_total;
                //     $stock_confirmation->save();
    
                // } else {
                    if (count(explode('-',$id_params)) > 1) {
                        $id = explode('-',$id_params)[0];
                        $id_plan_box = $id;
                    } else {
                        $id_plan_box = $id_params;
                    }

                    $delivery_plan_box = RegularDeliveryPlanBox::find($id_plan_box);
    
                    $stock_confirmation = $delivery_plan_box->refRegularDeliveryPlan->refRegularStockConfirmation;
                    $in_stock_wh = $stock_confirmation->in_wh;
                    $in_wh_total = $in_stock_wh + $delivery_plan_box->qty_pcs_box;
                    $in_dc_total = $stock_confirmation->in_dc - $delivery_plan_box->qty_pcs_box;

                    $stock_confirmation->in_dc = $in_dc_total;
                    $stock_confirmation->in_wh = $in_wh_total;
                    $stock_confirmation->save();
                // }

                //update ke fix quantity
                if ($stock_confirmation->in_dc == 0 && $stock_confirmation->in_wh == $stock_confirmation->qty && $stock_confirmation->production == 0) {
                    $stock_confirmation->status_instock = 3;
                    $stock_confirmation->save();
                }  

                // $fixed_quantity_confirmation = RegularFixedQuantityConfirmation::where('id_regular_delivery_plan', $stock_confirmation->id_regular_delivery_plan)->first();
                // if(!$fixed_quantity_confirmation) $fixed_quantity_confirmation = new RegularFixedQuantityConfirmation;
                $fixed_quantity_confirmation = new RegularFixedQuantityConfirmation;
                $fixed_quantity_confirmation->id_regular_delivery_plan = $stock_confirmation->id_regular_delivery_plan;
                $attr['id_regular_delivery_plan'] = $stock_confirmation->id_regular_delivery_plan;
                $attr['datasource'] = $fixed_quantity_confirmation->refRegularDeliveryPlan->datasource;
                $attr['code_consignee'] = $fixed_quantity_confirmation->refRegularDeliveryPlan->code_consignee;
                $attr['model'] = $fixed_quantity_confirmation->refRegularDeliveryPlan->model;
                $attr['item_no'] = $fixed_quantity_confirmation->refRegularDeliveryPlan->item_no;
                $attr['item_serial'] = $fixed_quantity_confirmation->refRegularDeliveryPlan->item_no == null ? null : $fixed_quantity_confirmation->refRegularDeliveryPlan->refPart->item_serial;
                $attr['disburse'] = $fixed_quantity_confirmation->refRegularDeliveryPlan->disburse;
                $attr['delivery'] = $fixed_quantity_confirmation->refRegularDeliveryPlan->delivery;
                $attr['qty'] = $fixed_quantity_confirmation->refRegularDeliveryPlan->qty;
                $attr['order_no'] = $fixed_quantity_confirmation->refRegularDeliveryPlan->order_no;
                $attr['cust_item_no'] = $fixed_quantity_confirmation->refRegularDeliveryPlan->cust_item_no;
                $attr['etd_ypmi'] = $fixed_quantity_confirmation->refRegularDeliveryPlan->etd_ypmi;
                $attr['etd_wh'] = $fixed_quantity_confirmation->refRegularDeliveryPlan->etd_wh;
                $attr['etd_jkt'] = $fixed_quantity_confirmation->refRegularDeliveryPlan->etd_jkt;
                $attr['in_dc'] = $stock_confirmation->in_dc;
                $attr['in_wh'] = $stock_confirmation->in_wh;
                $attr['production'] = $stock_confirmation->production;
                $attr['is_actual'] = 0;
                $attr['status'] = 1;
                $fixed_quantity_confirmation->fill($attr);
                $fixed_quantity_confirmation->save();

                foreach ($fixed_quantity_confirmation->refRegularDeliveryPlan->manyDeliveryPlanBox as $item_box) {
                    $check_outstock = RegularStokConfirmationHistory::where('id_regular_delivery_plan_box', $item_box->id)->where('type', 'OUTSTOCK')->first();
                    $check_status_outstock = RegularStokConfirmationTemp::where('qr_key', $item_box->id.'-'.count($item_box->refRegularDeliveryPlan->manyDeliveryPlanSet))->where('status_outstock', 3)->first();
                    if ($check_outstock && $check_status_outstock) {
                        $fixed_quantity_confirmation_box = RegularFixedQuantityConfirmationBox::where('id_regular_delivery_plan_box', $item_box->id)->first();
                        if(!$fixed_quantity_confirmation_box) {
                            $fixed_quantity_confirmation_box = new RegularFixedQuantityConfirmationBox;
                            $attr['id_fixed_quantity_confirmation'] = $fixed_quantity_confirmation->id;
                            $attr['id_regular_delivery_plan'] = $fixed_quantity_confirmation->id_regular_delivery_plan;
                            $attr['id_regular_delivery_plan_box'] = $item_box->id;
                            $attr['id_box'] = $item_box->id_box;
                            $attr['qty_pcs_box'] = $item_box->qty_pcs_box;
                            $attr['id_proc'] = $item_box->id_proc;
                            $attr['lot_packing'] = $item_box->lot_packing;
                            $attr['packing_date'] = $item_box->packing_date;
                            $attr['qrcode'] = $item_box->qrcode;
                            $attr['is_labeling'] = $fixed_quantity_confirmation_box->is_labeling == 1 ? $fixed_quantity_confirmation_box->is_labeling : $item_box->is_labeling;
                            $fixed_quantity_confirmation_box->fill($attr);
                            $fixed_quantity_confirmation_box->save();
                        }
                    }
                }

            }

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
                $lsp = $creation == null ? null : ($creation->refMstLsp->name ?? null);
                $truck = $creation == null ? null : ($creation->refMstTypeDelivery->name ?? null);
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

            // $dataSendDetail = RegularStokConfirmation::select(
            //     DB::raw("string_agg(DISTINCT regular_stock_confirmation.id::character varying, ',') as id_stock_confirmation"),
            //     DB::raw("string_agg(DISTINCT a.item_no::character varying, ',') as item_no"),
            //     DB::raw("string_agg(DISTINCT a.order_no::character varying, ',') as order_no"),
            //     DB::raw("string_agg(DISTINCT a.id_prospect_container::character varying, ',') as id_prospect_container"),
            //     DB::raw("SUM(CAST(regular_stock_confirmation.in_wh as INT)) as qty")
            // )
            // ->whereIn('regular_stock_confirmation.id',$stokTemp->toArray())
            // ->join('regular_delivery_plan as a','a.id','regular_stock_confirmation.id_regular_delivery_plan')
            // ->groupBy('a.id')
            // ->get();

            $dataSendDetail = RegularStokConfirmationTemp::whereIn('qr_key', $request->id)->get();

            $dataSendDetail->transform(function($item){

                // $plan_box = RegularDeliveryPlanBox::where('id_regular_delivery_plan',$item->id_regular_delivery_plan)->orderBy('qty_pcs_box','desc')->orderBy('id','asc')->get();
            
                // if($item->refRegularDeliveryPlan->item_no == null) {
                //     $plan_set = RegularDeliveryPlanSet::where('id_delivery_plan',$item->id_regular_delivery_plan)->get()->pluck('item_no');
                //     $check_scan = RegularStokConfirmationHistory::where('id_regular_delivery_plan',$item->id_regular_delivery_plan)->where('type','OUTSTOCK')->get()->pluck('id_regular_delivery_plan_box');
    
                //     $mst_box = MstBox::where('part_set', 'set')->whereIn('item_no', $plan_set->toArray())->get();
                //     $sum_qty = [];
                //     foreach ($mst_box as $key => $value_box) {
                //         $sum_qty[] = $value_box->qty;
                //     }
    
                //     $result_qty = [];
                //     $result_id_planbox = [];
                //     $result_arr = [];
                //     $qty = 0;
                //     $group_qty = [];
                //     $group_id_planbox = [];
                //     $group_arr = [];
                //     foreach ($plan_box as $key => $val) {
                //         $qty += $val->qty_pcs_box;
                //         if (in_array($val->id,$check_scan->toArray())) {
                //             $group_qty[] = $val->qty_pcs_box;
                //             $group_id_planbox[] = $val->id;
                //         }
    
                //         if ($qty >= (array_sum($sum_qty) * count($plan_set->toArray()))) {
                //             $result_qty[] = $group_qty;
                //             $result_id_planbox[] = $group_id_planbox;
                //             $result_arr[] = $group_arr[0] ?? [];
                //             $qty = 0;
                //             $group_qty = [];
                //             $group_id_planbox = [];
                //             $group_arr = [];
                //         }
                //     }
    
                //     if (!empty($group_qty)) {
                //         $result_qty[] = $group_qty;
                //     }
                //     if (!empty($group_id_planbox)) {
                //         $result_id_planbox[] = $group_id_planbox;
                //     }
                //     if (!empty($group_arr)) {
                //         $result_arr[] = $group_arr[0];
                //     }
                    
                //     $in_wh_arr = [];
                //     for ($i=0; $i < count($result_qty); $i++) { 
                //         if (count($result_qty[$i]) !== 0) {
                //             $in_wh_arr[] = (array_sum($result_qty[$i]) / count($plan_set->toArray()));
                //         }
                //     }

                //     $valueCounts = array_count_values($in_wh_arr);
                //     $maxCount = max($valueCounts);
                //     $maxValues = array_keys($valueCounts, $maxCount);
                //     $in_wh = $maxValues[0];
                        
                //     $mst_part = MstPart::whereIn('item_no', $plan_set->toArray())->get();
                //     $item_no = [];
                //     $item_name = [];
                //     foreach ($mst_part as $key => $value) {
                //         $item_no[] = $value->item_no;
                //         $item_name[] = $value->description;
                //     }
                // } else {
                    // $mst_part = MstPart::where('item_no', $item->refRegularDeliveryPlan->item_no)->first();
                    // $item_no = $mst_part->item_no;
                    // $item_name = $mst_part->description;
                    // $in_wh = $item->qty;
                // }

                $prospect = RegularDeliveryPlanProspectContainer::where('id', $item->id_prospect_container)->first();
                return [
                    'id_stock_confirmation' => $item->id_stock_confirmation,
                    'item_no' => $item->refRegularDeliveryPlan->item_no,
                    'order_no' => $item->refRegularDeliveryPlan->order_no,
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
        try {
            $stokTemp = RegularStokConfirmationTemp::whereIn('qr_key',$request->id)->orderBy('id_stock_confirmation', 'asc')->get();
            $data = Model::whereJsonContains('id_stock_confirmation',[$stokTemp[0]->id_stock_confirmation])->orderBy('id','desc')->first();
            
            $words = explode(' ', $data->shipper);
            $data->shipperFirstWords = str_replace("JL.", " ",implode(' ', array_slice($words, 0, 13)));
            $data->shipperLastWords = str_replace("LTD", " ",implode(' ', array_slice($words, -23)));

            $items = RegularStokConfirmationOutstockNoteDetail::select('id_stock_confirmation', 'qty',
                                                                DB::raw("string_agg(DISTINCT regular_stock_confirmation_outstock_note_detail.id::character varying, ',') as id_note_detail"),
                                                                DB::raw("string_agg(DISTINCT regular_stock_confirmation_outstock_note_detail.item_no::character varying, ',') as item_no"),
                                                                DB::raw("string_agg(DISTINCT regular_stock_confirmation_outstock_note_detail.order_no::character varying, ',') as order_no"),
                                                                DB::raw("string_agg(DISTINCT regular_stock_confirmation_outstock_note_detail.no_packing::character varying, ',') as no_packing"),
                                                            )->where('id_stock_confirmation_outstock_note', $data->id)
                                                            ->groupBy('id_stock_confirmation', 'qty')
                                                            ->get();

            Pdf::loadView('pdf.stock-confirmation.outstock.delivery_note',[
              'data' => $data,
              'items' => $items,
            ])
            ->save($pathToFile)
            ->setPaper('A4','potrait')
            ->download($filename);
          } catch (\Throwable $th) {
              return Helper::setErrorResponse($th);
          }
    }
}
