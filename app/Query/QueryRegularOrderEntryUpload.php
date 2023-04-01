<?php

namespace App\Query;

use App\Constants\Constant;
use App\Models\RegularOrderEntryUpload AS Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\ApiHelper as Helper;
use App\Imports\OrderEntry;
use App\Models\RegularDeliveryPlan;
use App\Models\RegularDeliveryPlanBox;
use App\Models\RegularOrderEntry;
use App\Models\RegularOrderEntryUpload;
use App\Models\RegularOrderEntryUploadDetail;
use App\Models\RegularOrderEntryUploadDetailBox;
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

    public static function byId($params,$id)
    {
        $data = self::where('id_regular_order_entry',$id)->paginate($params->limit ?? null);

        if($data == null) throw new \Exception("id tidak ditemukan", 400);

        $data->transform(function ($item){
            $regularOrderEntry = $item->refRegularOrderEntry;
            if($regularOrderEntry){
                $item->regular_order_entry_period = $regularOrderEntry->period;
                $item->regular_order_entry_month = $regularOrderEntry->month;
                $item->regular_order_entry_year = $regularOrderEntry->year;
            }

            $item->status_desc = Constant::STS_PROCESS_RG_ENTRY[$item->status];
            unset($item->refRegularOrderEntry);
            return $item;

        });

        return [
            'items' => $data->items(),
            'last_page' => $data->lastPage(),
        ];
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
                    $result->id_upload = $item->id;
                    $result->filename = $item->filename;
                    $result->batch = $item->iteration;
                    $result->status = "Send To Dc Spv";
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
                    $result->id_upload = $item->id;
                    $result->filename = $item->filename;
                    $result->batch = $item->iteration;
                    $result->status = "Send To Pc";
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
                $query->whereIn('status',[Constant::STS_PROCESS_REVISION, Constant::STS_PROCESS_APPROVED, Constant::STS_PROCESS_REJECTED]);
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
                    $result->id_upload = $item->id;
                    $result->filename = $item->filename;
                    $result->batch = $item->iteration;
                    if($item->status == 4)
                        $result->status_desc = 'Correction';
                    else if($item->status == 5)
                        $result->status_desc = 'Approved';
                    else if($item->status == 9)
                        $result->status_desc = 'Rejected';
                    else
                        $result->status_desc = 'Error';

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
                'id'
            ]);

            $upload = RegularOrderEntryUpload::find($params->id);
            $upload->status = Constant::STS_FINISH;
            $upload->save();

            $items = RegularOrderEntry::find($upload->id_regular_order_entry);
            if(!$items) throw new \Exception("Data tidak ditemukan", 500);

            $data = self::getDifferentPart(199);
            $result = collect($data)->chunk(50)->toArray() ?? null;
            if($result){
                foreach ($result as $key => $item){
                    foreach ($item as $indx => $items){
                        echo $items->code_consignee;
                    }
                }
            }



//            RegularOrderEntryUploadDetail::where('id_regular_order_entry_upload',$params->id)
//            ->where('status','fixed')
//            ->chunk(100,function ($datas) use ($upload){
//                foreach ($datas as $key => $items) {
//
//                    $items->is_delivery_plan = Constant::IS_ACTIVE;
//                    $items->save();
//
//                    $item = $items->toArray();
//                    $store = RegularDeliveryPlan::create([
//                        "model" => $item['model'],
//                        "item_no" => $item['item_no'],
//                        "code_consignee" => $item['code_consignee'],
//                        "disburse" => $item['disburse'],
//                        "delivery" => $item['delivery'],
//                        "qty" => $item['qty'],
//                        "order_no" => $item['order_no'],
//                        "cust_item_no" => $item['cust_item_no'],
//                        "etd_jkt" => $item['etd_jkt'],
//                        "etd_ypmi" => $item['etd_ypmi'],
//                        "etd_wh" => $item['etd_wh'],
//                        "id_regular_order_entry" => $upload->id_regular_order_entry,
//                        "created_at" => now(),
//                        "is_inquiry" => 0,
//                        "uuid" => (string) Str::uuid()
//                    ]);
//
//                    $box = RegularOrderEntryUploadDetailBox::where('uuid_regular_order_entry_upload_detail',$item['uuid'])
//                    ->get()->map(function ($item) use ($store) {
//                        return [
//                            'id_box' => $item->id_box,
//                            'id_regular_delivery_plan' => $store->id,
//                            'created_at' => now()
//                        ];
//                    })->toArray();
//
//                    foreach (array_chunk($box,1000) as $item_box) {
//                        RegularDeliveryPlanBox::insert($item_box);
//                    }
//
//                }
//
//            });

            if($is_transaction) DB::commit();
            Cache::flush([self::cast]); //delete cache
        } catch (\Throwable $th) {
            if($is_transaction) DB::rollBack();
            throw $th;
        }
    }

    public static function getDifferentPart($id){

        return DB::select(DB::raw("SELECT
                    c.code_consignee,
                    c.model, c.item_no,
                    c.disburse,
                    c.delivery,
                    c.qty, c.order_no,
                    c.cust_item_no,
                    c.etd_jkt
                    FROM
                    regular_order_entry a,
                    regular_order_entry_upload b,
                    regular_order_entry_upload_detail c
                    where a.id = b.id_regular_order_entry and
                    b.id = c.id_regular_order_entry_upload and
                    c.status = 'fixed' and c.is_delivery_plan = 0 and
                    a.id = ?
                    EXCEPT
                    SELECT
                    c.code_consignee,
                    c.model, c.item_no,
                    c.disburse,
                    c.delivery,
                    c.qty, c.order_no,
                    c.cust_item_no,
                    c.etd_jkt
                    FROM
                    regular_delivery_plan c WHERE c.id_regular_order_entry = ?"), [$id,$id]) ?? null;
    }
}
