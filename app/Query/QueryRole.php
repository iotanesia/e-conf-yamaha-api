<?php

namespace App\Query;

use App\Constants\Constant;
use App\Models\Role AS Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\ApiHelper as Helper;
use Illuminate\Support\Facades\Cache;

class QueryRole extends Model {
    
    const cast = 'master-position';

    public static function getAll($params)
    {
        try {
            $key = self::cast.json_encode($params->query());
            if($params->dropdown == Constant::IS_ACTIVE) $params->limit = Model::count();
            
            $query = self::where(function ($query) use ($params){
                if($params->kueri) $query->where('name',"%$params->kueri%");
 
             });
             if($params->withTrashed == 'true') $query->withTrashed();
             $data = $query
             ->orderBy('id','asc')
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
        } catch (\Throwable $th) {
            throw $th;
        }
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
                'id'
            ]);

            $params = $request->all();
            $update = self::find($params['id']);
            if(!$update) throw new \Exception("id tida ditemukan", 400);
            $update->fill($params);
            $update->save();
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
