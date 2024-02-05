<?php

namespace App\Query;

use App\Constants\Constant;
use App\Models\RegularOrderEntryUploadRevision AS Model;
use App\Models\RegularOrderEntryUpload AS RegularOrderEntryUpload;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\ApiHelper as Helper;
use App\Imports\OrderEntry;
use App\Models\RegularOrderEntry;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class QueryRegularOrderEntryUploadRevision extends Model {

    const cast = 'regular-order-entry-upload-revision';

    public static function retrive($params)
    {
        $key = self::cast.json_encode($params->query());
        return Helper::storageCache($key, function () use ($params){
            $query = self::where(function ($query) use ($params){
               $query->where('type', 'REVISION');
               if($params->search)
                    $query->where('note', 'like', "'%$params->search%'");
            });

            if($params->dropdown == Constant::IS_ACTIVE) {
                $params->limit = null;
                $params->page = 1;
            }
            if($params->withTrashed == 'true') $query->withTrashed();
            if($params->id_regular_order_entry) $query->where('id_regular_order_entry', $params->id);

            $data = $query
            ->orderBy('id','desc')
            ->paginate($params->limit ?? null);
            return [
                'items' => $data->map(function ($item){
                    $result = $item;
                    $result->user = $item->refUser->name;
                    return $result;
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

    public static function sendRevision($request, $is_transaction = true)
    {
        if($is_transaction) DB::beginTransaction();
        try {

            if(!$request->id) throw new \Exception("id tidak ditemukan", 400);
            if(!$request->id_user) throw new \Exception("user id tidak ditemukan", 400);
            if(!$request->note) throw new \Exception("note belum terisi", 400);

            //update upload
            $update = RegularOrderEntryUpload::where('id',$request->id)
                        ->update(['status' => Constant::STS_PROCESS_REVISION]);

            //save revisi
            $params['id_regular_order_entry_upload'] = $request->id;
            $params['id_user'] = $request->id_user;
            $params['note'] = $request->note;
            $params['type'] = 'REVISION';
            self::create($params);

            if($is_transaction) DB::commit();
        } catch (\Throwable $th) {
            if($is_transaction) DB::rollBack();
            throw $th;
        }
    }

    public static function sendRejected($request, $is_transaction = true)
    {
        if($is_transaction) DB::beginTransaction();
        try {

            if(!$request->id) throw new \Exception("id tidak ditemukan", 400);
            if(!$request->id_user) throw new \Exception("user id tidak ditemukan", 400);
            if(!$request->note) throw new \Exception("note belum terisi", 400);

            //update upload
            $update = RegularOrderEntryUpload::where('id',$request->id)
                ->update(['status' => Constant::STS_PROCESS_REJECTED]);

            //save revisi
            $params['id_regular_order_entry_upload'] = $request->id;
            $params['id_user'] = $request->id_user;
            $params['note'] = $request->note;
            $params['type'] = 'REJECTED';
            self::create($params);

            return ['message' => 'Data Rejected'];

            if($is_transaction) DB::commit();
        } catch (\Throwable $th) {
            if($is_transaction) DB::rollBack();
            throw $th;
        }
    }
}
