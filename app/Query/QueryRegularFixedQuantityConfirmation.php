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
use App\Models\RegularFixedQuantityConfirmation;
use App\Models\RegularFixedQuantityConfirmationBox;
use App\Models\RegularFixedShippingInstruction;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class QueryRegularFixedQuantityConfirmation extends Model {

    const cast = 'regular-fixed-quantity-confirmation';


    public static function getFixedQuantity($params)
    {

        $key = self::cast.json_encode($params->query());
        return Helper::storageCache($key, function () use ($params){
            $query = self::where(function ($query) use ($params){

                $query->where('is_actual',Constant::IS_NOL);
                $category = $params->category ?? null;
                if($category) {
                    if($category == 'cust_name'){
                        $query->with('refConsignee')->whereRelation('refConsignee', 'nick_name', $params->kueri)->get();
                    } else {
                        $query->where($category, 'ilike', $params->kueri);
                    }
                }

            });

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
                            if ($item->refRegularDeliveryPlan->refRegularStockConfirmation->status_instock == 1 || $item->refRegularDeliveryPlan->refRegularStockConfirmation->status_instock == 2 && $item->refRegularDeliveryPlan->refRegularStockConfirmation->status_outstock == 1 || $item->refRegularDeliveryPlan->refRegularStockConfirmation->status_outstock == 2 && $item->refRegularDeliveryPlan->refRegularStockConfirmation->in_dc = 0 && $item->refRegularDeliveryPlan->refRegularStockConfirmation->in_wh == 0) $status = 'In Process';
                            if ($item->refRegularDeliveryPlan->refRegularStockConfirmation->status_instock == 3 && $item->refRegularDeliveryPlan->refRegularStockConfirmation->status_outstock == 3) $status = 'Finish Production';
                        } else {
                            $status = 'Out Of Date';
                        }
                    }

                    $item->status_desc = $status ?? null;
                    $item->customer_name = $item->refConsignee->nick_name;
                    $item->item_name = $item->refRegularDeliveryPlan->refPart->description ?? null;
                    $item->production = $item->production ?? null;
                    $item->in_dc = $item->in_dc ?? null;
                    $item->in_wh = $item->in_wh ?? null;

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

            $check = Model::select('etd_jkt','code_consignee','datasource')->whereIn('id',$params->id)
                ->groupBy('etd_jkt','code_consignee','datasource')
                ->get()
                ->toArray();

            if(count($check) > 1) throw new \Exception("ETD JKT and Customer name not same", 400);

            $data = Model::select(DB::raw('count(order_no) as total'),'order_no')->whereIn('id',$params->id)
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
                   $query->where('etd_jkt',str_replace('-','',$params->etd_jkt));
                   $query->where('datasource','PYMAC');
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
                if($category == 'cust_name'){
                    $query->with('refConsignee')->whereRelation('refConsignee', 'nick_name', $params->value)->get();
                } else {
                    $query->where($category, 'ilike', $params->value);
                }
            }

            if($params->date_start || $params->date_finish)
                $query->whereBetween('etd_jkt',[$params->date_start, $params->date_finish]);


        })->where('is_actual', $params->is_actual ?? 0)
            ->paginate($params->limit ?? null);

        $data->map(function ($item){
            $item->cust_name = $item->refConsignee->nick_name ?? null;
            //$item->status_desc = 'Confirmed';

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

        $fixed_quantity_confirmation = RegularFixedQuantityConfirmation::select('id_fixed_actual_container')->whereIn('id_fixed_actual_container',$params->id)->groupBy('id_fixed_actual_container')->get()
        ->transform(function ($delivery){
            $id_fixed_actual_container = $delivery->id_fixed_actual_container;
            $data = RegularFixedQuantityConfirmation::where('id_fixed_actual_container',$id_fixed_actual_container)->get()->map(function ($item){
                $qty = $item->refRegularDeliveryPlan->manyDeliveryPlanBox->count();
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
                    'id_fixed_actual_container' => $data[0]['id_fixed_actual_container'],
                ]);
            }

            return $creation;
        })->toArray();

        $actual_container_creation = [];
        foreach ($fixed_quantity_confirmation as $creations) {
            foreach ($creations as $item) {
                $store= RegularFixedActualContainerCreation::create($item);
                $actual_container_creation[] = $store;
            }
        }

        foreach ($actual_container_creation as $val) {
            Model::where('id_fixed_actual_container',$val->id_fixed_actual_container)
                ->update([
                    'id_fixed_actual_container_creation' => $val->id
                ]);
            
            RegularFixedActualContainer::where('id', $val->id_fixed_actual_container)
                ->update([
                    'is_actual' => 1
                ]);
        }

        Model::whereIn('id', $params->id)->update(['is_actual' => Constant::IS_ACTUAL]);

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
        $data = RegularFixedActualContainerCreation::whereIn('id_fixed_actual_container',$params->id)->paginate($params->limit ?? null);
        if(!$data) throw new \Exception("Data not found", 400);
        return [
            'items' => $data->getCollection()->transform(function($item){
                $item->cust_name = $item->refMstConsignee->nick_name;
                $item->type_delivery = $item->refMstTypeDelivery->name;
                $item->lsp = $item->refMstLsp->name;
                $item->net_weight = $item->refMstContainer->net_weight;
                $item->gross_weight = $item->refMstContainer->gross_weight;
                $item->container_type = $item->refMstContainer->container_type;
                $item->load_extension_length = $item->refMstContainer->long;
                $item->load_extension_width = $item->refMstContainer->wide;
                $item->load_extension_height = $item->refMstContainer->height;
                $item->load_qty = "100";
                $item->container_name = $item->refMstContainer->container_type." ".$item->refMstContainer->container_value;

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

                unset(
                    $item->refRegularDeliveryPlan
                );

                return $item;
            }),
            'last_page' => $data->lastPage()
        ];
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

    public static function generateNobooking($request,$is_transaction = true)
    {
        Helper::requireParams(['id']);
        if($is_transaction) DB::beginTransaction();
        try {
            $data = $request->all();
            $etdJkt = RegularFixedActualContainerCreation::select('etd_jkt','datasource')->whereIn('id_fixed_actual_container',$request->id)->groupBy('etd_jkt','datasource')->get();
            if(!count($etdJkt)) throw new \Exception("Data not found", 400);
            if(count($etdJkt) > 1)  throw new \Exception("Invalid ETD JKT", 400);
            $data['no_booking'] = 'BOOK'.Carbon::parse($etdJkt[0]->etd_jkt)->format('dmY').mt_rand(10000,99999);
            $data['datasource'] = $etdJkt[0]->datasource;
            $data['booking_date'] = Carbon::now()->format('Y-m-d');
            $insert = RegularFixedShippingInstruction::create($data);
            RegularFixedActualContainerCreation::whereIn('id_fixed_actual_container',$request->id)->update(['id_fixed_shipping_instruction'=>$insert->id]);
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

    public static function savebooking($request,$is_transaction = true) {
        Helper::requireParams(['id']);
        if($is_transaction) DB::beginTransaction();
        try {
            $res = RegularFixedShippingInstruction::find($request->id);
            $res->status = Constant::STS_BOOK_FINISH;
            $res->save();
            $actual_creation = RegularFixedActualContainerCreation::where('id_fixed_shipping_instruction', $res->id)->get();
            foreach ($actual_creation as $key => $value) {
                RegularFixedActualContainer::where('id', $value->id_fixed_actual_container)->update(['is_actual' => 2]);
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
            if($category) {
                if($category == 'cust_name'){
                    $query->with('refConsignee')->whereRelation('refConsignee', 'nick_name', $params->value)->get();
                } else {
                    $query->where($category, 'ilike', $params->value);
                }
            }

            if($params->date_start || $params->date_finish)
                $query->whereBetween('etd_jkt',[$params->date_start, $params->date_finish]);


        })->paginate($params->limit ?? null);

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
            $data = RegularFixedQuantityConfirmation::where('id_fixed_actual_container',$id)->get();

            Pdf::loadView('pdf.casemarks.casemarks_doc',[
              'data' => $data
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
            $data = RegularFixedActualContainer::find($id);

            Pdf::loadView('pdf.packaging.packaging_doc',[
              'data' => $data
            ])
            ->save($pathToFile)
            ->setPaper('A4','potrait')
            ->download($filename);

          } catch (\Throwable $th) {
              return Helper::setErrorResponse($th);
          }
    }

}
