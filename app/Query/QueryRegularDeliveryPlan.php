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
use App\Models\MstConsignee;
use App\Models\MstContainer;
use App\Models\MstShipment;
use App\Models\MstSignature;
use App\Models\RegularDeliveryPlan;
use App\Models\RegularDeliveryPlanBox;
use App\Models\RegularDeliveryPlanProspectContainer;
use App\Models\RegularDeliveryPlanProspectContainerCreation;
use App\Models\RegularDeliveryPlanShippingInsruction;
use App\Models\RegularDeliveryPlanShippingInsructionCreation;
use App\Models\RegularDeliveryPlanShippingInsructionCreationDraft;
use App\Models\RegularProspectContainer;
use App\Models\RegularProspectContainerCreation;
use App\Models\RegularProspectContainerDetail;
use App\Models\RegularProspectContainerDetailBox;
use App\Models\RegularStokConfirmation;
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
        $data = self::where('id_regular_order_entry',$id_regular_order_entry)
        ->where(function ($query) use ($params){
            $category = $params->category ?? null;
            if($category) {
                if($category == 'cust_name'){
                    $query->with('refConsignee')->whereRelation('refConsignee', 'nick_name', $params->value)->get();
                } else {
                    $query->where($category, 'ilike', $params->value);
                }
            }

            // $filterdate = Helper::filterDate($params);
            $date_from = str_replace('-','',$params->date_from);
            $date_to = str_replace('-','',$params->date_to);
            if($params->date_from || $params->date_to) $query->whereBetween('etd_jkt',[$date_from, $date_to]);
        })
        ->where('is_inquiry', 0)
        ->paginate($params->limit ?? null);

        $data->transform(function ($item){
            $item->item_no = $item->refPart->item_serial ?? null;
            $item->item_name = $item->refPart->description ?? null;
            $item->cust_name = $item->refConsignee->nick_name ?? null;
            $regularOrderEntry = $item->refRegularOrderEntry;
            $item->regular_order_entry_period = $regularOrderEntry->period ?? null;
            $item->regular_order_entry_month = $regularOrderEntry->month ?? null;
            $item->regular_order_entry_year = $regularOrderEntry->year ?? null;
            $item->box = self::getCountBox($item->id) ?? [];

            unset(
                $item->refRegularOrderEntry,
                $item->manyDeliveryPlanBox,
                $item->refPart,
                $item->refConsignee
            );

            return $item;

        });


        return [
            'items' => $data->items(),
            'last_page' => $data->lastPage(),

        ];
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
        $data = self::where('id_regular_order_entry',$id_regular_order_entry)
            ->where(function ($query) use ($params){
                $category = $params->category ?? null;
                if($category) {
                    if($category == 'cust_name'){
                        $query->with('refConsignee')->whereRelation('refConsignee', 'nick_name', $params->value)->get();
                    } else {
                        $query->where($category, 'ilike', $params->value);
                    }
                }

                // $filterdate = Helper::filterDate($params);
                $date_from = str_replace('-','',$params->date_from);
                $date_to = str_replace('-','',$params->date_to);
                if($params->date_from || $params->date_to) $query->whereBetween('etd_jkt',[$date_from, $date_to]);
            })->paginate($params->limit ?? null);

        $data->transform(function ($item){
            $item->item_no = $item->refPart->item_serial ?? null;
            $item->item_name = $item->refPart->description ?? null;
            $item->cust_name = $item->refConsignee->nick_name ?? null;
            $regularOrderEntry = $item->refRegularOrderEntry;
            $item->regular_order_entry_period = $regularOrderEntry->period ?? null;
            $item->regular_order_entry_month = $regularOrderEntry->month ?? null;
            $item->regular_order_entry_year = $regularOrderEntry->year ?? null;
            $item->box = self::getCountBox($item->id) ?? [];

            unset(
                $item->refRegularOrderEntry,
                $item->manyDeliveryPlanBox,
                $item->refPart,
                $item->refConsignee
            );

            return $item;

        });


        return [
            'items' => $data->items(),
            'last_page' => $data->lastPage(),

        ];
    }

    public static function detailProduksiBox($params,$id)
    {
        $data = RegularDeliveryPlanBox::where('id_regular_delivery_plan',$id)->orderBy('id','asc')
            ->paginate($params->limit ?? null);

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
            'items' => $data->items(),
            'last_page' => $data->lastPage()
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
                $set['qty'] =  $item->refBox->qty." x ".$item->jml." pcs";
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

           self::where(function ($query) use ($params){
                   $query->whereIn('id',$params->id);
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

            $check = RegularDeliveryPlan::select('etd_jkt','code_consignee','datasource')->whereIn('id',$params->id)
            ->groupBy('etd_jkt','code_consignee','datasource')
            ->get()
            ->toArray();

            if(count($check) > 1) throw new \Exception("ETD JKT and Customer name not same", 400);

            $data = RegularDeliveryPlan::select(DB::raw('count(order_no) as total'),'order_no')->whereIn('id',$params->id)
            ->groupBy('order_no')
            ->orderBy('total','desc')
            ->get()
            ->toArray();

            if(count($data) == 0) throw new \Exception("Data not found", 400);

            $no_packaging = $data[0]['order_no'].substr(mt_rand(),0,5);
            $tanggal = $check[0]['etd_jkt'];
            $code_consignee = $check[0]['code_consignee'];
            $datasource = $check[0]['datasource'];

            return [
                "items" => [
                    'id' => $params->id,
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
        $item = RegularDeliveryPlanBox::where('id',$id)->orderBy('id','asc')->first();
        if(!$item) throw new \Exception("Data not found", 400);
        $data = [
            'id' => $item->id,
            'item_name' => trim($item->refRegularDeliveryPlan->refPart->description) ?? null,
            'item_no' => $item->refRegularDeliveryPlan->refPart->item_serial ?? null,
            'order_no' => $item->refRegularDeliveryPlan->order_no ?? null,
            'qty_pcs_box' => $item->qty_pcs_box ?? 0,
            'packing_date' => $item->packing_date ?? null,
            'lot_packing' => $item->lot_packing ?? null,
            'qrcode' => route('file.download').'?filename='.$item->qrcode.'&source=qr_labeling',
            'qr_key' => $item->id,
            'no_box' => $item->refBox->no_box ?? null,
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

            $id = [];
            foreach ($request['data'] as $key => $item) {
                $check = RegularDeliveryPlanBox::find($item['id']);
                if($check) {
                    $check->fill($item);
                    $check->save();
                }
                $id[] = $item['id'];
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

            $queryStok = RegularStokConfirmation::query();
            $is_stok = $queryStok->where('id_regular_delivery_plan', $check->id_regular_delivery_plan)->first();
            if ($is_stok) {
                $is_stok->update([
                    'production' => $is_stok->production + $check->qty_pcs_box,
                    'qty' => $is_stok->qty + $check->qty_pcs_box
                ]);
            } else {
                $queryStok->create([
                    "id_regular_delivery_plan" => $check->id_regular_delivery_plan,
                    "count_box" => $check->refRegularDeliveryPlan->manyDeliveryPlanBox->count() ?? 0,
                    "production" => $check->qty_pcs_box,
                    "qty" => $check->qty_pcs_box,
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
            }

            if($is_trasaction) DB::commit();

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
            , DB::raw("string_agg(DISTINCT regular_delivery_plan_prospect_container_creation.etd_ypmi::character varying, ',') as etd_ypmi"))
            ->where('regular_delivery_plan_prospect_container_creation.id_shipping_instruction', $id)
            ->groupBy('regular_delivery_plan_prospect_container_creation.code_consignee', 'regular_delivery_plan_prospect_container_creation.etd_jkt')
            ->paginate($params->limit ?? null);

        if(!$data) throw new \Exception("Data not found", 400);

        $data->transform(function ($item) use ($check) {

            $id_delivery_plan = $item->manyDeliveryPlan()->pluck('id');
            $summary_box_air = RegularDeliveryPlanBox::whereIn('id_regular_delivery_plan', $id_delivery_plan)->get();

            return [
                'cust_name' => $item->refMstConsignee->nick_name,
                'etd_jkt' => $item->etd_jkt,
                'etd_wh' => $item->etd_wh,
                'etd_ypmi' => $item->etd_ypmi,
                'summary_container' => $item->summary_container,
                'summary_box' => $check->id_mot == 2 ? count($summary_box_air) : 0,
                'code_consignee' => $item->code_consignee,
                'datasource' => $item->datasource,
                'id_shipping_instruction_creation' => $item->id_shipping_instruction_creation
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
                $data = RegularDeliveryPlanShippingInsructionCreation::find($item->id_shipping_instruction_creation);
                return $data->toArray();
            } else {

                $mst_shipment = MstShipment::where('is_active', 1)->first();

                $data = RegularDeliveryPlanProspectContainer::where('id', $item->id_prospect_container)->get();

                foreach ($data as $key => $value) {
                    $plan_box = $value->manyRegularDeliveryPlan;
                }

                $box = [];
                foreach ($plan_box as $key => $val) {
                    $box[] = RegularDeliveryPlanBox::with('refBox')->where('id_regular_delivery_plan', $val['id'])->get()->toArray();
                }

                $count_qty = 0;
                $count_net_weight = 0;
                $count_gross_weight = 0;
                $count_meas = 0;
                foreach (array_merge(...$box) as $box_item){
                    $count_qty += $box_item['qty_pcs_box'];
                    $count_net_weight += $box_item['ref_box']['unit_weight_kg'];
                    $count_gross_weight += $box_item['ref_box']['total_gross_weight'];
                    $count_meas += (($box_item['ref_box']['length'] * $box_item['ref_box']['width'] * $box_item['ref_box']['height']) / 1000000000);
                }

                $container_count = [];
                foreach (array_count_values(explode(',', $item->container_type)) as $key => $value) {
                    $container_count[] = $value;
                }

                $sumamry_box = RegularDeliveryPlanProspectContainerCreation::where('code_consignee', $item->code_consignee)
                                                                            ->where('etd_jkt', $item->etd_jkt)
                                                                            ->where('datasource', $item->datasource)
                                                                            ->get()->map(function($q){
                                                                                $items = $q->summary_box;
                                                                                return $items;
                                                                            });

                return [
                    'code_consignee' => $item->code_consignee,
                    'consignee' => $item->refMstConsignee->name.'<br>'.$item->refMstConsignee->address1.'<br>'.$item->refMstConsignee->address2.'<br>'.$item->refMstConsignee->tel.'<br>'.$item->refMstConsignee->fax,
                    'customer_name' => $item->refMstConsignee->nick_name ?? null,
                    'etd_jkt' => $item->etd_jkt,
                    'etd_wh' => $item->etd_wh,
                    'summary_container' => count($sumamry_box->toArray()),
                    'hs_code' => '',
                    'via' => $item->mot,
                    'freight_chart' => 'COLLECT',
                    'incoterm' => 'FOB',
                    'shipped_by' => $item->mot,
                    'container_value' => explode(',', $item->container_type),
                    'container_count' => [count($sumamry_box->toArray())],
                    'container_type' => $item->container_value,
                    'net_weight' => round($count_net_weight,1),
                    'gross_weight' => round($count_gross_weight,1),
                    'measurement' => round($count_meas,3),
                    'port_of_discharge' => $item->port,
                    'port_of_loading' => $item->type_delivery,
                    'type_delivery' => $item->type_delivery,
                    'count' => $item->summary_container,
                    'summary_box' => array_sum($sumamry_box->toArray()),
                    'to' => $item->refMstLsp->name ?? null,
                    'status' => $item->status ?? null,
                    'id_shipping_instruction_creation' => $item->id_shipping_instruction_creation ?? null,
                    'id_shipping_instruction' => $item->id_shipping_instruction ?? null,
                    'packing_list_no' => [$item->no_packaging],
                    'shipment' => $mst_shipment->shipment ?? null,
                    'tel' => $mst_shipment->telp ?? null,
                    'fax' => $mst_shipment->fax ?? null,
                    'fax_id' => $mst_shipment->fax_id ?? null,
                    'tel_consignee' => $item->tel_consignee,
                    'fax_consignee' => $item->fax_consignee,
                    // 'consignee_address' => $item->consignee_address,
                    'notify_part_address' => '',
                    'tel_notify_part' => '',
                    'fax_notify_part' => '',
                    'description_of_good_1' => '',
                    'description_of_good_2' => '',
                    'seal_no' => '',
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
            $params = $request->all();
            Helper::requireParams([
                'to',
                'cc',
            ]);

            $shipping_instruction_creation = RegularDeliveryPlanShippingInsructionCreation::where('id', $request->id_shipping_instruction_creation)->first();

            if ($shipping_instruction_creation == null) {
                $insert = RegularDeliveryPlanShippingInsructionCreation::create($params);
                $prospect_container_creation = RegularDeliveryPlanProspectContainerCreation::query();
                $update_creation = $prospect_container_creation->where('datasource',$request->datasource)->where('code_consignee',$request->consignee)->where('etd_jkt',$request->etd_jkt)->get();
                foreach ($update_creation as $key => $value) {
                    $value->update(['id_shipping_instruction_creation'=>$insert->id, 'status' => 2]);
                }

                if (count($prospect_container_creation->where('id_shipping_instruction', $params['id_shipping_instruction'])->get()) == count($prospect_container_creation->where('id_shipping_instruction', $params['id_shipping_instruction'])->where('status', 2)->get())) {
                    RegularDeliveryPlanShippingInsruction::where('id', $params['id_shipping_instruction'])->update(['status' => 2]);
                }

                $params['id_shipping_instruction_creation'] = $insert->id;
                RegularDeliveryPlanShippingInsructionCreationDraft::create($params);
            } else {
                $shipping_instruction_creation->update($params);
                $params['id_shipping_instruction_creation'] = $shipping_instruction_creation->id;
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

            $no_booking = 'BOOK'.Carbon::parse($etdJkt[0]->etd_jkt)->format('dmY').mt_rand(10000,99999);
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
            $data->instruction_date = Carbon::parse($data->instruction_date)->subDay(2)->format('D, M d, Y');
            $data->etd_wh = Carbon::parse($data->etd_jkt)->subDay(2)->format('D, M d, Y');
            $data->eta_destination = Carbon::parse($data->eta_destination)->subDay(2)->format('M d, Y');
            $data->etd_jkt = Carbon::parse($data->etd_jkt)->subDay(2)->format('M d, Y');
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
