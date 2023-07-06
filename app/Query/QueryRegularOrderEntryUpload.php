<?php

namespace App\Query;

use App\Constants\Constant;
use App\Models\RegularOrderEntryUpload AS Model;
use App\Models\RegularOrderEntryUploadDetailRevision;
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
use App\Models\RegularOrderEntryUploadDetailSet;
use App\Models\RegularOrderEntryUploadDetailTemp;
use App\Models\VFinishBox;
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
                    else if($item->status == 10)
                        $item->status_desc = "Failed Upload";

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

    public static function getRetry($params)
    {
        $data = RegularOrderEntryUploadDetailRevision::where('id_regular_order_entry_upload', $params->id)
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
            'last_page' => $data->lastPage(),
            'attributes' => [
                'total' => $data->total(),
                'current_page' => $data->currentPage(),
                'from' => $data->currentPage(),
                'per_page' => (int) $data->perPage(),
            ]
        ];
    }

    public static function getRetryInfo($params)
    {
        $data = Model::find($params->id);
        $set['datasource'] = $data->refRegularOrderEntry->datasource ?? null;
        $set['month'] = $data->refRegularOrderEntry->month ?? null;
        $set['year'] = $data->refRegularOrderEntry->year ?? null;
        return [
            'items' => $set,
            'last_page' => null,
            'attributes' => [
                'total' => 0,
                'current_page' => null,
                'from' => null,
                'per_page' => null,
            ]
        ];
    }

    public static function byId($params,$id)
    {
        $data = self::where('id_regular_order_entry',$id)->paginate($params->limit ?? null);

        if($data == null) throw new \Exception("id tidak ditemukan", 400);

        $data->transform(function ($item){
            $regularOrderEntry = $item->refRegularOrderEntry;
            if($regularOrderEntry){
                $item->filename = $item->filename.'.xlsx';
                $item->regular_order_entry_period = $regularOrderEntry->period;
                $item->regular_order_entry_month = $regularOrderEntry->month;
                $item->regular_order_entry_year = $regularOrderEntry->year;
                $item->datasource = $regularOrderEntry->datasource;
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

    public static function saveFile($store,$request,$is_transaction = true)
    {
        if($is_transaction) DB::beginTransaction();
        try {

            $file = $request->file('file');
            // $filename = $file->getClientOriginalName();
            $filename = 'OE-'.$request->month.$request->year.'-'.$store->datasource.'-0'.$request->iteration;
            $ext = $file->getClientOriginalExtension();
            if(!in_array($ext,['xls','xlx','xlsx','xlsb'])) throw new \Exception("file format error", 400);
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

            //
                //mapping data set
            // sleep(4);
            $data = RegularOrderEntryUploadDetailTemp::
            select('regular_order_entry_upload_detail_temp.etd_jkt','a.id_box','a.part_set',
            DB::raw("string_agg(DISTINCT regular_order_entry_upload_detail_temp.id::character varying, ',') as id_regular_order_entry_upload_detail_temp"),
            DB::raw("string_agg(DISTINCT regular_order_entry_upload_detail_temp.code_consignee::character varying, ',') as code_consignee"),
            DB::raw("string_agg(DISTINCT regular_order_entry_upload_detail_temp.item_no::character varying, ',') as item_no"),
            DB::raw("string_agg(DISTINCT regular_order_entry_upload_detail_temp.id_regular_order_entry_upload::character varying, ',') as id_regular_order_entry_upload"),
            DB::raw("string_agg(DISTINCT regular_order_entry_upload_detail_temp.model::character varying, ',') as model"),
            DB::raw("string_agg(DISTINCT regular_order_entry_upload_detail_temp.disburse::character varying, ',') as disburse"),
            DB::raw("string_agg(DISTINCT regular_order_entry_upload_detail_temp.delivery::character varying, ',') as delivery"),
            DB::raw("string_agg(DISTINCT regular_order_entry_upload_detail_temp.qty::character varying, ',') as qty"),
            DB::raw("string_agg(DISTINCT regular_order_entry_upload_detail_temp.status::character varying, ',') as status"),
            DB::raw("string_agg(DISTINCT regular_order_entry_upload_detail_temp.order_no::character varying, ',') as order_no"),
            DB::raw("string_agg(DISTINCT regular_order_entry_upload_detail_temp.cust_item_no::character varying, ',') as cust_item_no"),
            DB::raw("string_agg(DISTINCT regular_order_entry_upload_detail_temp.uuid::character varying, ',') as uuid"),
            DB::raw("string_agg(DISTINCT regular_order_entry_upload_detail_temp.etd_wh::character varying, ',') as etd_wh"),
            DB::raw("string_agg(DISTINCT regular_order_entry_upload_detail_temp.etd_ypmi::character varying, ',') as etd_ypmi"),
            DB::raw("string_agg(DISTINCT a.item_no_series::character varying, ',') as item_no_series"),
            DB::raw("SUM(regular_order_entry_upload_detail_temp.qty) as sum_qty")
            )
            ->where('id_regular_order_entry_upload', $store->id)
            ->leftJoin('mst_box as a','regular_order_entry_upload_detail_temp.item_no','a.item_no')
            ->groupBy('a.part_set','a.id_box','regular_order_entry_upload_detail_temp.etd_jkt')
            ->orderBy('id_regular_order_entry_upload_detail_temp','asc')
            ->get();

            $set = [];
            $single = [];
            foreach ($data->toArray() as $value) {
                if ($value['part_set'] == 'set') {
                    $set[] = $value;
                } elseif ($value['part_set'] == 'single') {
                    $single[] = $value;
                }
            }

            $id_set = [];
            foreach ($set as $value) {
                $id_set[] = $value['id_regular_order_entry_upload_detail_temp'];
            }

            $single_upload = [];
            foreach ($single as $value) {
                if (!str_contains(implode(',',$id_set),$value['id_regular_order_entry_upload_detail_temp'])) {
                    $single_upload[] = $value;
                }
            }

            foreach ($set as $value) {
                $upload_detail = RegularOrderEntryUploadDetail::create([
                    'id_regular_order_entry_upload' => $value['id_regular_order_entry_upload'],
                    'code_consignee' => $value['code_consignee'],
                    'delivery' => $value['delivery'],
                    'qty' => $value['sum_qty'],
                    'status' => $value['status'],
                    'order_no' => $value['order_no'],
                    'cust_item_no' => $value['cust_item_no'],
                    'etd_wh' => $value['etd_wh'],
                    'etd_ypmi' => $value['etd_ypmi'],
                    'etd_jkt' => $value['etd_jkt'],
                    'uuid' => $value['uuid'],
                    'jenis' => 'set'
                ]);

                foreach (explode(',',$value['item_no']) as $value) {
                    RegularOrderEntryUploadDetailSet::create([
                        'id_detail' => $upload_detail->id,
                        'item_no' => $value,
                    ]);
                }
            }

            foreach ($single_upload as $value) {
                $upload = RegularOrderEntryUploadDetail::create($value);
                $upload->update(['jenis' => 'single']);
            }
            //

            // sleep(2);
            // dd(RegularOrderEntryUploadDetail::where('id_regular_order_entry_upload',$store->id)->get());

            RegularOrderEntryUploadDetail::where([
                'id_regular_order_entry_upload' => $store->id
            ])
            ->each(function ($item){
                $request = $item->toArray();

                $detail_set = RegularOrderEntryUploadDetailSet::where('id_detail', $request['id'])->get();
                if ($detail_set) {
                    foreach ($detail_set as $key => $value) {
                        $box = QueryMstBox::byItemNoCdConsignee($value->item_no,$request['code_consignee']);
                        if($box) {
                            $box = $box->toArray();
                            $box_capacity = $box['qty'];
                            $qty = $request['qty'];
                            $loops = (int) ceil($qty / $box_capacity);
                            $ext = [];
                            for ($i=0; $i < $loops ; $i++) {
                                if($qty > $box_capacity)
                                    $qty_pcs_box = $box_capacity;
                                else
                                    $qty_pcs_box = $qty;
                                $ext[] = [
                                    'uuid' => (string) Str::uuid(),
                                    'id_regular_order_entry_upload_detail' => $request['id'],
                                    'uuid_regular_order_entry_upload_detail' => $request['uuid'],
                                    'id_box' => $box['id_box'],
                                    'created_at' => now(),
                                    'updated_at' => now(),
                                    'qty_pcs_box' => $qty_pcs_box
                                ];
                                $sum = $qty - $box_capacity;
                                $qty = $sum;
                            }

                            foreach (array_chunk($ext,10000) as $chunk) {
                                RegularOrderEntryUploadDetailBox::insert($chunk);
                            }
                        }
                    }
                }
                
                $box = QueryMstBox::byItemNoCdConsignee($request['item_no'],$request['code_consignee']);
                if($box) {
                    $box = $box->toArray();
                    $box_capacity = $box['qty'];
                    $qty = $request['qty'];
                    $loops = (int) ceil($qty / $box_capacity);
                    $ext = [];
                    for ($i=0; $i < $loops ; $i++) {
                        if($qty > $box_capacity)
                            $qty_pcs_box = $box_capacity;
                        else
                            $qty_pcs_box = $qty;
                        $ext[] = [
                            'uuid' => (string) Str::uuid(),
                            'id_regular_order_entry_upload_detail' => $request['id'],
                            'uuid_regular_order_entry_upload_detail' => $request['uuid'],
                            'id_box' => $box['id_box'],
                            'created_at' => now(),
                            'updated_at' => now(),
                            'qty_pcs_box' => $qty_pcs_box
                        ];
                        $sum = $qty - $box_capacity;
                        $qty = $sum;
                    }

                    foreach (array_chunk($ext,10000) as $chunk) {
                        RegularOrderEntryUploadDetailBox::insert($chunk);
                    }
                }
            });

        //     if($is_transaction) DB::commit();
            return $store->id;
        } catch (\Throwable $th) {
            if($is_transaction) DB::rollBack();
            throw $th;
        }
    }


    public static function revisionFile($request,$is_transaction = true)
    {
        if($is_transaction) DB::beginTransaction();
        try {

            $data = self::find($request->id_regular_order_entry_upload);
            if(!$data) throw new \Exception("data not found", 400);

            $upload_detail = RegularOrderEntryUploadDetail::where('id_regular_order_entry_upload', $request->id_regular_order_entry_upload)->get();
            if ($upload_detail) {
                foreach ($upload_detail as $value) {
                    $value->refRegularOrderEntryUploadDetailBox()->delete();
                    $value->delete();
                }
            }
            
            DB::table('regular_order_entry_upload_detail_revision')
            ->where('id_regular_order_entry_upload',$request->id_regular_order_entry_upload)
            ->delete();

            $file = $request->file('file');
            // $filename = $file->getClientOriginalName();
            $filename = 'OE-'.$data->refRegularOrderEntry->month.$data->refRegularOrderEntry->year.'-'.$data->refRegularOrderEntry->datasource.'-0'.$data->iteration;
            $ext = $file->getClientOriginalExtension();
            if(!in_array($ext,['xls','xlx','xlsx','xlsb'])) throw new \Exception("file format error", 400);
            $savedname = (string) Str::uuid().'.'.$ext;
            $params = [
                'filename' => $filename,
                'filepath' => '/order-entry/'.$data->refRegularOrderEntry->year.'/'.$data->refRegularOrderEntry->month.'/'.$savedname,
                'upload_date' => Carbon::now(),
                'status' => Constant::STS_PROCESSED,
            ];
            Storage::putFileAs(str_replace($savedname,'',$params['filepath']),$file,$savedname);

            $data->fill($params);
            $data->save();

            Excel::queueImport(new OrderEntry($data->id,[
                'year' => $data->refRegularOrderEntry->year,
                'month' => $data->refRegularOrderEntry->month,
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
            $check = DB::table('regular_order_entry_upload_detail_revision')
            ->where('id_regular_order_entry_upload',$id)->count();

            $store = self::find($id);
            $store->status = $check > 0 ? Constant::STS_REVISION_UPLOAD : Constant::STS_PROCESS_FINISHED;
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
    }


    public static function getOrderEntryPc($params)
    {

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
    }

    public static function getOrderEntryDcOff($params)
    {

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
            if(!$upload) throw new \Exception("Data not found", 400);
            $upload->status = Constant::STS_FINISH;
            $upload->save();

            $items = RegularOrderEntry::find($upload->id_regular_order_entry);
            if(!$items) throw new \Exception("Data tidak ditemukan", 500);

            $data = self::getDifferentPart($upload->id_regular_order_entry,$params->id);
            $result = collect($data)->toArray() ?? null;

            // dd(count($result));

            if($result){
                foreach ($result as $item){

                    $store = RegularDeliveryPlan::create([
                       "model" => $item->model,
                       "item_no" => $item->item_no,
                       "code_consignee" => $item->code_consignee,
                       "disburse" => $item->disburse,
                       "delivery" => $item->delivery,
                       "qty" => $item->qty,
                       "order_no" => $item->order_no,
                       "cust_item_no" => $item->cust_item_no,
                       "etd_jkt" => $item->etd_jkt,
                       "etd_ypmi" => $item->etd_ypmi,
                       "etd_wh" => $item->etd_wh,
                       "id_regular_order_entry" => $upload->id_regular_order_entry,
                       "created_at" => now(),
                       "is_inquiry" => 0,
                       'datasource' => $item->datasource,
                    //    "id_regular_order_entry_upload_detail" => $item->id,
                       "uuid" => (string) Str::uuid()
                   ]);

                   $box = VFinishBox::where([
                        "model" => $item->model,
                        "item_no" => $item->item_no,
                        "code_consignee" => $item->code_consignee,
                        "disburse" => $item->disburse,
                        "delivery" => $item->delivery,
                        "qty" => $item->qty,
                        "order_no" => $item->order_no,
                        "cust_item_no" => $item->cust_item_no,
                        "etd_jkt" => $item->etd_jkt,
                        "id_regular_order_entry" => $upload->id_regular_order_entry,
                   ])
                   ->get()->map(function ($item_box) use ($store) {
                       return [
                           'id_box' => $item_box->id_box,
                           'id_regular_delivery_plan' => $store->id,
                           'created_at' => now(),
                           'qty_pcs_box' => $item_box->qty_pcs_box
                       ];
                   })->toArray();


                   foreach (array_chunk($box,1000) as $item_box) {
                       RegularDeliveryPlanBox::insert($item_box);
                   }

                }
            }



            if($is_transaction) DB::commit();
            Cache::flush([self::cast]); //delete cache
        } catch (\Throwable $th) {
            if($is_transaction) DB::rollBack();
            throw $th;
        }
    }

    public static function getDifferentPart($id,$id_regular_order_entry_upload){

        return DB::select(DB::raw("SELECT
                    a.datasource,
                    c.code_consignee,
                    c.model, c.item_no,
                    c.disburse,
                    c.delivery,
                    c.qty, c.order_no,
                    c.cust_item_no,
                    c.etd_jkt,
                    c.etd_ypmi,
                    c.etd_wh
                    FROM
                    regular_order_entry a,
                    regular_order_entry_upload b,
                    regular_order_entry_upload_detail c
                    where a.id = b.id_regular_order_entry and
                    b.id = c.id_regular_order_entry_upload and
                    c.status = 'fixed' and c.is_delivery_plan = 0 and
                    a.id = ? and b.id = ?
                    EXCEPT
                    SELECT
                    c.datasource,
                    c.code_consignee,
                    c.model, c.item_no,
                    c.disburse,
                    c.delivery,
                    c.qty, c.order_no,
                    c.cust_item_no,
                    c.etd_jkt,
                    c.etd_ypmi,
                    c.etd_wh
                    FROM
                    regular_delivery_plan c WHERE c.id_regular_order_entry = ?"), [$id,$id_regular_order_entry_upload,$id]) ?? null;
    }
}
