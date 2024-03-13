<?php

namespace App\Query;

use App\Constants\Constant;
use App\Models\IregularShippingInstruction AS Model;
use App\Models\IregularShippingInstructionCreation;
use App\ApiHelper as Helper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class QueryIregularShippingInstruction extends Model {

    const cast = 'iregular-shipping-instruction';

    public static function getAll($params, $min_status = null)
    {
        $key = self::cast.json_encode($params->query());
        return Helper::storageCache($key, function () use ($params, $min_status){
            $query = self::where(function ($query) use ($params, $min_status){

                $category = $params->category ?? null;
                if($category) {
                    $query->where($category, 'ilike', $params->kueri);
                }

                if(isset($min_status))
                    $query->where('status', '>=', $min_status);

            });

            if($params->withTrashed == 'true') $query->withTrashed();

            $totalRow = $query->count();
            $data = $query->paginate($params->limit ?? 10);
            
            $lastPage = ceil($totalRow/($params->limit ?? 10));
            return [
                'items' => $data->getCollection()->transform(function($item){
                    $item->delivery_plan = $item->refDeliveryPlan;
                    $item->delivery_plan->order_entry = $item->refDeliveryPlan->refOrderEntry;
                    $item->delivery_plan->order_entry->shipped_by = $item->refDeliveryPlan->refOrderEntry->refShippedBy;
                    $item->delivery_plan->order_entry->type_transaction = $item->refDeliveryPlan->refOrderEntry->refTypeTransaction;

                    unset($item->refDeliveryPlan->refOrderEntry->refShippedBy);
                    unset($item->refDeliveryPlan->refOrderEntry->refTypeTransaction);
                    unset($item->refDeliveryPlan->refOrderEntry);
                    unset($item->refDeliveryPlan);


                    $item->status_desc = "-";
                    if($item->status == 1)
                        $item->status_desc = "Waiting SI";
                    else if($item->status == 2)
                        $item->status_desc = "Approval CC Supervisor";
                    else if($item->status == 3)
                        $item->status_desc = "Approval CC Manager";
                    else if($item->status == 4)
                        $item->status_desc = "Approved CC Manager";

                    return $item;
                }),
                'last_page' => $lastPage,
                'attributes' => [
                    'total' => $data->total(),
                    'current_page' => $data->currentPage(),
                    'from' => $data->currentPage(),
                    'per_page' => (int) $data->perPage(),
                ]
            ];
        });
    }
    
    public static function getById($params, $id){
        $data = self::find($id);
        if(!$data) throw new \Exception("id tidak ditemukan", 400);

        $data->delivery_plan = $data->refDeliveryPlan;

        unset($data->refDeliveryPlan);
                    
        return [
            'items' => $data,
        ];
    }

    
    public static function getCreation($params, $id){
        $data = IregularShippingInstructionCreation::where(["id_iregular_shipping_instruction"=>$id])->first();
        if(!$data) throw new \Exception("id tidak ditemukan", 400);
                    
        return [
            'items' => $data,
        ];
    }

    public static function storeData($request,$is_transaction = true)
    {
        if($is_transaction) DB::beginTransaction();
        try {
            $params = $request->all();
            Helper::requireParams([
                'to',
                'cc',
            ]);

            $data = IregularShippingInstructionCreation::where('id_iregular_shipping_instruction', $params["id_iregular_shipping_instruction"])->first();
            if(isset($data)){
                $update = $data->update($params);
            } else {
                $insert = IregularShippingInstructionCreation::create($params);
            }

            $si = self::find($params["id_iregular_shipping_instruction"]);
            if(!$si) throw new \Exception("id tidak ditemukan", 400);

            $si->update([
                "status" => 2
            ]);

            if($is_transaction) DB::commit();
            Cache::flush([self::cast]); //delete cache
        } catch (\Throwable $th) {
            if($is_transaction) DB::rollBack();
            throw $th;
        }
    }

    public static function updateStatus($request, $to_status, $is_transaction = true)
    {
        if($is_transaction) DB::beginTransaction();
        try {
            $params = $request->all();
           
            $data = self::find($params["id"]);
            if(!$data) throw new \Exception("id tidak ditemukan", 400);

            $data->update([
                "status" => $to_status
            ]);

            if($is_transaction) DB::commit();
            Cache::flush([self::cast]); //delete cache
        } catch (\Throwable $th) {
            if($is_transaction) DB::rollBack();
            throw $th;
        }
    }
    
}
