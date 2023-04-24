<?php

namespace App\Query;

use App\Constants\Constant;
use App\Models\RegularFixedPackingCreation AS Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\ApiHelper as Helper;
use Illuminate\Support\Facades\Cache;

class QueryRegularFixedPackingCreation extends Model {
    
    const cast = 'regular-fixed-packing-creation';

    public static function getAll($params)
    {
        try {
            $key = self::cast.json_encode($params->query());
            if($params->dropdown == Constant::IS_ACTIVE) $params->limit = Model::count();
            
            $query = self::where(function ($query) use ($params){
                if($params->search) $query->where('name',"like", "%$params->search%")
                                            ->orWHere('nickname',"like", "%$params->search%");
 
             });
             if($params->withTrashed == 'true') $query->withTrashed();
             $data = $query
             ->orderBy('id','asc')
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
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public static function byId($id)
    {
        return ['items'=>self::find($id)];
    }

    public static function store($request,$is_transaction = true)
    {
        if($is_transaction) DB::beginTransaction();
        try {

            $params = $request->all();
            $insert = self::create($params);

            if($is_transaction) DB::commit();
            Cache::flush([self::cast]); //delete cache

            return ['items'=>$insert];

        } catch (\Throwable $th) {
            if($is_transaction) DB::rollBack();
            throw $th;
        }
    }

    public static function change($id,$request,$is_transaction = true)
    {
        if($is_transaction) DB::beginTransaction();
        try {

            Helper::requireParams([
                'id'
            ]);

            $params = $request->all();
            $update = self::find($id);
            if(!$update) throw new \Exception("data tidak ditemukan", 400);
            $update->fill($params);
            $update->save();
            if($is_transaction) DB::commit();
            Cache::flush([self::cast]); //delete cache
            return ['items'=>$update];
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