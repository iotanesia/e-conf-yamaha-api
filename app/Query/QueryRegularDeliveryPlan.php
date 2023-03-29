<?php

namespace App\Query;

use App\Constants\Constant;
use App\Models\RegularDeliveryPlan AS Model;
use Illuminate\Support\Facades\DB;
use App\ApiHelper as Helper;
use App\ApiHelper;
use App\Models\RegularDeliveryPlan;
use App\Models\RegularDeliveryPlanBox;
use App\Models\RegularProspectContainer;
use App\Models\RegularProspectContainerDetail;
use App\Models\RegularProspectContainerDetailBox;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

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
            $item->box = $item->manyDeliveryPlanBox->map(function ($item)
            {
                return [
                    'id' => $item->id,
                    'id_box' => $item->id_box,
                    'qty' => $item->refBox->qty ?? null,
                    'width' => $item->refBox->width ?? null,
                    'height' => $item->refBox->height ?? null,
                ];
            });

            unset(
                $item->refRegularOrderEntry,
                $item->manyDeliveryPlanBox
            );

        });
        return $data;
    }

    public static function inquiryProcess($params, $is_trasaction = true)
    {
        Helper::requireParams([
            'id',
            'no_packaging',
            'etd_jkt',
            'code_consignee',
            'datasource'
        ]);

        if($is_trasaction) DB::beginTransaction();
        try {
           $check = RegularProspectContainer::where('no_packaging',$params->no_packaging)->first();
           if($check) throw new \Exception("no_packaging registered", 400);

           $store = RegularProspectContainer::create([
                        "code_consignee" => $params->code_consignee,
                        "etd_ypmi" => Carbon::parse($params->etd_jkt)->subDays(4)->format('Y-m-d'),
                        "etd_wh" => Carbon::parse($params->etd_jkt)->subDays(2)->format('Y-m-d'),
                        "etd_jkt" => $params->etd_jkt,
                        "no_packaging" => $params->no_packaging,
                        "datasource" => $params->datasource,
                        "created_at" => now(),
            ]);


           self::where(function ($query) use ($params){
                   $query->whereIn('id',$params->id);
                   $query->where('code_consignee',$params->code_consignee);
           })
           ->chunk(1000,function ($data) use ($params,$store){
                foreach ($data as $key => $item) {

                    $detail = RegularProspectContainerDetail::create([
                        "code_consignee" => $item->code_consignee,
                        "model" => $item->model,
                        "item_no" => $item->item_no,
                        "delivery" => $item->delivery,
                        "qty" => $item->qty,
                        "status" => $item->status,
                        "order_no" => $item->order_no,
                        "cust_item_no" => $item->cust_item_no,
                        "etd_ypmi" => Carbon::parse($params->etd_jkt)->subDays(4)->format('Y-m-d'),
                        "etd_wh" => Carbon::parse($params->etd_jkt)->subDays(2)->format('Y-m-d'),
                        "etd_jkt" => $params->etd_jkt,
                        "created_at" => now(),
                        "id_prospect_container" => $store->id,
                        "uuid" => (string) Str::uuid()
                    ]);

                    $box = RegularDeliveryPlanBox::where('id_regular_delivery_plan',$item->id)
                    ->get()->map(function ($item) use ($detail) {
                        return [
                            'id_box' => $item->id_box,
                            'id_prospect_container_detail' => $detail->id,
                            'created_at' => now()
                        ];
                    })->toArray();

                    foreach (array_chunk($box,1000) as $item_box) {
                        RegularProspectContainerDetailBox::insert($item_box);
                    }

                    $item->is_inquiry = Constant::IS_ACTIVE;
                    $item->save();

                }
           });

          if($is_trasaction) DB::commit();
        } catch (\Throwable $th) {
            if($is_trasaction) DB::rollBack();
            throw $th;
        }
    }

    public static function noPackaging($params,$is_trasaction = true)
    {
        if($is_trasaction) DB::beginTransaction();
        try {

            Helper::requireParams([
                'id',

            ]);


            $check = RegularDeliveryPlan::select('etd_jkt','code_consignee')->whereIn('id',$params->id)
            ->groupBy('etd_jkt','code_consignee')
            ->get()
            ->toArray();

            if(count($check) > 1) throw new \Exception("etd_jkt & code_consignee not same" . json_encode($check), 400);

            $data = RegularDeliveryPlan::select(DB::raw('count(cust_item_no) as total'),'cust_item_no')->whereIn('id',$params->id)
            ->groupBy('cust_item_no')
            ->orderBy('total','desc')
            ->get()
            ->toArray();

            if(count($data) == 0) throw new \Exception("Data not found", 400);

            $no_packaging = $data[0]['cust_item_no'].substr(mt_rand(),0,5);
            $tanggal = $check[0]['etd_jkt'];

            return [
                "items" => [
                    'no_packaging' => $no_packaging,
                    'tanggal' => $tanggal,
                ]
            ];

            if($is_trasaction) DB::commit();
        } catch (\Throwable $th) {
            if($is_trasaction) DB::rollBack();
            throw $th;
        }
    }
}
