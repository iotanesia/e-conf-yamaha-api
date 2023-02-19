<?php

namespace App\Query;

use App\Constants\Constant;
use App\Models\MstPortOfDischarge AS Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\ApiHelper as Helper;
use Illuminate\Support\Facades\Cache;

class QueryMstPortOfDischarge extends Model {


    const cast = 'master-port-of-discharge';


    public static function getAll($params)
    {
        $key = self::cast.json_encode($params->query());
        return Helper::storageCache($key, function () use ($params){
            $query = self::where(function ($query) use ($params){
               if($params->kueri) $query->where('mot',"%$params->kueri%");

            });
            if($params->withTrashed == 'true') $query->withTrashed();
            $data = $query
            ->orderBy('id','asc')
            ->paginate($params->limit ?? null);
            return [
                'items' => $data->getCollection()->transform(function($item){
                    $item->port_code = $item->refPort->code ?? null;
                    $item->port_name = $item->refPort->name ?? null;
                    $item->consignee_code = $item->refConsignee->code ?? null;
                    $item->consignee_name = $item->refConsignee->name ?? null;
                    unset(
                        $item->refPort,
                        $item->refConsignee
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
        $data = self::find($id);

        if($data){
            $data->port_code = $data->refPort->code ?? null;
            $data->port_name = $data->refPort->name ?? null;
            $data->consignee_code = $data->refConsignee->code ?? null;
            $data->consignee_name = $data->refConsignee->name ?? null;
            unset(
                $data->refPort,
                $data->refConsignee
            );
        }

        return $data;
    }

    public static function store($request,$is_transaction = true)
    {
        if($is_transaction) DB::beginTransaction();
        try {

            $params = $request->all();

            if($params["id_port"]){
                $port = QueryMstPort::find($params["id_port"]);
                if(!$port) throw new \Exception("Port dengan id ".$params["id_port"]." tidak ditemukan", 400);
            }
            if($params["id_consignee"]){
                $port = QueryMstConsignee::find($params["id_consignee"]);
                if(!$port) throw new \Exception("Consignee dengan id ".$params["id_consignee"]." tidak ditemukan", 400);
            }


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
            if(!$update) throw new \Exception("id tidak ditemukan", 400);

            if($params["id_port"]){
                $port = QueryMstPort::find($params["id_port"]);
                if(!$port) throw new \Exception("Port dengan id ".$params["id_port"]." tidak ditemukan", 400);
            }
            if($params["id_consignee"]){
                $port = QueryMstConsignee::find($params["id_consignee"]);
                if(!$port) throw new \Exception("Consignee dengan id ".$params["id_consignee"]." tidak ditemukan", 400);
            }
            
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
