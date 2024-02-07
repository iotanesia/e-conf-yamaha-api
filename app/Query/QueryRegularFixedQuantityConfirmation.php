<?php

namespace App\Query;

use App\Constants\Constant;
use App\Models\RegularFixedQuantityConfirmation AS Model;
use App\ApiHelper as Helper;
use App\Jobs\ContainerActual;
use App\Models\MstBox;
use App\Models\MstConsignee;
use App\Models\MstContainer;
use App\Models\MstLsp;
use App\Models\MstPart;
use App\Models\RegularDeliveryPlan;
use App\Models\RegularDeliveryPlanBox;
use App\Models\RegularDeliveryPlanSet;
use App\Models\RegularFixedActualContainer;
use App\Models\RegularFixedActualContainerCreation;
use App\Models\RegularFixedQuantityConfirmation;
use App\Models\RegularFixedQuantityConfirmationBox;
use App\Models\RegularFixedShippingInstruction;
use App\Models\RegularOrderEntry;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\PackingExport;
use App\Exports\PebExport;
use App\Exports\FixedQuantityExport;
use App\Models\RegularFixedShippingInstructionCreation;
use App\Models\RegularStokConfirmationHistory;

class QueryRegularFixedQuantityConfirmation extends Model {

    const cast = 'regular-fixed-quantity-confirmation';


