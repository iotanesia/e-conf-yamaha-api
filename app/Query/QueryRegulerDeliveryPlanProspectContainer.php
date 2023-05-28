<?php

namespace App\Query;

use App\Constants\Constant;
use App\Models\MstConsignee;
use App\Models\RegularDeliveryPlanProspectContainer AS Model;
use App\ApiHelper as Helper;
use App\Models\MstContainer;
use App\Models\MstLsp;
use App\Models\RegularDeliveryPlan;
use App\Models\RegularDeliveryPlanBox;
use App\Models\RegularDeliveryPlanProspectContainerCreation;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class QueryRegulerDeliveryPlanProspectContainer extends Model {

    const cast = 'regular-delivery-plan-prospect-container-container';

    public static function getAll($params) {
        $data = Model::where(function ($query) use ($params){
            $category = $params->category ?? null;
            if($category) {
                if($category == 'cust_name'){
                    $query->with('refConsignee')->whereRelation('refConsignee', 'nick_name', $params->value)->get();
                } else {
                    $query->where($category, 'ilike', $params->value);
                }
            }

            //$filterdate = Helper::filterDate($params);
            if($params->date_start || $params->date_finish)
                $query->whereBetween('etd_jkt',[$params->date_start, $params->date_finish]);

        })->where('is_prospect', $params->is_prospect ?? 1)
            ->paginate($params->limit ?? null);
        //if(count($data) == 0) throw new \Exception("Data tidak ditemukan.", 400);

//        $id_container = [];
//        foreach ($data as $value) {
//            $id_container[] = $value->id;
//        }
//
//        $creation = RegularDeliveryPlanProspectContainerCreation::whereIn('id_prospect_container', $id_container)->get();
//
//        $id_creation = [];
//        foreach ($creation as $value) {
//            $id_creation[] = $value->id;
//        }
//
//        $delivery_plan = RegularDeliveryPlan::whereIn('id_prospect_container_creation',$id_creation)->get();
//
//        if(count($delivery_plan) == 0) throw new \Exception("Data tidak ditemukan.", 400);
//
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

    public static function byIdProspectContainer($params,$id)
    {
        $data = RegularDeliveryPlan::where('id_prospect_container_creation',$id)
        ->where(function ($query) use ($params){
            $category = $params->category ?? null;
            if($category) {
                 $query->where($category, 'ilike', $params->kueri);
            }

            $filterdate = Helper::filterDate($params);
            if($params->date_start || $params->date_finish) $query->whereBetween('etd_jkt',$filterdate);
        })
        ->paginate($params->limit ?? null);

        if(count($data) == 0) throw new \Exception("Data tidak ditemukan.", 400);

        $data->transform(function ($item) use ($id){
            $item->item_name = $item->refPart->description ?? null;
            $item->cust_name = $item->refConsignee->nick_name ?? null;
            $regularOrderEntry = $item->refRegularOrderEntry;
            $item->regular_order_entry_period = $regularOrderEntry->period ?? null;
            $item->regular_order_entry_month = $regularOrderEntry->month ?? null;
            $item->regular_order_entry_year = $regularOrderEntry->year ?? null;
            $item->box = $item->manyDeliveryPlanBox->map(function ($item) use ($id)
            {
                return [
                    'id' => $item->id,
                    'id_prospect_container' => $id,
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


    public static function createionProcess($params,$is_transaction = true)
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

            $lsp = MstLsp::where('code_consignee',$data[0]['code_consignee'])->first();

            $boxSize = 0;
            foreach ($data as $key => $item) {
                $boxSize += $item['total_qty'];
            }


            $mst_container = MstContainer::find(2);
            $capacity = $mst_container->capacity;
            $boxSizes = array_fill(0,$boxSize,1);
            $containers = self::packBoxesIntoContainers($boxSizes,$capacity);
            $creation = [];
            foreach ($containers as $summary_box) {
                array_push($creation,[
                    'id_type_delivery' => $lsp->id_type_delivery,
                    'id_mot' => $lsp->refTypeDelivery->id_mot,
                    'id_container' => 2, //
                    'id_lsp' => $lsp->id, // ini cari table mst lsp by code cogsingne
                    'summary_box' => $summary_box,
                    'code_consignee' => $data[0]['code_consignee'],
                    'etd_jkt' => $data[0]['etd_jkt'],
                    'etd_ypmi' => $data[0]['etd_ypmi'],
                    'etd_wh' => $data[0]['etd_wh'],
                    'measurement' => $mst_container->measurement ?? null,
                    'iteration' => 1,
                    'id_prospect_container' => $data[0]['id_prospect_container'],
                ]);
            }

            return $creation;
        })->toArray();

        $prospect_container_creation = [];
        foreach ($delivery_plan as $creations) {
            foreach ($creations as $item) {
                $store = RegularDeliveryPlanProspectContainerCreation::create($item);
                $prospect_container_creation[] = $store;
            }
        }

        foreach ($prospect_container_creation as $val) {
            RegularDeliveryPlan::where('id_prospect_container',$val->id_prospect_container)
            ->update([
                'id_prospect_container_creation' => $val->id
            ]);
        }

        Model::whereIn('id', $params->id)->update(['is_prospect' => 1]);

        if($is_transaction) DB::commit();
        } catch (\Throwable $th) {
             if($is_transaction) DB::rollBack();
             throw $th;
        }
    }

    public static function createionMoveProcess($params,$is_transaction = true){

        if($is_transaction) DB::beginTransaction();
        try {
            Helper::requireParams([
                'id'
            ]);
            $check = RegularDeliveryPlan::select('id_prospect_container_creation')
                ->with('manyDeliveryPlanBox')
                ->whereIn('id', $params->id)
                ->groupBy('id_prospect_container_creation')
                ->get();
            if(count($check) > 1) throw new \Exception("Code consignee, ETD JKT and datasource not same", 400);
            $drp = $check[0];
            $prospect = RegularDeliveryPlanProspectContainerCreation::find($drp->id_prospect_container_creation);
            $nextprospect = RegularDeliveryPlanProspectContainerCreation::where(function ($query) use ($prospect){
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
                $creation['summary_box'] = RegularDeliveryPlanBox::whereIn('id_regular_delivery_plan',$params->id)->count() ?? 0;
                $creation['code_consignee'] = $prospect->code_consignee;
                $creation['etd_jkt'] = $prospect->etd_jkt;
                $creation['etd_ypmi'] = $prospect->etd_ypmi;
                $creation['etd_wh'] = $prospect->etd_wh;
                $creation['measurement'] = $prospect->measurement;
                $creation['iteration'] = $prospect->iteration+1;
                $creation['id_prospect_container'] = $prospect->id_prospect_container;
                $ins = RegularDeliveryPlanProspectContainerCreation::create($creation);
                RegularDeliveryPlan::whereIn('id',$params->id)->update(['id_prospect_container_creation'=>$ins->id]);
            }else
                RegularDeliveryPlan::whereIn('id',$params->id)->update(['id_prospect_container_creation'=>$nextprospect->id]);
            if($is_transaction) DB::commit();
        } catch (\Throwable $th) {
            if($is_transaction) DB::rollBack();
            throw $th;
        }
    }

    public static function detail($params)
    {
        $data = RegularDeliveryPlanProspectContainerCreation::whereIn('id_prospect_container',$params->id)->paginate($params->limit ?? null);
        if(!$data) throw new \Exception("Data not found", 400);
        return [
            'items' => $data->getCollection()->transform(function($item){
                $item->cust_name = $item->refRegularDeliveryPlanPropspectContainer->refConsignee->nick_name;
                $item->type_delivery = $item->refMstTypeDelivery->name;
                $item->id_type_delivery = $item->refMstTypeDelivery->id;
                $item->lsp = $item->refMstLsp->name;
                $item->id_mot = $item->refMstMot->id;
                $item->net_weight = $item->refMstContainer->net_weight;
                $item->gross_weight = $item->refMstContainer->gross_weight;
                $item->measurement = $item->refMstContainer->measurement;
                $item->container_type = $item->refMstContainer->container_type;

                unset(
                    $item->refRegularDeliveryPlanPropspectContainer,
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


    static function array_flatten($array) {
        $return = array();
        foreach ($array as $key => $value) {
            if (is_array($value)){
                $return = array_merge($return, self::array_flatten($value));
            } else {
                $return[$key] = $value;
            }
        }

        return $return;
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

    public static function loadCalculationPacking(){
        return null;
    }

    public static function fifoProcess($request,$is_transaction = true)
    {
        if($is_transaction) DB::beginTransaction();
        try {
            if($is_transaction) DB::commit();
        } catch (\Throwable $th) {
            if($is_transaction) DB::rollBack();
            throw $th;
        }
    }

    public static function simulation($params)
    {
        $plan = RegularDeliveryPlan::select('id','code_consignee')
            ->where('id_prospect_container', $params->id)
            ->orderBy('id', 'asc')
            ->get();
        $delivery_plan = [];
        foreach ($plan as $item){
            $delivery_plan[] = $item->id;
        }

        $container = MstContainer::find($params->id_container);
        $delivery_plan_box = RegularDeliveryPlanBox::select('id_regular_delivery_plan',
            'id_box', DB::raw('count(id_box) as count_box'))
        ->whereIn('id_regular_delivery_plan',$delivery_plan)
        ->groupBy('id_box', 'id_regular_delivery_plan')
        ->get()
        ->map(function ($item){
            return [
                'id_delivery_plan' => $item->id_regular_delivery_plan,
                'label' => $item->refBox->no_box,
                'w' =>  floatval($item->refBox->width/1000),
                'h' => floatval($item->refBox->height/1000),
                'l' => floatval($item->refBox->length/1000),
                'q' => $item->count_box,
                'priority' => 1,
                'stackingCapacity' => 0,
                'rotations' => [
                    'base'
                ]
            ];
        });

        $route = MstLsp::where('code_consignee',$plan[0]->code_consignee)
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'from' => 'jakarta',
                    'to' => $item->name,
                    'type' => 'dechargement'
                ];
            });

        return [
            'items' => [
                'container' => [
                    'w' => floatval(round($container->long,2)) ?? null,
                    'h' => floatval(round($container->height,2)) ?? null,
                    'l' => floatval(round($container->wide,2)) ?? null
                ],
                'routes' => $route,
                'colis' => $delivery_plan_box
            ]
        ];

    }

}
