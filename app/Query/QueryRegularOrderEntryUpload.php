<?php

namespace App\Query;

use App\Constants\Constant;
use App\Models\RegularOrderEntryUpload AS Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\ApiHelper as Helper;
use App\Imports\OrderEntry;
use App\Models\RegularDeliveryPlan;
use App\Models\RegularOrderEntry;
use App\Models\RegularOrderEntryUploadDetail;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

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
            if($params->dropdown == Constant::IS_ACTIVE) {
                $params->limit = null;
                $params->page = 1;
            }
            if($params->withTrashed == 'true') $query->withTrashed();
            if($params->id_regular_order_entry) $query->where('id_regular_order_entry', $params->id_regular_order_entry);

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
                    if($item->status == 1)
                        $item->status_desc = "Proses";
                    else if($item->status == 2)
                        $item->status_desc = "Selesai";
                    else if($item->status == 3)
                        $item->status_desc = "Send To PC";
                    else if($item->status == 4)
                        $item->status_desc = "Revisi";
                    else if($item->status == 5)
                        $item->status_desc = "Approved";
                    else if($item->status == 6)
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
        $data = self::where('id_regular_order_entry',$id)->get();

        if($data == null) throw new \Exception("id tidak ditemukan", 400);

        $data->map(function ($item){
            $regularOrderEntry = $item->refRegularOrderEntry;
            if($regularOrderEntry){
                $item->regular_order_entry_period = $regularOrderEntry->period;
                $item->regular_order_entry_month = $regularOrderEntry->month;
                $item->regular_order_entry_year = $regularOrderEntry->year;
            }

            unset($item->refRegularOrderEntry);
            $item->status_desc = null;
            if($item->status == 1)
                $item->status_desc = "Process";
            else if($item->status == 2)
                $item->status_desc = "Done Upload";
            else if($item->status == 3)
                $item->status_desc = "Send To PC";
            else if($item->status == 4)
                $item->status_desc = "Correction";
            else if($item->status == 5)
                $item->status_desc = "Approved PC";
            else if($item->status == 6)
                $item->status_desc = "Error";
            else if($item->status == 7)
                $item->status_desc = "Send To DC Manager";
            else if($item->status == 8)
                $item->status_desc = "Finish";

        });

        return $data;
    }

    public static function saveFile($request,$is_transaction = true)
    {
        if($is_transaction) DB::beginTransaction();
        try {

            $file = $request->file('file');
            $filename = $file->getClientOriginalName();
            $ext = $file->getClientOriginalExtension();
            if(!in_array($ext,['xlx','xlsx','xlsb'])) throw new \Exception("file format error", 400);
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

            $store = self::create($params);


            Excel::queueImport(new OrderEntry($store->id,[
                'year' => $request->year,
                'month' => $request->month,
            ]),storage_path().'/app/'.$params['filepath']);


            if($is_transaction) DB::commit();
        } catch (\Throwable $th) {
            if($is_transaction) DB::rollBack();
            throw $th;
        }
    }


    public static function deletedByIdOrderEntry($id_order_entry,$is_transaction = true)
    {
        if($is_transaction) DB::beginTransaction();
        try {
            self::where('id_regular_order_entry',$id_order_entry)->delete();
            if($is_transaction) DB::commit();
        } catch (\Throwable $th) {
            if($is_transaction) DB::rollBack();
            throw $th;
        }
    }


    public static function updateStatusAfterImport($id,$is_transaction = true)
    {
        if($is_transaction) DB::beginTransaction();
        try {
            $store = self::find($id);
            $store->status = Constant::STS_PROCESS_FINISHED;
            $store->save();
            if($is_transaction) DB::commit();
        } catch (\Throwable $th) {
            if($is_transaction) DB::rollBack();
            throw $th;
        }
    }

    public static function updateStatusSendToPc($request,$is_transaction = true)
    {
        if($is_transaction) DB::beginTransaction();
        try {
            $store = self::find($request->id);
            $store->status = Constant::STS_PROCESS_SEND_TO_PC;
            $store->save();
            if($is_transaction) DB::commit();
        } catch (\Throwable $th) {
            if($is_transaction) DB::rollBack();
            throw $th;
        }
    }

    public static function updateStatusSendToApprove($request,$is_transaction = true)
    {
        if($is_transaction) DB::beginTransaction();
        try {
            $store = self::find($request->id);
            $store->status = Constant::STS_PROCESS_APPROVED;
            $store->save();
            if($is_transaction) DB::commit();
        } catch (\Throwable $th) {
            if($is_transaction) DB::rollBack();
            throw $th;
        }
    }

    public static function updateStatusSendToDcManager($request,$is_transaction = true)
    {
        if($is_transaction) DB::beginTransaction();
        try {
            $store = self::find($request->id);
            $store->status = Constant::STS_SEND_TO_DC_MANAGER;
            $store->save();
            if($is_transaction) DB::commit();
        } catch (\Throwable $th) {
            if($is_transaction) DB::rollBack();
            throw $th;
        }
    }

    public static function getOrderDcSpv($params)
    {
        $key = self::cast.'-pc-'.json_encode($params->query());
        return Helper::storageCache($key, function () use ($params){
            $query = self::where(function ($query) use ($params){
                $query->where('status',Constant::STS_SEND_TO_DC_MANAGER);
                if($params->search) $query->where('filename', 'like', "'%$params->search%'");
            })
                ->whereHas('refRegularOrderEntry',function ($query) use ($params){
                    if($params->datasoruce) $query->where('datasoruce',$params->datasoruce);
                    if($params->date) $query->whereDate('created_at',$params->date);
                });


            if($params->dropdown == Constant::IS_ACTIVE) {
                $params->limit = null;
                $params->page = 1;
            }

            if($params->withTrashed == 'true') $query->withTrashed();

            $data = $query
                ->orderBy('id','desc')
                ->paginate($params->limit ?? null);
            return [

                'items' => $data->getCollection()->transform(function ($item){
                    $result = $item->refRegularOrderEntry;
                    $result->filename = $item->filename;
                    $result->batch = $item->iteration;
                    return $result;
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


    public static function getOrderEntryPc($params)
    {
        $key = self::cast.'-pc-'.json_encode($params->query());
        return Helper::storageCache($key, function () use ($params){
            $query = self::where(function ($query) use ($params){
               $query->where('status',Constant::STS_PROCESS_SEND_TO_PC);
               if($params->search) $query->where('filename', 'like', "'%$params->search%'");
            })
            ->whereHas('refRegularOrderEntry',function ($query) use ($params){
                if($params->datasoruce) $query->where('datasoruce',$params->datasoruce);
                if($params->date) $query->whereDate('created_at',$params->date);
            });


            if($params->dropdown == Constant::IS_ACTIVE) {
                $params->limit = null;
                $params->page = 1;
            }

            if($params->withTrashed == 'true') $query->withTrashed();

            $data = $query
            ->orderBy('id','desc')
            ->paginate($params->limit ?? null);
            return [

                'items' => $data->getCollection()->transform(function ($item){
                    $result = $item->refRegularOrderEntry;
                    $result->filename = $item->filename;
                    $result->batch = $item->iteration;
                    return $result;
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

    public static function getOrderEntryDcOff($params)
    {
        $key = self::cast.'-pc-'.json_encode($params->query());
        return Helper::storageCache($key, function () use ($params){
            $query = self::where(function ($query) use ($params){
                $query->whereIn('status',[Constant::STS_PROCESS_REVISION, Constant::STS_PROCESS_APPROVED]);
                if($params->search) $query->where('filename', 'like', "'%$params->search%'");
            })
                ->whereHas('refRegularOrderEntry',function ($query) use ($params){
                    if($params->datasoruce) $query->where('datasoruce',$params->datasoruce);
                    if($params->date) $query->whereDate('created_at',$params->date);
                });


            if($params->dropdown == Constant::IS_ACTIVE) {
                $params->limit = null;
                $params->page = 1;
            }

            if($params->withTrashed == 'true') $query->withTrashed();

            $data = $query
                ->orderBy('id','desc')
                ->paginate($params->limit ?? null);
            return [

                'items' => $data->getCollection()->transform(function ($item){
                    $result = $item->refRegularOrderEntry;
                    $result->filename = $item->filename;
                    $result->batch = $item->iteration;
                    return $result;
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

    public static  function getOrderEntryDcManager($params)
    {
        $key = self::cast.'-dc-'.json_encode($params->query());
        return Helper::storageCache($key, function () use ($params){
            $query = self::where(function ($query) use ($params){
               $query->where('status',Constant::STS_SEND_TO_DC_MANAGER);
               if($params->search) $query->where('filename', 'like', "'%$params->search%'");
            })
            ->whereHas('refRegularOrderEntry',function ($query) use ($params){
                if($params->datasoruce) $query->where('datasoruce',$params->datasoruce);
                if($params->date) $query->whereDate('created_at',$params->date);
            });


            if($params->dropdown == Constant::IS_ACTIVE) {
                $params->limit = null;
                $params->page = 1;
            }

            if($params->withTrashed == 'true') $query->withTrashed();

            $data = $query
            ->orderBy('id','desc')
            ->paginate($params->limit ?? null);
            return [
                'items' => $data->getCollection()->transform(function ($item){
                    $result = $item->refRegularOrderEntry;
                    $result->status = $item->status;
                    $result->status_desc = "";
                    return $result;
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

    public static function destroyz($id,$is_transaction = true)
    {
        if($is_transaction) DB::beginTransaction();
        try {

            $data = self::find($id);
            if(!$data) throw new \Exception("Data not found.", 400);

            $check = self::where('id_regular_order_entry',$data->id_regular_order_entry)->count();
            if($check > 1) $data->forceDelete();
            else RegularOrderEntry::find($data->id_regular_order_entry)->forceDelete();
            if($is_transaction) DB::commit();
            Cache::flush([self::cast]); //delete cache
        } catch (\Throwable $th) {
            if($is_transaction) DB::rollBack();
            throw $th;
        }
    }

    public static function retriveFinish($params,$is_transaction = true)
    {
        if($is_transaction) DB::beginTransaction();
        try {

            Helper::requireParams([
                'id_order_entry'
            ]);


            $items = RegularOrderEntry::find($params->id_order_entry);
            if(!$items) throw new \Exception("Data tidak ditemukan", 500);
            $data = self::whereHas('refRegularOrderEntry',function ($query){
                $query->where('year','2023');
                $query->where('month','02');
            })->get()->pluck('id');

            $detail = RegularOrderEntryUploadDetail::whereIn('id_regular_order_entry_upload',$data->toArray())->get()
            ->where('status','fixed')
            ->map(function ($item) use ($params){
                $item->id_regular_order_entry = $params->id_order_entry;
                return $item;
            })
            ->toArray();

            $ext = [];
            foreach ($detail as $key => $item) {
                $ext[] = [
                    "model" => $item['model'],
                    "item_no" => $item['item_no'],
                    "code_consignee" => $item['code_consignee'],
                    "disburse" => $item['disburse'],
                    "delivery" => $item['delivery'],
                    "qty" => $item['qty'],
                    "order_no" => $item['order_no'],
                    "cust_item_no" => $item['cust_item_no'],
                    "etd_jkt" => $item['etd_jkt'],
                    "etd_ypmi" => $item['etd_ypmi'],
                    "etd_wh" => $item['etd_wh'],
                    "id_regular_order_entry" => $item['id_regular_order_entry'],
                    "created_at" => now(),
                    'is_inquiry' => 0,
                    "uuid" => (string) Str::uuid()
                ];
            }

            foreach (array_chunk($ext,1000) as $param) {
                RegularDeliveryPlan::insert($param);
            }
            if($is_transaction) DB::commit();
            Cache::flush([self::cast]); //delete cache
        } catch (\Throwable $th) {
            if($is_transaction) DB::rollBack();
            throw $th;
        }
    }
}