    public static function getFixedQuantity($params)
    {

        $key = self::cast.json_encode($params->query());
        return Helper::storageCache($key, function () use ($params){
            $query = self::select('id_regular_delivery_plan',
                DB::raw("string_agg(DISTINCT regular_fixed_quantity_confirmation.id::character varying, ',') as id_fixed_quantity"),
                DB::raw("string_agg(DISTINCT regular_fixed_quantity_confirmation.id_fixed_actual_container::character varying, ',') as id_fixed_actual_container"),
                DB::raw("string_agg(DISTINCT regular_fixed_quantity_confirmation.id_fixed_actual_container::character varying, ',') as id_fixed_actual_container"),
                DB::raw("string_agg(DISTINCT regular_fixed_quantity_confirmation.id_fixed_actual_container_creation::character varying, ',') as id_fixed_actual_container_creation"),
                DB::raw("string_agg(DISTINCT regular_fixed_quantity_confirmation.code_consignee::character varying, ',') as code_consignee"),
                DB::raw("string_agg(DISTINCT regular_fixed_quantity_confirmation.model::character varying, ',') as model"),
                DB::raw("string_agg(DISTINCT regular_fixed_quantity_confirmation.item_no::character varying, ',') as item_no"),
                DB::raw("string_agg(DISTINCT regular_fixed_quantity_confirmation.item_serial::character varying, ',') as item_serial"),
                DB::raw("string_agg(DISTINCT regular_fixed_quantity_confirmation.disburse::character varying, ',') as disburse"),
                DB::raw("string_agg(DISTINCT regular_fixed_quantity_confirmation.delivery::character varying, ',') as delivery"),
                DB::raw('MAX(regular_fixed_quantity_confirmation.qty) as qty'),
                DB::raw("string_agg(DISTINCT regular_fixed_quantity_confirmation.order_no::character varying, ',') as order_no"),
                DB::raw("string_agg(DISTINCT regular_fixed_quantity_confirmation.cust_item_no::character varying, ',') as cust_item_no"),
                DB::raw("string_agg(DISTINCT regular_fixed_quantity_confirmation.etd_ypmi::character varying, ',') as etd_ypmi"),
                DB::raw("string_agg(DISTINCT regular_fixed_quantity_confirmation.etd_wh::character varying, ',') as etd_wh"),
                DB::raw("string_agg(DISTINCT regular_fixed_quantity_confirmation.etd_jkt::character varying, ',') as etd_jkt"),
                DB::raw("string_agg(DISTINCT regular_fixed_quantity_confirmation.is_actual::character varying, ',') as is_actual"),
                DB::raw("string_agg(DISTINCT regular_fixed_quantity_confirmation.status::character varying, ',') as status"),
                DB::raw('MIN(regular_fixed_quantity_confirmation.in_dc) as in_dc'),
                DB::raw('MAX(regular_fixed_quantity_confirmation.in_wh) as in_wh'),
                DB::raw('MAX(regular_fixed_quantity_confirmation.production) as production'),
            )->where(function ($query) use ($params){

                $query->where('is_actual',Constant::IS_NOL);
                $query->where('datasource',$params->datasource);
                $category = $params->category ?? null;
                $kueri = $params->kueri ?? null;
            
                if ($category && $kueri) {
                    if ($category == 'cust_name') {
                        $query->whereHas('refConsignee', function ($q) use ($kueri) {
                            $q->where('nick_name', 'like', '%' . $kueri . '%');
                        });
                    } elseif ($category == 'item_name') {
                        $query->whereHas('refRegularDeliveryPlan.refPart', function ($q) use ($kueri) {
                            $q->where('description', 'like', '%' . $kueri . '%');
                        });
                    } elseif ($category == 'item_no') {
                        $query->whereHas('refRegularDeliveryPlan', function ($q) use ($kueri) {
                            $q->where('item_no', 'like', '%' . str_replace('-', '', $kueri) . '%');
                        });
                    }elseif ($category == 'etd_ypmi') {
                        $query->where('etd_ypmi', 'like', '%' . $kueri . '%');
                    }elseif ($category == 'etd_wh') {
                        $query->where('etd_wh', 'like', '%' . $kueri . '%');
                    }elseif ($category == 'etd_jkt') {
                        $query->where('etd_jkt', 'like', '%' . $kueri . '%');
                    } else {
                        $query->where('etd_jkt', 'like', '%' . $kueri . '%')
                            ->orWhere('order_no', 'like', '%' . $kueri . '%')
                            ->orWhere('cust_item_no', 'like', '%' . $kueri . '%')
                            ->orWhere('etd_ypmi', 'like', '%' . $kueri . '%')
                            ->orWhere('etd_wh', 'like', '%' . $kueri . '%');
                    }
                }

            })->groupBy('id_regular_delivery_plan');

            if($params->withTrashed == 'true') $query->withTrashed();
            if($params->dropdown == Constant::IS_ACTIVE) {
                $params->limit = null;
                $params->page = 1;
            }

            $data = $query->paginate($params->limit ?? null);
            return [
                'items' => $data->getCollection()->transform(function($item){

                    if ($item->refRegularDeliveryPlan !== null) {

                        if (Carbon::now() <= Carbon::parse($item->refRegularDeliveryPlan->etd_ypmi)) {
                            if ($item->qty !== $item->in_wh) $status = 'In Process';
                            if ($item->qty == $item->in_wh) $status = 'Finish Production';
                        } else {
                            $status = 'Out Of Date';
                        }

                        if ($item->refRegularDeliveryPlan->item_no == null) {
                            $item_no_set = RegularDeliveryPlanSet::where('id_delivery_plan', $item->refRegularDeliveryPlan->id)->get()->pluck('item_no');
                            $item_no_series = MstBox::where('part_set', 'set')->whereIn('item_no', $item_no_set->toArray())->get()->pluck('item_no_series');
                            $mst_part = MstPart::select('mst_part.item_no',
                                                DB::raw("string_agg(DISTINCT mst_part.description::character varying, ',') as description"))
                                                ->whereIn('mst_part.item_no', $item_no_set->toArray())
                                                ->groupBy('mst_part.item_no')->get();
                            $item_name = [];
                            foreach ($mst_part as $value) {
                                $item_name[] = $value->description;
                            }
                        }
                    }

                    $item->status_desc = $status ?? null;
                    $item->customer_name = $item->refConsignee->nick_name;
                    $item->production = $item->production ?? null;
                    $item->in_dc = $item->in_dc ?? null;
                    $item->in_wh = $item->in_wh ?? null;
                    $item->item_no = $item->refRegularDeliveryPlan->item_no == null ? $item_no_series : $item->refRegularDeliveryPlan->refPart->item_serial;
                    // $item->item_no = $item->refRegularDeliveryPlan->item_no == null ? $item_no_set : $item->refRegularDeliveryPlan->item_no;
                    $item->item_name = $item->refRegularDeliveryPlan->item_no == null ? $item_name : $item->refRegularDeliveryPlan->refPart->description;

                    unset(
                        $item->refConsignee,
                        $item->refRegularDeliveryPlan,
                    );

                    return $item;
                }),
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

    public static function noPackaging($params)
    {
        try {

            Helper::requireParams([
                'id_fixed_quantity',
            ]);

            $id_fixed_quantity = [];
            foreach ($params->id_fixed_quantity as $value) {
                $id_fixed_quantity[] = explode(',',$value);
            }

            $check = Model::select('code_consignee','etd_jkt','datasource')->whereIn('id',array_merge(...$id_fixed_quantity))
                ->groupBy('code_consignee','datasource','etd_jkt')
                ->get()
                ->toArray();

            if(count($check) > 1) throw new \Exception("ETD JKT and Customer name not same", 400);

            $data = Model::select(DB::raw('count(order_no) as total'),'order_no')->whereIn('id',array_merge(...$id_fixed_quantity))
                ->groupBy('order_no')
                ->orderBy('total','desc')
                ->get()
                ->toArray();

            if(count($data) == 0) throw new \Exception("Data not found", 400);

            $check_no_packaging = RegularFixedActualContainer::orderByDesc('updated_at')->first();

            if ($check_no_packaging == null) {
                $iteration = 'P01';
            } else {
                $last_two_digits = (int)substr($check_no_packaging->no_packaging, -2);
                $new_number = $last_two_digits + 1;

                if ($new_number >= 10) {
                    $iteration = 'P' . $new_number;
                } else {
                    $iteration = 'P0' . $new_number;
                }
            }

            $no_packaging = $data[0]['order_no'].$iteration;
            $tanggal = $check[0]['etd_jkt'];
            $code_consignee = $check[0]['code_consignee'];
            $datasource = $check[0]['datasource'];

            return [
                "items" => [
                    'id' => array_merge(...$id_fixed_quantity),
                    'no_packaging' => $no_packaging,
                    'etd_jkt' => date('Y-m-d', strtotime($tanggal)),
                    'code_consignee' => $code_consignee,
                    'datasource' => $datasource
                ]
            ];

        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public static function inquiryProcess($params, $is_trasaction = true)
    {
        Helper::requireParams([
            'id_fixed_quantity',
            'no_packaging',
            'etd_jkt',
            'code_consignee',
            'datasource'
        ]);

        if($is_trasaction) DB::beginTransaction();
        try {
           $check = RegularFixedActualContainer::where('no_packaging',$params->no_packaging)->first();
           if($check) throw new \Exception("no_packaging registered", 400);

           $store = RegularFixedActualContainer::create([
                        "code_consignee" => $params->code_consignee,
                        "etd_ypmi" => Carbon::parse($params->etd_jkt)->subDays(4)->format('Y-m-d'),
                        "etd_wh" => Carbon::parse($params->etd_jkt)->subDays(2)->format('Y-m-d'),
                        "etd_jkt" => $params->etd_jkt,
                        "no_packaging" => $params->no_packaging,
                        "created_at" => now(),
                        "datasource" => $params->datasource,
                        "id_mot" => $params->id_mot,
                        "is_prospect" => $params->id_mot == 2 ? 2 : 0
            ]);

            if ($params->id_mot == 2) {
                $store->update(['id_type_delivery' => 2]);

                $shipping = RegularFixedShippingInstruction::create([
                        "no_booking" =>  null,
                        "booking_date" => now(),
                        "datasource" => $params->datasource,
                        "status" => 1,
                        "id_mot" => $params->id_mot
                ]);
                
                $container_creation = RegularFixedActualContainerCreation::create([
                    "id_fixed_actual_container" => $store->id,
                    "id_type_delivery" => 2,
                    "id_mot" => 2,
                    "code_consignee" => $params->code_consignee,
                    "etd_ypmi" => Carbon::parse($params->etd_jkt)->subDays(4)->format('Y-m-d'),
                    "etd_wh" => Carbon::parse($params->etd_jkt)->subDays(2)->format('Y-m-d'),
                    "etd_jkt" => $params->etd_jkt,
                    "datasource" => $params->datasource,
                    'id_fixed_shipping_instruction' => $shipping->id,
                ]);

                $id_delivery_plan = $container_creation->manyFixedQuantityConfirmation()->pluck('id_regular_delivery_plan');
                $summary_box = RegularFixedQuantityConfirmationBox::whereIn('id_regular_delivery_plan', $id_delivery_plan->toArray())->get();
                $container_creation->update([
                    'summary_box' => count($summary_box)
                ]);
            }
            
            $id_container_creation = $params->id_mot == 2 ? $container_creation->id : null;

            $id_fixed_quantity = [];
            foreach ($params->id_fixed_quantity as $value) {
                $id_fixed_quantity[] = explode(',',$value);
            }

           self::where(function ($query) use ($params,$id_fixed_quantity){
                   $query->whereIn('id',array_merge(...$id_fixed_quantity));
                   $query->where('code_consignee',$params->code_consignee);
                   $query->where('etd_jkt',str_replace('-','',$params->etd_jkt));
                   $query->where('datasource','PYMAC');
           })
           ->chunk(1000,function ($data) use ($params,$store,$id_container_creation){
                foreach ($data as $key => $item) {
                    $item->is_actual = Constant::IS_ACTIVE;
                    $item->id_fixed_actual_container = $store->id;
                    if ($params->id_mot == 2) {
                        $item->id_fixed_actual_container_creation = $id_container_creation;
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

    public static function checkNoBooking()
    {
        $check_no_booking = RegularFixedShippingInstruction::orderByDesc('updated_at')->first();

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

        return 'BOOK'.Carbon::now()->format('dmY').$iteration;
    }

    public static function changeEtd($params,$is_trasaction = true)
    {
        if($is_trasaction) DB::beginTransaction();
        try {

            Helper::requireParams([
                'id_fixed_quantity',
                'etd_jkt'
            ]);

            $tahun = date('Y', strtotime($params->etd_jkt));
            $bulan = date('m', strtotime($params->etd_jkt));
            $bulan_str = $bulan < 10 ? '0'.$bulan : $bulan;

            $chek = RegularOrderEntry::where('year', $tahun)->where('month', $bulan_str)->first();
            if($chek == null) throw new \Exception("Data not deliver yet", 400);

            $id_fixed_quantity = [];
            foreach ($params->id_fixed_quantity as $value_id) {
                $id_fixed_quantity[] = explode(',',$value_id);
            }

            $data = self::whereIn('id',array_merge(...$id_fixed_quantity))->get();
            if(!$data) throw new \Exception("Data not found", 400);
            $request = $params->all();
            $request['etd_jkt'] = Carbon::parse($params->etd_jkt)->format('Ymd');
            $request['etd_ypmi'] =Carbon::parse($params->etd_jkt)->subDays(4)->format('Ymd');
            $request['etd_wh'] =Carbon::parse($params->etd_jkt)->subDays(2)->format('Ymd');
            foreach ($data as $value) {
                $value->fill($request);
                $value->is_actual = 1;
                $value->save();
            }
            
            if($is_trasaction) DB::commit();
        } catch (\Throwable $th) {
            if($is_trasaction) DB::rollBack();
            throw $th;
        }
    }

    public static function exportExcel($request)
    {
        $data = self::getFixedQuantity($request);

        $res = [];
        foreach ($data['items'] as $key => $value) {
            $res[] = [
                'no' => $key +1,
                'cust_name' => $value->refConsignee->nick_name ?? null,
                'item_no' => $value->refRegularDeliveryPlan->item_no == null ? implode(',', $value->item_no->toArray()) : $value->item_no,
                'item_name' => $value->refRegularDeliveryPlan->item_no == null ? implode(',', $value->item_name) : $value->item_name,
                'cust_item_no' => $value->cust_item_no,
                'cust_order_no' => $value->order_no,
                'qty' => (string)$value->qty,
                'etd_ypmi' => $value->etd_ypmi,
                'etd_wh' => $value->etd_wh,
                'etd_jkt' => $value->etd_jkt,
                'production' => (string)$value->production,
                'in_dc' => (string)$value->in_dc,
                'in_wh' => (string)$value->in_wh
            ];
        }

        $filename = 'fixed-quantity-'.Carbon::now()->format('Ymd');

        return Excel::download(new FixedQuantityExport($res), $filename.'.xlsx');
    }

    public static function getActualContainer($params) {
        $data = RegularFixedActualContainer::where(function ($query) use ($params){
            $category = $params->category ?? null;
            $kueri = $params->kueri ?? null;
        
            if ($category && $kueri) {
                if ($category == 'cust_name') {
                    $query->whereHas('refConsignee', function ($q) use ($kueri) {
                        $q->where('nick_name', 'like', '%' . $kueri . '%');
                    });
                }elseif ($category == 'etd_ypmi') {
                    $query->where('etd_ypmi', 'like', '%' . $kueri . '%');
                }elseif ($category == 'etd_wh') {
                    $query->where('etd_wh', 'like', '%' . $kueri . '%');
                }elseif ($category == 'etd_jkt') {
                    $query->where('etd_jkt', 'like', '%' . $kueri . '%');
                } else {
                    $query->where('etd_jkt', 'like', '%' . $kueri . '%')
                        ->orWhere('no_packaging', 'like', '%' . $kueri . '%')
                        ->orWhere('etd_ypmi', 'like', '%' . $kueri . '%')
                        ->orWhere('etd_wh', 'like', '%' . $kueri . '%');
                }
            }

            $date_from = str_replace('-','',$params->date_from);
            $date_to = str_replace('-','',$params->date_to);
            if($params->date_from || $params->date_to) $query->whereBetween('etd_jkt',[$date_from, $date_to]);

            if($params->is_actual == 0)
                $query->whereIn('is_actual', [0,99]);
            else
                $query->where('is_actual', $params->is_actual);


        })->paginate($params->limit ?? null);

        $data->map(function ($item){
            $item->cust_name = $item->refConsignee->nick_name ?? null;
            $item->mot = $item->refMot->name ?? null;
            $item->status_desc = 'Confirmed';

            unset(
                $item->refConsignee,
                $item->refMot
            );
            return $item;
        })->toArray();

        return [
            'items' => $data->items(),
            'last_page' => $data->lastPage()
        ];
    }

    public static function simulation($params)
    {
        $container = MstContainer::find(3); // 40HC
        $delivery_plan_box = RegularFixedQuantityConfirmationBox::select('id_box', DB::raw('count(id_box) as count_box'))
        ->whereIn('id_regular_delivery_plan',[
            4659,
            4663,
            4670,
            4674,
        ])->groupBy('id_box')
        ->get()
        ->map(function ($item){
            return [
                'label' => $item->refBox->no_box,
                'w' =>  floatval($item->refBox->width/1000),
                'h' => floatval($item->refBox->height/1000),
                'l' => floatval($item->refBox->length/1000),
                'q' => $item->count_box,
                'priority' => 1,
                'stackingCapacity' => 1,
                'rotations' => [
                    'base'
                ]
            ];

        });

        return [
            'items' => [
                'container' => [
                    'w' => floatval(round($container->long,2)) ?? null,
                    'h' => floatval(round($container->height,2)) ?? null,
                    'l' => floatval(round($container->wide,2)) ?? null
                ],
                'routes' => [
                    [
                        'id' => 1,
                        'from' => 'Casa',
                        'to' => 'Rabat',
                        'type' => 'dechargement'
                    ],
                    [
                        'id' => 2,
                        'from' => 'Rabat',
                        'to' => 'Kenitra',
                        'type' => 'dechargement'
                    ],
                    [
                        'id' => 3,
                        'from' => 'Kenitra',
                        'to' => 'Tanger',
                        'type' => 'dechargement'
                    ]
                ],
                'colis' => $delivery_plan_box
            ]
        ];
    }

    public static function creationProcess($params,$is_transaction = true)
    {
        if($is_transaction) DB::beginTransaction();
        try {
         Helper::requireParams([
             'id'
         ]);

            $actual_container = RegularFixedActualContainer::find($params->id);
            $mst_container = MstContainer::where('id', $params->id_container)->first();
            $lsp = MstLsp::where('code_consignee',$actual_container->code_consignee)
                ->where('id_type_delivery', 1)
                ->first();
            $simulation = self::simulation($params)['items'];
            $containerInfo = $simulation['container'];
            $colis = $simulation['colis'];
            $containerVolume = $containerInfo ? round($containerInfo['w'] * $containerInfo['h'] * $containerInfo['l'],0) : 0;
            $boxVolume = [];
            $stackCapacities = [];
            $qty = 0;
            $id_reg_dev_plan = [];
            foreach ($colis as $value){
                $volume = $value['w'] * $value['h'] * $value['l'];
                $boxVolume[] = round($volume,2) ?? 0;
                $stackCapacities[] = 1;
                $id_reg_dev_plan[] = $value['id_delivery_plan'];
                $qty += $value['q'];
            }

            //dimensional algorithm container

            $total_boxes = self::countBoxesInContainer($containerVolume, $boxVolume, $stackCapacities) ?? 0;
            $sisa = ceil($qty/$total_boxes);
            $index = 1;
            for ($i=0; $i < $sisa ; $i++) {
                if($qty > $total_boxes)
                    $summary_box = $total_boxes;
                else
                    $summary_box = $qty;

                $creation = [
                    'id_type_delivery' => $lsp->id_type_delivery,
                    'id_mot' => $lsp->refTypeDelivery->id_mot,
                    'id_container' => $params->id_container,
                    'id_lsp' => $lsp->id,
                    'summary_box' => $summary_box,
                    'code_consignee' => $actual_container->code_consignee,
                    'etd_jkt' => $actual_container->etd_jkt,
                    'etd_ypmi' => $actual_container->etd_ypmi,
                    'etd_wh' => $actual_container->etd_wh,
                    'measurement' => $mst_container->measurement ?? 0,
                    'iteration' => $index,
                    'id_prospect_container' => $params->id,
                    'status_bml' => 0,
                    'datasource' => $params->datasource
                ];
                RegularFixedActualContainerCreation::create($creation);

                $sum = $qty - $summary_box;
                $qty = $sum;
                $index = $index + 1;
            }

            $upd = RegularFixedActualContainer::find($params->id);
            $upd->is_actual = 99;
            $upd->save();

            $set = [
                'id' => $params->id,
                'colis' => $colis,
            ];

            if($is_transaction) DB::commit();

           ContainerActual::dispatch($set);

        } catch (\Throwable $th) {
             if($is_transaction) DB::rollBack();
             throw $th;
        }
    }

    public static function creationCalculation($params)
    {
        DB::beginTransaction();
        try {

            $actual_container = RegularFixedActualContainer::where('id',$params->id)->first();
            $lsp = MstLsp::where('code_consignee',$actual_container->code_consignee)
                ->where('id_type_delivery', ($actual_container->id_type_delivery ?? 1))
                ->first();
            
            $fixedQuantity = RegularFixedQuantityConfirmation::select('id','code_consignee','item_no','id_regular_delivery_plan')
            ->where('id_fixed_actual_container', $params->id)
            ->orderBy('id', 'asc')
            ->get();

            $id_fixed_quantity = [];
            foreach ($fixedQuantity as $item){
                $id_fixed_quantity[] = $item->id;
            }

            $quantityConfirmationBox = RegularFixedQuantityConfirmationBox::select('id_fixed_quantity_confirmation',
                'id_box', DB::raw('count(id_box) as count_box'),DB::raw("SUM(regular_fixed_quantity_confirmation_box.qty_pcs_box) as sum_qty"),DB::raw("string_agg(DISTINCT regular_fixed_quantity_confirmation_box.id_regular_delivery_plan::character varying, ',') as id_regular_delivery_plan"))
            ->whereIn('id_fixed_quantity_confirmation',$id_fixed_quantity)
            ->where('is_labeling',0)
            ->whereNotNull('qrcode')
            ->whereNotNull('a.id_fixed_actual_container')
            ->leftJoin('regular_fixed_quantity_confirmation as a','a.id','regular_fixed_quantity_confirmation_box.id_fixed_quantity_confirmation')
            ->groupBy('id_box', 'id_fixed_quantity_confirmation')
            ->orderBy('count_box','desc')
            ->get()
            ->map(function ($item, $index) {

                $row_length = $item->refMstBox->fork_side == 'Length' ? ($item->refMstBox->width * (int)ceil($item->count_box / 4)) : ($item->refMstBox->length * (int)ceil($item->count_box / 4));
                $count_box = $item->count_box;
                $box = RegularFixedQuantityConfirmationBox::select('regular_fixed_quantity_confirmation_box.id')
                                                            ->where('id_fixed_quantity_confirmation', $item->id_fixed_quantity_confirmation)
                                                            ->whereNull('id_prospect_container_creation')
                                                            ->where('is_labeling',0)
                                                            ->whereNotNull('qrcode')
                                                            ->whereNotNull('a.id_fixed_actual_container')
                                                            ->leftJoin('regular_fixed_quantity_confirmation as a','a.id','regular_fixed_quantity_confirmation_box.id_fixed_quantity_confirmation')
                                                            ->orderBy('regular_fixed_quantity_confirmation_box.id', 'asc')
                                                            ->get();
                $box_set_count = count($box);

                return [
                    'id_fixed_quantity_confirmation' => $item->id_fixed_quantity_confirmation,
                    'item_no' => $item->refMstBox->item_no,
                    'label' => $item->refMstBox->no_box,
                    'width' =>  $item->refMstBox->width,
                    'length' => $item->refMstBox->length,
                    'count_box' => $count_box,
                    'sum_qty' => $item->sum_qty,
                    'priority' => $index + 1,
                    'forkside' => $item->refMstBox->fork_side,
                    'stackingCapacity' => $item->refMstBox->stack_capacity,
                    'row' => (int)ceil($count_box / 4),
                    'first_row_length' => $item->refMstBox->fork_side == 'Length' ? $item->refMstBox->width : $item->refMstBox->length,
                    'row_length' => $row_length,
                    'box' => $box,
                    'box_set_count' => $box_set_count
                ];
            });

            $box_set_count = 0;
            $sum_row_length = 0;
            $sum_count_box = 0;
            $sum_qty_box = [];
            $first_row_length = [];
            $first_row = [];
            $first_count_box = [];
            $row_length = [];
            $count_box = [];
            $big_row_length = [];
            foreach ($quantityConfirmationBox as $key => $value) {
                $box_set_count += $value['box_set_count'];
                $sum_row_length += $value['row_length'];
                $sum_count_box += $value['count_box'];
                $sum_qty_box[] = $value['sum_qty'];
                $first_row_length[] = $quantityConfirmationBox[$key]['first_row_length'];
                $first_row[] = $quantityConfirmationBox[$key]['row'];
                $first_count_box[] = $quantityConfirmationBox[$key]['count_box'];
                $row_length[] = $quantityConfirmationBox[$key]['row_length'];
                $count_box[] = $quantityConfirmationBox[$key]['count_box'];
                $big_row_length[] = $quantityConfirmationBox[$key]['first_row_length'] * $quantityConfirmationBox[$key]['row'];
            }

            $creation = [
                'id_type_delivery' => $lsp->id_type_delivery,
                'id_mot' => $lsp->refTypeDelivery->id_mot,
                'id_lsp' => $lsp->id,
                'code_consignee' => $actual_container->code_consignee,
                'etd_jkt' => $actual_container->etd_jkt,
                'etd_ypmi' => $actual_container->etd_ypmi,
                'etd_wh' => $actual_container->etd_wh,
                'id_fixed_actual_container' => $actual_container->id,
                'status_bml' => 0,
                'datasource' => $params->datasource,
            ];

            $count_container = (int)ceil($sum_row_length / 12031);
            $send_summary_box = self::ratioSummaryBox(array_sum($count_box), $sum_row_length, 12031);
            for ($i=1; $i <= $count_container; $i++) { 
                if ($sum_row_length < 5905) {
                    $creation['id_container'] = 1;
                    $creation['measurement'] = MstContainer::find(1)->measurement ?? 0;
                    $creation['summary_box'] = (int)$send_summary_box[$i-1]['summary_box'];
                    $creation['iteration'] = $i;
                    $creation['space'] = 5905 - (int)$sum_row_length;
                } else {
                    $creation['id_container'] = 2;
                    $creation['measurement'] = MstContainer::find(2)->measurement ?? 0;
                    $creation['summary_box'] = (int)$send_summary_box[$i-1]['summary_box'];
                    $creation['iteration'] = $i;
                    $creation['space'] = (int)$send_summary_box[$i-1]['space'];
                }

                $check = RegularFixedActualContainerCreation::where('id_fixed_actual_container', $actual_container->id)->where('space', null)->first();
                if($check) $check->forceDelete();
                RegularFixedActualContainerCreation::create($creation);

                $sum_row_length = $sum_row_length - 12031;
            }
            
            $upd = RegularFixedActualContainer::where('id',$params->id)->first();
            $upd->is_actual = 99;
            $upd->save();

            $set = [
                'id' => $params->id,
                'colis' => $quantityConfirmationBox,
                'box_set_count' => $box_set_count,
                'type' => 'single'
            ];

            ContainerActual::dispatch($set);
            
           DB::commit();

        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }
    
    public static function ratioSummaryBox($qty, $sum_row_length, $divisor)
    {
        $ratios = [];
        $fullChunks = floor($sum_row_length / $divisor);
        for ($i = 0; $i < $fullChunks; $i++) {
            $ratios[] = $divisor;
        }

        $remainingQty = $sum_row_length % $divisor;
        if ($remainingQty > 0) {
            $ratios[] = $remainingQty;
        }

        $totalRatio = array_sum($ratios);
    
        $result = [];
        foreach ($ratios as $key => $ratio) {
            if ($key+1 == count($ratios)) {
                $result[$key] = [
                    'summary_box' => ceil($qty * ($ratio / $totalRatio)),
                    'space' => ceil($qty * ($ratio / $totalRatio)) - ($qty * ($ratio / $totalRatio))
                ];
            } else {
                $result[$key] = [
                    'summary_box' => floor($qty * ($ratio / $totalRatio)),
                    'space' => ($qty * ($ratio / $totalRatio)) - floor($qty * ($ratio / $totalRatio))
                ];
            }
            
        }
        
        return $result;
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

            $update = RegularFixedActualContainerCreation::find($params['id']);
            if(!$update) throw new \Exception("id tidak ditemukan", 400);

            $check = MstLsp::where('code_consignee', $params['code_consignee'])
                ->where('id_type_delivery', $params['id_type_delivery'])
                ->first();

            if(!$check) throw new \Exception("LSP not found", 400);

            $update->fill($params);
            $update->id_lsp = $check->id;
            $update->save();
            if($is_transaction) DB::commit();
        } catch (\Throwable $th) {
            if($is_transaction) DB::rollBack();
            throw $th;
        }
    }

    public static function countBoxesInContainer($containerVolume, $boxVolumes, $stackCapacities){
        rsort($boxVolumes);
        rsort($stackCapacities);
        $totalBoxes = 0;
        $remainingVolume = $containerVolume;
        foreach ($boxVolumes as $boxVolume) {
            foreach ($stackCapacities as $stackCapacity) {
                $boxesInVolume = floor($remainingVolume / $boxVolume);
                $fullStacks = floor($boxesInVolume / $stackCapacity);
                $remainingBoxes = $boxesInVolume % $stackCapacity;
                $totalBoxes += $fullStacks * $stackCapacity + $remainingBoxes;
                $remainingVolume -= $boxesInVolume * $boxVolume;
                if ($boxesInVolume == 0 || $remainingVolume <= 0) {
                    break;
                }
            }
        }
        return $totalBoxes;
    }

    static function packBoxesIntoContainers($boxSizes, $containerCapacity) {
        sort($boxSizes); // Sort box sizes in ascending order
        $containers = array(); // Initialize array of containers
        $containerCount = 0; // Initialize container count
        $currentContainer = array(); // Initialize current container

        foreach ($boxSizes as $boxSize) {
            if (array_sum($currentContainer) + $boxSize <= $containerCapacity) {
                // Add box to current container
                $currentContainer[] = $boxSize;
            } else {
                // Close current container, add to array of containers, and create new container
                $containers[] = $currentContainer;
                $containerCount++;
                $currentContainer = array($boxSize);
            }
        }

        // Add the last container to the array of containers
        $containers[] = $currentContainer;
        $containerCount++;

        return array_map(function ($item){
            return count($item);
        },$containers);
    }

    public static function creationDetail($params)
    {
        $data = RegularFixedActualContainerCreation::whereIn('id_fixed_actual_container',$params->id)

                ->where(function($query) use($params) {
                    $category = $params->category ?? null;
                    $kueri = $params->kueri ?? null;
                
                    if ($category && $kueri) {
                        if ($category == 'cust_name') {
                            $query->whereHas('refMstConsignee', function ($q) use ($kueri) {
                                $q->where('nick_name', 'like', '%' . $kueri . '%');
                            });
                        } elseif ($category == 'logistic_service_provider') {
                            $query->whereHas('refMstLsp', function ($q) use ($kueri) {
                                $q->where('name', 'like', '%' . $kueri . '%');
                            });
                        }elseif ($category == 'etd_ypmi') {
                            $query->where('etd_ypmi', 'like', '%' . $kueri . '%');
                        }elseif ($category == 'etd_wh') {
                            $query->where('etd_wh', 'like', '%' . $kueri . '%');
                        }elseif ($category == 'etd_jkt') {
                            $query->where('etd_jkt', 'like', '%' . $kueri . '%');
                        } else {
                            $query->where('etd_jkt', 'like', '%' . $kueri . '%')
                                ->orWhere('summary_box', 'like', '%' . $kueri . '%')
                                ->orWhere('etd_wh', 'like', '%' . $kueri . '%');
                        }
                    }

                    $date_from = str_replace('-','',$params->date_from);
                    $date_to = str_replace('-','',$params->date_to);
                    if($params->date_from || $params->date_to) $query->whereBetween('etd_jkt',[$date_from, $date_to]);
                })

            ->orderBy('iteration', 'asc')
            ->paginate($params->limit ?? null);
        if(!$data) throw new \Exception("Data not found", 400);
        return [
            'items' => $data->getCollection()->transform(function($item){

                $quantity_confirmation = RegularFixedQuantityConfirmation::where('id_fixed_actual_container', $item->id_fixed_actual_container)->get();
                $box = RegularFixedQuantityConfirmationBox::with('refMstBox', 'refRegularDeliveryPlan')->whereIn('id_fixed_quantity_confirmation', $quantity_confirmation->pluck('id')->toArray())
                                                            ->where('id_prospect_container_creation', $item->id)
                                                            ->whereNotNull('qrcode')->get();

                $count_net_weight = 0;
                $count_outer_carton_weight = 0;
                $count_meas = 0;
                $total_net_weight = 0;
                $total_gross_weight = 0;
                foreach ($box as $key => $box_item){
                    if ($box_item->refRegularDeliveryPlan->item_no == null) {
                        $master = [];
                        $check = [];
                        $item_no_set = [];
                        foreach ($box_item->refRegularDeliveryPlan->manyDeliveryPlanSet as $set) {
                            $item_no_set[] = $set->item_no;
                            $master[] = $set->refBox->qty;
                            $check[] = $box->pluck('qty_pcs_box')->toArray()[$key];
                        } 
                        $mst_box = MstBox::whereIn('item_no', $item_no_set)->get();
                        $nw_gw = self::nettWeightGrossWeight($box->pluck('qty_pcs_box')->toArray(), $mst_box->pluck('qty')->toArray(), $mst_box, $box_item->refRegularDeliveryPlan->manyDeliveryPlanSet);
                            
                        $total_net_weight += array_sum($nw_gw[$key]['unit_weight_kg']);
                        $total_gross_weight += array_sum($nw_gw[$key]['total_gross_weight']);
                        $count_meas += ($box_item->refMstBox->length * $box_item->refMstBox->width * $box_item->refMstBox->height) * $box_item->qty_pcs_box / 1000000000;
                    } else {
                        $count_net_weight = $box_item->refMstBox->unit_weight_gr;
                        $count_outer_carton_weight = $box_item->refMstBox->outer_carton_weight;
                        $count_meas += (($box_item->refMstBox->length * $box_item->refMstBox->width * $box_item->refMstBox->height) * $box_item->qty_pcs_box / 1000000000);
                        $total_net_weight += ($count_net_weight * $box_item->qty_pcs_box)/1000;
                        $total_gross_weight += (($count_net_weight * $box_item->qty_pcs_box)/1000) + $count_outer_carton_weight;
                    }
                }

                $item->cust_name = $item->refMstConsignee->nick_name ?? null;
                $item->id_type_delivery = $item->id_type_delivery;
                $item->type_delivery = $item->refMstTypeDelivery->name ?? null;
                $item->lsp = $item->refMstLsp->name ?? null;
                $item->net_weight = number_format($total_net_weight, 2);
                $item->gross_weight = number_format($total_gross_weight, 2);
                $item->measurement = number_format($count_meas,3);
                $item->container_type = $item->id_mot == 2 ? null : ($item->refMstContainer->container_type ?? null);
                $item->load_extension_length = $item->refMstContainer->long ?? null;
                $item->load_extension_width = $item->refMstContainer->wide ?? null;
                $item->load_extension_height = $item->refMstContainer->height ?? null;
                $item->load_qty = "100";
                $item->container_name = ($item->refMstContainer->container_type ?? null)." ".($item->refMstContainer->container_value ?? null);

                unset(
                    $item->refMstConsignee,
                    $item->refMstTypeDelivery,
                    $item->refMstLsp,
                    $item->refMstMot,
                    $item->refMstContainer,
                );

                return $item;
            }),
            'last_page' => $data->lastPage()
        ];

    }

    public static function getCreationMove($params, $id)
    {
        $data = RegularFixedQuantityConfirmation::where('id_fixed_actual_container_creation',$id)->paginate($params->limit ?? null);
        if(!$data) throw new \Exception("Data not found", 400);
        return [
            'items' => $data->getCollection()->transform(function($item){
                $item->item_name = trim($item->refRegularDeliveryPlan->refPart->description);
                $item->cust_name = $item->refRegularDeliveryPlan->refConsignee->nick_name;
                $item->box = self::getCountBox($item->id)[0] ?? null;

                unset(
                    $item->refRegularDeliveryPlan,
                    $item->status,
                    $item->in_dc,
                    $item->in_wh,
                    $item->production,
                );

                return $item;
            }),
            'last_page' => $data->lastPage()
        ];
    }

    public static function getCountBoxFifo($id, $id_actual_creation){
        $data = RegularFixedQuantityConfirmationBox::select('id_box', DB::raw('count(*) as jml'), 
                    DB::raw('MAX(regular_fixed_quantity_confirmation_box.qty_pcs_box) as qty_pcs_box')
                )
                ->whereIn('id_fixed_quantity_confirmation', explode(',',$id))
                ->whereIn('id_prospect_container_creation', explode(',',$id_actual_creation))
                ->whereNotNull('qrcode')
                ->groupBy('id_box')
                ->get();
        return
            $data->map(function ($item){
                $set['id'] = 0;
                $set['id_box'] = $item->id_box;
                $set['qty'] =  $item->qty_pcs_box." x ".$item->jml;
                $set['length'] =  "";
                $set['width'] =  "";
                $set['height'] =  "";
                return $set;
            });
    }

    public static function getCountBox($id){
        $data = RegularFixedQuantityConfirmationBox::select('id_box', DB::raw('count(*) as jml'))
                ->whereIn('id_fixed_quantity_confirmation', explode(',',$id))
                ->whereNotNull('qrcode')
                ->groupBy('id_box')
                ->get();
        return
            $data->map(function ($item){
                $set['id'] = 0;
                $set['id_box'] = $item->id_box;
                $set['qty'] =  $item->refMstBox->qty." x ".$item->jml." pcs";
                $set['length'] =  "";
                $set['width'] =  "";
                $set['height'] =  "";
                return $set;
            });
    }

    public static function getCreationMoveContainer($params, $id)
    {
        $data = RegularFixedActualContainerCreation::with('refMstContainer')
            ->find($id)
            ->first();

        if(!$data) throw new \Exception("Data not found", 400);
        $ret["container_type"] = $data->refMstContainer->container_type." ".$data->refMstContainer->container_value;
        $ret["container_capacity"] = $data->refMstContainer->capacity;
        $ret["actual_capacity"] = $data->refMstContainer->capacity;
        $ret["container_number"] = ($data->iteration < 10) ? "0".strval($data->iteration) : $data->iteration;
        return [
            'items' => $ret,
            'last_page' => 0
        ];

    }

    public static function creationMove($params, $is_transaction = true)
    {
        if($is_transaction) DB::beginTransaction();
        try {
            Helper::requireParams([
                'id'
            ]);
            $check = RegularFixedQuantityConfirmation::select('id_fixed_actual_container_creation')
                ->with('manyFixedQuantityConfirmationBox')
                ->whereIn('id', $params->id)
                ->groupBy('id_fixed_actual_container_creation')
                ->get();
            if(count($check) > 1) throw new \Exception("Code consignee, ETD JKT and datasource not same", 400);
            $drp = $check[0];
            $prospect = RegularFixedActualContainerCreation::find($drp->id_fixed_actual_container_creation);
            $nextprospect = RegularFixedActualContainerCreation::where(function ($query) use ($prospect){
                $query->where('code_consignee',$prospect->code_consignee);
                $query->where('datasource',$prospect->datasource);
                $query->where('iteration', $prospect->iteration+1);
                $query->where('etd_jkt',Carbon::parse($prospect->etd_jkt)->format('Y-m-d'));
            })->first();
            if(!$nextprospect){
                $creation['id_type_delivery'] = $prospect->id_type_delivery;
                $creation['id_mot'] = $prospect->id_mot;
                $creation['id_container'] = $prospect->id_container;
                $creation['id_lsp'] =  $prospect->id_lsp;
                $creation['summary_box'] = RegularFixedQuantityConfirmationBox::whereIn('id_fixed_quantity_confirmation',$params->id)->count() ?? 0;
                $creation['code_consignee'] = $prospect->code_consignee;
                $creation['etd_jkt'] = $prospect->etd_jkt;
                $creation['etd_ypmi'] = $prospect->etd_ypmi;
                $creation['etd_wh'] = $prospect->etd_wh;
                $creation['measurement'] = $prospect->measurement;
                $creation['iteration'] = $prospect->iteration+1;
                $creation['id_fixed_actual_container'] = $prospect->id_fixed_actual_container;
                $ins = RegularFixedActualContainerCreation::create($creation);
                RegularFixedQuantityConfirmation::whereIn('id',$params->id)->update(['id_fixed_actual_container_creation'=>$ins->id]);
            }else
                RegularFixedQuantityConfirmation::whereIn('id',$params->id)->update(['id_fixed_actual_container_creation'=>$nextprospect->id]);
            if($is_transaction) DB::commit();
        } catch (\Throwable $th) {
            if($is_transaction) DB::rollBack();
            throw $th;
        }
    }

    public static function creationDownloadDoc($request,$pathToFile,$filename)
    {
        try {
            Pdf::loadView('pdf.actual-container.simulation')
            ->save($pathToFile)
            ->setPaper('A4','potrait')
            ->download($filename);

          } catch (\Throwable $th) {
              return Helper::setErrorResponse($th);
          }
    }

    public static function generateNobooking($request,$is_transaction = true)
    {
        Helper::requireParams(['id']);
        if($is_transaction) DB::beginTransaction();
        try {
            $data = $request->all();
            $etdJkt = RegularFixedActualContainerCreation::select('etd_jkt','id_mot','datasource')->whereIn('id_fixed_actual_container',$request->id)->groupBy('etd_jkt','id_mot','datasource')->get();
            if(!count($etdJkt)) throw new \Exception("Data not found", 400);
            // if(count($etdJkt) > 1)  throw new \Exception("Invalid ETD JKT", 400);

            $check_no_booking = RegularFixedShippingInstruction::orderByDesc('updated_at')->first();
            if($check_no_booking == null || $check_no_booking->id_mot == 2) $check_no_booking = RegularFixedShippingInstruction::whereNotNull('no_booking')->orderByDesc('updated_at')->first();

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

            if($is_transaction) DB::commit();
            return [
                'items' => [
                    'id' => $request->id,
                    'no_booking' => 'BOOK'.Carbon::now()->format('dmY').$iteration,
                    'etd_jkt' => $etdJkt[0]->etd_jkt,
                    'id_mot' => $etdJkt[0]->id_mot,
                    'datasource' => $etdJkt[0]->datasource
                    ]
            ];
        } catch (\Throwable $th) {
            if($is_transaction) DB::rollBack();
            throw $th;
        }
    }

    public static function byIdProspectContainer($params,$id)
    {
        $data = RegularFixedQuantityConfirmationBox::select('regular_fixed_quantity_confirmation_box.id_prospect_container_creation', 'a.id',
                    DB::raw("string_agg(DISTINCT regular_fixed_quantity_confirmation_box.id_regular_delivery_plan::character varying, ',') as id_delivery_plan"),
                    DB::raw("string_agg(DISTINCT regular_fixed_quantity_confirmation_box.id_fixed_quantity_confirmation::character varying, ',') as id_fixed_quantity_confirmation"),
                    DB::raw("string_agg(DISTINCT regular_fixed_quantity_confirmation_box.id_box::character varying, ',') as id_box"),
                    DB::raw("string_agg(DISTINCT a.code_consignee::character varying, ',') as code_consignee"),
                    DB::raw("string_agg(DISTINCT a.cust_item_no::character varying, ',') as cust_item_no"),
                    DB::raw("string_agg(DISTINCT a.order_no::character varying, ',') as order_no"),
                    DB::raw("string_agg(DISTINCT a.etd_ypmi::character varying, ',') as etd_ypmi"),
                    DB::raw("string_agg(DISTINCT a.etd_wh::character varying, ',') as etd_wh"),
                    DB::raw("string_agg(DISTINCT a.etd_jkt::character varying, ',') as etd_jkt"),
                    DB::raw("string_agg(DISTINCT regular_fixed_quantity_confirmation_box.qty_pcs_box::character varying, ',') as qty"),
                    DB::raw("string_agg(DISTINCT a.item_no::character varying, ',') as item_no"),
                    DB::raw("string_agg(DISTINCT d.item_no::character varying, ',') as item_no_set"),
                    )
                    ->where('regular_fixed_quantity_confirmation_box.id_prospect_container_creation', $id)
                    ->whereNotNull('regular_fixed_quantity_confirmation_box.qrcode')
                    ->whereNotNull('c.id_fixed_actual_container')
                    ->leftJoin('regular_delivery_plan as a', 'a.id', 'regular_fixed_quantity_confirmation_box.id_regular_delivery_plan')
                    ->leftJoin('regular_fixed_quantity_confirmation as c', 'c.id', 'regular_fixed_quantity_confirmation_box.id_fixed_quantity_confirmation')
                    ->leftJoin('regular_delivery_plan_set as d','d.id_delivery_plan','regular_fixed_quantity_confirmation_box.id_regular_delivery_plan')
                    ->groupBy('regular_fixed_quantity_confirmation_box.id_prospect_container_creation', 'a.id')
                    ->distinct() // Make the entire result set distinct
                    ->paginate($params->limit ?? null);

        $data->transform(function ($item) {
            $custname = self::getCustName($item->code_consignee);
            $itemname = [];
            foreach (explode(',', $item->item_no) as $value) {
                $itemname[] = self::getPart($value);
            }
            $item_no = [];
            foreach (explode(',', $item->item_no) as $value) {
                $item_no[] = self::getItemSerial($value);
            }

            if ($item->item_no == null) {
                $itemname = [];
                foreach (explode(',', $item->item_no_set) as $value) {
                    $itemname[] = self::getPart($value);
                }
                $item_no = [];
                foreach (explode(',', $item->item_no_set) as $value) {
                    $item_no[] = self::getItemSerial($value);
                }
            }

            $box_result = self::getCountBoxFifo($item->id_fixed_quantity_confirmation,$item->id_prospect_container_creation);

            $qty_scan = RegularFixedQuantityConfirmationBox::whereIn('id_fixed_quantity_confirmation', explode(',',$item->id_fixed_quantity_confirmation))
                                                ->where('id_prospect_container_creation', $item->id_prospect_container_creation)
                                                ->whereNotNull('qrcode')->get()->pluck('qty_pcs_box');

            $qty_result = array_sum($qty_scan->toArray());

            $item->item_no = $item_no;
            $item->item_name = $itemname;
            $item->cust_name = $custname;
            $item->qty = [$qty_result];
            $item->box = $box_result;
            unset(
                $item->refRegularDeliveryPlan,
            );

            return $item;

        });

        return [
            'items' => $data->items(),
            'last_page' => $data->lastPage(),

        ];
    }

    public static function getCustName($code_consignee){
        $data = MstConsignee::where('code', $code_consignee)->first();
        return $data->nick_name ?? null;
    }

    public static function getPart($id_part){
        $data = MstPart::where('item_no', $id_part)->first();
        return $data->description ?? null;
    }

    public static function getItemSerial($id_part){
        $data = MstPart::where('item_no', $id_part)->first();
        return $data->item_serial ?? null;
    }

    public static function detailById($params)
    {
        $data = RegularFixedActualContainerCreation::whereIn('id',$params->id)->paginate($params->limit ?? null);


        if(!$data) throw new \Exception("Data not found", 400);

        return [
            'items' => [$data->first()],
            'last_page' => $data->lastPage()
        ];
    }

    public static function savebooking($request,$is_transaction = true) {
        Helper::requireParams(['id']);
        if($is_transaction) DB::beginTransaction();
        try {
         
            $data['booking_date'] = Carbon::now()->format('Y-m-d');
            $data['status'] = Constant::STS_BOOK_FINISH;
            $data['no_booking'] = $request->no_booking;
            $data['datasource'] = $request->datasource;
            $data['id_mot'] = $request->id_mot;
            if($request->id_mot == 1) $res = RegularFixedShippingInstruction::create($data);
            if($request->id_mot == 2) {
                $res = RegularFixedShippingInstruction::whereNull('no_booking')->orderByDesc('updated_at')->first();
                $res->update(['no_booking' => $request->no_booking]);
            } 

            $actual_container = RegularFixedActualContainer::where('id',$request->id)->get();
            foreach ($actual_container as $update) {
                $update->update(['is_actual' => 2]);
            }

            $container_creation = RegularFixedActualContainerCreation::where('id_fixed_actual_container',$request->id)->get();
            foreach ($container_creation as $upd) {
                $upd->update(['id_fixed_shipping_instruction' => $res->id]);
            }

            if($is_transaction) DB::commit();
            return ['items'=>$res];
        } catch (\Throwable $th) {
            if($is_transaction) DB::rollBack();
            throw $th;
        }
    }

    public static function getCasemarks($params)
    {
        $data = RegularFixedActualContainer::where(function ($query) use ($params){
            $category = $params->category ?? null;
            $kueri = $params->kueri ?? null;
        
            if ($category && $kueri) {
                if ($category == 'cust_name') {
                    $query->whereHas('refConsignee', function ($q) use ($kueri) {
                        $q->where('nick_name', 'like', '%' . $kueri . '%');
                    });
                }elseif ($category == 'etd_ypmi') {
                    $query->where('etd_ypmi', 'like', '%' . $kueri . '%');
                }elseif ($category == 'etd_wh') {
                    $query->where('etd_wh', 'like', '%' . $kueri . '%');
                }elseif ($category == 'etd_jkt') {
                    $query->where('etd_jkt', 'like', '%' . $kueri . '%');
                } else {
                    $query->where('etd_jkt', 'like', '%' . $kueri . '%')
                        ->orWhere('no_packaging', 'like', '%' . $kueri . '%')
                        ->orWhere('etd_ypmi', 'like', '%' . $kueri . '%')
                        ->orWhere('etd_wh', 'like', '%' . $kueri . '%');
                }
            }

            $date_from = str_replace('-','',$params->date_from);
            $date_to = str_replace('-','',$params->date_to);
            if($params->date_from || $params->date_to) $query->whereBetween('etd_jkt',[$date_from, $date_to]);


        })->orderBy('created_at', 'asc')
        ->paginate($params->limit ?? null);

        $data->map(function ($item){
            $item->cust_name = $item->refConsignee->nick_name ?? null;
            $item->status_desc = 'Case Marks Finished';
            $item->invoice_no = $item->no_packaging;

            unset(
                $item->refConsignee,
                $item->manyFixedPackingCreation,
            );
            return $item;
        })->toArray();

        return [
            'items' => $data->items(),
            'last_page' => $data->lastPage()
        ];
    }

    public static function printCasemarks($request,$id,$pathToFile,$filename)
    {
        try {
            $data = RegularFixedActualContainer::where('id', $id)->get();
            $id_delivery_plan = [];
            foreach ($data[0]->manyFixedQuantityConfirmation as $id_delivery) {
                $id_delivery_plan[] = $id_delivery->id_regular_delivery_plan;
            }
            $deliv_plan = RegularDeliveryPlan::with('manyFixedQuantityConfirmationBox')->orderBy('item_no','asc')->whereIn('id',$id_delivery_plan)->orderBy('item_no','asc')->get();

            $res_box_single = [];
            $res_box_set = [];
            foreach ($deliv_plan as $key => $deliv_value) {
                if ($deliv_value->item_no !== null) {
                    $res = $deliv_value->manyFixedQuantityConfirmationBox->map(function($item) use($id) {
                        if ($item->refFixedQuantityConfirmation->id_fixed_actual_container == $id) {
                            $res['qrcode'] = $item->qrcode;
                            $res['item_no'] = [$item->refRegularDeliveryPlan->item_no];
                            $res['qty_pcs_box'] = [$item->qty_pcs_box];
                            $res['item_no_series'] = [$item->refMstBox->item_no_series];
                            $res['unit_weight_kg'] = [($item->refMstBox->unit_weight_gr * $item->qty_pcs_box)/1000];
                            $res['total_gross_weight'] = [(($item->refMstBox->unit_weight_gr * $item->qty_pcs_box)/1000) + $item->refMstBox->outer_carton_weight];
                            $res['length'] = $item->refMstBox->length;
                            $res['width'] = $item->refMstBox->width;
                            $res['height'] = $item->refMstBox->height;
                            return $res;
                        }
                    });
                    
                    $box_single = [];
                    foreach ($res as $key => $item) {
                        if ($item !== null && $item['qrcode'] !== null && !in_array($item, $box_single)) {
                            $box_single[] = $item;
                        }
                    }
                    
                    $res_box_single[] = $box_single;
                }
                
                if ($deliv_value->item_no == null) {
                    $plan_set = RegularDeliveryPlanSet::where('id_delivery_plan',$deliv_value->id)->get();
                    $deliv_plan_box = $deliv_value->manyFixedQuantityConfirmationBox()
                                        ->whereHas('refFixedQuantityConfirmation', function ($q) use ($id) {
                                            $q->where('id_fixed_actual_container', $id);
                                        })
                                        ->where('id_regular_delivery_plan',$deliv_value->id)->where('qrcode','!=',null)->get();
                    
                    $item_no = [];
                    $set_qty = [];
                    foreach ($plan_set as $key => $value) {
                        $item_no[] = $value->item_no;
                        $set_qty[] = $value->qty;
                    }

                    $item_no_series = MstBox::where('part_set', 'set')->whereIn('item_no', $plan_set->pluck('item_no'))->get()->pluck('item_no_series');

                    $mst_box = MstBox::where('part_set', 'set')->whereIn('item_no', $item_no)->get();
                    $qty_box = [];
                    $sum_qty = [];
                    $unit_weight_kg_mst = [];
                    $total_gross_weight_mst = [];
                    $unit_weight_kg = [];
                    $total_gross_weight = [];
                    $count_outer_carton_weight = 0;
                    $length = '';
                    $width = '';
                    $height = '';
                    $count_net_weight = 0;
                    foreach ($mst_box as $key => $value) {
                        $qty_box[] = $value->qty;
                        $sum_qty[] = $value->qty;
                        $count_net_weight = $value->unit_weight_gr;
                        $count_outer_carton_weight = $value->outer_carton_weight / count($plan_set);
                        $unit_weight_kg_mst[] = ($count_net_weight * $value->qty)/1000;
                        $total_gross_weight_mst[] = (($count_net_weight * $value->qty)/1000) + $count_outer_carton_weight;
                        $unit_weight_kg[] = ($count_net_weight * ((array_sum($deliv_plan_box->pluck('qty_pcs_box')->toArray()) / count($deliv_plan_box)) / count($plan_set)))/1000;
                        $total_gross_weight[] = (($count_net_weight * ((array_sum($deliv_plan_box->pluck('qty_pcs_box')->toArray()) / count($deliv_plan_box)) / count($plan_set)))/1000) + $count_outer_carton_weight;
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
        
                        if ($qty >= array_sum($mst_box->pluck('qty')->toArray())) {
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
                    for ($i=0; $i < count($deliv_plan_box); $i++) { 
                        $nw_gw = self::nettWeightGrossWeight($deliv_plan_box->pluck('qty_pcs_box')->toArray(), $mst_box->pluck('qty')->toArray(), $mst_box, $plan_set);
                        $box_set[] = [
                            'item_no' => $item_no,
                            'qty_pcs_box' => [$deliv_plan_box->pluck('qty_pcs_box')->toArray()[$i]],
                            'item_no_series' => $item_no_series,
                            'unit_weight_kg' => $nw_gw[$i]['unit_weight_kg'],
                            'total_gross_weight' => $nw_gw[$i]['total_gross_weight'],
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
            $gross_weight_per_part = [];
            foreach ($box as $box_item){
                $count_qty += array_sum($box_item['qty_pcs_box']);
                $count_net_weight += array_sum($box_item['unit_weight_kg']);
                $count_gross_weight += array_sum($box_item['total_gross_weight']);
                $count_meas += (($box_item['length'] * $box_item['width'] * $box_item['height']) / 1000000000);
                $gross_weight_per_part[] = $box_item['total_gross_weight'];
            }

            $count_data = [];
            foreach ($box as $key => $box_item){
                for ($i = 0; $i < count($box_item['item_no_series']); $i++){
                    $count_data[] = 'count';
                }
            }
            
            Pdf::loadView('pdf.casemarks.casemarks_doc',[
                'count_data' => count($count_data),
                'data' => $data,
                'box' => $box
            ])
            ->save($pathToFile)
            ->setPaper('A4','potrait')
            ->download($filename);

        } catch (\Throwable $th) {
            return Helper::setErrorResponse($th);
        }
    }

    public static function printPackaging($request,$id,$pathToFile,$filename)
    {
        try {
            $data = RegularFixedActualContainer::where('id',$id)->get();
            $check_shipping = RegularFixedShippingInstructionCreation::where('id',$data[0]->manyFixedActualContainerCreation[0]->id_fixed_shipping_instruction_creation)->first();
            $id_delivery_plan = [];
            foreach ($data[0]->manyFixedQuantityConfirmation as $id_delivery) {
                $id_delivery_plan[] = $id_delivery->id_regular_delivery_plan;
            }
            $deliv_plan = RegularDeliveryPlan::with('manyFixedQuantityConfirmationBox')->orderBy('item_no','asc')->whereIn('id',$id_delivery_plan)->get();

            $res_box_single = [];
            $res_box_set = [];
            foreach ($deliv_plan as $key => $deliv_value) {
                if ($deliv_value->item_no !== null) {
                    $res = $deliv_value->manyFixedQuantityConfirmationBox->map(function($item) use($id) {
                        if ($item->refFixedQuantityConfirmation->id_fixed_actual_container == $id) {
                            $res['qrcode'] = $item->qrcode;
                            $res['item_no'] = [$item->refRegularDeliveryPlan->item_no];
                            $res['qty_pcs_box'] = [$item->qty_pcs_box];
                            $res['item_no_series'] = [$item->refMstBox->item_no_series];
                            $res['unit_weight_kg'] = [($item->refMstBox->unit_weight_gr * $item->qty_pcs_box)/1000];
                            $res['total_gross_weight'] = [(($item->refMstBox->unit_weight_gr * $item->qty_pcs_box)/1000) + $item->refMstBox->outer_carton_weight];
                            $res['length'] = $item->refMstBox->length;
                            $res['width'] = $item->refMstBox->width;
                            $res['height'] = $item->refMstBox->height;
                            return $res;
                        }
                    });
                    
                    $box_single = [];
                    foreach ($res as $key => $item) {
                        if ($item !== null && $item['qrcode'] !== null && !in_array($item, $box_single)) {
                            $box_single[] = $item;
                        }
                    }
                    
                    $res_box_single[] = $box_single;
                }
                
                if ($deliv_value->item_no == null) {
                    $plan_set = RegularDeliveryPlanSet::where('id_delivery_plan',$deliv_value->id)->get();
                    $deliv_plan_box = $deliv_value->manyFixedQuantityConfirmationBox()
                                        ->whereHas('refFixedQuantityConfirmation', function ($q) use ($id) {
                                            $q->where('id_fixed_actual_container', $id);
                                        })
                                        ->where('id_regular_delivery_plan',$deliv_value->id)->where('qrcode','!=',null)->get();
              
                    $item_no = [];
                    $set_qty = [];
                    foreach ($plan_set as $key => $value) {
                        $item_no[] = $value->item_no;
                        $set_qty[] = $value->qty;
                    }

                    $item_no_series = MstBox::where('part_set', 'set')->whereIn('item_no', $plan_set->pluck('item_no'))->get()->pluck('item_no_series');

                    $mst_box = MstBox::where('part_set', 'set')->whereIn('item_no', $item_no)->get();
                    $qty_box = [];
                    $sum_qty = [];
                    $unit_weight_kg_mst = [];
                    $total_gross_weight_mst = [];
                    $unit_weight_kg = [];
                    $total_gross_weight = [];
                    $count_outer_carton_weight = 0;
                    $length = '';
                    $width = '';
                    $height = '';
                    $count_net_weight = 0;
                    foreach ($mst_box as $key => $value) {
                        $qty_box[] = $value->qty;
                        $sum_qty[] = $value->qty;
                        $count_net_weight = $value->unit_weight_gr;
                        $count_outer_carton_weight = $value->outer_carton_weight / count($plan_set);
                        $unit_weight_kg_mst[] = ($count_net_weight * $value->qty)/1000;
                        $total_gross_weight_mst[] = (($count_net_weight * $value->qty)/1000) + $count_outer_carton_weight;
                        $unit_weight_kg[] = ($count_net_weight * ((array_sum($deliv_plan_box->pluck('qty_pcs_box')->toArray()) / count($deliv_plan_box)) / count($plan_set)))/1000;
                        $total_gross_weight[] = (($count_net_weight * ((array_sum($deliv_plan_box->pluck('qty_pcs_box')->toArray()) / count($deliv_plan_box)) / count($plan_set)))/1000) + $count_outer_carton_weight;
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
        
                        if ($qty >= array_sum($mst_box->pluck('qty')->toArray())) {
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
                    for ($i=0; $i < count($deliv_plan_box); $i++) { 
                        $ratio_qty = self::inputQuantity($deliv_plan_box->pluck('qty_pcs_box')->toArray(), $mst_box->pluck('qty')->toArray());
                        $nw_gw = self::nettWeightGrossWeight($deliv_plan_box->pluck('qty_pcs_box')->toArray(), $mst_box->pluck('qty')->toArray(), $mst_box, $plan_set);
                        $box_set[] = [
                            'item_no' => $item_no,
                            'qty_pcs_box' => count($res_qty) == 0 ? $res_qty : $ratio_qty[$i],
                            'item_no_series' => $item_no_series,
                            'unit_weight_kg' => $nw_gw[$i]['unit_weight_kg'],
                            'total_gross_weight' => $nw_gw[$i]['total_gross_weight'],
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
            $count_meas = [];
            $gross_weight_per_part = [];
            foreach ($box as $box_item){
                $count_qty += array_sum($box_item['qty_pcs_box']);
                $count_net_weight += array_sum($box_item['unit_weight_kg']);
                $count_gross_weight += array_sum($box_item['total_gross_weight']);
                $count_meas[] = round((($box_item['length'] * $box_item['width'] * $box_item['height']) * array_sum($box_item['qty_pcs_box']) / 1000000000), 3);
                $gross_weight_per_part[] = $box_item['total_gross_weight'];
            }

            $count_data = [];
            foreach ($box as $key => $box_item){
                for ($i = 0; $i < count($box_item['item_no_series']); $i++){
                    $count_data[] = 'count';
                }
            }

            Pdf::loadView('pdf.packaging.packaging_doc',[
                'count_data' => count($count_data),
                'data' => $data,
                'box' => $box,
                'gross_weight_per_part' => $gross_weight_per_part,
                'count_qty' => $count_qty,
                'count_net_weight' => $count_net_weight,
                'count_gross_weight' => $count_gross_weight,
                'count_meas' => array_sum($count_meas),
                'check_shipping' => $check_shipping
            ])
            ->save($pathToFile)
            ->setPaper('A4','potrait')
            ->download($filename);

          } catch (\Throwable $th) {
              return Helper::setErrorResponse($th);
          }
    }

    public static function inputQuantity($value, $ratio) 
    {
        $reference_value = min($ratio);
        $ratios = [];
        foreach ($ratio as $val) {
            $ratios[] = $val / $reference_value;
        }
        
        $result = [];
        foreach ($value as $qty) {
            $result_qty = [];
            foreach ($ratios as $res) {
                $result_qty[] = $qty * ($res / array_sum($ratios));
            }

            $result[] = $result_qty;
        }

        return $result;
    }

    public static function nettWeightGrossWeight($value, $ratio, $mst_box, $plan_set)
    {
        $qty_ratio = self::inputQuantity($value, $ratio);

        $result = [];
        foreach ($qty_ratio as $qty) {
            $unit_weight_kg = [];
            $total_gross_weight = [];
            foreach ($mst_box as $key => $value) {
                $count_net_weight = $value->unit_weight_gr;
                $count_outer_carton_weight = count($plan_set) == 0 ? $value->outer_carton_weight : ($value->outer_carton_weight / count($plan_set));
                $unit_weight_kg[] = ($count_net_weight * $qty[$key])/1000;
                $total_gross_weight[] = (($count_net_weight * $qty[$key])/1000) + $count_outer_carton_weight;
            }

            $result[] = [
                'unit_weight_kg' => $unit_weight_kg,
                'total_gross_weight' => $total_gross_weight,
            ];
        }

        return $result;

    }

    public static function exportCSV($request, $id)
    {
        $id_fixed_quantity = RegularFixedQuantityConfirmation::where('id_fixed_actual_container', $id)->get();
        $query = RegularFixedQuantityConfirmationBox::whereIn('id_fixed_quantity_confirmation', $id_fixed_quantity->pluck('id')->toArray())
        ->orderBy('created_at','asc')
        ->get();

        $data = $query->map(function ($item, $key){
            $plan_box = RegularDeliveryPlanBox::where('id', $item->id_regular_delivery_plan_box)->first();
            $fixedQuantity = RegularFixedQuantityConfirmation::where('id_regular_delivery_plan', $item->id_regular_delivery_plan)->first();
            $container_creation = RegularFixedActualContainerCreation::where('id', $item->id_prospect_container_creation)->first();

            if ($fixedQuantity && $fixedQuantity->id_fixed_actual_container !== null) {
                if ($plan_box->refRegularDeliveryPlan->item_no == null) {
                    $plan_set = RegularDeliveryPlanSet::where('id_delivery_plan', $item->id_regular_delivery_plan)->get();
                    $deliv_plan_box = $fixedQuantity->manyFixedQuantityConfirmationBox()
                                        ->where('id_regular_delivery_plan',$item->id_regular_delivery_plan)->where('qrcode','!=',null)->get();
                    $mst_box = MstBox::whereIn('item_no', $plan_set->pluck('item_no')->toArray())->get();
                    $nw_gw = self::nettWeightGrossWeight([$item->qty_pcs_box], $mst_box->pluck('qty')->toArray(), $mst_box, $plan_box->refRegularDeliveryPlan->manyDeliveryPlanSet);
                    $ratio_qty = self::inputQuantity($deliv_plan_box->pluck('qty_pcs_box')->toArray(), $mst_box->pluck('qty')->toArray());
                    $res = [];
                    foreach ($plan_set as $key => $value) {
                        $res[] = [
                            'gl_account' => $value->refPart->gl_account,
                            'coa' => $value->refPart->coa,
                            'cost_center' => $value->refPart->cost_center,
                            'urutan_no_container' => ($container_creation->iteration.' ('.$container_creation->refMstContainer->container_type.')') ?? null,
                            'kosong' => null,
                            'po_no' => $fixedQuantity->order_no,
                            'part_no' => $value->refPart->item_serial,
                            'qty' => $ratio_qty[0][$key],
                            'no' => $key == 0 ? '0' : null,
                            'nw' => str_replace('.',',',number_format($nw_gw[0]['unit_weight_kg'][$key], 2)),
                            'gw' => str_replace('.',',',number_format($nw_gw[0]['total_gross_weight'][$key], 2)),
                            'model_code' => $fixedQuantity->cust_item_no,
                            'type_box' => 'CARTON BOX',
                            'panjang' => $value->refBox->length,
                            'lebar' => $value->refBox->width,
                            'tinggi' => $value->refBox->height,
                            'hs_code' => $value->refPart->hs_code
                        ];
                    }
                    return $res;
                } else {
                    $mst_box = MstBox::where('id', $plan_box->id_box)->get();
                    $nw_gw = self::nettWeightGrossWeight([$item->qty_pcs_box], [$plan_box->refBox->qty], $mst_box, $plan_box->refRegularDeliveryPlan->manyDeliveryPlanSet);
                    
                    $res["gl_account"] = $plan_box->refRegularDeliveryPlan->refPart->gl_account;
                    $res["coa"] = $plan_box->refRegularDeliveryPlan->refPart->coa;
                    $res["cost_center"] = $plan_box->refRegularDeliveryPlan->refPart->cost_center;
                    $res["urutan_no_container"] = ($container_creation->iteration.' ('.$container_creation->refMstContainer->container_type.')') ?? null;
                    $res["kosong"] = null;
                    $res["po_no"] = $fixedQuantity->order_no ?? null;
                    $res["part_no"] = $plan_box->refRegularDeliveryPlan->refPart->item_serial;
                    $res["qty"] = $item->qty_pcs_box ?? null;
                    $res['no'] = '0';
                    $res['nw'] = str_replace('.',',',number_format($nw_gw[0]['unit_weight_kg'][0], 2)) ?? null;
                    $res['gw'] = str_replace('.',',',number_format($nw_gw[0]['total_gross_weight'][0], 2)) ?? null;
                    $res["model_code"] = $fixedQuantity->cust_item_no ?? null;
                    $res["type_box"] = 'CARTON BOX';
                    $res["panjang"] = $plan_box->refBox->length ?? null;
                    $res["lebar"] = $plan_box->refBox->width ?? null;
                    $res["tinggi"] = $plan_box->refBox->height ?? null;
                    $res["hs_code"] = $plan_box->refRegularDeliveryPlan->refPart->hs_code;
                    
                    return [$res];
                }
                

            }

        });

        $filteredData = array_values(array_filter($data->toArray()));
        $flattenedArray = call_user_func_array('array_merge', $filteredData);
        $no = 1;
        foreach ($flattenedArray as $key => &$subarray) {
            $subarray["no"] = $subarray["no"] == '0' ? $no++ : $flattenedArray[$key-1]["no"];
        }
        $filename = 'packing-list-'.Carbon::now()->format('Ymd');

        return Excel::download(new PackingExport($flattenedArray), $filename.'.csv');
    }

    public static function exportPEB($request, $id)
    {
        $id_fixed_quantity = RegularFixedQuantityConfirmation::where('id_fixed_actual_container', $id)->get();
        $query = RegularFixedQuantityConfirmationBox::select('id_regular_delivery_plan',
            DB::raw("string_agg(DISTINCT regular_fixed_quantity_confirmation_box.id_fixed_quantity_confirmation::character varying, ',') as id_fixed_quantity_confirmation"),
            DB::raw("string_agg(DISTINCT regular_fixed_quantity_confirmation_box.created_at::character varying, ',') as created_at"),
            DB::raw("string_agg(DISTINCT regular_fixed_quantity_confirmation_box.qty_pcs_box::character varying, ',') as qty_pcs_box"),
            DB::raw("string_agg(DISTINCT regular_fixed_quantity_confirmation_box.id_regular_delivery_plan_box::character varying, ',') as id_regular_delivery_plan_box"),
        )
        ->whereIn('regular_fixed_quantity_confirmation_box.id_fixed_quantity_confirmation', $id_fixed_quantity->pluck('id')->toArray())
        ->groupBy('id_regular_delivery_plan')
        ->orderBy('created_at','asc')
        ->get();

        $data = $query->map(function ($item, $key){
            $fixedQuantity = RegularFixedQuantityConfirmation::where('id_regular_delivery_plan', $item->id_regular_delivery_plan)->first();

            if ($fixedQuantity && $fixedQuantity->id_fixed_actual_container !== null) {
                if ($item->refRegularDeliveryPlan->item_no == null) {
                    $item_no = [];
                    foreach ($item->refRegularDeliveryPlan->manyDeliveryPlanSet as $value) {
                        $item_no[] = $value->item_no;
                    }
    
                    $mst_box = MstBox::whereIn('item_no', $item_no)->get();
                    $nw_gw = self::nettWeightGrossWeight(explode(',', $item->qty_pcs_box), $mst_box->pluck('qty')->toArray(), $mst_box, $item->refRegularDeliveryPlan->manyDeliveryPlanSet);
                    $netto = array_sum($nw_gw[0]['unit_weight_kg']);
                    $qty_ratio = self::inputQuantity(explode(',', $item->qty_pcs_box), $mst_box->pluck('qty')->toArray());
    
                    $volume = 0;
                    foreach ($mst_box as $vol) {
                        $volume = ($vol->length * $vol->width * $vol->height) / 1000000000;
                    }
    
                    $res = [];
                    foreach ($item->refRegularDeliveryPlan->manyDeliveryPlanSet as $i => $value) {
                        $res[] = [
                                'no_packaging' => $fixedQuantity->refFixedActualContainer->no_packaging ?? null,
                                'tanggal' => date('Ymd', strtotime($fixedQuantity->refFixedActualContainer->created_at)) ?? null,
                                'seri_barang' => null,
                                'hs' => $value->refPart->hs_code,
                                'kode_barang' => $value->item_no.', '.trim($value->refPart->description),
                                'uraian' => 'PRODUCTION PARTS FOR YAMAHA MOTORCYCLES',
                                'kode_satuan' => 'PCE',
                                'jumlah_satuan' => $qty_ratio[0][$i],
                                'kode_kemasan' => 'CT',
                                'jumlah_kemasan' => $i == 0 ? count(explode(',', $item->id_regular_delivery_plan_box)) : null,
                                'netto' => number_format($nw_gw[0]['unit_weight_kg'][$i], 2),
                                'volume' => $i == 0 ? number_format($volume, 3) : null,
                        ];
                    }
    
                    return $res;
                } else {
                    $kode_barang = $item->refRegularDeliveryPlan->item_no.', '.trim($item->refRegularDeliveryPlan->refPart->description);
                    $hs_code = $item->refRegularDeliveryPlan->refPart->hs_code;
                    
                    $mst_box = MstBox::where('item_no', $item->refRegularDeliveryPlan->item_no)->get();
                    $nw_gw = self::nettWeightGrossWeight(explode(',', $item->qty_pcs_box), $mst_box->pluck('qty')->toArray(), $mst_box, $item->refRegularDeliveryPlan->manyDeliveryPlanSet);
                    $netto = array_sum($nw_gw[0]['unit_weight_kg']);
                    
                    $volume = 0;
                    foreach ($mst_box as $vol) {
                        $volume = ($vol->length * $vol->width * $vol->height) / 1000000000;
                    }
    
                    $res['no_packaging'] = $fixedQuantity->refFixedActualContainer->no_packaging ?? null;
                    $res['tanggal'] = date('Ymd', strtotime($fixedQuantity->refFixedActualContainer->created_at)) ?? null;
                    $res['seri_barang'] = null;
                    $res['hs'] = $hs_code;
                    $res['kode_barang'] = $kode_barang;
                    $res['uraian'] = 'PRODUCTION PARTS FOR YAMAHA MOTORCYCLES';
                    $res['kode_satuan'] = 'PCE';
                    $res['jumlah_satuan'] = array_sum(explode(',', $item->qty_pcs_box));
                    $res['kode_kemasan'] = 'CT';
                    $res['jumlah_kemasan'] = count(explode(',', $item->id_regular_delivery_plan_box));
                    $res['netto'] = number_format($netto, 2);
                    $res['volume'] = number_format($volume, 3);
    
                    return [$res];
                }
            }

        });
        $filteredData = array_values(array_filter($data->toArray()));
        $flattenedArray = call_user_func_array('array_merge', $filteredData);
        foreach ($flattenedArray as $key => &$subarray) {
            $subarray["seri_barang"] = $key +1;
        }
        $filename = 'peb-'.Carbon::now()->format('Ymd');

        return Excel::download(new PebExport($flattenedArray), $filename.'.xlsx');
    }

}
