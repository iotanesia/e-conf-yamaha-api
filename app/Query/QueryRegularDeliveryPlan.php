<?php

namespace App\Query;

use App\Constants\Constant;
use App\Models\RegularDeliveryPlan AS Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\ApiHelper as Helper;
use App\Models\RegularDeliveryPlan;
use App\Models\RegularOrderEntry;
use App\Models\RegularProspectContainer;
use Illuminate\Support\Facades\Cache;

class QueryRegularDeliveryPlan extends Model {

    const cast = 'regular-delivery-plan';


    public static function getDeliveryPlan($params)
    {
        $key = self::cast.json_encode($params->query());
        return Helper::storageCache($key, function () use ($params){
            $query = self::select(
                'id_regular_order_entry'
            )->where(function ($query) use ($params){
               if($params->search)
                    $query->where('code_consignee', 'like', "'%$params->search%'")
                            ->orWhere('model', 'like', "'%$params->search%'")
                            ->orWhere('item_no', 'like', "'%$params->search%'")
                            ->orWhere('disburse', 'like', "'%$params->search%'")
                            ->orWhere('delivery', 'like', "'%$params->search%'")
                            ->orWhere('qty', 'like', "'%$params->search%'")
                            ->orWhere('status', 'like', "'%$params->search%'")
                            ->orWhere('order_no', 'like', "'%$params->search%'")
                            ->orWhere('cust_item_no', 'like', "'%$params->search%'");

            })
            ->whereHas('refRegularOrderEntry',function ($query) use ($params){
                if($params->datasource) $query->where('datasource',$params->datasource);
            });

            if($params->withTrashed == 'true') $query->withTrashed();
            if($params->dropdown == Constant::IS_ACTIVE) {
                $params->limit = null;
                $params->page = 1;
            }

            $data = $query
            ->groupBy('id_regular_order_entry')
            ->orderBy('id_regular_order_entry','desc')
            ->paginate($params->limit ?? null);
            return [
                'items' => $data->getCollection()->transform(function ($item){
                    $month_code = $item->refRegularOrderEntry->month ?? null;
                    $sts = $item->refRegularOrderEntry->status ?? null;
                    return [
                        'month_code' => $month_code,
                        'month' => Helper::monthName($month_code),
                        'year' => $item->refRegularOrderEntry->year ?? null,
                        'uploaded' => $item->refRegularOrderEntry->uploaded ?? null,
                        'updated_at' => $item->refRegularOrderEntry->updated_at ?? null,
                        'id' => $item->id_regular_order_entry ?? null,
                        'status' =>  $item->refRegularOrderEntry->status ?? null,
                        'datasource' =>  $item->refRegularOrderEntry->datasource ?? null,
                        'status_desc' =>  Constant::STS_PROCESS_RG_ENTRY[$sts] ?? null,
                    ];
                }),
                'attributes' => [
                    'total' => $data->total(),
                    'current_page' => $data->currentPage(),
                    'from' => $data->currentPage(),
                    'per_page' => (int) $data->perPage(),
                ]
            ];
        });
    }


    public static function getDeliveryPlanDetail($params)
    {
        $key = self::cast.json_encode($params->query());
        return Helper::storageCache($key, function () use ($params){
            $query = self::where(function ($query) use ($params){
               if($params->search)
                    $query->where('code_consignee', 'like', "'%$params->search%'")
                            ->orWhere('model', 'like', "'%$params->search%'")
                            ->orWhere('item_no', 'like', "'%$params->search%'")
                            ->orWhere('disburse', 'like', "'%$params->search%'")
                            ->orWhere('delivery', 'like', "'%$params->search%'")
                            ->orWhere('qty', 'like', "'%$params->search%'")
                            ->orWhere('status', 'like', "'%$params->search%'")
                            ->orWhere('order_no', 'like', "'%$params->search%'")
                            ->orWhere('cust_item_no', 'like', "'%$params->search%'");

            });

            if($params->withTrashed == 'true')
                $query->withTrashed();

            if($params->id_regular_order_entry)
                $query->where("id_regular_order_entry", $params->id_regular_order_entry);
            else
                throw new \Exception("id_regular_order_entry must be sent in request", 400);

            $data = $query
            ->orderBy('id','desc')
            ->paginate($params->limit ?? null);
            return [
                'items' => $data->getCollection()->transform(function($item){
                    $item->regular_delivery_plan_box = $item->manyDeliveryPlanBox;
                    unset($item->manyDeliveryPlanBox);
                    foreach($item->regular_delivery_plan_box as $box){
                        $box->box = $box->refBox;
                        unset($box->refBox);
                    }
                    return $item;
                }),
                'attributes' => [
                    'total' => $data->total(),
                    'current_page' => $data->currentPage(),
                    'from' => $data->currentPage(),
                    'per_page' => (int) $data->perPage(),
                ]
            ];
        });
    }

    public static function detail($id_regular_order_entry)
    {
        $data = self::where('id_regular_order_entry',$id_regular_order_entry)
        ->where('is_inquiry',0)->get();
        if(count($data) == 0) throw new \Exception("Data tidak ditemukan.", 400);

        $data->map(function ($item){
            $regularOrderEntry = $item->refRegularOrderEntry;
            $item->regular_order_entry_period = $regularOrderEntry->period ?? null;
            $item->regular_order_entry_month = $regularOrderEntry->month ?? null;
            $item->regular_order_entry_year = $regularOrderEntry->year ?? null;

            unset(
                $item->refRegularOrderEntry
            );

        });
        return $data;
    }

    public static function inquiryProcess($params, $is_trasaction = true)
    {

        if($is_trasaction) DB::beginTransaction();
        try {
           $check = RegularProspectContainer::where('no_packaging',$params->no_packaging)->first();
           if($check) throw new \Exception("no_packaging registered", 400);
           $data = RegularDeliveryPlan::where(function ($query) use ($params)
           {
                $query->whereIn('id_regular_order_entry',$params->id);
                $query->where('code_consignee',$params->code_consignee);
           })
           ->get()
           ->map(function ($item) use ($params){
                return [
                    "code_consignee" => $item->code_consignee,
                    "etd_ypmi" => null,
                    "etd_wh" => null,
                    "etd_jkt" => $params->etd_jkt,
                    "no_packaging" => $params->no_packaging,
                    "created_at" => now(),
                ];
           })->toArray();

           foreach (array_chunk($data,1000) as $item) {
                RegularProspectContainer::insert($item);
           }

          if($is_trasaction) DB::commit();
        } catch (\Throwable $th) {
            if($is_trasaction) DB::rollBack();
            throw $th;
        }
    }
}
