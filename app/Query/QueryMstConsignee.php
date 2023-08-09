<?php

namespace App\Query;

use App\Constants\Constant;
use App\Models\MstConsignee AS Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\ApiHelper as Helper;
use App\Models\MstPortOfDischarge;
use App\Models\MstPortOfLoading;
use Illuminate\Support\Facades\Cache;

class QueryMstConsignee extends Model {


    const cast = 'master-consignee';


    public static function getAll($params)
    {
        $key = self::cast.json_encode($params->query());
        return Helper::storageCache($key, function () use ($params){
            $query = self::where(function ($query) use ($params){
               if($params->kueri) $query->where('name',"%$params->kueri%");

            });
            if($params->withTrashed == 'true') $query->withTrashed();
            $data = $query
            ->orderBy('id','asc')
            ->paginate($params->limit ?? null);
            return [
                'items' => $data->getCollection()->transform(function($item){

                    $pol = MstPortOfLoading::get();
                    $pol_name = [];
                    foreach ($pol as $value) {
                        $pol_name[] = $value->name;
                    }
                    
                    $pod_name = [];
                    foreach ($item->manyPortOfDischarge as $value) {
                        $pod_name[] = $value->port;
                    }
                    
                    $item->pod = $pod_name ?? null;
                    $item->pol = $pol_name ?? null;

                    unset(
                        $item->manyPortOfDischarge,
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
        $data = self::where('id',$id)->get();

        return $data->transform(function($item){
                $item->pod = $item->manyPortOfDischarge ?? null;
                $item->pol = MstPortOfLoading::get() ?? null;

                unset(
                    $item->manyPortOfDischarge
                );

                return $item;
            })[0];
    }

    public static function store($request,$is_transaction = true)
    {
        if($is_transaction) DB::beginTransaction();
        try {
            
            foreach ($request->pod as $key => $value) {
                if ($key == 0) {
                    $id_mot = 2;
                    $tipe = 4;
                } elseif (($key == 1)) {
                    $id_mot = 1;
                    $tipe = 2;
                } elseif (($key == 2)) {
                    $id_mot = 1;
                    $tipe = 3;
                }
                MstPortOfDischarge::create([
                    'code_consignee' => $request->code,
                    'id_mot' => $id_mot,
                    'tipe' => $tipe,
                    'port' => $value,
                ]);
            }

            foreach ($request->pol as $key => $pol) {
                $mst_pol = MstPortOfLoading::where('id', $key+1)->first();
                $mst_pol->update(['name' => $pol]);
            }

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
            if(!$update) throw new \Exception("id tidak ditemukan", 400);

            $pod = MstPortOfDischarge::where('code_consignee', $update->code)->get();
            if (count($pod) > 0) {
                foreach ($pod as $key => $value) {
                    $value->delete();
                }
            }
            
            $update->fill($params);
            $update->save();

            foreach ($request->pod as $key => $val) {
                if ($key == 0) {
                    $id_mot = 2;
                    $tipe = 4;
                } elseif (($key == 1)) {
                    $id_mot = 1;
                    $tipe = 2;
                } elseif (($key == 2)) {
                    $id_mot = 1;
                    $tipe = 3;
                }
                MstPortOfDischarge::create([
                    'code_consignee' => $request->code,
                    'id_mot' => $id_mot,
                    'tipe' => $tipe,
                    'port' => $val,
                ]);
            }
            
            foreach ($request->pol as $key => $pol) {
                $mst_pol = MstPortOfLoading::where('id', $key+1)->first();
                $mst_pol->update(['name' => $pol]);
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
