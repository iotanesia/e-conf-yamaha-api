<?php

namespace App\Query;

use App\Constants\Constant;
use App\Models\RegularStokConfirmationHistory AS Model;
use App\Models\RegularStokConfirmation;
use App\ApiHelper as Helper;
use App\Models\MstContainer;
use App\Models\MstLsp;
use App\Models\RegularDeliveryPlan;
use App\Models\RegularDeliveryPlanBox;
use App\Models\RegularDeliveryPlanProspectContainerCreation;
use Illuminate\Support\Facades\DB;

class QueryStockConfirmationHistory extends Model {

    const cast = 'regular-stock-confirmation-history';

    public static function deleteInStock($request,$id,$is_transaction = true)
    {
        if($is_transaction) DB::beginTransaction();
        try {
            Model::where('id_regular_delivery_plan',$id)->where('type',Constant::INSTOCK)->delete();
            RegularStokConfirmation::where('id_regular_delivery_plan',$id)->update(['in_dc'=>Constant::IS_NOL]);
            
            if($is_transaction) DB::commit();
        } catch (\Throwable $th) {
            if($is_transaction) DB::rollBack();
            throw $th;
        }
    }
    public static function deleteOutStock($request,$id,$is_transaction = true)
    {
        if($is_transaction) DB::beginTransaction();
        try {
            Model::where('id_regular_delivery_plan',$id)->where('type',Constant::OUTSTOCK)->delete();
            RegularStokConfirmation::where('id_regular_delivery_plan',$id)->update(['in_wh'=>Constant::IS_NOL]);
            
            if($is_transaction) DB::commit();
        } catch (\Throwable $th) {
            if($is_transaction) DB::rollBack();
            throw $th;
        }
    }

    public static function getInStock($request)
    {
        $data = RegularStokConfirmation::where('status','>',1)->paginate($request->limit ?? null);
        if(!$data) throw new \Exception("Data not found", 400);
        return [
            'items' => $data->getCollection()->transform(function($item){
                $item->regular_delivery_plan = $item->refRegularDeliveryPlan;
                $item->status_instock = 'default';
                unset(
                    $item->id,
                    $item->id_regular_delivery_plan,
                    $item->count_box,
                    $item->in_wh,
                    $item->created_at,
                    $item->created_by,
                    $item->updated_at,
                    $item->updated_by,
                    $item->deleted_at,
                    $item->refRegularDeliveryPlan
                );

                return $item;
            }),
            'last_page' => $data->lastPage()
        ];
    }

    public static function getOutStock($request)
    {
        $data = RegularStokConfirmation::where('status','<',1)->paginate($request->limit ?? null);
        if(!$data) throw new \Exception("Data not found", 400);
        return [
            'items' => $data->getCollection()->transform(function($item){
                $item->regular_delivery_plan = $item->refRegularDeliveryPlan;
                $item->status_instock = 'default';
                unset(
                    $item->id,
                    $item->id_regular_delivery_plan,
                    $item->count_box,
                    $item->in_wh,
                    $item->created_at,
                    $item->created_by,
                    $item->updated_at,
                    $item->updated_by,
                    $item->deleted_at,
                    $item->refRegularDeliveryPlan
                );

                return $item;
            }),
            'last_page' => $data->lastPage()
        ];
    }

    public static function tracking($request)
    {
        $data = RegularStokConfirmation::paginate($request->limit ?? null);
        if(!$data) throw new \Exception("Data not found", 400);
        return [
            'items' => $data->items(),
            'last_page' => $data->lastPage()
        ];
    }
}
