<?php

namespace App\Query;

use App\Constants\Constant;
use App\Models\IregularPacking AS Model;
use App\ApiHelper as Helper;
use App\Models\IregularDeliveryPlan;
use App\Models\IregularPackingDetail;
use App\Models\MstComodities;
use App\Models\MstDoc;
use App\Models\MstDutyTax;
use App\Models\MstFreight;
use App\Models\MstFreightCharge;
use App\Models\MstGoodCondition;
use App\Models\MstGoodCriteria;
use App\Models\MstGoodPayment;
use App\Models\MstGoodStatus;
use App\Models\MstIncoterms;
use App\Models\MstInlandCost;
use App\Models\MstInsurance;
use App\Models\MstShippedBy;
use App\Models\MstTypeTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class QueryIregularPacking extends Model {

    const cast = 'iregular-packing';

    public static function getAll($params)
    {
        $key = self::cast.json_encode($params->query());
        return Helper::storageCache($key, function () use ($params){
            $query = self::where(function ($query) use ($params){

                $category = $params->category ?? null;
                if($category) {
                    $query->where($category, 'ilike', $params->kueri);
                }

            });

            if($params->withTrashed == 'true') $query->withTrashed();

            $data = $query->paginate($params->limit ?? 10);
            
            $totalRow = self::count();
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
                        $item->status_desc = "Waiting Delivery Note";
                    else if($item->status == 2)
                        $item->status_desc = "Delivery Note Created";

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

    public static function getDeliveryNote($params, $id){
        $data = self::find($id);
        if(!$data) throw new \Exception("id tidak ditemukan", 400);
                    
        return [
            'items' => $data,
        ];
    }

    public static function getDeliveryNoteDetail($params, $id){
                    
        $key = self::cast.json_encode($params->query());
        return Helper::storageCache($key, function () use ($params, $id){
            $query = IregularPackingDetail::where('id_iregular_packing', $id);
            $data = $query->paginate($params->limit ?? 10);
            
            $totalRow = IregularPackingDetail::where('id_iregular_packing', $id)->count();;
            $lastPage = ceil($totalRow/($params->limit ?? 10));
            return [
                'items' => $data->getCollection(),
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

    public static function updateDeliveryNote($request,$id,$is_transaction = true)
    {
        if($is_transaction) DB::beginTransaction();
        try {
            $params = $request->all();

            $data = self::find($id);
            if(!$data) throw new \Exception("id tidak ditemukan", 400);

            $params["status"] = 2;
            $data->update($params);

            if($is_transaction) DB::commit();
            Cache::flush([self::cast]); //delete cache
            return ['items' => ['id' => $data->id]];
        } catch (\Throwable $th) {
            if($is_transaction) DB::rollBack();
            throw $th;
        }
    }


}
