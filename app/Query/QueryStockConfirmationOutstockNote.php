<?php

namespace App\Query;

use App\Constants\Constant;
use App\Models\RegularStokConfirmationOutstockNote AS Model;
use App\Models\RegularStokConfirmation;
use App\ApiHelper as Helper;
use App\Models\MstShipment;
use App\Models\RegularDeliveryPlan;
use App\Models\RegularDeliveryPlanBox;
use App\Models\RegularDeliveryPlanProspectContainer;
use App\Models\RegularStokConfirmationTemp;
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
            $lastData = Model::latest()->first();
            Helper::generateCodeLetter($lastData);
            $stokTemp = RegularStokConfirmationTemp::where('id', $request->id)->get()->pluck('id_stock_confirmation');
            $stokConfirmation = RegularStokConfirmation::whereIn('id',$stokTemp->toArray())->get();
            $idDeliveryPlan = $stokConfirmation->pluck('id_regular_delivery_plan')->toArray();
            $deliveryPlan = RegularDeliveryPlan::select(DB::raw("string_agg(DISTINCT b.nick_name::character varying, ',') as code_consignee"),DB::raw("string_agg(DISTINCT c.name::character varying, ',') as lsp"),DB::raw("string_agg(DISTINCT d.name::character varying, ',') as truck_type"))
            ->whereIn('regular_delivery_plan.id',$idDeliveryPlan)
            ->join('regular_delivery_plan_prospect_container_creation as a','a.id','regular_delivery_plan.id_prospect_container_creation')
            ->join('mst_consignee as b','b.code','regular_delivery_plan.code_consignee')
            ->join('mst_lsp as c','c.id','a.id_lsp')
            ->join('mst_type_delivery as d','d.id','a.id_type_delivery')
            ->get();

            $dataSend =  $deliveryPlan->transform(function($item) use($lastData,$request,$stokTemp){
                return [
                    'shipper'=>MstShipment::where('is_active',Constant::IS_ACTIVE)->first()->shipment ?? null,
                    'yth'=> $request->yth ?? $item->lsp,
                    'consignee'=> $request->username ?? $item->code_consignee,
                    'no_letters'=>Helper::generateCodeLetter($lastData),
                    'delivery_date'=>Carbon::now()->format('Y-m-d'),
                    'truck_type'=>$item->truck_type,
                    'truck_no' => $request->truck_no ?? null,
                    'id_stock_confirmation' =>$stokTemp->toArray()[0]
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
            ->join('mst_part as c','c.item_no','a.item_no')
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
                $insert->manyRegularStockConfirmationOutstockNoteDetail()->createMany(self::getParamDetail($dataSendDetail[0],$insert));
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
            $stokTemp = RegularStokConfirmationTemp::whereIn('id',$request->id)->first();
            $data = Model::whereJsonContains('id_stock_confirmation',["$stokTemp->id_stock_confirmation"])->orderBy('id','desc')->first();

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
