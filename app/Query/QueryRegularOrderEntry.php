<?php

namespace App\Query;

use App\Constants\Constant;
use App\Models\RegularOrderEntry AS Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\ApiHelper as Helper;
use App\Imports\OrderEntry;
use App\Models\RegularOrderEntryUploadDetail;
use App\Models\RegularOrderEntryUploadDetailSet;
use App\Models\RegularOrderEntryUploadDetailTemp;
use Illuminate\Support\Facades\Cache;
use Maatwebsite\Excel\Facades\Excel;

class QueryRegularOrderEntry extends Model {

    const cast = 'regular-order-entry';


    public static function getAll($params)
    {
        $key = self::cast.json_encode($params->query());
        return Helper::storageCache($key, function () use ($params){
            $query = self::where(function ($query) use ($params){

                $category = $params->category ?? null;
                if($category) {
                    $query->where($category, 'ilike', $params->kueri);
                }


               if($params->search) $query->where('year', 'like', "'%$params->search%'")
                            ->orWhere('month', 'like',  "'%$params->search%'")
                            ->orWhere('period', 'like',  "'%$params->search%'")
                            ->orWhere('datasource', 'like',  "'%$params->search%'");

            });

            if($params->datasource) $query->where('datasource', "$params->datasource");
            if($params->withTrashed == 'true') $query->withTrashed();
            if($params->dropdown == Constant::IS_ACTIVE) {
                $params->limit = null;
                $params->page = 1;
            }

            $data = $query
            ->orderBy('year','desc')
            ->paginate($params->limit ?? null);
            return [
                'items' => $data->items(),
                'attributes' => [
                    'total' => $data->total(),
                    'current_page' => $data->currentPage(),
                    'from' => $data->currentPage(),
                    'per_page' => (int) $data->perPage(),
                ]
            ];
        });
    }

    public static function getAllPc($params)
    {
        $key = self::cast.json_encode($params->query());
        return Helper::storageCache($key, function () use ($params){
            $query = self::where(function ($query) use ($params){
                if($params->search)
                    $query->where('year', 'like', "'%$params->search%'")
                        ->orWhere('month', 'like',  "'%$params->search%'")
                        ->orWhere('period', 'like',  "'%$params->search%'")
                        ->orWhere('datasource', 'like',  "'%$params->search%'");
            });

            if($params->datasource) $query->where('datasource', "$params->datasource");
            if($params->withTrashed == 'true') $query->withTrashed();
            if($params->dropdown == Constant::IS_ACTIVE) {
                $params->limit = null;
                $params->page = 1;
            }

            $data = $query
                ->orderBy('year','desc')
                ->paginate($params->limit ?? null);
            return [
                'items' => $data->items(),
                'attributes' => [
                    'total' => $data->total(),
                    'current_page' => $data->currentPage(),
                    'from' => $data->currentPage(),
                    'per_page' => (int) $data->perPage(),
                ]
            ];
        });
    }

