<?php

namespace App\Query;

use App\Constants\Constant;
use App\Models\RegularOrderEntry AS Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\ApiHelper as Helper;
use App\Imports\OrderEntry;
use Illuminate\Support\Facades\Cache;
use Maatwebsite\Excel\Facades\Excel;

class QueryRegularOrderEntry extends Model {

    const cast = 'regular-order-entry';


    public static function getAll($params)
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
            ->orderBy('id','desc')
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

            $iteration = self::checkIteration($request);
            if($iteration == 3) throw new \Exception("error iteration by year, month, and datasouce", 400);
            $request->iteration = $iteration;
            $params = $request->all();
            $params['status'] = Constant::STS_PROCESSED;
            $params['uploaded'] = $iteration;
            $store = self::create($params);
            $request->id_regular_order_entry = $store->id;
            QueryRegularOrderEntryUpload::saveFile($request,false);


            if($is_transaction) DB::commit();
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

}
