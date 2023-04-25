<?php

namespace App\Query;

use App\Constants\Constant;
use App\Models\MstLsp;
use App\Models\RegularDeliveryPlan AS Model;
use Illuminate\Support\Facades\DB;
use App\ApiHelper as Helper;
use App\ApiHelper;
use App\Models\MstConsignee;
use App\Models\MstShipment;
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
        });
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
        if(count($data) == 0) throw new \Exception("Data tidak ditemukan.", 400);

        $data->transform(function ($item){
            $item->item_no = $item->refPart->item_serial;
            $item->item_name = $item->refPart->description ?? null;
            $item->cust_name = $item->refConsignee->nick_name ?? null;
            $regularOrderEntry = $item->refRegularOrderEntry;
            $item->regular_order_entry_period = $regularOrderEntry->period ?? null;
            $item->regular_order_entry_month = $regularOrderEntry->month ?? null;
            $item->regular_order_entry_year = $regularOrderEntry->year ?? null;
            $item->box = $item->manyDeliveryPlanBox->map(function ($item)
            {
                return [
                    'id' => $item->id,
                    'id_box' => $item->id_box,
                    'qty' => $item->refBox->qty ?? null,
                    'width' => $item->refBox->width ?? null,
                    'height' => $item->refBox->height ?? null,
                ];
            });

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

    public static function inquiryProcess($params, $is_trasaction = true)
    {
        Helper::requireParams([
            'id',
            'no_packaging',
            'etd_jkt',
            'code_consignee',
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
            ]);


           self::where(function ($query) use ($params){
                   $query->whereIn('id',$params->id);
                   $query->where('code_consignee',$params->code_consignee);
                   $query->where('etd_jkt',$params->etd_jkt);
           })
           ->chunk(1000,function ($data) use ($params,$store){
                foreach ($data as $key => $item) {
                    $item->is_inquiry = Constant::IS_ACTIVE;
                    $item->id_prospect_container = $store->id;
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

            $data = self::find($params->id);
            if(!$data) throw new \Exception("Data not found", 400);
            $request = $params->all();
            $request['etd_jkt'] = Carbon::parse($params->etd_jkt)->format('Ymd');
            $request['etd_ypmi'] =Carbon::parse($params->etd_jkt)->subDays(4)->format('Ymd');
            $request['etd_wh'] =Carbon::parse($params->etd_jkt)->subDays(2)->format('Ymd');
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

        $data = RegularDeliveryPlanBox::where('id_regular_delivery_plan',$id)->paginate($params->limit ?? null);
        if(!$data) throw new \Exception("Data not found", 400);


        $data->transform(function ($item)
        {
            $no = $item->refBox->no_box ?? null;
            $qty = $item->refBox->qty ?? null;
            return [
                'id' => $item->id,
                'item_name' => $item->refRegularDeliveryPlan->refPart->description ?? null,
                'cust_name' => $item->refRegularDeliveryPlan->refConsignee->nick_name ?? null,
                'item_no' => $item->refRegularDeliveryPlan->item_no ?? null,
                'order_no' => $item->refRegularDeliveryPlan->order_no ?? null,
                'qty_pcs_box' => $qty,
                'namebox' => $no. " ".$qty. " pcs" ,
            ];
        });


        return [
            'items' => $data->items(),
            'last_page' => $data->lastPage()
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


            if($is_trasaction) DB::commit();

            $data = RegularDeliveryPlanBox::whereIn('id',$id)->paginate($params->limit ?? null);
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
                    'qty_pcs_box' => $item->qty_pcs_box,
                    'namebox' => $no. " ".$qty. " pcs" ,
                    'qrcode' => route('file.download').'?filename='.$qr_name.'&source=qr_labeling',
                    'lot_packing' => $item->lot_packing,
                    'packing_date' => $item->packing_date,
                    'qr_key' => $item->id,
                    'no_box' => $item->refBox->no_box ?? null,
                ];
            });



            return [
                'items' => $data->items(),
                'last_page' => $data->lastPage()
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
                'id_type_delivery',
                'code_consignee'
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
        foreach ($crontainer_creation as $value) {
            $no_packaging[] = $value->refRegularDeliveryPlanPropspectContainer->no_packaging;
        }

        return [
            'items' => $data->getCollection()->transform(function($item) use ($no_packaging){
                $item->no_packaging = $no_packaging ?? null;

                return $item;
            }),
            'last_page' => $data->lastPage()
        ];
    }

    public static function shippingDetail($params,$id)
    {
        $data = RegularProspectContainerCreation::select('regular_delivery_plan_prospect_container_creation.code_consignee','regular_delivery_plan_prospect_container_creation.status_bml','regular_delivery_plan_prospect_container_creation.etd_jkt','regular_delivery_plan_prospect_container_creation.etd_wh','regular_delivery_plan_prospect_container_creation.id_lsp','g.status','id_shipping_instruction_creation','f.measurement','f.net_weight','f.gross_weight','f.container_value','f.container_type','e.name','c.name','b.hs_code','no_packaging','d.port'
        ,DB::raw('COUNT(regular_delivery_plan_prospect_container_creation.etd_jkt) AS summary_container')
        ,DB::raw("string_agg(DISTINCT no_packaging::character varying, ',') as no_packaging")
        ,DB::raw("string_agg(DISTINCT b.hs_code::character varying, ',') as hs_code")
        ,DB::raw("string_agg(DISTINCT c.name::character varying, ',') as mot")
        ,DB::raw("string_agg(DISTINCT d.port::character varying, ',') as port")
        ,DB::raw("string_agg(DISTINCT e.name::character varying, ',') as type_delivery")
        ,DB::raw("string_agg(DISTINCT f.container_type::character varying, ',') as container_type")
        ,DB::raw("string_agg(DISTINCT f.container_value::character varying, ',') as container_value")
        ,DB::raw("SUM(f.net_weight) as net_weight")
        ,DB::raw("SUM(f.gross_weight) as gross_weight")
        ,DB::raw("SUM(f.measurement) as measurement")
        ,DB::raw("SUM(regular_delivery_plan_prospect_container_creation.summary_box) as summary_box_sum"))
        ->where('regular_delivery_plan_prospect_container_creation.id_shipping_instruction',$id)
        ->leftJoin('regular_delivery_plan_prospect_container as a','regular_delivery_plan_prospect_container_creation.id_prospect_container','a.id')
        ->leftJoin('mst_part as b','regular_delivery_plan_prospect_container_creation.item_no','b.item_no')
        ->leftJoin('mst_mot as c','regular_delivery_plan_prospect_container_creation.id_mot','c.id')
        ->leftJoin('mst_port_of_discharge as d','regular_delivery_plan_prospect_container_creation.code_consignee','d.code_consignee')
        ->leftJoin('mst_port_of_loading as e','regular_delivery_plan_prospect_container_creation.id_type_delivery','e.id_type_delivery')
        ->leftJoin('mst_container as f','regular_delivery_plan_prospect_container_creation.id_container','f.id')
        ->leftJoin('regular_delivery_plan_shipping_instruction_creation as g','regular_delivery_plan_prospect_container_creation.id_shipping_instruction_creation','g.id')
        ->groupBy('regular_delivery_plan_prospect_container_creation.code_consignee','regular_delivery_plan_prospect_container_creation.status_bml','regular_delivery_plan_prospect_container_creation.etd_jkt','regular_delivery_plan_prospect_container_creation.etd_wh','regular_delivery_plan_prospect_container_creation.id_lsp','g.status','id_shipping_instruction_creation','f.measurement','f.net_weight','f.gross_weight','f.container_value','f.container_type','e.name','c.name','b.hs_code','no_packaging','d.port')
        ->paginate($params->limit ?? null);
        if(!$data) throw new \Exception("Data not found", 400);

        $data->transform(function ($item) {

            if ($item->status_bml == 1) {
                $status_desc = 'BML Confirmed';
            } else {
                $status_desc = 'BML Inconfirmed';
            }

            return [
                'code_consignee' => $item->code_consignee,
                'consignee' => $item->refMstConsignee->name.'<br>'.$item->refMstConsignee->address1.'<br>'.$item->refMstConsignee->address2,
                'customer_name' => $item->refMstConsignee->nick_name,
                'etd_jkt' => $item->etd_jkt,
                'etd_wh' => $item->etd_wh,
                'summary_container' => $item->summary_container,
                'no_packaging' => $item->no_packaging,
                'hs_code' => $item->hs_code,
                'via' => $item->mot,
                'freight_chart' => 'COLLECT',
                'incoterm' => 'FOB',
                'shipped_by' => $item->mot,
                'container_value' => intval($item->container_type),
                'container_type' => $item->container_value,
                'net_weight' => $item->net_weight,
                'gross_weight' => $item->gross_weight,
                'measurement' => $item->measurement,
                'port' => $item->port,
                'type_delivery' => $item->type_delivery,
                'count' => $item->summary_container,
                'summary_box' => $item->summary_box_sum,
                'to' => $item->refMstLsp->name ?? null,
                'status' => $item->status ?? null,
                'id_shipping_instruction_creation' => $item->id_shipping_instruction_creation ?? null,
                'shipment' => MstShipment::where('is_active',1)->first()->shipment ?? null,
                'status_desc' => $status_desc
            ];
        });

        return [
            'items' => $data->items(),
            'last_page' => $data->lastPage()
        ];
    }

    public static function shippingStore($request,$is_transaction = true)
    {
        if($is_transaction) DB::beginTransaction();
        try {
            $consignee = MstConsignee::where('code',$request->code_consignee)->first();
            $request->merge(['consignee'=>json_encode($consignee),'status'=>Constant::DRAFT]);
            $params = $request->all();
            Helper::requireParams([
                'to',
                'cc',
            ]);
            $insert = RegularDeliveryPlanShippingInsructionCreation::create($params);
            RegularDeliveryPlanProspectContainerCreation::where('code_consignee',$request->code_consignee)->where('etd_jkt',$request->etd_jkt)->update(['id_shipping_instruction_creation'=>$insert->id]);
            $params['id_regular_delivery_plan_shipping_instruction_creation'] = $insert->id;
            RegularDeliveryPlanShippingInsructionCreationDraft::create($params);
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
            $regStok->map(function($item){
                RegularStokConfirmation::create(self::paramStok($item));
            });
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
        if($is_transaction) DB::beginTransaction();
        try {
            $data = $request->all();
            $etdJkt = RegularDeliveryPlanProspectContainerCreation::select('etd_jkt','datasource')->whereIn('id',$request->id)->groupBy('etd_jkt','datasource')->get();
            if(!count($etdJkt)) throw new \Exception("Data not found", 400);
            if(count($etdJkt) > 1)  throw new \Exception("Invalid ETD JKT", 400);
            $data['no_booking'] = 'BOOK'.Carbon::parse($etdJkt[0]->etd_jkt)->format('dmY').mt_rand(10000,99999);
            $data['datasource'] = $etdJkt[0]->datasource;
            $data['booking_date'] = Carbon::now()->format('Y-m-d');
            $insert = RegularDeliveryPlanShippingInsruction::create($data);
            RegularDeliveryPlanProspectContainerCreation::select('etd_jkt','datasource')->whereIn('id',$request->id)->update(['id_shipping_instruction'=>$insert->id]);
            if($is_transaction) DB::commit();
            return [
                'items' => ['id'=>$insert->id,'no_booking'=>$data['no_booking'],'etd_jkt'=>$etdJkt[0]->etd_jkt]
            ];
        } catch (\Throwable $th) {
            if($is_transaction) DB::rollBack();
            throw $th;
        }
    }

    public static function saveBook($request,$is_transaction = true) {
        Helper::requireParams(['id']);
        if($is_transaction) DB::beginTransaction();
        try {
            $res = RegularDeliveryPlanShippingInsruction::find($request->id);
            $res->status = Constant::STS_BOOK_FINISH;
            $res->save();
            if($is_transaction) DB::commit();
            return ['items'=>$res];
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
            $pathToFile = storage_path().'/app/shipping_instruction/'.$filename;
            Pdf::loadView('pdf.shipping_instruction',[
              'data' => $data
            ])
            ->save($pathToFile)
            ->setPaper('A4','potrait')
            ->download($filename);

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

    public static function shippingDraftDok($params,$id)
    {
        $data = RegularDeliveryPlanShippingInsructionCreationDraft::select('id','no_draft','created_at')
            ->where('id_regular_delivery_plan_shipping_instruction_creation',$id)
            ->paginate($params->limit ?? null);

        if(!$data) throw new \Exception("Data not found", 400);
        return [
            'items' => $data->items(),
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
