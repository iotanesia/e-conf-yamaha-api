<?php

namespace App\Query;

use App\Constants\Constant;
use App\Models\MstLsp;
use App\Models\RegularDeliveryPlan AS Model;
use App\Models\RegularOrderEntry;
use Illuminate\Support\Facades\DB;
use App\ApiHelper as Helper;
use App\ApiHelper;
use App\Exports\InquiryExport;
use App\Models\MstBox;
use App\Models\MstConsignee;
use App\Models\MstContainer;
use App\Models\MstPart;
use App\Models\MstShipment;
use App\Models\MstSignature;
use App\Models\RegularDeliveryPlan;
use App\Models\RegularDeliveryPlanBox;
use App\Models\RegularDeliveryPlanProspectContainer;
use App\Models\RegularDeliveryPlanProspectContainerCreation;
use App\Models\RegularDeliveryPlanSet;
use App\Models\RegularDeliveryPlanShippingInsruction;
use App\Models\RegularDeliveryPlanShippingInsructionCreation;
use App\Models\RegularDeliveryPlanShippingInsructionCreationDraft;
use App\Models\RegularOrderEntryUpload;
use App\Models\RegularOrderEntryUploadDetailTemp;
use App\Models\RegularProspectContainer;
use App\Models\RegularProspectContainerCreation;
use App\Models\RegularProspectContainerDetail;
use App\Models\RegularProspectContainerDetailBox;
use App\Models\RegularStokConfirmation;
use App\Models\RegularStokConfirmationTemp;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class QueryRegularDeliveryPlan extends Model {

    const cast = 'regular-delivery-plan';


    public static function getDeliveryPlan($params)
    {
        $key = self::cast.json_encode($params->query());
        return Helper::storageCache($key, function () use ($params){
            $query = self::select(
                'id_regular_order_entry'
            )->where(function ($query) use ($params){



               if($params->search) $query->where('code_consignee', 'like', "'%$params->search%'")
                            ->orWhere('model', 'like', "'%$params->search%'")
                            ->orWhere('item_no', 'like', "'%$params->search%'")
                            ->orWhere('disburse', 'like', "'%$params->search%'")
                            ->orWhere('delivery', 'like', "'%$params->search%'")
                            ->orWhere('qty', 'like', "'%$params->search%'")
                            ->orWhere('status', 'like', "'%$params->search%'")
                            ->orWhere('order_no', 'like', "'%$params->search%'")
                            ->orWhere('cust_item_no', 'like', "'%$params->search%'");



            })
            ->whereHas('refRegularOrderEntry',function ($query) use ($params){
                $category = $params->category ?? null;
                if($category) {
                    $query->where($category, 'ilike', $params->kueri);
                }
            });

            if($params->withTrashed == 'true') $query->withTrashed();
            if($params->dropdown == Constant::IS_ACTIVE) {
                $params->limit = null;
                $params->page = 1;
            }

            $data = $query
            ->groupBy('id_regular_order_entry')
            ->orderBy('id_regular_order_entry','desc')
            ->paginate($params->limit ?? null);
            return [
                'items' => $data->getCollection()->transform(function ($item){
                    $month_code = $item->refRegularOrderEntry->month ?? null;
                    $sts = $item->refRegularOrderEntry->status ?? null;
                    return [
                        'month_code' => $month_code,
                        'month' => Helper::monthName($month_code),
                        'year' => $item->refRegularOrderEntry->year ?? null,
                        'uploaded' => $item->refRegularOrderEntry->uploaded ?? null,
                        'updated_at' => $item->refRegularOrderEntry->updated_at ?? null,
                        'id' => $item->id_regular_order_entry ?? null,
                        'status' =>  $item->refRegularOrderEntry->status ?? null,
                        'datasource' =>  $item->refRegularOrderEntry->datasource ?? null,
                        'status_desc' =>  Constant::STS_PROCESS_RG_ENTRY[$sts] ?? null,
                    ];
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


    public static function getDeliveryPlanDetail($params)
    {
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

        if($params->withTrashed == 'true')
            $query->withTrashed();

        if($params->id_regular_order_entry)
            $query->where("id_regular_order_entry", $params->id_regular_order_entry);
        else
            throw new \Exception("id_regular_order_entry must be sent in request", 400);

        $data = $query
        ->orderBy('id','desc')
        ->paginate($params->limit ?? null);
        return [
            'items' => $data->getCollection()->transform(function($item){
                $item->regular_delivery_plan_box = $item->manyDeliveryPlanBox;
                unset($item->manyDeliveryPlanBox);
                foreach($item->regular_delivery_plan_box as $box){
                    $box->box = $box->refBox;
                    unset($box->refBox);
                }
                return $item;
            }),
            'attributes' => [
                'total' => $data->total(),
                'current_page' => $data->currentPage(),
                'from' => $data->currentPage(),
                'per_page' => (int) $data->perPage(),
            ]
        ];
    }

    public static function detail($params,$id_regular_order_entry)
    {
        $query = self::where('id_regular_order_entry',$id_regular_order_entry)
        ->where(function ($query) use ($params){
            $category = $params->category ?? null;
            $kueri = $params->kueri ?? null;
        
            if ($category && $kueri) {
                if ($category == 'cust_name') {
                    $query->whereHas('refConsignee', function ($q) use ($kueri) {
                        $q->where('nick_name', 'like', '%' . $kueri . '%');
                    });
                } elseif ($category == 'item_name') {
                    $query->whereHas('refPart', function ($q) use ($kueri) {
                        $q->where('description', 'like', '%' . $kueri . '%');
                    });
                } else {
                    $query->where('etd_jkt', 'like', '%' . $kueri . '%')
                        ->orWhere('item_no', 'like', '%' . str_replace('-', '', $kueri) . '%')
                        ->orWhere('order_no', 'like', '%' . $kueri . '%')
                        ->orWhere('cust_item_no', 'like', '%' . $kueri . '%')
                        ->orWhere('qty', 'like', '%' . $kueri . '%')
                        ->orWhere('etd_ypmi', 'like', '%' . $kueri . '%')
                        ->orWhere('etd_wh', 'like', '%' . $kueri . '%');
                }
            }

            // $filterdate = Helper::filterDate($params);
            $date_from = str_replace('-','',$params->date_from);
            $date_to = str_replace('-','',$params->date_to);
            if($params->date_from || $params->date_to) $query->whereBetween('etd_jkt',[$date_from, $date_to]);
        })
        ->where('is_inquiry', 0);

        $data = $query
        ->orderBy('id','asc')
        ->paginate($params->limit ?? null);

        $data->transform(function ($item){
            $custname = self::getCustName($item->code_consignee);
            $itemname = self::getPart($item->item_no);

            $item_no_series = MstBox::where('item_no', $item->item_no)->first();

            if ($item->item_no == null) {
                $item_no_set = RegularDeliveryPlanSet::where('id_delivery_plan', $item->id)->get()->pluck('item_no');
                $item_no_series = MstBox::where('part_set', 'set')->whereIn('item_no', $item_no_set->toArray())->get()->pluck('item_no_series');
                $mst_part = MstPart::select('mst_part.item_no',
                                    DB::raw("string_agg(DISTINCT mst_part.description::character varying, ',') as description"))
                                    ->whereIn('mst_part.item_no', $item_no_set->toArray())
                                    ->groupBy('mst_part.item_no')->get();
                $item_name = [];
                foreach ($mst_part as $value) {
                $item_name[] = $value->description;
                }

                $mst_box = MstBox::whereIn('item_no', $item_no_set->toArray())
                                ->get()->map(function ($item){
                                    $qty = [
                                        $item->item_no => $item->qty
                                    ];
                                
                                    return array_merge($qty);
                                });

                $deliv_plan_set = RegularDeliveryPlanSet::where('id_delivery_plan', $item->id)->get();
                $qty_per_item_no = [];
                foreach ($deliv_plan_set as $key => $value) {
                    $qty_per_item_no[] = [
                        $value->item_no => $value->qty
                    ];
                }

                $qty = [];
                foreach ($mst_box as $key => $value) {
                    $arary_key = array_keys($value)[0];
                    $qty[] = array_merge(...$qty_per_item_no)[$arary_key] / $value[$arary_key];
                }
                
                $box = [
                    'qty' =>  array_sum(array_merge(...$mst_box->toArray()))." x ".(int)ceil(max($qty)),
                    'length' =>  "",
                    'width' =>  "",
                    'height' =>  "",
                ];
            }

            $set["id"] = $item->id;
            $set["code_consignee"] = $item->code_consignee;
            $set["cust_name"] = $custname;
            $set["model"] = $item->model;
            $set["item_name"] = $item->item_no == null ? $item_name : $itemname;
            $set["item_no"] = $item->item_no == null ? $item_no_series->toArray() : $item_no_series->item_no_series;
            $set["disburse"] = $item->disburse;
            $set["delivery"] = $item->delivery;
            $set["qty"] = $item->qty;
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
            $set["box"] = $item->item_no == null ? [$box] : self::getCountBox($item->id);

            unset($item->refRegularOrderEntry);
            return $set;
        });

        return [
            'items' => $data->items(),
            'last_page' => $data->lastPage(),

        ];
    }

    public static function getPart($id_part){
        $data = MstPart::where('item_no', $id_part)->first();
        return $data->description ?? null;
    }

    public static function getCustName($code_consignee){
        $data = MstConsignee::where('code', $code_consignee)->first();
        return $data->nick_name ?? null;
    }

    public static function detailBox($params,$id)
    {
        $data = RegularDeliveryPlanBox::where('id_regular_delivery_plan', $id)
        ->where(function ($query) use ($params){
            $category = $params->category ?? null;
            if($category) {
                if($category == 'cust_name'){
                    $query->whereHas('refRegularDeliveryPlan', function($q) use($params) {
                        return $q->with('refConsignee')->whereRelation('refConsignee', 'nick_name', $params->value)->get();
                    });
                } else {
                    $query->where($category, 'ilike', $params->value);
                }
            }

            // $filterdate = Helper::filterDate($params);
            $date_from = str_replace('-','',$params->date_from);
            $date_to = str_replace('-','',$params->date_to);
            if($params->date_from || $params->date_to) {
                $query->whereHas('refRegularDeliveryPlan', function($q) use($date_from, $date_to) {
                    return $q->whereBetween('etd_jkt',[$date_from, $date_to]);
                });
            };
        })
        ->paginate($params->limit ?? null);

        $data->transform(function ($item){
            $item->cust_name = $item->refRegularDeliveryPlan->refConsignee->nick_name ?? null;
            $item->item_no = $item->refRegularDeliveryPlan->refPart->item_serial ?? null;
            $item->item_name = $item->refRegularDeliveryPlan->refPart->description ?? null;
            $item->box = $item->refBox->no_box.' - '.$item->qty_pcs_box.' pcs';
            $item->status = $item->qrcode == null ? 'Waiting Create QR Code' : 'Done Create QR Code';

            unset(
                $item->refRegularDeliveryPlan,
                $item->refBox,
                $item->id_regular_delivery_plan,
                $item->id_box,
                $item->id_proc,
                $item->qty_pcs_box,
                $item->lot_packing,
                $item->packing_date,
                $item->qrcode,
                $item->created_at,
                $item->created_by,
                $item->updated_at,
                $item->updated_by,
                $item->deleted_at,
                $item->is_labeling,
                $item->id_regular_order_entry_upload_detail,
                $item->id_regular_order_entry_upload_detail_box,
            );

            return $item;

        });


        return [
            'items' => $data->items(),
            'last_page' => $data->lastPage(),

        ];
    }

    public static function detailProduksi($params,$id_regular_order_entry)
    {
        $query = self::where('id_regular_order_entry',$id_regular_order_entry)
            ->where(function ($query) use ($params){
                $category = $params->category ?? null;
                $kueri = $params->kueri ?? null;
                
                if ($category && $kueri) {
                    if ($category == 'cust_name') {
                        $query->whereHas('refConsignee', function ($q) use ($kueri) {
                            $q->where('nick_name', 'like', '%' . $kueri . '%');
                        });
                    } elseif ($category == 'item_name') {
                        $query->whereHas('refPart', function ($q) use ($kueri) {
                            $q->where('description', 'like', '%' . $kueri . '%');
                        });
                    } elseif ($category == 'item_no') {
                        $query->whereHas('manyRegularDeliveryPlanSet', function ($q) use ($kueri) {
                            $q->where('item_no', 'like', '%' . str_replace('-', '', $kueri) . '%');
                        });

                        if ($query->count() == 0) {
                            $query->where('etd_jkt', 'like', '%' . $kueri . '%')
                            ->orWhere('item_no', 'like', '%' . str_replace('-', '', $kueri) . '%')
                            ->orWhere('order_no', 'like', '%' . $kueri . '%')
                            ->orWhere('cust_item_no', 'like', '%' . $kueri . '%')
                            ->orWhere('qty', 'like', '%' . $kueri . '%')
                            ->orWhere('etd_ypmi', 'like', '%' . $kueri . '%')
                            ->orWhere('etd_wh', 'like', '%' . $kueri . '%');
                        }
                    } else {
                        $query->where('etd_jkt', 'like', '%' . $kueri . '%')
                            ->orWhere('item_no', 'like', '%' . str_replace('-', '', $kueri) . '%')
                            ->orWhere('order_no', 'like', '%' . $kueri . '%')
                            ->orWhere('cust_item_no', 'like', '%' . $kueri . '%')
                            ->orWhere('qty', 'like', '%' . $kueri . '%')
                            ->orWhere('etd_ypmi', 'like', '%' . $kueri . '%')
                            ->orWhere('etd_wh', 'like', '%' . $kueri . '%');
                    }
                }

                // $filterdate = Helper::filterDate($params);
                $date_from = str_replace('-','',$params->date_from);
                $date_to = str_replace('-','',$params->date_to);
                if($params->date_from || $params->date_to) $query->whereBetween('etd_jkt',[$date_from, $date_to]);
            });

        $data = $query
        ->orderBy('id', 'asc')
        ->paginate($params->limit ?? null);

        $data->transform(function ($item){
            $custname = self::getCustName($item->code_consignee);

            $itemname = self::getPart($item->item_no);

            $item_no_series = MstBox::where('item_no', $item->item_no)->first();

            if ($item->item_no == null) {
                $item_no_set = RegularDeliveryPlanSet::where('id_delivery_plan', $item->id)->get()->pluck('item_no');
                $item_no_series = MstBox::where('part_set', 'set')->whereIn('item_no', $item_no_set->toArray())->get()->pluck('item_no_series');
                $mst_part = MstPart::select('mst_part.item_no',
                                    DB::raw("string_agg(DISTINCT mst_part.description::character varying, ',') as description"))
                                    ->whereIn('mst_part.item_no', $item_no_set->toArray())
                                    ->groupBy('mst_part.item_no')->get();
                $item_name = [];
                foreach ($mst_part as $value) {
                $item_name[] = $value->description;
                }

                $mst_box = MstBox::whereIn('item_no', $item_no_set->toArray())
                                ->get()->map(function ($item){
                                    $qty = [
                                        $item->item_no.'+' => $item->qty
                                    ];
                                
                                    return array_merge($qty);
                                });

                $order_entry_upload = RegularOrderEntryUpload::where('id_regular_order_entry', $item->id_regular_order_entry)->first();
                $upload_temp = RegularOrderEntryUploadDetailTemp::where('id_regular_order_entry_upload', $order_entry_upload->id)
                                                                ->whereIn('item_no', $item_no_set->toArray())
                                                                ->where('etd_jkt', $item->etd_jkt)
                                                                ->get()->pluck('qty');
                $qty_per_item_no = [];
                foreach ($item_no_set as $key => $value) {
                    $qty_per_item_no[] = [
                        $value.'+' => $upload_temp->toArray()[$key]
                    ];
                }

                $qty = [];
                foreach ($mst_box as $key => $value) {
                    $arary_key = array_keys($value)[0];
                    $qty[] = array_merge(...$qty_per_item_no)[$arary_key] / $value[$arary_key];
                }
                
                $box = [
                    'qty' =>  array_sum(array_merge(...$mst_box->toArray()))." x ".(int)ceil(max($qty)),
                    'length' =>  "",
                    'width' =>  "",
                    'height' =>  "",
                ];
            }

            $set["id"] = $item->id;
            $set["code_consignee"] = $item->code_consignee;
            $set["cust_name"] = $custname;
            $set["model"] = $item->model;
            $set["item_name"] = $item->item_no == null ? $item_name : $itemname;
            $set["item_no"] = $item->item_no == null ? $item_no_series->toArray() : $item_no_series->item_no_series;
            $set["disburse"] = $item->disburse;
            $set["delivery"] = $item->delivery;
            $set["qty"] = $item->qty;
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
            $set["part_set"] = $item->part_set;
            $set["num_set"] = $item->num_set;
            $set["box"] = $item->item_no == null ? [$box] : self::getCountBox($item->id);

            unset($item->refRegularOrderEntry);
            return $set;
        });

        return [
            'items' => $data->items(),
            'last_page' => $data->lastPage(),

        ];
    }

    public static function detailProduksiBox($params,$id)
    {
        $data = RegularDeliveryPlanBox::where('id_regular_delivery_plan',$id)
                                        ->orderBy('qty_pcs_box','desc')
                                        ->orderBy('id','asc')
                                        ->get();

        if ($data[0]->refRegularDeliveryPlan->item_no == null) {
            $plan_set = RegularDeliveryPlanSet::where('id_delivery_plan',$id)->get();
            $item_no = [];
            foreach ($plan_set as $key => $val_set) {
                $item_no[] = $val_set->item_no;
            }
            $mst_box = MstBox::where('part_set', 'set')->whereIn('item_no', $item_no)->get();
            $no = '';
            $qty_box = '';
            $sum_qty = [];
            foreach ($mst_box as $key => $val) {
                $no = $val->no_box;
                $qty_box = $val->qty;
                $sum_qty[] = $val->qty;
            }

            $id_deliv_box = [];
            $qty_pcs_box = [];
            $lot_packing = [];
            $packing_date = [];
            $qty = 0;
            $group = [];
            $group_qty = [];
            $group_lot_packing = [];
            $group_packing_date = [];
            foreach ($data as $key => $value) {
                $qty += $value->qty_pcs_box;
                $group[] = $value->id;
                $group_qty[] = $value->qty_pcs_box;
                $group_lot_packing[] = $value->lot_packing;
                $group_packing_date[] = $value->packing_date;

                if ($qty >= (array_sum($sum_qty) * count($item_no))) {
                    $id_deliv_box[] = $group;
                    $qty_pcs_box[] = $group_qty;
                    $lot_packing[] = $group_lot_packing;
                    $packing_date[] = $group_packing_date;
                    $qty = 0;
                    $group = [];
                    $group_qty = [];
                    $group_lot_packing = [];
                    $group_packing_date = [];
                }
            }

            if (!empty($group)) {
                $id_deliv_box[] = $group;
            }
            if (!empty($group_qty)) {
                $qty_pcs_box[] = $group_qty;
            }
            if (!empty($group_lot_packing)) {
                $lot_packing[] = $group_lot_packing;
            }
            if (!empty($group_packing_date)) {
                $packing_date[] = $group_packing_date;
            }

            $result = [];
            for ($i=0; $i < count($id_deliv_box); $i++) { 
                $qrcode = RegularDeliveryPlanBox::whereIn('id', $id_deliv_box[$i])->get()->pluck('qrcode');
                $result[] = [
                    'id' => $id_deliv_box[$i],
                    'qty_pcs_box' => array_sum($qty_pcs_box[$i]) / count($item_no),
                    'lot_packing' => $lot_packing[$i],
                    'packing_date' => $packing_date[$i],
                    'namebox' => $no. " - ".$qty_box. " pcs",
                    'status' => in_array(null,$qrcode->toArray()) !== true ? 'Done created QR code' : 'Waiting created QR code'
                ];
            }

            return [
                    'items' => $result,
                    'last_page' => 0
                ];    
        }
        
        $data->transform(function ($item)
        {
            $no = $item->refBox->no_box ?? null;
            $qty_box = $item->refBox->qty ?? null;
            $qty = $item->qty_pcs_box;

            return [
                'id' => $item->id,
                'qty_pcs_box' => $qty,
                'lot_packing' => $item->lot_packing,
                'packing_date' => $item->packing_date,
                'namebox' => $no. " - ".$qty_box. " pcs",
                'status' => $item->qrcode !== null ? 'Done created QR code' : 'Waiting created QR code'
            ];
        });

        return [
            'items' => $data,
            'last_page' => 0
        ];
    }

    public static function exportExcel($request,$id){
        $data = self::detail($request, $id);
        return Excel::download(new InquiryExport($data), 'inquiry.xlsx');
    }

    public static function getCountBox($id){
        $data = RegularDeliveryPlanBox::select('id_box', DB::raw('count(*) as jml'))
                ->where('id_regular_delivery_plan', $id)
                ->groupBy('id_box')
                ->get();
        return
            $data->map(function ($item){
                $set['id'] = 0;
                $set['id_box'] = $item->id_box;
                $set['qty'] =  $item->refBox->qty." x ".$item->jml;
                $set['length'] =  "";
                $set['width'] =  "";
                $set['height'] =  "";
                return $set;
            });
    }

    public static function inquiryProcess($params, $is_trasaction = true)
    {
        Helper::requireParams([
            'id',
            'no_packaging',
            'etd_jkt',
            'code_consignee',
            'datasource',
        ]);

        $id = $params->id;

        if($is_trasaction) DB::beginTransaction();
        try {
           $check = RegularDeliveryPlanProspectContainer::where('no_packaging',$params->no_packaging)->first();
           if($check) throw new \Exception("no_packaging registered", 400);

           $store = RegularDeliveryPlanProspectContainer::create([
                        "code_consignee" => $params->code_consignee,
                        "etd_ypmi" => Carbon::parse($params->etd_jkt)->subDays(4)->format('Y-m-d'),
                        "etd_wh" => Carbon::parse($params->etd_jkt)->subDays(2)->format('Y-m-d'),
                        "etd_jkt" => $params->etd_jkt,
                        "no_packaging" => $params->no_packaging,
                        "datasource" => $params->datasource,
                        "created_at" => now(),
                        "id_mot" => $params->id_mot,
                        "is_prospect" => $params->id_mot == 2 ? 2 : 0
            ]);

            if ($params->id_mot == 2) {
                $store->update(['id_type_delivery' => 1]);

                $container_creation = RegularDeliveryPlanProspectContainerCreation::create([
                        "id_prospect_container" => $store->id,
                        "id_type_delivery" => 1,
                        "id_mot" => 2,
                        "code_consignee" => $params->code_consignee,
                        "etd_ypmi" => Carbon::parse($params->etd_jkt)->subDays(4)->format('Y-m-d'),
                        "etd_wh" => Carbon::parse($params->etd_jkt)->subDays(2)->format('Y-m-d'),
                        "etd_jkt" => $params->etd_jkt,
                        "datasource" => $params->datasource,
                ]);

                $shipping = RegularDeliveryPlanShippingInsruction::create([
                        "no_booking" =>  'BOOK'.Carbon::parse($params->etd_jkt)->format('dmY').mt_rand(10000,99999),
                        "booking_date" => now(),
                        "datasource" => $params->datasource,
                        "status" => 1,
                        "id_mot" => $params->id_mot
                ]);

                $id_delivery_plan = $container_creation->manyDeliveryPlan()->pluck('id');
                $summary_box = RegularDeliveryPlanBox::whereIn('id_regular_delivery_plan', $id_delivery_plan)->get();
                $container_creation->update([
                    'id_shipping_instruction' => $shipping->id,
                    'summary_box' => count($summary_box)
                ]);
            }

            $id_container_creation = $params->id_mot == 2 ? $container_creation->id : null;

           self::where(function ($query) use ($params,$id){
                   $query->whereIn('id',$id);
                   $query->where('code_consignee',$params->code_consignee);
                   $query->where('etd_jkt',str_replace('-','',$params->etd_jkt));
                   $query->where('datasource',$params->datasource);
           })
           ->chunk(1000,function ($data) use ($params,$store,$id_container_creation){
                foreach ($data as $key => $item) {
                    $item->is_inquiry = Constant::IS_ACTIVE;
                    $item->id_prospect_container = $store->id;
                    if ($params->id_mot == 2) {
                        $item->id_prospect_container_creation = $id_container_creation;
                    }
                    $item->save();
                }
           });

          if($is_trasaction) DB::commit();
        } catch (\Throwable $th) {
            if($is_trasaction) DB::rollBack();
            throw $th;
        }
    }

    public static function noPackaging($params)
    {
        try {

            Helper::requireParams([
                'id',

            ]);

            $id = $params->id;

            $check = RegularDeliveryPlan::select('etd_jkt','code_consignee','datasource')->whereIn('id',$id)
            ->groupBy('etd_jkt','code_consignee','datasource')
            ->get()
            ->toArray();

            if(count($check) > 1) throw new \Exception("ETD JKT and Customer name not same", 400);

            $data = RegularDeliveryPlan::select(DB::raw('count(order_no) as total'),'order_no')->whereIn('id',$id)
            ->groupBy('order_no')
            ->orderBy('total','desc')
            ->get()
            ->toArray();

            if(count($data) == 0) throw new \Exception("Data not found", 400);

            $check_no_packaging = RegularDeliveryPlanProspectContainer::orderByDesc('updated_at')->first();

            if ($check_no_packaging == null) {
                $iteration = 'P01';
            } elseif (substr($check_no_packaging->no_packaging,-2) == '10') {
                $iteration = 'P01';
            } else {
                $iteration = 'P0'.(int)substr($check_no_packaging->no_packaging,-2) + 1;
            }

            $no_packaging = $data[0]['order_no'].$iteration;
            $tanggal = $check[0]['etd_jkt'];
            $code_consignee = $check[0]['code_consignee'];
            $datasource = $check[0]['datasource'];

            return [
                "items" => [
                    'id' => $id,
                    'no_packaging' => $no_packaging,
                    'etd_jkt' => $tanggal,
                    'code_consignee' => $code_consignee,
                    'datasource' => $datasource
                ]
            ];

        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public static function changeEtd($params,$is_trasaction = true)
    {
        if($is_trasaction) DB::beginTransaction();
        try {

            Helper::requireParams([
                'id',
                'etd_jkt'
            ]);

            $tahun = date('Y', strtotime($params->etd_jkt));
            $bulan = date('m', strtotime($params->etd_jkt));
            $bulan_str = $bulan < 10 ? '0'.$bulan : $bulan;

            $chek = RegularOrderEntry::where('year', $tahun)->where('month', $bulan_str)->first();
            if($chek == null) throw new \Exception("Data not deliver yet", 400);

            $data = self::find($params->id);
            if(!$data) throw new \Exception("Data not found", 400);
            $order_entry = $data->refRegularOrderEntry;

            $request = $params->all();
            $request['etd_jkt'] = Carbon::parse($params->etd_jkt)->format('Ymd');
            $request['etd_ypmi'] =Carbon::parse($params->etd_jkt)->subDays(4)->format('Ymd');
            $request['etd_wh'] =Carbon::parse($params->etd_jkt)->subDays(2)->format('Ymd');
            $request['id_regular_order_entry'] = $chek->id;
            $data->fill($request);
            $data->save();

            if($is_trasaction) DB::commit();
        } catch (\Throwable $th) {
            if($is_trasaction) DB::rollBack();
            throw $th;
        }
    }

    public static function label($params,$id)
    {
        if (count(explode(',',$id)) > 1) {
            $id_plan_box = explode(',',$id);
            $item = RegularDeliveryPlanBox::whereIn('id',$id_plan_box)->orderBy('id','asc')->get();
            $deliv_plan_set = RegularDeliveryPlanSet::where('id_delivery_plan', $item[0]->id_regular_delivery_plan)->get()->pluck('item_no');
            $part_set = MstPart::whereIn('item_no', $deliv_plan_set->toArray())->get();
            $item_no_set = [];
            $item_name_set = [];
            foreach ($part_set as $key => $value) {
                $item_no_set[] = $value->item_serial;
                $item_name_set[] = $value->description;
            }
            $itemname = [];
            $item_no = [];
            $order_no = '';
            $qty_pcs_box = [];
            $packing_date = '';
            $lot_packing = '';
            $qrcode = '';
            $no_box = '';
            $cust_name = '';
            foreach ($item as $value) {
                $itemname = array_unique($item_name_set);
                $item_no = array_unique($item_no_set);
                $order_no = $value->refRegularDeliveryPlan->order_no ?? null;
                $qty_pcs_box[] = $value->qty_pcs_box ?? 0;
                $packing_date = $value->packing_date ?? null;
                $lot_packing = $value->lot_packing ?? null;
                $qrcode = $value->qrcode;
                $no_box = $value->refBox->no_box ?? null;
                $cust_name = $value->refRegularDeliveryPlan->refConsignee->nick_name ?? null;
            }
            $qty_pcs_box = array_sum($qty_pcs_box) / count($item_no);
        } else {
            $item = RegularDeliveryPlanBox::where('id',$id)->orderBy('id','asc')->first();
        }
        
        if(!$item) throw new \Exception("Data not found", 400);
        $data = [
            'id' => count(explode(',',$id)) > 1 ? explode(',',$id)[0].'-'.count(explode(',',$id)) : $item->id,
            'item_name' => count(explode(',',$id)) > 1 ? $itemname : (trim($item->refRegularDeliveryPlan->refPart->description) ?? null),
            'item_no' => count(explode(',',$id)) > 1 ? $item_no : ($item->refRegularDeliveryPlan->refPart->item_serial ?? null),
            'order_no' => count(explode(',',$id)) > 1 ? $order_no : ($item->refRegularDeliveryPlan->order_no ?? null),
            'qty_pcs_box' => count(explode(',',$id)) > 1 ? $qty_pcs_box : ($item->qty_pcs_box ?? 0),
            'packing_date' => count(explode(',',$id)) > 1 ? $packing_date : ($item->packing_date ?? null),
            'lot_packing' => count(explode(',',$id)) > 1 ? $lot_packing : ($item->lot_packing ?? null),
            'qrcode' => count(explode(',',$id)) > 1 ? (route('file.download').'?filename='.$qrcode.'&source=qr_labeling') : (route('file.download').'?filename='.$item->qrcode.'&source=qr_labeling'),
            'qr_key' => count(explode(',',$id)) > 1 ? explode(',',$id)[0].'-'.count(explode(',',$id)) : $item->id,
            'no_box' => count(explode(',',$id)) > 1 ? $no_box : ($item->refBox->no_box ?? null),
            'cust_name' => count(explode(',',$id)) > 1 ? $cust_name : ($item->refRegularDeliveryPlan->refConsignee->nick_name ?? null),
        ];

        return [
            'items' => $data,
            'last_page' => 0
        ];


    }

    public static function storeLabel($params,$is_trasaction = true)
    {

        if($is_trasaction) DB::beginTransaction();
        try {

            Helper::requireParams([
                'data',
            ]);


            $request = $params->all();

            foreach ($request['data'] as $validasi) {
                if(!$validasi['packing_date']) throw new \Exception("Please input packing date", 500);
                if(!$validasi['lot_packing']) throw new \Exception("Please input lot packing", 500);
            }

            $id = [];
            foreach ($request['data'] as $key => $item) {
                if (count(explode('-',$item['id'])) > 1) {
                    $check = RegularDeliveryPlanBox::find(explode('-',$item['id'])[0]);
                    if($check) {
                        $upd = RegularDeliveryPlanBox::where('id_regular_delivery_plan', $check->id_regular_delivery_plan)
                                                        ->where('qrcode', null)
                                                        ->orderBy('qty_pcs_box', 'desc')
                                                        ->orderBy('id','asc')
                                                        ->get();
                            
                        foreach ($upd as $key => $val) {
                            if ($val->id === $check->id) {
                                for ($i=0; $i < explode('-',$item['id'])[1]; $i++) { 
                                    $upd[$key+$i]->update([
                                        'id_proc' => $item['id_proc'],
                                        'packing_date' => $item['packing_date'],
                                        'lot_packing' => $item['lot_packing'],
                                    ]);
                                }
                            }
                        }      
                    }
                    $id = explode('-',$item['id']);
                } else {
                    $check = RegularDeliveryPlanBox::find($item['id']);
                    if($check) {
                        $check->fill($item);
                        $check->save();
                    }
                    $id[] = $item['id'];
                }
            }

            $plan_box = RegularDeliveryPlanBox::where('id_regular_delivery_plan', $check->refRegularDeliveryPlan->id)->get();
            $qty_pcs_total = 0;
            $qrcode = [];
            foreach ($plan_box as $key => $value) {
                $qty_pcs_total += $value->qty_pcs_box;
                $qrcode[] = $value->qrcode;
            }

            if (in_array(null, $qrcode) == false) {
                if ($qty_pcs_total !== $check->refRegularDeliveryPlan->qty) {
                    $remain_qty = $check->refRegularDeliveryPlan->qty - $qty_pcs_total;
                    $remain_box = (int)floor($remain_qty / $check->refBox->qty);
                    $new_plan_box = new RegularDeliveryPlanBox;
                    for ($i=1; $i < $remain_box; $i++) {
                        $new_plan_box->id_regular_delivery_plan = $check->id_regular_delivery_plan;
                        $new_plan_box->id_regular_order_entry_upload_detail = $check->id_regular_order_entry_upload_detail;
                        $new_plan_box->id_regular_order_entry_upload_detail_box = $check->id_regular_order_entry_upload_detail_box;
                        $new_plan_box->id_box = $check->id_box;
                        $new_plan_box->id_proc = $check->id_proc;
                        $new_plan_box->qty_pcs_box = $check->refBox->qty;
                        $new_plan_box->lot_packing = $check->lot_packing;
                        $new_plan_box->packing_date = $check->packing_date;
                        $new_plan_box->is_labeling = $check->is_labeling;
                        $new_plan_box->save();
                    }

                    if ($remain_qty !== 0) {
                        $new_plan_box->id_regular_delivery_plan = $check->id_regular_delivery_plan;
                        $new_plan_box->id_regular_order_entry_upload_detail = $check->id_regular_order_entry_upload_detail;
                        $new_plan_box->id_regular_order_entry_upload_detail_box = $check->id_regular_order_entry_upload_detail_box;
                        $new_plan_box->id_box = $check->id_box;
                        $new_plan_box->id_proc = $check->id_proc;
                        $new_plan_box->qty_pcs_box = $remain_qty;
                        $new_plan_box->lot_packing = $check->lot_packing;
                        $new_plan_box->packing_date = $check->packing_date;
                        $new_plan_box->is_labeling = $check->is_labeling;
                        $new_plan_box->save();
                    }
                }
            }

            if (count($id) > 1) {
                $box = RegularDeliveryPlanBox::where('id_regular_delivery_plan', $check->id_regular_delivery_plan)
                                                        ->orderBy('qty_pcs_box', 'desc')
                                                        ->orderBy('id','asc')
                                                        ->get();
                
                $qty_pcs_box = [];
                foreach ($box as $key => $val) {
                    if ($val->id === $check->id) {
                        for ($i=0; $i < $id[1]; $i++) { 
                            $qty_pcs_box[] = $box[$key+$i]->qty_pcs_box;
                        }
                    }
                } 
                $deliv_plan_set = RegularDeliveryPlanSet::where('id_delivery_plan', $check->refRegularDeliveryPlan->id)->get()->pluck('item_no');
                $qty_pcs_box = array_sum($qty_pcs_box) / count($deliv_plan_set);
            }

            $queryStok = RegularStokConfirmation::query();
            $is_stok = $queryStok->where('id_regular_delivery_plan', $check->id_regular_delivery_plan)->first();
            if ($is_stok) {
                $is_stok->update([
                    'production' => count($id) > 1 ? $is_stok->production + $qty_pcs_box : $is_stok->production + $check->qty_pcs_box,
                    'qty' => count($id) > 1 ? $is_stok->qty + $qty_pcs_box : $is_stok->qty + $check->qty_pcs_box,
                ]);
                $id_stock = $is_stok->id;
            } else {
                $createStock = $queryStok->create([
                    "id_regular_delivery_plan" => $check->id_regular_delivery_plan,
                    "count_box" => $check->refRegularDeliveryPlan->manyDeliveryPlanBox->count() ?? 0,
                    "production" => count($id) > 1 ? $qty_pcs_box : $check->qty_pcs_box,
                    "qty" => count($id) > 1 ? $qty_pcs_box : $check->qty_pcs_box,
                    "in_dc" => Constant::IS_NOL,
                    "in_wh" => Constant::IS_NOL,
                    "status_instock" => Constant::STS_STOK,
                    "status_outstock" => Constant::STS_STOK,
                    "etd_ypmi" => $check->refRegularDeliveryPlan->etd_ypmi,
                    "etd_wh" => $check->refRegularDeliveryPlan->etd_wh,
                    "etd_jkt" => $check->refRegularDeliveryPlan->etd_jkt,
                    "code_consignee" => $check->refRegularDeliveryPlan->code_consignee,
                    "is_actual" => 0
                ]);
                $id_stock = $createStock->id;
            }
            RegularStokConfirmationTemp::create([
                "id_stock_confirmation" => $id_stock, 
                "id_regular_delivery_plan" => $check->id_regular_delivery_plan,
                "count_box" => $check->refRegularDeliveryPlan->manyDeliveryPlanBox->count() ?? 0,
                "production" => count($id) > 1 ? $qty_pcs_box : $check->qty_pcs_box,
                "qty" => count($id) > 1 ? $qty_pcs_box : $check->qty_pcs_box,
                "in_dc" => count($id) > 1 ? $qty_pcs_box : $check->qty_pcs_box,
                "in_wh" => count($id) > 1 ? $qty_pcs_box : $check->qty_pcs_box,
                "status_instock" => Constant::STS_STOK,
                "status_outstock" => Constant::STS_STOK,
                "etd_ypmi" => $check->refRegularDeliveryPlan->etd_ypmi,
                "etd_wh" => $check->refRegularDeliveryPlan->etd_wh,
                "etd_jkt" => $check->refRegularDeliveryPlan->etd_jkt,
                "code_consignee" => $check->refRegularDeliveryPlan->code_consignee,
                "is_actual" => 0,
                "qr_key" => $params->data[0]['id'],
            ]);

            if($is_trasaction) DB::commit();

            if (count($id) > 1) {
                $data = RegularDeliveryPlanBox::where('id',$id[0])->orderBy('id','asc')->get();
    
                $data->transform(function ($item) use($id)
                {
                    $no = $item->refBox->no_box ?? null;
                    $qty = $item->refBox->qty ?? null;
                    $datasource = $item->refRegularDeliveryPlan->refRegularOrderEntry->datasource ?? null;
                    
                    $qr_name = (string) Str::uuid().'.png';
                    $qr_key = implode('-',$id). " | ".$item->id_box. " | ".$datasource. " | ".$item->refRegularDeliveryPlan->etd_jkt. " | ".$item->qty_pcs_box;
                    QrCode::format('png')->generate($qr_key,storage_path().'/app/qrcode/label/'.$qr_name);

                    $upd = RegularDeliveryPlanBox::where('id_regular_delivery_plan', $item->refRegularDeliveryPlan->id)
                                                        ->where('qrcode', null)
                                                        ->whereNotNull('packing_date')
                                                        ->orderBy('qty_pcs_box', 'desc')
                                                        ->orderBy('id','asc')
                                                        ->get();
                    
                    $qty_pcs_box = [];
                    foreach ($upd as $key => $val) {
                        if ($val->id === $item->id) {
                            for ($i=0; $i < $id[1]; $i++) { 
                                $upd[$key+$i]->update([
                                    'qrcode' => $qr_name
                                ]);

                                $qty_pcs_box[] = $upd[$key+$i]->qty_pcs_box;
                            }
                        }
                    }

                    $deliv_plan_set = RegularDeliveryPlanSet::where('id_delivery_plan', $item->refRegularDeliveryPlan->id)->get()->pluck('item_no');
                    $part_set = MstPart::whereIn('item_no', $deliv_plan_set->toArray())->get();
                    $item_no_set = [];
                    $item_name_set = [];
                    foreach ($part_set as $key => $value) {
                        $item_no_set[] = $value->item_serial;
                        $item_name_set[] = $value->description;
                    }

                    
                    $qty_pcs_box = array_sum($qty_pcs_box) / count($item_no_set);
    
                    return [
                        'id' => implode('-',$id),
                        'item_name' => $item_name_set,
                        'cust_name' => $item->refRegularDeliveryPlan->refConsignee->nick_name ?? null,
                        'item_no' => $item_no_set,
                        'order_no' => $item->refRegularDeliveryPlan->order_no ?? null,
                        'qty_pcs_box' => $qty_pcs_box,
                        'namebox' => $no. " ".$qty. " pcs" ,
                        'qrcode' => route('file.download').'?filename='.$qr_name.'&source=qr_labeling',
                        'lot_packing' => $item->lot_packing,
                        'packing_date' => $item->packing_date,
                        'qr_key' => implode('-',$id),
                        'no_box' => $item->refBox->no_box ?? null,
                    ];
                });
            } else {
                $data = RegularDeliveryPlanBox::whereIn('id',$id)->orderBy('id','asc')->get();
                $data->transform(function ($item)
                {
                    $no = $item->refBox->no_box ?? null;
                    $qty = $item->refBox->qty ?? null;

                    $datasource = $item->refRegularDeliveryPlan->refRegularOrderEntry->datasource ?? null;

                    $qr_name = (string) Str::uuid().'.png';
                    $qr_key = $item->id. " | ".$item->id_box. " | ".$datasource. " | ".$item->refRegularDeliveryPlan->etd_jkt. " | ".$item->qty_pcs_box;
                    QrCode::format('png')->generate($qr_key,storage_path().'/app/qrcode/label/'.$qr_name);

                    $item->qrcode = $qr_name;
                    $item->save();

                    return [
                        'id' => $item->id,
                        'item_name' => $item->refRegularDeliveryPlan->refPart->description ?? null,
                        'cust_name' => $item->refRegularDeliveryPlan->refConsignee->nick_name ?? null,
                        'item_no' => $item->refRegularDeliveryPlan->item_no ?? null,
                        'order_no' => $item->refRegularDeliveryPlan->order_no ?? null,
                        'qty_pcs_box' => $item->qty_pcs_box ?? null,
                        'namebox' => $no. " ".$qty. " pcs" ,
                        'qrcode' => route('file.download').'?filename='.$qr_name.'&source=qr_labeling',
                        'lot_packing' => $item->lot_packing,
                        'packing_date' => $item->packing_date,
                        'qr_key' => $item->id,
                        'no_box' => $item->refBox->no_box ?? null,
                    ];
                });
            }
            
            return [
                'items' => $data,
                'last_page' => 0
            ];

        } catch (\Throwable $th) {
            if($is_trasaction) DB::rollBack();
            throw $th;
        }
    }

    public static function updateProspectContainerCreation($request,$is_transaction = true)
    {
        if($is_transaction) DB::beginTransaction();
        try {
            $params = $request->all();

            Helper::requireParams([
                'id',
                'id_mot',
                'id_type_delivery'
            ]);

            $update = RegularProspectContainerCreation::find($params['id']);
            if(!$update) throw new \Exception("id tidak ditemukan", 400);

            $check = MstLsp::where('code_consignee', $params['code_consignee'])
                ->where('id_type_delivery', $params['id_type_delivery'])
                ->first();

            if(!$check) throw new \Exception("LSP not found", 400);

            $update->fill($params);
            $update->id_lsp = $check->id;
            $update->save();
            if($is_transaction) DB::commit();
            Cache::flush([self::cast]); //delete cache
        } catch (\Throwable $th) {
            if($is_transaction) DB::rollBack();
            throw $th;
        }
    }

    public static function shipping($params)
    {
        $data = RegularDeliveryPlanShippingInsruction::
        where(function($query) use ($params){
            if($params->datasource) $query->where('datasource',$params->datasource);
            if($params->no_booking) $query->where('no_booking','ilike',$params->no_booking.'%');
        })
        ->paginate($params->limit ?? null);
        if(!$data) throw new \Exception("Data not found", 400);

        $id_shipping_instruction = [];
        foreach ($data as $value) {
            $id_shipping_instruction[] = $value->id;
        }

        $crontainer_creation = RegularDeliveryPlanProspectContainerCreation::whereIn('id_shipping_instruction', $id_shipping_instruction)->get();

        $no_packaging = [];
        $cust_name = [];
        foreach ($crontainer_creation as $value) {
            $no_packaging[] = $value->refRegularDeliveryPlanPropspectContainer->no_packaging;
            $cust_name[] = $value->refMstConsignee->nick_name;
        }

        $no_packaging_unique = [];
        foreach (array_unique($no_packaging) as $value) {
            $no_packaging_unique[] = $value;
        }

        $cust_name_unique = [];
        foreach (array_unique($cust_name) as $value) {
            $cust_name_unique[] = $value;
        }

        return [
            'items' => $data->getCollection()->transform(function($item) use ($no_packaging_unique,$cust_name_unique){
                $item->no_packaging = $no_packaging_unique ?? null;
                $item->cust_name = $cust_name_unique ?? null;
                $item->mot = $item->refMot->name ?? null;

                unset(
                    $item->refMot
                );

                return $item;
            }),
            'last_page' => $data->lastPage()
        ];
    }

    public static function shippingDetail($params,$id)
    {
        $check = RegularDeliveryPlanShippingInsruction::where('id', $id)->first();
        $data = RegularDeliveryPlanProspectContainerCreation::select('regular_delivery_plan_prospect_container_creation.code_consignee', 'regular_delivery_plan_prospect_container_creation.etd_jkt'
            , DB::raw('COUNT(regular_delivery_plan_prospect_container_creation.etd_jkt) AS summary_container')
            , DB::raw("string_agg(DISTINCT regular_delivery_plan_prospect_container_creation.id::character varying, ',') as id")
            , DB::raw("string_agg(DISTINCT regular_delivery_plan_prospect_container_creation.code_consignee::character varying, ',') as code_consignee")
            , DB::raw("string_agg(DISTINCT regular_delivery_plan_prospect_container_creation.datasource::character varying, ',') as datasource")
            , DB::raw("string_agg(DISTINCT regular_delivery_plan_prospect_container_creation.id_shipping_instruction_creation::character varying, ',') as id_shipping_instruction_creation")
            , DB::raw("string_agg(DISTINCT regular_delivery_plan_prospect_container_creation.etd_wh::character varying, ',') as etd_wh")
            , DB::raw("string_agg(DISTINCT regular_delivery_plan_prospect_container_creation.etd_ypmi::character varying, ',') as etd_ypmi")
            , DB::raw("string_agg(DISTINCT a.nick_name::character varying, ',') as cust_name"))
            ->where('regular_delivery_plan_prospect_container_creation.id_shipping_instruction', $id)
            ->where(function($query) use($params) {
                if($params->kueri) $query->where('regular_delivery_plan_prospect_container_creation.etd_jkt',"like", "%$params->kueri%")
                                        ->orWhere('regular_delivery_plan_prospect_container_creation.etd_wh',"like", "%$params->kueri%")
                                        ->orWhere('a.nick_name',"like", "%$params->kueri%");
            })
            ->leftJoin('mst_consignee as a','a.code','regular_delivery_plan_prospect_container_creation.code_consignee')
            ->groupBy('regular_delivery_plan_prospect_container_creation.code_consignee', 'regular_delivery_plan_prospect_container_creation.etd_jkt')
            ->paginate($params->limit ?? null);

        if(!$data) throw new \Exception("Data not found", 400);

        $data->transform(function ($item) use ($check) {

            $id_delivery_plan = $item->manyDeliveryPlan()->pluck('id');
            $summary_box_air = RegularDeliveryPlanBox::whereIn('id_regular_delivery_plan', $id_delivery_plan)->get();

            return [
                'id' => $item->id,
                'cust_name' => $item->refMstConsignee->nick_name,
                'etd_jkt' => $item->etd_jkt,
                'etd_wh' => $item->etd_wh,
                'etd_ypmi' => $item->etd_ypmi,
                'summary_container' => $item->summary_container,
                'summary_box' => $check->id_mot == 2 ? count($summary_box_air) : 0,
                'code_consignee' => $item->code_consignee,
                'datasource' => $item->datasource,
                'id_shipping_instruction_creation' => $item->id_shipping_instruction_creation,
                'consignee_address' => $item->refMstConsignee->name.'<br>'.$item->refMstConsignee->address1.'<br>'.$item->refMstConsignee->address2.'<br>'.$item->refMstConsignee->tel.'<br>'.$item->refMstConsignee->fax,
            ];
        });

        return [
            'items' => $data->items(),
            'last_page' => $data->lastPage()
        ];
    }

    public static function shippingDetailSI($params)
    {
        $data = RegularDeliveryPlanProspectContainerCreation::select('regular_delivery_plan_prospect_container_creation.id_shipping_instruction'
        ,DB::raw("string_agg(DISTINCT regular_delivery_plan_prospect_container_creation.id_shipping_instruction_creation::character varying, ',') as id_shipping_instruction_creation")
        ,DB::raw("string_agg(DISTINCT regular_delivery_plan_prospect_container_creation.id_container::character varying, ',') as id_container")
        ,DB::raw("string_agg(DISTINCT regular_delivery_plan_prospect_container_creation.id_lsp::character varying, ',') as id_lsp")
        ,DB::raw("string_agg(DISTINCT regular_delivery_plan_prospect_container_creation.etd_wh::character varying, ',') as etd_wh")
        ,DB::raw("string_agg(DISTINCT regular_delivery_plan_prospect_container_creation.etd_jkt::character varying, ',') as etd_jkt")
        ,DB::raw("string_agg(DISTINCT regular_delivery_plan_prospect_container_creation.code_consignee::character varying, ',') as code_consignee")
        ,DB::raw("string_agg(DISTINCT regular_delivery_plan_prospect_container_creation.datasource::character varying, ',') as datasource")
        ,DB::raw("string_agg(DISTINCT c.name::character varying, ',') as mot")
        ,DB::raw("string_agg(DISTINCT d.port::character varying, ',') as port")
        ,DB::raw("string_agg(DISTINCT e.name::character varying, ',') as type_delivery")
        ,DB::raw("string_agg(DISTINCT f.container_type::character varying, ',') as container_type")
        ,DB::raw("string_agg(DISTINCT f.container_value::character varying, ',') as container_value")
        ,DB::raw("string_agg(DISTINCT g.status::character varying, ',') as status")
        ,DB::raw("string_agg(DISTINCT h.tel::character varying, ',') as tel_consignee")
        ,DB::raw("string_agg(DISTINCT h.fax::character varying, ',') as fax_consignee")
        ,DB::raw("string_agg(DISTINCT h.address1::character varying, ',') as consignee_address")
        ,DB::raw("string_agg(DISTINCT i.no_packaging::character varying, ',') as no_packaging")
        ,DB::raw("string_agg(DISTINCT j.id::character varying, ',') as id_shipping_instruction")
        ,DB::raw("string_agg(DISTINCT regular_delivery_plan_prospect_container_creation.summary_box::character varying, ',') as summary_box")
        ,DB::raw("string_agg(DISTINCT regular_delivery_plan_prospect_container_creation.id_prospect_container::character varying, ',') as id_prospect_container")
        ,DB::raw("SUM(f.net_weight) as net_weight")
        ,DB::raw("SUM(f.gross_weight) as gross_weight")
        ,DB::raw("SUM(f.measurement) as measurement"))
        ->whereIn('regular_delivery_plan_prospect_container_creation.id', explode(',',$params->id))
        ->where('regular_delivery_plan_prospect_container_creation.code_consignee', $params->code_consignee)
        ->where('regular_delivery_plan_prospect_container_creation.etd_jkt', $params->etd_jkt)
        ->where('regular_delivery_plan_prospect_container_creation.datasource', $params->datasource)
        ->leftJoin('mst_mot as c','regular_delivery_plan_prospect_container_creation.id_mot','c.id')
        ->leftJoin('mst_port_of_discharge as d','regular_delivery_plan_prospect_container_creation.code_consignee','d.code_consignee')
        ->leftJoin('mst_port_of_loading as e','regular_delivery_plan_prospect_container_creation.id_type_delivery','e.id_type_delivery')
        ->leftJoin('mst_container as f','regular_delivery_plan_prospect_container_creation.id_container','f.id')
        ->leftJoin('regular_delivery_plan_shipping_instruction_creation as g','regular_delivery_plan_prospect_container_creation.id_shipping_instruction_creation','g.id')
        ->leftJoin('mst_consignee as h','regular_delivery_plan_prospect_container_creation.code_consignee','h.code')
        ->leftJoin('regular_delivery_plan_prospect_container as i','regular_delivery_plan_prospect_container_creation.id_prospect_container','i.id')
        ->leftJoin('regular_delivery_plan_shipping_instruction as j','regular_delivery_plan_prospect_container_creation.id_shipping_instruction','j.id')
        ->groupBy('regular_delivery_plan_prospect_container_creation.id_shipping_instruction')
        ->paginate(1);
        if(!$data) throw new \Exception("Data not found", 400);

        $data->transform(function ($item) {
            if ($item->id_shipping_instruction_creation) {
                $SI = RegularDeliveryPlanShippingInsructionCreation::where('id',$item->id_shipping_instruction_creation)->paginate(1);
                $summary_box = RegularDeliveryPlanProspectContainerCreation::where('code_consignee', $item->code_consignee)
                                                                            ->where('etd_jkt', $item->etd_jkt)
                                                                            ->where('datasource', $item->datasource)
                                                                            ->get()->map(function($q){
                                                                                $items = $q->summary_box;
                                                                                return $items;
                                                                            });

                $SI->transform(function ($si_item) use ($summary_box) {
                    $si_item->container_value = explode(',', $si_item->container_value);
                    $si_item->container_count = explode(',', $si_item->container_count);
                    $si_item->container_type = explode(',', $si_item->container_type);
                    $si_item->summary_box = array_sum($summary_box->toArray());

                    return $si_item;
                });

                return $SI->items()[0];
            } else {

                $mst_shipment = MstShipment::where('is_active', 1)->first();

                $data = RegularDeliveryPlanProspectContainer::where('id', $item->id_prospect_container)->get();
                $id_delivery_plan = [];
                foreach ($data[0]->manyRegularDeliveryPlan as $id_delivery) {
                    $id_delivery_plan[] = $id_delivery->id;
                }
                $deliv_plan = RegularDeliveryPlan::with('manyDeliveryPlanBox')->orderBy('item_no','asc')->whereIn('id',$id_delivery_plan)->get();

                $res_box_single = [];
                $res_box_set = [];
                foreach ($deliv_plan as $key => $deliv_value) {
                    if ($deliv_value->item_no !== null) {
                        $res = $deliv_value->manyDeliveryPlanBox->map(function($item) {
                            $res['qrcode'] = $item->qrcode;
                            $res['item_no'] = [$item->refRegularDeliveryPlan->item_no];
                            $res['qty_pcs_box'] = [$item->qty_pcs_box];
                            $res['item_no_series'] = [$item->refBox->item_no_series];
                            $res['unit_weight_kg'] = [$item->refBox->unit_weight_kg];
                            $res['total_gross_weight'] = $item->refBox->total_gross_weight;
                            $res['length'] = $item->refBox->length;
                            $res['width'] = $item->refBox->width;
                            $res['height'] = $item->refBox->height;
                            return $res;
                        });
                        
                        $box_single = [];
                        foreach ($res as $key => $item_res) {
                            $box_single[] = $item_res;
                        }
                        
                        $res_box_single[] = $box_single;
                    }

                    if ($deliv_value->item_no == null) {
                        $plan_set = RegularDeliveryPlanSet::where('id_delivery_plan',$deliv_value->id)->get();
                        $deliv_plan_box = RegularDeliveryPlanBox::where('id_regular_delivery_plan',$deliv_value->id)
                                                            ->orderBy('qty_pcs_box','desc')
                                                            ->orderBy('id','asc')
                                                            ->get();
                        $item_no = [];
                        $set_qty = [];
                        $item_no_series = [];
                        foreach ($plan_set as $key => $value) {
                            $item_no[] = $value->item_no;
                            $set_qty[] = $value->qty;
                            $item_no_series[] = $value->refBox->item_no_series;
                        }

                        $mst_box = MstBox::where('part_set', 'set')->whereIn('item_no', $item_no)->get();
                        $qty_box = [];
                        $sum_qty = [];
                        $unit_weight_kg = [];
                        $total_gross_weight = '';
                        $length = '';
                        $width = '';
                        $height = '';
                        foreach ($mst_box as $key => $value) {
                            $qty_box[] = $value->qty;
                            $sum_qty[] = $value->qty;
                            $unit_weight_kg[] = $value->unit_weight_kg;
                            $total_gross_weight = $value->total_gross_weight;
                            $length = $value->length;
                            $width = $value->width;
                            $height = $value->height;
                        }
            
                        $id_deliv_box = [];
                        $qty_pcs_box = [];
                        $qty = 0;
                        $group = [];
                        $group_qty = [];
                        foreach ($deliv_plan_box as $key => $value) {
                            $qty += $value->qty_pcs_box;
                            $group[] = $value->id;
                            $group_qty[] = $value->qty_pcs_box;
            
                            if ($qty >= (array_sum($sum_qty) * count($item_no))) {
                                $id_deliv_box[] = $group;
                                $qty_pcs_box[] = $group_qty;
                                $qty = 0;
                                $group = [];
                                $group_qty = [];
                            }
                        }
            
                        if (!empty($group)) {
                            $id_deliv_box[] = $group;
                        }
                        if (!empty($group_qty)) {
                            $qty_pcs_box[] = $group_qty;
                        }

                        $res_qty = [];
                        foreach ($set_qty as $key => $value) {
                            if (count($qty_pcs_box) >= count($set_qty)) {
                                if ($value == max($set_qty)) {
                                    $val = array_sum($qty_pcs_box[$key]) / count($item_no);
                                } else {
                                    $val = null;
                                }
                            } else {
                                $val = null;
                            }
                            
                            $res_qty[] = $val;
                        }
            
                        $box_set = [];
                        for ($i=0; $i < count($id_deliv_box); $i++) { 
                            $check = array_sum($qty_pcs_box[0]) / count($item_no);
                            $box_set[] = [
                                'item_no' => $item_no,
                                'qty_pcs_box' => $check == array_sum($qty_pcs_box[$i]) / count($item_no) ? $qty_box : $res_qty,
                                'item_no_series' => $item_no_series,
                                'unit_weight_kg' => $unit_weight_kg,
                                'total_gross_weight' => $total_gross_weight,
                                'length' => $length,
                                'width' => $width,
                                'height' => $height,
                            ];
                        }
                        
                        $res_box_set[] = $box_set;
                    }

                }
                
                $box = array_merge((array_merge(...$res_box_set) ?? []), (array_merge(...$res_box_single) ?? []));
                
                $count_qty = 0;
                $count_net_weight = 0;
                $count_gross_weight = 0;
                $count_meas = 0;
                foreach ($box as $box_item){
                    $count_qty += array_sum($box_item['qty_pcs_box']);
                    $count_net_weight += array_sum($box_item['unit_weight_kg']);
                    $count_gross_weight += $box_item['total_gross_weight'];
                    $count_meas += (($box_item['length'] * $box_item['width'] * $box_item['height']) / 1000000000);
                }

                $container_count = [];
                foreach (array_count_values(explode(',', $item->container_type)) as $key => $value) {
                    $container_count[] = $value;
                }

                $summary_box = RegularDeliveryPlanProspectContainerCreation::where('code_consignee', $item->code_consignee)
                                                                            ->where('etd_jkt', $item->etd_jkt)
                                                                            ->where('datasource', $item->datasource)
                                                                            ->get()->map(function($q){
                                                                                $items = $q->summary_box;
                                                                                return $items;
                                                                            });

                return [
                    'code_consignee' => $item->code_consignee,
                    'consignee_address' => $item->refMstConsignee->name.'<br>'.$item->refMstConsignee->address1.'<br>'.$item->refMstConsignee->address2.'<br>'.$item->refMstConsignee->tel.'<br>'.$item->refMstConsignee->fax,
                    'customer_name' => $item->refMstConsignee->nick_name ?? null,
                    'etd_jkt' => $item->etd_jkt,
                    'etd_wh' => $item->etd_wh,
                    'summary_container' => count($summary_box->toArray()),
                    'hs_code' => '',
                    'via' => $item->mot,
                    'freight_charge' => 'COLLECT',
                    'incoterm' => 'FOB',
                    'shipped_by' => $item->mot,
                    'container_value' => explode(',', $item->container_type),
                    'container_count' => [count($summary_box->toArray())],
                    'container_type' => $item->container_value,
                    'net_weight' => round($count_net_weight,1),
                    'gross_weight' => round($count_gross_weight,1),
                    'measurement' => round($count_meas,3),
                    'pod' => $item->port,
                    'pol' => $item->type_delivery,
                    'type_delivery' => $item->type_delivery,
                    'count' => $item->summary_container,
                    'summary_box' => array_sum($summary_box->toArray()),
                    'to' => $item->refMstLsp->name ?? null,
                    'status' => $item->status ?? null,
                    'id_shipping_instruction_creation' => $item->id_shipping_instruction_creation ?? null,
                    'id_shipping_instruction' => $item->id_shipping_instruction ?? null,
                    'invoice_no' => $item->no_packaging,
                    'shipper' => $mst_shipment->shipment ?? null,
                    'tel' => $mst_shipment->telp ?? null,
                    'fax' => $mst_shipment->fax ?? null,
                    'fax_id' => $mst_shipment->fax_id ?? null,
                    'tel_consignee' => $item->tel_consignee,
                    'fax_consignee' => $item->fax_consignee,
                    // 'consignee_address' => $item->consignee_address,
                    'notify_part_address' => '',
                    'tel_notify_part' => '',
                    'fax_notify_part' => '',
                    'description_of_goods_1' => '',
                    'description_of_goods_2' => $count_qty,
                    'seal_no' => '',
                    'connecting_vessel' => '',
                    'carton_box_qty' => count($box)
                ];
            }
        });

        return [
            'items' => $data->items()[0],
            'last_page' => $data->lastPage()
        ];
    }

    public static function shippingStore($request,$is_transaction = true)
    {
        if($is_transaction) DB::beginTransaction();
        try {
            $consignee = MstConsignee::where('code',$request->code_consignee)->first();
            // $request->merge(['consignee'=>json_encode($consignee),'status'=>Constant::DRAFT]);
            $request1 = $request->except(['container_count','container_value','container_type']);
            $request2 = [
                            'container_count' => implode(',',$request->container_count),
                            'container_value' => implode(',',$request->container_value),
                            'container_type' => implode(',',$request->container_type),
                        ];
            $params = array_merge($request1,$request2);
            Helper::requireParams([
                'to',
                'cc',
            ]);

            $shipping_instruction_creation = RegularDeliveryPlanShippingInsructionCreation::where('id', $request->id_shipping_instruction_creation)->first();

            if ($shipping_instruction_creation == null) {
                $insert = RegularDeliveryPlanShippingInsructionCreation::create($params);
                $prospect_container_creation = RegularDeliveryPlanProspectContainerCreation::query();
                $update_creation = $prospect_container_creation->where('datasource',$request->datasource)->where('code_consignee',$request->code_consignee)->where('etd_jkt',$request->etd_jkt)->get();
                foreach ($update_creation as $key => $value) {
                    $value->update(['id_shipping_instruction_creation'=>$insert->id, 'status' => 2]);
                }

                if (count($prospect_container_creation->where('id_shipping_instruction', $params['id_shipping_instruction'])->get()) == count($prospect_container_creation->where('id_shipping_instruction', $params['id_shipping_instruction'])->where('status', 2)->get())) {
                    RegularDeliveryPlanShippingInsruction::where('id', $params['id_shipping_instruction'])->update(['status' => 2]);
                }

                $params['id_regular_delivery_plan_shipping_instruction_creation'] = $insert->id;
                RegularDeliveryPlanShippingInsructionCreationDraft::create($params);
            } else {
                $shipping_instruction_creation->update($params);
                $params['id_regular_delivery_plan_shipping_instruction_creation'] = $shipping_instruction_creation->id;
                RegularDeliveryPlanShippingInsructionCreationDraft::create($params);
            }
            if($is_transaction) DB::commit();
            Cache::flush([self::cast]); //delete cache
        } catch (\Throwable $th) {
            if($is_transaction) DB::rollBack();
            throw $th;
        }
    }

    public static function shippingUpdate($request,$is_transaction = true)
    {
        if($is_transaction) DB::beginTransaction();
        try {
            $update = RegularDeliveryPlanShippingInsructionCreation::find($request->id);
            if(!$update) throw new \Exception("Data not found", 400);
            $update->status = Constant::FINISH;
            $update->save();
            $prospectContainer = RegularDeliveryPlanProspectContainerCreation::where('id_shipping_instruction_creation',$request->id)->get()->pluck('id');
            RegularDeliveryPlan::whereIn('id_prospect_container_creation',$prospectContainer)->update(['status_bml'=>1]);
            $regStok = RegularDeliveryPlan::whereIn('id_prospect_container_creation',$prospectContainer)->get();
            $update_status_bml = RegularDeliveryPlanProspectContainerCreation::where('id_shipping_instruction_creation',$request->id)->first();
            $update_status_bml->status_bml = 1;
            $update_status_bml->save();
            // $regStok->map(function($item){
            //     RegularStokConfirmation::create(self::paramStok($item));
            // });
            if($is_transaction) DB::commit();
            return ['items'=>$update];
            Cache::flush([self::cast]); //delete cache
        } catch (\Throwable $th) {
            if($is_transaction) DB::rollBack();
            throw $th;
        }
    }

    public static function paramStok($params) {
        $sum = 0;
        foreach ($params->manyDeliveryPlanBox as $value) {
            $sum += $value->refBox->qty;
        }
        return [
            "id_regular_delivery_plan" => $params->id,
            "count_box" => $params->manyDeliveryPlanBox->count() ?? 0,
            "production" => $params->qty,
            "qty" => $params->qty,
            "in_dc" => Constant::IS_NOL,
            "in_wh" => Constant::IS_NOL,
            "status_instock" => Constant::STS_STOK,
            "status_outstock" => Constant::STS_STOK,
            "etd_ypmi" => $params->etd_ypmi,
            "etd_wh" => $params->etd_wh,
            "etd_jkt" => $params->etd_jkt,
            "code_consignee" => $params->code_consignee,
            "is_actual" => 0
        ];
    }

    public static function genNoBook($request,$is_transaction = true) {
        Helper::requireParams(['id']);
        try {
            $check = RegularDeliveryPlanProspectContainerCreation::whereIn('id',$request->id)->whereNotNull('id_shipping_instruction')->count();
            if($check > 0) throw new \Exception("Prospect has been booked", 400);

            $etdJkt = RegularDeliveryPlanProspectContainerCreation::select('etd_jkt','datasource',DB::raw("string_agg(DISTINCT regular_delivery_plan_prospect_container_creation.id_mot::character varying, ',') as id_mot"))->whereIn('id',$request->id)->groupBy('etd_jkt','datasource')->get();
            if(!count($etdJkt)) throw new \Exception("Data not found", 400);

            $check_no_booking = RegularDeliveryPlanShippingInsruction::orderByDesc('updated_at')->first();

            if ($check_no_booking == null) {
                $iteration = '000001';
            } elseif (substr($check_no_booking->no_booking,-6) == '999999') {
                $iteration = '000001';
            } elseif (substr($check_no_booking->no_booking,8,-6) !== Carbon::now()->format('Y')) {
                $iteration = '000001';
            } else {
                $last_iteration = '000000'.(int)substr($check_no_booking->no_booking,-6) + 1;
                $iteration = substr($last_iteration,-6);
            }

            // $no_booking = 'BOOK'.Carbon::parse($etdJkt[0]->etd_jkt)->format('dmY').$iteration;
            $no_booking = 'BOOK'.Carbon::now()->format('dmY').$iteration;
            $datasource = $etdJkt[0]->datasource;
            $booking_date = Carbon::now()->format('dmY');

            return [
                'items' => [
                    'id' => $request->id,
                    'no_booking' => $no_booking,
                    'booking_date' => $booking_date,
                    'datasource' => $datasource,
                    'id_mot' => $etdJkt[0]->id_mot
                ]
            ];

        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public static function saveBook($request,$is_transaction = true) {
        Helper::requireParams(['id']);
        if($is_transaction) DB::beginTransaction();
        try {

           $data = RegularDeliveryPlanShippingInsruction::create(
                [
                    'no_booking' => $request->no_booking,
                    'booking_date' => substr($request->booking_date, -4).'-'.substr($request->booking_date, -6, 2).'-'.substr($request->booking_date, -8, 2),
                    'datasource' => $request->datasource,
                    'status' =>  Constant::STS_BOOK_FINISH,
                    'id_mot' => $request->id_mot
                ]
            );

           RegularDeliveryPlanProspectContainerCreation::whereIn('id_prospect_container',$request->id)->get()
            ->map(function ($item) use ($request,$data){
                $item->id_shipping_instruction = $data->id;
                $item->is_booking = Constant::IS_ACTIVE;
                $item->status = Constant::FINISH;
                $item->save();
            });

            if($is_transaction) DB::commit();
            return ['items'=> $data];
        } catch (\Throwable $th) {
            if($is_transaction) DB::rollBack();
            throw $th;
        }
    }

    public static function detailById($params)
    {
        $data = RegularProspectContainerCreation::whereIn('id_prospect_container',$params->id)->paginate($params->limit ?? null);


        if(!$data) throw new \Exception("Data not found", 400);

        return [
            'items' => [$data->first()],
            'last_page' => $data->lastPage()
        ];
    }

    public static function downloadDoc($params,$id)
    {
        try {
            $data = RegularDeliveryPlanShippingInsructionCreation::find($id);
            $data->instruction_date = Carbon::parse($data->instruction_date)->subDay(2)->format('l, F d, Y');
            $data->etd_wh = Carbon::parse($data->etd_jkt)->subDay(2)->format('l, F d, Y');
            $data->eta_destination = Carbon::parse($data->eta_destination)->format('M d, Y');
            $data->etd_jkt = Carbon::parse($data->etd_jkt)->format('M d, Y');
            $filename = 'shipping-instruction-'.$id.'.pdf';
            $pathToFile = storage_path().'/app/shipping_instruction/'.$filename;
            Pdf::loadView('pdf.shipping_instruction',[
              'data' => $data
            ])
            ->save($pathToFile)
            ->setPaper('A4','potrait')
            ->download($filename);
          } catch (\Throwable $th) {
              return Helper::setErrorResponse($th);
          }
    }

    public static function downloadDocDraft($params,$id)
    {
        try {
            $data = RegularDeliveryPlanShippingInsructionCreation::find($id);
            $data->instruction_date = Carbon::parse($data->instruction_date)->subDay(2)->format('D, M d, Y');
            $data->etd_wh = Carbon::parse($data->etd_jkt)->subDay(2)->format('D, M d, Y');
            $data->eta_destination = Carbon::parse($data->eta_destination)->subDay(2)->format('M d, Y');
            $data->etd_jkt = Carbon::parse($data->etd_jkt)->subDay(2)->format('M d, Y');
            $filename = 'shipping-instruction-draft'.$id.'.pdf';

            return [
                'items' => [
                    'url' => url('api/v1/regular/delivery-plan/shipping-instruction/download-dok-draft/'.$id.'/'.$filename),
                ],
            ];
          } catch (\Throwable $th) {
              return Helper::setErrorResponse($th);
          }
    }

    public static function downloadDocDraftSave($params,$id,$filename)
    {
        try {
            $data = RegularDeliveryPlanShippingInsructionCreation::find($id);
            $data->instruction_date = Carbon::parse($data->instruction_date)->subDay(2)->format('D, M d, Y');
            $data->etd_wh = Carbon::parse($data->etd_jkt)->subDay(2)->format('D, M d, Y');
            $data->eta_destination = Carbon::parse($data->eta_destination)->subDay(2)->format('M d, Y');
            $data->etd_jkt = Carbon::parse($data->etd_jkt)->subDay(2)->format('M d, Y');
            $pathToFile = storage_path().'/app/shipping_instruction/'.$filename;
            Pdf::loadView('pdf.shipping_instruction',[
              'data' => $data
            ])
            ->save($pathToFile)
            ->setPaper('A4','potrait')
            ->download($filename);
          } catch (\Throwable $th) {
              return Helper::setErrorResponse($th);
          }
    }

    public static function getShippingDraftDok($params,$id)
    {
        $data = RegularDeliveryPlanShippingInsructionCreationDraft::select('id','no_draft','created_at','consignee')
            ->where('id_regular_delivery_plan_shipping_instruction_creation',$id)
            ->paginate($params->limit ?? null);

        if(!$data) throw new \Exception("Data not found", 400);

        return [
            'items' => $data->getCollection()->transform(function($item){

                $item->date = $item->created_at;
                $item->title = 'SI Draft '.json_decode($item->consignee)->nick_name;

                unset(
                    $item->created_at,
                    $item->consignee,
                );

                return $item;
            }),
            'last_page' => $data->lastPage()
        ];
    }

    public static function shippingDraftDok($params)
    {
        $container_creation = RegularDeliveryPlanProspectContainerCreation::where('code_consignee', $params->code_consignee)->where('etd_jkt', $params->etd_jkt)->where('datasource', $params->datasource)->first();

        if($container_creation->id_shipping_instruction_creation == null) return ['items' => []];

        $data = RegularDeliveryPlanShippingInsructionCreationDraft::select('id','consignee','created_at')
            ->where('id_regular_delivery_plan_shipping_instruction_creation', $container_creation->id_regular_delivery_plan_shipping_instruction_creation)
            ->paginate($params->limit ?? null);

        if(!$data) throw new \Exception("Data not found", 400);
        return [
            'items' => $data->getCollection()->transform(function($item){

                $item->title = 'SI Draft '.$item->consignee;
                $item->date = $item->created_at;

                unset(
                    $item->consignee,
                    $item->created_at,
                );

                return $item;
            }),
            'last_page' => $data->lastPage()
        ];
    }

    public static function shippingDraftDokDetail($params,$id)
    {
        $data = RegularDeliveryPlanShippingInsructionCreationDraft::where('id',$id)->first();
        if(!$data) throw new \Exception("Data not found", 400);

        $data->container_type = $data->container;
        $data->container_value = $data->container_count;
        return [
            'items' => $data
        ];
    }

    public static function bml($params)
    {
        $data = Model::where('status_bml',1)->paginate($params->limit ?? null);
        if(!$data) throw new \Exception("Data not found", 400);

        return [
            'items' => $data->getCollection()->transform(function($item){
                $item->item_no = $item->refPart->item_serial;
                unset($item->refPart);

                return $item;
            }),
            'attributes' => [
                'total' => $data->total(),
                'current_page' => $data->currentPage(),
                'from' => $data->currentPage(),
                'per_page' => (int) $data->perPage(),
            ]
        ];
    }

    public static function bmlDetail($params)
    {
        $data = Model::where('id', $params->id)->where('status_bml',1)->paginate($params->limit ?? null);
        if(!$data) throw new \Exception("Data not found", 400);
        return [
            'items' => $data->getCollection()->transform(function($item){
                $item->regular_delivery_plan_box = $item->manyDeliveryPlanBox;
                unset($item->manyDeliveryPlanBox);
                foreach($item->regular_delivery_plan_box as $box){
                    $box->box = $box->refBox;
                    unset($box->refBox);
                }
                return $item;
            }),
            'attributes' => [
                'total' => $data->total(),
                'current_page' => $data->currentPage(),
                'from' => $data->currentPage(),
                'per_page' => (int) $data->perPage(),
            ]
        ];
    }
}
