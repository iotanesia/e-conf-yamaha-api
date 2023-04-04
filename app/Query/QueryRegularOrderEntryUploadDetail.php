<?php

namespace App\Query;

use App\Constants\Constant;
use App\Models\RegularOrderEntryUploadDetail AS Model;
use App\Models\MstConsignee;
use App\Models\MstPart;
use App\Models\VRegularOrderEntryUploadDetail AS VModel;
use App\Models\RegularOrderEntryUploadDetailBox;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\ApiHelper as Helper;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use stdClass;

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
                    $box = self::getDetailBox($item->uuid);
                    $custname = self::getCustName($item->code_consignee);
                    $itemname = self::getPart($item->item_no);

                    $set["id"] = $item->id;
                    $set["id_regular_order_entry_upload"] = $item->id_regular_order_entry_upload;
                    $set["code_consignee"] = $item->code_consignee;
                    $set["cust_name"] = $custname;
                    $set["model"] = $item->model;
                    $set["item_name"] = $itemname;
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
                    $set["etd_jkt"] = $item->etd_jkt;
                    $set["etd_wh"] = $item->etd_wh;
                    $set["etd_ypmi"] = $item->etd_ypmi;
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
                'last_page' => $data->lastPage()
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

    public static function getCustName($code_consignee){
        $data = MstConsignee::where('code', $code_consignee)->first();
        return $data->nick_name ?? null;
    }

    public static function getPart($id_part){
        $data = MstPart::where('item_no', $id_part)->first();
        return $data->description ?? null;
    }

    public static function byId($params,$id)
    {
        $data = self::where('id_regular_order_entry_upload',$id)->paginate($params->limit ?? null);
        if($data == null) throw new \Exception("id tidak ditemukan", 400);
        $data->transform(function ($item){
            $regularOrderEntry = $item->refRegularOrderEntry;
            if($regularOrderEntry){
                $item->regular_order_entry_period = $regularOrderEntry->period;
                $item->regular_order_entry_month = $regularOrderEntry->month;
                $item->regular_order_entry_year = $regularOrderEntry->year;
            }

            unset($item->refRegularOrderEntry);
            $item->status_desc = Constant::STS_PROCESS_RG_ENTRY[$item->status];
            return $item;

        });

        return [
            'items' => $data->items(),
            'last_page' => $data->lastPage(),
        ];
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
            $store = self::insert($request);
            if($is_transaction) DB::commit();
            Cache::flush([self::cast]); //delete cache
            return $store;
        } catch (\Throwable $th) {
            if($is_transaction) DB::rollBack();
            throw $th;
        }
    }

    public static function created($request,$is_transaction = true)
    {
        if($is_transaction) DB::beginTransaction();
        try {
            $store = self::create($request);
            if($is_transaction) DB::commit();
            Cache::flush([self::cast]); //delete cache
            return $store;
        } catch (\Throwable $th) {
            if($is_transaction) DB::rollBack();
            throw $th;
        }
    }

    public static function getItem(){

        $dummyObj = '{
                    "items": [
                        {
                            "id": "1",
                            "name": "Oli Jon",
                            "item_no": "XSKJKAS203912",
                            "item_name": "yow",
                            "customer_item_no": "XCX92139JASDJAS",
                            "registration_code": "WEQWJE2312",
                            "customer_od_no": "12930912",
                            "qty": "120",
                            "box": [
                                "10 PCS",
                                "20 PCS",
                                "30 PCS"
                            ],
                            "qty2": "230",
                            "box2": [
                                "10 PCS",
                                "20 PCS",
                                "30 PCS"
                            ],
                            "qty3": "120",
                            "box3": [
                                "10 PCS",
                                "20 PCS",
                                "30 PCS"
                            ],
                            "total": "2000",
                            "_children": [
                                {
                                    "id": "",
                                    "name": "",
                                    "item_no": "",
                                    "item_name": "",
                                    "customer_item_no": "",
                                    "registration_code": "",
                                    "customer_od_no": "12930912",
                                    "qty": "120",
                                    "box": [
                                        "10 PCS",
                                        "20 PCS",
                                        "30 PCS"
                                    ],
                                    "qty2": "230",
                                    "box2": [
                                        "10 PCS",
                                        "20 PCS",
                                        "30 PCS"
                                    ],
                                    "qty3": "120",
                                    "box3": [
                                        "10 PCS",
                                        "20 PCS",
                                        "30 PCS"
                                    ],
                                    "total": "2000"
                                },
                                {
                                    "id": "",
                                    "name": "",
                                    "item_no": "",
                                    "item_name": "",
                                    "customer_item_no": "",
                                    "registration_code": "",
                                    "customer_od_no": "12930912",
                                    "qty": "120",
                                    "box": [
                                        "10 PCS",
                                        "20 PCS",
                                        "30 PCS"
                                    ],
                                    "qty2": "230",
                                    "box2": [
                                        "10 PCS",
                                        "20 PCS",
                                        "30 PCS"
                                    ],
                                    "qty3": "120",
                                    "box3": [
                                        "10 PCS",
                                        "20 PCS",
                                        "30 PCS"
                                    ],
                                    "total": "2000"
                                },
                                {
                                    "id": "",
                                    "name": "",
                                    "item_no": "",
                                    "item_name": "",
                                    "customer_item_no": "",
                                    "registration_code": "",
                                    "customer_od_no": "12930912",
                                    "qty": "120",
                                    "box": [
                                        "10 PCS",
                                        "20 PCS",
                                        "30 PCS"
                                    ],
                                    "qty2": "230",
                                    "box2": [
                                        "10 PCS",
                                        "20 PCS",
                                        "30 PCS"
                                    ],
                                    "qty3": "120",
                                    "box3": [
                                        "10 PCS",
                                        "20 PCS",
                                        "30 PCS"
                                    ],
                                    "total": "2000"
                                }
                            ]
                        }
                    ]
                }';

        return json_decode($dummyObj);

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
              "field": "cust_name",
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
              "field": "cust_item_no",
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
                              "headerHozAlign": "center",
                              "field": "customer_od_no"
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
                              "headerHozAlign": "center",
                              "field": "qty"
                            }
                          ]
                        }
                      ]
                    }
                  ]
                },
                {
                  "title": "BOX",
                  "field": "box",
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
                              "headerHozAlign": "center",
                              "field": "qty2"
                            }
                          ]
                        }
                      ]
                    }
                  ]
                },
                {
                  "title": "BOX",
                  "field": "box2",
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
                              "headerHozAlign": "center",
                              "field": "qty3"
                            }
                          ]
                        }
                      ]
                    }
                  ]
                },
                {
                  "title": "BOX",
                  "field": "box3",
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
                              "headerHozAlign": "center",
                              "field": "qty4"
                            }
                          ]
                        }
                      ]
                    }
                  ]
                },
                {
                  "title": "BOX",
                  "field": "box4",
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
                'items' => json_decode(''),

//                'items' => $data->map(function ($item){
//
//                    $etd_jkt = date('Y-m-d',strtotime($item->delivery)) ?? null;
//                    $box = self::getDetailBox($item->uuid);
//
//                    $set["id"] = $item->id;
//                    $set["id_regular_order_entry_upload"] = $item->id_regular_order_entry_upload;
//                    $set["code_consignee"] = $item->code_consignee;
//                    $set["model"] = $item->model;
//                    $set["item_no"] = $item->item_no;
//                    $set["disburse"] = $item->disburse;
//                    $set["delivery"] = $item->delivery;
//                    $set["qty"] = $item->qty;
//                    $set["status"] = $item->status;
//                    $set["order_no"] = $item->order_no;
//                    $set["cust_item_no"] = $item->cust_item_no;
//                    $set["created_at"] = $item->created_at;
//                    $set["created_by"] = $item->created_by;
//                    $set["updated_at"] = $item->updated_at;
//                    $set["updated_by"] = $item->updated_by;
//                    $set["deleted_at"] = $item->deleted_at;
//                    $set["uuid"] = $item->uuid;
//                    $set["etd_jkt"] = $etd_jkt;
//                    $set["etd_wh"] = date_create($etd_jkt)->modify('-2 days')->format('Y-m-d');
//                    $set["etd_ypmi"] = date_create($etd_jkt)->modify('-4 days')->format('Y-m-d');
//                    $set["box"] = $box;
//
//                    unset($item->refRegularOrderEntryUpload);
//                    return $set;
//                }),
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

    public static function getPivotDetail($param) {
        return VModel::where('id_regular_order_entry_upload',$param->id_regular_order_entry_upload)

            ->get();
    }

    public static function setPivotJson($param,$etd = null,$tanggal = null ) {
          $data = new stdClass;
          $data->id = $param->id ?? null;
          $data->name = $param->name ?? null;
          $data->item_no = $param->item_no ?? null;
          $data->item_name = 'yow';
          $data->customer_item_no = $param->cust_item_no ?? null;
          $data->etd = $etd;
          $data->tanggal = $tanggal;
          $data->customer_od_no = $param->order_no;
          $data->qty = '120';
          $data->box = [
            '10 PCS',
            '20 PCS',
            '30 PCS'
          ];
          $data->total = '2000';
          $data->code_consignee =$param->code_consignee ?? null;
          $data->etd_ypmi =$param->etd_ypmi ?? null;
          $data->etd_wh =$param->etd_wh ?? null;
          $data->etd_jkt =$param->etd_jkt ?? null;
          $data->order_no =$param->order_no ?? null;

        return $data;
    }

    public static function getCategoryPivot($param) {
        $result[] = ['value'=>'cust_name','label'=>'Customer Name'];
        $result[] = ['value'=>'item_no','label'=>'Item No'];
        $result[] = ['value'=>'item_name','label'=>'Item Name'];
        $result[] = ['value'=>'cust_item_no','label'=>'Customer Item No'];
        $result[] = ['value'=>'etd_ypmi','label'=>'ETD YPMI'];
        $result[] = ['value'=>'etd_wh','label'=>'ETD W/H'];
        $result[] = ['value'=>'etd_jkt','label'=>'ETD JKT'];
        return $result;
    }

}
