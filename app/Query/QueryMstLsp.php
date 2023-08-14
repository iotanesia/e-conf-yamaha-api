<?php

namespace App\Query;

use App\Constants\Constant;
use App\Models\MstLsp AS Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\ApiHelper as Helper;
use Illuminate\Support\Facades\Cache;

class QueryMstLsp extends Model {


    const cast = 'master-lsp';


    public static function getAll($params)
    {
        $key = self::cast.json_encode($params->query());
        return Helper::storageCache($key, function () use ($params){
            $query = Model::select('mst_lsp.code_consignee',
                DB::raw("string_agg(DISTINCT mst_lsp.id::character varying, ',') as id_lsp"),
                DB::raw("string_agg(DISTINCT mst_lsp.name::character varying, ',') as name"),
                DB::raw("string_agg(DISTINCT a.name::character varying, ',') as type_delivery"),
                DB::raw("string_agg(DISTINCT b.nick_name::character varying, ',') as cust_name")
            )->leftJoin('mst_type_delivery as a','mst_lsp.id_type_delivery','a.id')
            ->leftJoin('mst_consignee as b','mst_lsp.code_consignee','b.code')
            ->groupBy('mst_lsp.code_consignee');
            $data = $query
            ->orderBy('id_lsp','asc')
            ->paginate($params->limit ?? null);

            $data->transform(function ($item) {
                return [
                    'name' => explode(',', $item->name),
                    'type_delivery' => explode(',',$item->type_delivery),
                    'cust_name' => $item->cust_name
                ];
            });

            return [
                'items' => $data->items(),
                'last_page' => $data->lastPage(),
                'attributes' => [
                    'total' => $data->total(),
                    'current_page' => $data->currentPage(),
                    'from' => $data->currentPage(),
                    'per_page' => (int) $data->perPage(),
                ]
            ];
        });
    }

    public static function byId($id)
    {
        return self::find($id);
    }

    public static function store($request,$is_transaction = true)
    {
        if($is_transaction) DB::beginTransaction();
        try {

            $params = $request->all();

            foreach ($params['id_type_delivery'] as $key => $value) {
                Model::create([
                    'name' => $params['name'][$key],
                    'id_type_delivery' => $value,
                    'code_consignee' => $params['code_consignee']
                ]);
            }

            if($is_transaction) DB::commit();
            Cache::flush([self::cast]); //delete cache

        } catch (\Throwable $th) {
            if($is_transaction) DB::rollBack();
            throw $th;
        }
    }

    public static function change($request,$is_transaction = true)
    {
        if($is_transaction) DB::beginTransaction();
        try {

            Helper::requireParams([
                'id'
            ]);

            $params = $request->all();
            $update = self::find($params['id']);
            if(!$update) throw new \Exception("id tida ditemukan", 400);

            $consignee = Model::where('code_consignee', $update->code_consignee)->get();
            foreach ($consignee as $key => $value) {
                $upd = Model::find($value->id);
                $upd->update([
                    'name' => $params['name'][$key],
                    'id_type_delivery' => $params['id_type_delivery'][$key],
                    'code_consignee' => $params['code_consignee']
                ]);
            }
            
            if($is_transaction) DB::commit();
            Cache::flush([self::cast]); //delete cache
        } catch (\Throwable $th) {
            if($is_transaction) DB::rollBack();
            throw $th;
        }
    }

    public static function deleted($id,$is_transaction = true)
    {
        if($is_transaction) DB::beginTransaction();
        try {
            self::destroy($id);
            if($is_transaction) DB::commit();
            Cache::flush([self::cast]); //delete cache
        } catch (\Throwable $th) {
            if($is_transaction) DB::rollBack();
            throw $th;
        }
    }


}
