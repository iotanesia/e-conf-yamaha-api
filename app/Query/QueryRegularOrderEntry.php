<?php

namespace App\Query;

use App\Constants\Constant;
use App\Models\RegularOrderEntry AS Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\ApiHelper as Helper;
use Illuminate\Support\Facades\Cache;

class QueryRegularOrderEntry extends Model {

    const cast = 'regular-order-entry';


    public static function getAll($params)
    {
        $key = self::cast.json_encode($params->query());
        return Helper::storageCache($key, function () use ($params){
            $query = self::where(function ($query) use ($params){
               if($params->kueri) $query->where('year',"%$params->kueri%");

            });
            if($params->withTrashed == 'true') $query->withTrashed();
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
}
