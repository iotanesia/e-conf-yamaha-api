<?php

namespace App\Query;

use App\Constants\Constant;
use App\Models\MstPart AS Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\ApiHelper as Helper;
use Illuminate\Support\Facades\Cache;

class QueryMstPart extends Model {


    const cast = 'master-part';


    public static function getAll($params)
    {
        $key = self::cast.json_encode($params->query());
        return Helper::storageCache($key, function () use ($params){
            $query = self::where(function ($query) use ($params){
               if($params->kueri) $query->where('item_no',"like", $params->kueri)
                                        ->orWhere('description',"like", "%$params->kueri%")
                                        ->orWhere('hs_code',"like", "%$params->kueri%")
                                        ->orWhere('customer_use',"like", "%$params->kueri%")
                                        ->orWhere('code_consignee',"like", "%$params->kueri%")
                                        ->orWhere('cost_center',"like", "%$params->kueri%")
                                        ->orWhere('coa',"like", "%$params->kueri%")
                                        ->orWhere('gl_account',"like", "%$params->kueri%")
                                        ->orWhere('item_serial',"like", "%$params->kueri%");

            });
            if($params->withTrashed == 'true') $query->withTrashed();
            $data = $query
            ->orderBy('id','asc')
            ->paginate($params->limit ?? null);
            return [
                'items' => $data->getCollection()->transform(function($item){

                    $item->cust_name = $item->refConsignee->nick_name ?? null;
                    $item->division = $item->refGrooupProduct->group_product ?? null;
    
                    unset(
                        $item->refConsignee,
                        $item->refGrooupProduct,
                    );
    
                    return $item;
                }),
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
            $params['item_serial'] = substr($params['item_no'], 0, 3).'-'.substr($params['item_no'], 3, 5).'-'.substr($params['item_no'], 8, 2).'-'.substr($params['item_no'], 10);
            $params['customer_use'] = $params['code_consignee'];
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
