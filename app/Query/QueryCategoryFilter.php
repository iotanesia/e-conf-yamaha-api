<?php

namespace App\Query;

use App\Constants\Constant;
use App\Models\MstCategoryFilter AS Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\ApiHelper as Helper;
use Illuminate\Support\Facades\Cache;

class QueryCategoryFilter extends Model {


    const cast = 'master-container';


    public static function getProspectContainer()
    {
        $data = Model::select('value', 'label')
            ->where('module', 'Prospect Container')
            ->get(10);

            return [
                'items' => $data,
                'last_page' => null,
                'attributes' => [
                    'total' => count($data),
                    'current_page' => null,
                    'from' => null,
                    'per_page' => null,
                ]
            ];
    }

    public static function getPart()
    {
        $data = Model::select('value', 'label')
            ->where('module', 'Part')
            ->get(10);

        return [
            'items' => $data,
            'last_page' => null,
            'attributes' => [
                'total' => count($data),
                'current_page' => null,
                'from' => null,
                'per_page' => null,
            ]
        ];
    }

    public static function getInquiry()
    {
        $data = Model::select('value', 'label')
            ->where('module', 'Inquiry')
            ->get(10);

        return [
            'items' => $data,
            'last_page' => null,
            'attributes' => [
                'total' => count($data),
                'current_page' => null,
                'from' => null,
                'per_page' => null,
            ]
        ];
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