    public static function store($request,$is_transaction = true)
    {
        if($is_transaction) DB::beginTransaction();
        try {

            Helper::requireParams([
                'file',
                'year'
            ]);

            $check = self::check($request);
            $iteration = self::checkIteration($request);
            // if($iteration == 3) throw new \Exception("Upload data exceeds the maximum limit", 400);
            $request->iteration = $iteration;
            $params = $request->all();
            $params['status'] = Constant::STS_PROCESSED;
            $params['uploaded'] = $iteration;

            if(!$check) $store = self::create($params);
            else {
                $store = $check;
                $store->fill($params);
                $store->save();
            }
            $request->id_regular_order_entry = $store->id;
            $id_upload = QueryRegularOrderEntryUpload::saveFile($store,$request,false);

            if($is_transaction) DB::commit();

            //mapping data set
            $data = RegularOrderEntryUploadDetailTemp::
            select('regular_order_entry_upload_detail_temp.etd_jkt','a.id_box','a.part_set',
            DB::raw("string_agg(DISTINCT regular_order_entry_upload_detail_temp.id::character varying, ',') as id_regular_order_entry_upload_detail_temp"),
            DB::raw("string_agg(DISTINCT regular_order_entry_upload_detail_temp.code_consignee::character varying, ',') as code_consignee"),
            DB::raw("string_agg(DISTINCT regular_order_entry_upload_detail_temp.item_no::character varying, ',') as item_no"),
            DB::raw("string_agg(DISTINCT regular_order_entry_upload_detail_temp.id_regular_order_entry_upload::character varying, ',') as id_regular_order_entry_upload"),
            DB::raw("string_agg(DISTINCT regular_order_entry_upload_detail_temp.model::character varying, ',') as model"),
            DB::raw("string_agg(DISTINCT regular_order_entry_upload_detail_temp.disburse::character varying, ',') as disburse"),
            DB::raw("string_agg(DISTINCT regular_order_entry_upload_detail_temp.delivery::character varying, ',') as delivery"),
            DB::raw("string_agg(DISTINCT regular_order_entry_upload_detail_temp.qty::character varying, ',') as qty"),
            DB::raw("string_agg(DISTINCT regular_order_entry_upload_detail_temp.status::character varying, ',') as status"),
            DB::raw("string_agg(DISTINCT regular_order_entry_upload_detail_temp.order_no::character varying, ',') as order_no"),
            DB::raw("string_agg(DISTINCT regular_order_entry_upload_detail_temp.cust_item_no::character varying, ',') as cust_item_no"),
            DB::raw("string_agg(DISTINCT regular_order_entry_upload_detail_temp.uuid::character varying, ',') as uuid"),
            DB::raw("string_agg(DISTINCT regular_order_entry_upload_detail_temp.etd_wh::character varying, ',') as etd_wh"),
            DB::raw("string_agg(DISTINCT regular_order_entry_upload_detail_temp.etd_ypmi::character varying, ',') as etd_ypmi"),
            DB::raw("string_agg(DISTINCT a.item_no_series::character varying, ',') as item_no_series"),
            DB::raw("SUM(regular_order_entry_upload_detail_temp.qty) as sum_qty")
            )
            ->where('id_regular_order_entry_upload', $id_upload)
            ->leftJoin('mst_box as a','regular_order_entry_upload_detail_temp.item_no','a.item_no')
            ->groupBy('a.part_set','a.id_box','regular_order_entry_upload_detail_temp.etd_jkt')
            ->orderBy('id_regular_order_entry_upload_detail_temp','asc')
            ->get();

            $set = [];
            $single = [];
            foreach ($data->toArray() as $value) {
                if ($value['part_set'] == 'set') {
                    $set[] = $value;
                } elseif ($value['part_set'] == 'single') {
                    $single[] = $value;
                }
            }
dd(RegularOrderEntryUploadDetailTemp::where('id_regular_order_entry_upload', $id_upload)->get());
            $id_set = [];
            foreach ($set as $value) {
                $id_set[] = $value['id_regular_order_entry_upload_detail_temp'];
            }

            $single_upload = [];
            foreach ($single as $value) {
                if (!str_contains(implode(',',$id_set),$value['id_regular_order_entry_upload_detail_temp'])) {
                    $single_upload[] = $value;
                }
            }

            foreach ($set as $value) {
                $upload_detail = RegularOrderEntryUploadDetail::create([
                    'id_regular_order_entry_upload' => $value['id_regular_order_entry_upload'],
                    'code_consignee' => $value['code_consignee'],
                    'delivery' => $value['delivery'],
                    'qty' => $value['sum_qty'],
                    'status' => $value['status'],
                    'order_no' => $value['order_no'],
                    'cust_item_no' => $value['cust_item_no'],
                    'etd_wh' => $value['etd_wh'],
                    'etd_ypmi' => $value['etd_ypmi'],
                    'etd_jkt' => $value['etd_jkt'],
                    'jenis' => 'set'
                ]);

                foreach (explode(',',$value['item_no']) as $value) {
                    RegularOrderEntryUploadDetailSet::create([
                        'id_detail' => $upload_detail->id,
                        'item_no' => $value,
                    ]);
                }
            }

            foreach ($single_upload as $value) {
                $upload = RegularOrderEntryUploadDetail::create($value);
                $upload->update(['jenis' => 'single']);
            }

            Cache::flush([self::cast]); //delete cache
        } catch (\Throwable $th) {
            if($is_transaction) DB::rollBack();
            throw $th;
        }
    }

    public static function checkIteration($request)
    {
        return self::where([
            'year' => $request->year,
            'month' => $request->month,
            'datasource' => $request->datasource,
        ])->count() + 1;
    }

     public static function check($request)
    {
        return self::where([
            'year' => $request->year,
            'month' => $request->month,
            'datasource' => $request->datasource,
        ])->first();
    }

    public static function change($request,$uuid, $is_transaction = true)
    {
        if($is_transaction) DB::beginTransaction();
        try {

            Helper::requireParams([
                'file',
                'year'
            ]);

            $update = self::where('uuid',$uuid)->first();
            if(!$update) throw new \Exception("Data not found", 400);
            $params = $request->all();
            $update->fill($params);
            $update->save();
            $request->id_regular_order_entry = $update->id;
            QueryRegularOrderEntryUpload::deletedByIdOrderEntry($update->id,false);
            QueryRegularOrderEntryUpload::saveFile($request,false);
            if($is_transaction) DB::commit();
            Cache::flush([self::cast]); //delete cache
        } catch (\Throwable $th) {
            if($is_transaction) DB::rollBack();
            throw $th;
        }
    }

    public static function getListDatasource()
    {
        $data = self::select("datasource")->whereNotNull('datasource')
                        ->groupBy('datasource')
                        ->get();

        $arr = array();

        foreach($data as $item){
            array_push($arr, $item->datasource);
        }

        return $arr;
    }

    public static function revision($request,$is_transaction = true)
    {
        if($is_transaction) DB::beginTransaction();
        try {

            QueryRegularOrderEntryUpload::revisionFile($request,false);

            if($is_transaction) DB::commit();
            Cache::flush([self::cast]); //delete cache
        } catch (\Throwable $th) {
            if($is_transaction) DB::rollBack();
            throw $th;
        }
    }

}
