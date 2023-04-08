<?php

namespace App\Query;

use App\Constants\Constant;
use App\Models\RegularStokConfirmationOutstockNote AS Model;
use App\Models\RegularStokConfirmation;
use App\ApiHelper as Helper;
use App\Models\MstShipment;
use App\Models\RegularDeliveryPlan;
use App\Models\RegularDeliveryPlanBox;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Str;
class QueryStockConfirmationOutstockNote extends Model {
    const cast = 'regular-stock-confirmation-outstock-note';
    public static function storeOutStockNote($request,$is_transaction = true)
    {
        if($is_transaction) DB::beginTransaction();
        try {
            $lastData = Model::latest()->first();
            Helper::generateCodeLetter($lastData);
            $stokConfirmation = RegularStokConfirmation::whereIn('id',$request->id)->get();
            $idDeliveryPlan = $stokConfirmation->pluck('id_regular_delivery_plan')->toArray();
            $deliveryPlan = RegularDeliveryPlan::select(DB::raw("string_agg(DISTINCT b.nick_name::character varying, ',') as code_consignee"),DB::raw("string_agg(DISTINCT c.name::character varying, ',') as lsp"))
            ->whereIn('regular_delivery_plan.id',$idDeliveryPlan)
            ->join('regular_delivery_plan_prospect_container_creation as a','a.id','regular_delivery_plan.id_prospect_container_creation')
            ->join('mst_consignee as b','b.code','regular_delivery_plan.code_consignee')
            ->join('mst_lsp as c','c.id','a.id_lsp')
            ->get();
            $deliveryPlanDetail = RegularDeliveryPlan::whereIn('regular_delivery_plan.id',$idDeliveryPlan)->get();
            dd($deliveryPlanDetail);
            $dataSend =  $deliveryPlan->transform(function($item) use($lastData){
                return [
                    'shipper'=>MstShipment::where('is_active',Constant::IS_ACTIVE)->first()->shipment ?? null,
                    'yth'=>$item->lsp,
                    'consignee'=>$item->code_consignee,
                    'no_letters'=>Helper::generateCodeLetter($lastData),
                    'delivery_date'=>Carbon::now()->format('d-m-Y'),
                    'truck_type'=>'LCL'
                ];
            });
            $dataSendDetail = $deliveryPlanDetail->transform(function($item){
                return [
                    'item_no' => $item->item_no,
                    'order_no' => $item->order_no,
                    'qty' => $item->refRegularStockConfirmation->count_box ?? null,
                    'no_packing' => $item->refRegularDeliveryPlanProspectContainer->no_packing ?? null,
                ];
            });
            if(isset($dataSend[0])) {
                $insert = Model::create($dataSend[0]);
                $insert->manyRegularStockConfirmationOutstockNoteDetail()->createMany(self::getParamDetail($dataSendDetail,$insert));
            }
            if($is_transaction) DB::commit();
            return ['items'=>$dataSend];
        } catch (\Throwable $th) {
            if($is_transaction) DB::rollBack();
            throw $th;
        }
    }
    public static function getParamDetail($params,$data) {
        foreach ($params as $value) {
            $res[] = [
                'item_no' => $value->item_no,
                'order_no' => $value->order_no,
                'qty' => $value->qty,
                'no_packing' => $value->no_packing,
                'id_regular_delivery_plan' => $data->id
            ];
        }
    }
}
