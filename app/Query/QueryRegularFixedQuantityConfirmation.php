<?php

namespace App\Query;

use App\Constants\Constant;
use App\Models\RegularFixedQuantityConfirmation AS Model;
use App\ApiHelper as Helper;
use App\Models\MstContainer;
use App\Models\MstLsp;
use App\Models\RegularDeliveryPlan;
use App\Models\RegularFixedActualContainer;
use App\Models\RegularFixedActualContainerCreation;
use App\Models\RegularFixedQuantityConfirmationBox;
use App\Models\RegularFixedShippingInstruction;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class QueryRegularFixedQuantityConfirmation extends Model {

    const cast = 'regular-fixed-quantity-confirmation';


    public static function getFixedQuantity($params)
    {
        $key = self::cast.json_encode($params->query());
        return Helper::storageCache($key, function () use ($params){
            $query = self::where(function ($query){
                $query->where('is_actual',Constant::IS_NOL);
            });

            if($params->withTrashed == 'true') $query->withTrashed();
            if($params->dropdown == Constant::IS_ACTIVE) {
                $params->limit = null;
                $params->page = 1;
            }

            $data = $query->paginate($params->limit ?? null);
            return [
                'items' => $data->items(),
                'attributes' => [
                    'total' => $data->total(),
                    'current_page' => $data->currentPage(),
                    'from' => $data->currentPage(),
                    'per_page' => (int) $data->perPage(),
                ]
            ];
        });
    }

    public static function noPackaging($params)
    {
        try {

            Helper::requireParams([
                'id',

            ]);

            $check = RegularFixedActualContainer::select('etd_jkt','code_consignee')->whereIn('id',$params->id)
            ->groupBy('etd_jkt','code_consignee')
            ->get()
            ->toArray();

            if(count($check) > 1) throw new \Exception("etd jkt & code consignee not same", 400);

            $data = RegularFixedActualContainer::whereIn('id',$params->id)->get()->toArray();
            if(count($data) == 0) throw new \Exception("Data not found", 400);

            $tanggal = $check[0]['etd_jkt'];
            $code_consignee = $check[0]['code_consignee'];

            return [
                "items" => [
                    'id' => $params->id,
                    'etd_jkt' => $tanggal,
                    'code_consignee' => $code_consignee,
                ]
            ];

        } catch (\Throwable $th) {
            throw $th;
        }
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
           $check = RegularFixedActualContainer::where('no_packaging',$params->no_packaging)->first();
           if($check) throw new \Exception("no_packaging registered", 400);

           $store = RegularFixedActualContainer::create([
                        "code_consignee" => $params->code_consignee,
                        "etd_ypmi" => Carbon::parse($params->etd_jkt)->subDays(4)->format('Y-m-d'),
                        "etd_wh" => Carbon::parse($params->etd_jkt)->subDays(2)->format('Y-m-d'),
                        "etd_jkt" => $params->etd_jkt,
                        "no_packaging" => $params->no_packaging,
                        "created_at" => now(),
            ]);


           self::where(function ($query) use ($params){
                   $query->whereIn('id',$params->id);
                   $query->where('code_consignee',$params->code_consignee);
                   $query->where('etd_jkt',$params->etd_jkt);
           })
           ->chunk(1000,function ($data) use ($params,$store){
                foreach ($data as $key => $item) {
                    $item->is_actual = Constant::IS_ACTIVE;
                    $item->id_fixed_actual_container = $store->id;
                    $item->save();
                }
           });

          if($is_trasaction) DB::commit();
        } catch (\Throwable $th) {
            if($is_trasaction) DB::rollBack();
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
            $data->is_actual = 1;
            $data->save();

            if($is_trasaction) DB::commit();
        } catch (\Throwable $th) {
            if($is_trasaction) DB::rollBack();
            throw $th;
        }
    }

    public static function getActualContainer($params) {
        $data = RegularFixedActualContainer::where(function ($query) use ($params){
            $category = $params->category ?? null;
            if($category) {
                $query->where($category, 'ilike', $params->kueri);
            }

            $filterdate = Helper::filterDate($params);
            if($params->date_start || $params->date_finish) $query->whereBetween('etd_jkt',$filterdate);


        })->paginate($params->limit ?? null);
        if(count($data) == 0) throw new \Exception("Data tidak ditemukan.", 400);

        $data->map(function ($item){
            $item->cust_name = $item->refConsignee->nick_name ?? null;

            unset(
                $item->refConsignee
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

        $delivery_plan = RegularDeliveryPlan::select('id_prospect_container')->whereIn('id_prospect_container',$params->id)->groupBy('id_prospect_container')->get()
        ->transform(function ($delivery){


            $id_prospect_container = $delivery->id_prospect_container;
            $data = RegularDeliveryPlan::where('id_prospect_container',$id_prospect_container)->get()->map(function ($item){
                $qty = $item->manyDeliveryPlanBox->count();
                $item->total_qty = $qty;
                unset(
                    $item->manyDeliveryPlanBox
                );
                return $item;
            })->toArray();

            $lsp = MstLsp::where('code_consignee',$data[0]['code_consignee'])
            ->where('id_type_delivery',2)
            ->first();


            $boxSize = 0;
            foreach ($data as $key => $item) {
                $boxSize += $item['total_qty'];
            }

            $mst_container = MstContainer::find(2);
            $capacity = $mst_container->capacity;
            $boxSizes = array_fill(0,$boxSize,1); // Create an array of 2400 boxes with size 1
            $containers = self::packBoxesIntoContainers($boxSizes,$capacity);
            
            $creation = [];
            foreach ($containers as $summary_box) {
                array_push($creation,[
                    'id_type_delivery' => 2,
                    'id_mot' => 1,
                    'id_container' => 2, //
                    'id_lsp' => $lsp->id ?? 2, // ini cari table mst lsp by code cogsingne
                    'summary_box' => $summary_box,
                    'code_consignee' => $data[0]['code_consignee'],
                    'etd_jkt' => $data[0]['etd_jkt'],
                    'etd_ypmi' => $data[0]['etd_ypmi'],
                    'etd_wh' => $data[0]['etd_wh'],
                    'measurement' => $mst_container->measurement ?? null,
                    'status' => $data[0]['status'],
                    'datasource' => $data[0]['datasource'],
                    'item_no' => $data[0]['item_no'],
                ]);
            }

            return $creation;
        })->toArray();

        foreach ($delivery_plan as $creations) {
            foreach ($creations as $item) {
                RegularFixedActualContainerCreation::create($item);
            }
        }

        if($is_transaction) DB::commit();
        } catch (\Throwable $th) {
             if($is_transaction) DB::rollBack();
             throw $th;
        }
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
        $data = RegularFixedActualContainerCreation::whereIn('id',$params->id)->paginate($params->limit ?? null);
        if(!$data) throw new \Exception("Data not found", 400);
        return [
            'items' => $data->getCollection()->transform(function($item){
                $item->cust_name = $item->refConsignee->nick_name;
                $item->type_delivery = $item->refMstTypeDelivery->name;
                $item->lsp = $item->refMstLsp->name;
                $item->net_weight = $item->refMstContainer->net_weight;
                $item->gross_weight = $item->refMstContainer->gross_weight;
                $item->container_type = $item->refMstContainer->container_type;

                unset(
                    $item->refConsignee,
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

    public static function generateNobooking($request,$is_transaction = true) {
        Helper::requireParams(['id']);
        if($is_transaction) DB::beginTransaction();
        try {
            $data = $request->all();
            $etdJkt = RegularFixedActualContainerCreation::select('etd_jkt','datasource')->whereIn('id',$request->id)->groupBy('etd_jkt','datasource')->get();
            if(!count($etdJkt)) throw new \Exception("Data not found", 400);
            if(count($etdJkt) > 1)  throw new \Exception("Invalid ETD JKT", 400);
            $data['no_booking'] = 'BOOK'.Carbon::parse($etdJkt[0]->etd_jkt)->format('dmY').mt_rand(10000,99999);
            $data['datasource'] = $etdJkt[0]->datasource;
            $data['booking_date'] = Carbon::now()->format('Y-m-d');
            $insert = RegularFixedShippingInstruction::create($data);
            RegularFixedActualContainerCreation::select('etd_jkt','datasource')->whereIn('id',$request->id)->update(['id_fixed_shipping_instruction'=>$insert->id]);
            if($is_transaction) DB::commit();
            return [
                'items' => ['id'=>$insert->id,'no_booking'=>$data['no_booking'],'etd_jkt'=>$etdJkt[0]->etd_jkt]
            ];
        } catch (\Throwable $th) {
            if($is_transaction) DB::rollBack();
            throw $th;
        }
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

}