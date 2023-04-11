<?php

namespace App\Query;

use App\Constants\Constant;
use App\Models\MstBox AS Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\ApiHelper as Helper;
use Illuminate\Support\Facades\Cache;

class QueryMstBox extends Model {


    const cast = 'master-box';


    public static function getAll($params)
    {
        $key = self::cast.json_encode($params->query());
        return Helper::storageCache($key, function () use ($params){
            $query = self::where(function ($query) use ($params){
               if($params->kueri) $query->where('no_box',"%$params->kueri%");

            });
            if($params->withTrashed == 'true') $query->withTrashed();
            $data = $query
            ->orderBy('id','asc')
            ->paginate($params->limit ?? null);
            return [
                'items' => $data->getCollection()->transform(function($item){
                    $item->part_item_no = $item->refPart->item_no ?? null;
                    $item->part_description = $item->refPart->description ?? null;
                    unset(
                        $item->refPart
                    );
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

    public static function byId($id)
    {
        $result = self::find($id);
        if($result) {
            $result->part_item_no = $result->refPart->item_no ?? null;
            $result->part_description = $result->refPart->description ?? null;
            unset(
                $result->refPart
            );
        }
        return $result;
    }

    public static function store($request,$is_transaction = true)
    {
        if($is_transaction) DB::beginTransaction();
        try {

            Helper::requireParams([
                'no_box',
                'id_part'
            ]);

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
                'id',
                'no_box',
                'id_part'
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

    public static function byItemNoCdConsignee($itemNo,$consingee)
    {
        // echo 'item no : '.$itemNo." consignee : ".$consingee;
        // die();
        $tes = self::where('item_no',trim($itemNo))
            ->where('code_consignee',trim($consingee))
            ->first();

        DB::table('box_temporary')->insert([
            'item_no' => $itemNo,
            'consignee' => trim($consingee),
            'status' => $tes ? 1 : 0,
            'id_box' => $tes->id,
            'qty' => $tes->qty
        ]);
        return $tes;
    }


}
