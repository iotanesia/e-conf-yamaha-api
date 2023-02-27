<?php

namespace App\Query;

use App\Constants\Constant;
use App\Models\MstDatasource AS Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\ApiHelper as Helper;
use Illuminate\Support\Facades\Cache;

class QueryMstDatasource extends Model {


    const cast = 'master-datasource';


    public static function getAll($params)
    {
        $key = self::cast.json_encode($params->query());
        return Helper::storageCache($key, function () use ($params){
            $query = self::where(function ($query) use ($params){
               if($params->search) $query->where('nama', "like", "%$params->kueri%");

            });
            if($params->dropdown == Constant::IS_ACTIVE) {
                $params->limit = null;
                $params->page = 1;
            }
            if($params->withTrashed == 'true') $query->withTrashed();
            $data = $query
            ->orderBy('nama','asc')
            ->paginate($params->limit ?? null);
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


    public static function byNama($nama)
    {
        $data = self::where('nama' , $nama)->first();
        return $data;
    }

    public static function store($request,$is_transaction = true)
    {
        if($is_transaction) DB::beginTransaction();
        try {

            $params = $request->all();
            self::create($params);

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
                'nama_old',
                'nama_new'
            ]);

            $params = $request->all();
            $update = self::where('nama' , $params["nama_old"])->first();
            if(!$update) throw new \Exception("nama tidak ditemukan", 400);
            self::where("nama", $params["nama_old"])->update(["nama" => $params["nama_new"]]);
            if($is_transaction) DB::commit();
            Cache::flush([self::cast]); //delete cache
        } catch (\Throwable $th) {
            if($is_transaction) DB::rollBack();
            throw $th;
        }
    }

    public static function deleted($nama,$is_transaction = true)
    {
        if($is_transaction) DB::beginTransaction();
        try {
            self::where("nama", $nama)->delete();
            if($is_transaction) DB::commit();
            Cache::flush([self::cast]); //delete cache
        } catch (\Throwable $th) {
            if($is_transaction) DB::rollBack();
            throw $th;
        }
    }

}