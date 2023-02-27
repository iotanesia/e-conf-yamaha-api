<?php

namespace App\Query;

use App\Constants\Constant;
use App\Models\RegularOrderEntryUpload AS Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\ApiHelper as Helper;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
class QueryRegularOrderEntryUpload extends Model {

    const cast = 'regular-order-entry-upload';

    public static function getAll($params)
    {
        $key = self::cast.json_encode($params->query());
        return Helper::storageCache($key, function () use ($params){
            $query = self::where(function ($query) use ($params){
               if($params->search)
                    $query->where('filename', 'like', "'%$params->search%'");
            });

            if($params->status) $query->where('status', "$params->status");

            if($params->withTrashed == 'true')
                $query->withTrashed();
            if($params->id_regular_order_entry)
                $query->where('id_regular_order_entry', $params->id_regular_order_entry);

            $data = $query
            ->orderBy('id','desc')
            ->paginate($params->limit ?? null);
            return [
                'items' => $data->map(function ($item){
                    $regularOrderEntry = $item->refRegularOrderEntry;
                    if($regularOrderEntry){
                        $item->regular_order_entry_period = $regularOrderEntry->period;
                        $item->regular_order_entry_month = $regularOrderEntry->month;
                        $item->regular_order_entry_year = $regularOrderEntry->year;
                    }

                    unset($item->refRegularOrderEntry);

                    $item->status_desc = null;
                    if($item->status == 0)
                        $item->status_desc = "Proses";
                    else if($item->status == 1)
                        $item->status_desc = "Selesai";
                    else if($item->status == 2)
                        $item->status_desc = "Send To PC";
                    else if($item->status == 3)
                        $item->status_desc = "Revisi";
                    else if($item->status == 4)
                        $item->status_desc = "Approved";
                    else if($item->status == 5)
                        $item->status_desc = "Error";

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
        $data = self::where('id_regular_order_entry', $id)->get();

        if($data == null) throw new \Exception("id tidak ditemukan", 400);

        $regularOrderEntry = $data->refRegularOrderEntry;
        if($regularOrderEntry){
            $data->regular_order_entry_period = $regularOrderEntry->period;
            $data->regular_order_entry_month = $regularOrderEntry->month;
            $data->regular_order_entry_year = $regularOrderEntry->year;
        }

        unset($data->refRegularOrderEntry);

        $data->status_desc = null;
        if($data->status == 0)
            $data->status_desc = "Proses";
        else if($data->status == 1)
            $data->status_desc = "Selesai";
        else if($data->status == 2)
            $data->status_desc = "Send To PC";
        else if($data->status == 3)
            $data->status_desc = "Revisi";
        else if($data->status == 4)
            $data->status_desc = "Approved";
        else if($data->status == 5)
            $data->status_desc = "Error";
        return $data;
    }

    public static function saveFile($request,$is_transaction = true)
    {
        if($is_transaction) DB::beginTransaction();
        try {

            $file = $request->file('file');
            $filename = $file->getClientOriginalName();
            $ext = $file->getClientOriginalExtension();
            if(!in_array($ext,['xlx','xlsx'])) throw new \Exception("file format error", 400);
            $savedname = (string) Str::uuid().'.'.$ext;
            $params = [
                'id_regular_order_entry' => $request->id_regular_order_entry,
                'filename' => $filename,
                'filepath' => '/order-entry/'.$request->year.'/'.$request->month.'/'.$savedname,
                'upload_date' => Carbon::now(),
                'iteration' => $request->iteration,
                'status' => Constant::STS_PROCESSED,
                'uuid' => (string) Str::uuid()
            ];
            Storage::putFileAs(str_replace($savedname,'',$params['filepath']),$file,$savedname);
            self::create($params);
            if($is_transaction) DB::commit();
        } catch (\Throwable $th) {
            if($is_transaction) DB::rollBack();
            throw $th;
        }
    }



}
