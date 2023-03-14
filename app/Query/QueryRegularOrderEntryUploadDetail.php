<?php

namespace App\Query;

use App\Constants\Constant;
use App\Models\RegularOrderEntryUploadDetail AS Model;
use App\Models\RegularOrderEntryUploadDetailBox;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\ApiHelper as Helper;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class QueryRegularOrderEntryUploadDetail extends Model {

    const cast = 'regular-order-entry-upload-detail';

    public static function getAll($params)
    {
        $key = self::cast.json_encode($params->query());
        return Helper::storageCache($key, function () use ($params){
            $query = self::where(function ($query) use ($params){
               if($params->search)
                    $query->where('code_consignee', 'like', "'%$params->search%'")
                            ->orWhere('model', 'like', "'%$params->search%'")
                            ->orWhere('item_no', 'like', "'%$params->search%'")
                            ->orWhere('disburse', 'like', "'%$params->search%'")
                            ->orWhere('delivery', 'like', "'%$params->search%'")
                            ->orWhere('qty', 'like', "'%$params->search%'")
                            ->orWhere('status', 'like', "'%$params->search%'")
                            ->orWhere('order_no', 'like', "'%$params->search%'")
                            ->orWhere('cust_item_no', 'like', "'%$params->search%'");
                });

            if($params->withTrashed == 'true') $query->withTrashed();
            if($params->dropdown == Constant::IS_ACTIVE) {
                $params->limit = null;
                $params->page = 1;
            }
            if($params->id_regular_order_entry_upload) $query->where('id_regular_order_entry_upload', $params->id_regular_order_entry_upload);

            $data = $query
            ->orderBy('id','asc')
            ->paginate($params->limit ?? null);

            return [
                'items' => $data->map(function ($item){
                    $etd_jkt = date('Y-m-d',strtotime($item->delivery)) ?? null;
                    $box = self::getDetailBox($item->uuid);

                    $set["id"] = $item->id;
                    $set["id_regular_order_entry_upload"] = $item->id_regular_order_entry_upload;
                    $set["code_consignee"] = $item->code_consignee;
                    $set["model"] = $item->model;
                    $set["item_no"] = $item->item_no;
                    $set["disburse"] = $item->disburse;
                    $set["delivery"] = $item->delivery;
                    $set["qty"] = $item->qty;
                    $set["status"] = $item->status;
                    $set["order_no"] = $item->order_no;
                    $set["cust_item_no"] = $item->cust_item_no;
                    $set["created_at"] = $item->created_at;
                    $set["created_by"] = $item->created_by;
                    $set["updated_at"] = $item->updated_at;
                    $set["updated_by"] = $item->updated_by;
                    $set["deleted_at"] = $item->deleted_at;
                    $set["uuid"] = $item->uuid;
                    $set["etd_jkt"] = $etd_jkt;
                    $set["etd_wh"] = date_create($etd_jkt)->modify('-2 days')->format('Y-m-d');
                    $set["etd_ypmi"] = date_create($etd_jkt)->modify('-4 days')->format('Y-m-d');
                    $set["box"] = $box;

                    unset($item->refRegularOrderEntryUpload);
                    return $set;
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

    public static function getDetailBox($uuid){
        $data = RegularOrderEntryUploadDetailBox::select('mst_box.qty','mst_box.length','mst_box.width','mst_box.height')
                ->where('uuid_regular_order_entry_upload_detail', $uuid)
                ->join('mst_box','mst_box.id','regular_order_entry_upload_detail_box.id_box')
                ->get();

        return $data;
    }

    public static function byId($id)
    {
        $data = self::where('id_regular_order_entry_upload',$id)->get();

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

        });

        return $data;
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

            $update->fill($params);
            $update->save();
            if($is_transaction) DB::commit();
            Cache::flush([self::cast]); //delete cache
        } catch (\Throwable $th) {
            if($is_transaction) DB::rollBack();
            throw $th;
        }
    }

    public static function store($request,$is_transaction = true)
    {
        if($is_transaction) DB::beginTransaction();
        try {
            self::insert($request);
            if($is_transaction) DB::commit();
            Cache::flush([self::cast]); //delete cache
        } catch (\Throwable $th) {
            if($is_transaction) DB::rollBack();
            throw $th;
        }
    }

    public static function getColumn(){
        $dummyObj = '[
            {
              "title": "Id",
              "field": "id",
              "width": 160,
              "headerSort": false
            },
            {
              "title": "Cust Name",
              "field": "name",
              "width": 160,
              "headerSort": false
            },
            {
              "title": "Item No",
              "field": "item_no",
              "width": 160,
              "headerSort": false
            },
            {
              "title": "Item Name",
              "field": "item_name",
              "width": 160,
              "headerSort": false
            },
            {
              "title": "Customer Item No",
              "field": "customer_item_no",
              "width": 170,
              "headerSort": false
            },
            {
              "title": "Registration Code",
              "field": "registration_code",
              "width": 170,
              "headerSort": false
            },
            {
              "title": "",
              "columns": [
                {
                  "title": "ETD YPMI",
                  "field": "progress",
                  "hozAlign": "right",
                  "sorter": "number",
                  "width": 150,
                  "headerSort": false,
                  "headerHozAlign": "center",
                  "columns": [
                    {
                      "title": "ETD W/H",
                      "width": 150,
                      "hozAlign": "center",
                      "headerHozAlign": "center",
                      "columns": [
                        {
                          "title": "ETD JKT",
                          "width": 150,
                          "headerSort": false,
                          "hozAlign": "center",
                          "headerHozAlign": "center",
                          "columns": [
                            {
                              "title": "Customer OD No",
                              "width": 150,
                              "headerSort": false,
                              "hozAlign": "center",
                              "headerHozAlign": "center"
                            }
                          ]
                        }
                      ]
                    }
                  ]
                },
                {
                  "title": "28-JUL",
                  "field": "progress",
                  "hozAlign": "right",
                  "sorter": "number",
                  "width": 150,
                  "headerSort": false,
                  "headerHozAlign": "center",
                  "columns": [
                    {
                      "title": "4-AUG",
                      "width": 150,
                      "hozAlign": "center",
                      "headerHozAlign": "center",
                      "columns": [
                        {
                          "title": "7-AUG",
                          "width": 150,
                          "headerSort": false,
                          "hozAlign": "center",
                          "headerHozAlign": "center",
                          "columns": [
                            {
                              "title": "QTY",
                              "width": 150,
                              "headerSort": false,
                              "hozAlign": "center",
                              "headerHozAlign": "center"
                            }
                          ]
                        }
                      ]
                    }
                  ]
                },
                {
                  "title": "BOX",
                  "field": "car",
                  "hozAlign": "center",
                  "width": 150,
                  "headerSort": false,
                  "headerHozAlign": "center"
                }
              ]
            },
            {
              "title": "AUG",
              "columns": [
                {
                  "title": "28-JUL",
                  "field": "progress",
                  "hozAlign": "right",
                  "sorter": "number",
                  "width": 150,
                  "headerSort": false,
                  "headerHozAlign": "center",
                  "columns": [
                    {
                      "title": "4-AUG",
                      "width": 150,
                      "hozAlign": "center",
                      "headerHozAlign": "center",
                      "columns": [
                        {
                          "title": "7-AUG",
                          "width": 150,
                          "headerSort": false,
                          "hozAlign": "center",
                          "headerHozAlign": "center",
                          "columns": [
                            {
                              "title": "QTY",
                              "width": 150,
                              "headerSort": false,
                              "hozAlign": "center",
                              "headerHozAlign": "center"
                            }
                          ]
                        }
                      ]
                    }
                  ]
                },
                {
                  "title": "BOX",
                  "field": "car",
                  "hozAlign": "center",
                  "width": 150,
                  "headerSort": false,
                  "headerHozAlign": "center"
                }
              ]
            },
            {
              "title": "",
              "columns": [
                {
                  "title": "28-JUL",
                  "field": "progress",
                  "hozAlign": "right",
                  "sorter": "number",
                  "width": 150,
                  "headerSort": false,
                  "headerHozAlign": "center",
                  "columns": [
                    {
                      "title": "4-AUG",
                      "width": 150,
                      "hozAlign": "center",
                      "headerHozAlign": "center",
                      "columns": [
                        {
                          "title": "7-AUG",
                          "width": 150,
                          "headerSort": false,
                          "hozAlign": "center",
                          "headerHozAlign": "center",
                          "columns": [
                            {
                              "title": "QTY",
                              "width": 150,
                              "headerSort": false,
                              "hozAlign": "center",
                              "headerHozAlign": "center"
                            }
                          ]
                        }
                      ]
                    }
                  ]
                },
                {
                  "title": "BOX",
                  "field": "car",
                  "hozAlign": "center",
                  "width": 150,
                  "headerSort": false,
                  "headerHozAlign": "center"
                }
              ]
            },
            {
              "title": "",
              "columns": [
                {
                  "title": "28-JUL",
                  "field": "progress",
                  "hozAlign": "right",
                  "sorter": "number",
                  "width": 150,
                  "headerSort": false,
                  "headerHozAlign": "center",
                  "columns": [
                    {
                      "title": "4-AUG",
                      "width": 150,
                      "hozAlign": "center",
                      "headerHozAlign": "center",
                      "columns": [
                        {
                          "title": "7-AUG",
                          "width": 150,
                          "headerSort": false,
                          "hozAlign": "center",
                          "headerHozAlign": "center",
                          "columns": [
                            {
                              "title": "QTY",
                              "width": 150,
                              "headerSort": false,
                              "hozAlign": "center",
                              "headerHozAlign": "center"
                            }
                          ]
                        }
                      ]
                    }
                  ]
                },
                {
                  "title": "BOX",
                  "field": "car",
                  "hozAlign": "center",
                  "width": 150,
                  "headerSort": false,
                  "headerHozAlign": "center"
                },
                {
                  "title": "TOTAL",
                  "field": "total",
                  "width": 160,
                  "headerSort": false,
                  "hozAlign": "center",
                  "headerHozAlign": "center"
                }
              ]
            }
          ]';


        //return Schema::getColumnListing('regular_order_entry_upload_detail');
        return  json_decode($dummyObj);
    }

    public static function editPivot($params)
    {
        try {
            $query = self::where('id_regular_order_entry_upload', $params->id_regular_order_entry_upload);
            if($params->category && $params->value)
                $query->whereAnd($params->category, "'$params->value'");

            $query->whereBetween("delivery", ["'$params->start_date'","'$params->end_date'"]);

            $data = $query->orderBy('id','asc')
            ->paginate($params->limit ?? null);

            return [
                'items' => $data->map(function ($item){

                    $etd_jkt = date('Y-m-d',strtotime($item->delivery)) ?? null;
                    $box = self::getDetailBox($item->uuid);

                    $set["id"] = $item->id;
                    $set["id_regular_order_entry_upload"] = $item->id_regular_order_entry_upload;
                    $set["code_consignee"] = $item->code_consignee;
                    $set["model"] = $item->model;
                    $set["item_no"] = $item->item_no;
                    $set["disburse"] = $item->disburse;
                    $set["delivery"] = $item->delivery;
                    $set["qty"] = $item->qty;
                    $set["status"] = $item->status;
                    $set["order_no"] = $item->order_no;
                    $set["cust_item_no"] = $item->cust_item_no;
                    $set["created_at"] = $item->created_at;
                    $set["created_by"] = $item->created_by;
                    $set["updated_at"] = $item->updated_at;
                    $set["updated_by"] = $item->updated_by;
                    $set["deleted_at"] = $item->deleted_at;
                    $set["uuid"] = $item->uuid;
                    $set["etd_jkt"] = $etd_jkt;
                    $set["etd_wh"] = date_create($etd_jkt)->modify('-2 days')->format('Y-m-d');
                    $set["etd_ypmi"] = date_create($etd_jkt)->modify('-4 days')->format('Y-m-d');
                    $set["box"] = $box;

                    unset($item->refRegularOrderEntryUpload);
                    return $set;
                }),
                'last_page' => $data->lastPage(),
                'attributes' => [
                    'total' => $data->total(),
                    'current_page' => $data->currentPage(),
                    'from' => $data->currentPage(),
                    'per_page' => (int) $data->perPage(),
                ],
            ];
        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
