<?php

namespace App\Query;

use App\Constants\Constant;
use App\Jobs\ContainerPlan;
use App\Models\MstConsignee;
use App\Models\RegularDeliveryPlanProspectContainer AS Model;
use App\ApiHelper as Helper;
use App\Models\MstBox;
use App\Models\MstContainer;
use App\Models\MstLsp;
use App\Models\MstPart;
use App\Models\MstTypeDelivery;
use App\Models\RegularDeliveryPlan;
use App\Models\RegularDeliveryPlanBox;
use App\Models\RegularDeliveryPlanProspectContainer;
use App\Models\RegularDeliveryPlanProspectContainerCreation;
use App\Models\RegularDeliveryPlanSet;
use App\Models\RegularProspectContainer;
use App\Models\RegularProspectContainerCreation;
use BaconQrCode\Common\Mode;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\Console\Output;


class QueryRegulerDeliveryPlanProspectContainer extends Model {

    const cast = 'regular-delivery-plan-prospect-container-container';

    public static function getAll($params) {
        $data = Model::where(function ($query) use ($params){
            $category = $params->category ?? null;
            $kueri = $params->kueri ?? null;
            if($category) {
                if($category == 'cust_name'){
                    $query->with('refConsignee')->whereRelation('refConsignee', 'nick_name', $kueri)->get();
                }elseif ($category == 'etd_ypmi') {
                    $query->where('etd_ypmi', 'like', '%' . $kueri . '%');
                }elseif ($category == 'etd_wh') {
                    $query->where('etd_wh', 'like', '%' . $kueri . '%');
                }elseif ($category == 'etd_jkt') {
                    $query->where('etd_jkt', 'like', '%' . $kueri . '%');
                } else {
                    $query->where($category, 'ilike', $kueri);
                }
            }

            //$filterdate = Helper::filterDate($params);
            // if($params->date_start || $params->date_finish)
            //     $query->whereBetween('etd_jkt',[$params->date_start, $params->date_finish]);


            $date_from = str_replace('-','',$params->date_from);
            $date_to = str_replace('-','',$params->date_to);
            if($params->date_from || $params->date_to) $query->whereBetween('etd_jkt',[$date_from, $date_to]);

            if($params->is_prospect == 0)
                $query->whereIn('is_prospect', [0,99]);
            else
                $query->where('is_prospect', $params->is_prospect);

        })->paginate($params->limit ?? null);
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
            $type_delivery = MstTypeDelivery::where('id', $item->id_type_delivery)->first();

            $item->cust_name = $item->refConsignee->nick_name ?? null;
            $item->type_delivery = $type_delivery !== null ? (str_contains($type_delivery->name, 'SEA') ? 'SEA' : 'AIR') : null;
            $item->mot = $item->refMot->name ?? null;
            $item->status = $item->is_prospect;
            if($item->is_prospect == 1)
                $item->status_desc = 'Done Prospect Container';
            elseif($item->is_prospect == 0)
                $item->status_desc = 'Prospect Container yet';
            elseif($item->is_prospect == 99)
                $item->status_desc = 'Waiting Prospect Container';

            unset(
                $item->refConsignee,
                $item->refMot,
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
        $data = RegularDeliveryPlanBox::select('regular_delivery_plan_box.id_prospect_container_creation',
                    'b.part_set',
                    DB::raw("string_agg(DISTINCT regular_delivery_plan_box.id_regular_delivery_plan::character varying, ',') as id_delivery_plan"),
                    DB::raw("string_agg(DISTINCT a.code_consignee::character varying, ',') as code_consignee"),
                    DB::raw("string_agg(DISTINCT a.cust_item_no::character varying, ',') as cust_item_no"),
                    DB::raw("string_agg(DISTINCT a.order_no::character varying, ',') as order_no"),
                    DB::raw("string_agg(DISTINCT a.qty::character varying, ',') as qty"),
                    DB::raw("string_agg(DISTINCT a.etd_ypmi::character varying, ',') as etd_ypmi"),
                    DB::raw("string_agg(DISTINCT a.etd_wh::character varying, ',') as etd_wh"),
                    DB::raw("string_agg(DISTINCT a.etd_jkt::character varying, ',') as etd_jkt"),
                    DB::raw("string_agg(DISTINCT a.item_no::character varying, ',') as item_no"),
                    DB::raw("string_agg(DISTINCT b.part_set::character varying, ',') as part_set"),
                    DB::raw("string_agg(DISTINCT b.id_box::character varying, ',') as id_box"),
                    DB::raw("string_agg(DISTINCT c.nick_name::character varying, ',') as cust_name"),
                    DB::raw("string_agg(DISTINCT d.description::character varying, ',') as item_name"),
                    DB::raw("string_agg(DISTINCT e.item_no::character varying, ',') as item_no_set"),
                    )
                    ->where(function($query) use($params, $id) {
                        $query->where('regular_delivery_plan_box.id_prospect_container_creation', $id);

                        if($params->kueri) $query->where('c.nick_name',"like", "%$params->kueri%")
                                                ->orWhere('a.cust_item_no',"like", "%$params->kueri%")
                                                ->orWhere('a.item_no',"like", '%' . str_replace('-', '', $params->kueri) . '%')
                                                ->orWhere('d.description',"like", "%$params->kueri%")
                                                ->orWhere('a.etd_ypmi',"like", "%$params->kueri%")
                                                ->orWhere('a.etd_jkt',"like", "%$params->kueri%")
                                                ->orWhere('a.etd_wh',"like", "%$params->kueri%")
                                                ->orWhere('a.qty',"like", "%$params->kueri%")
                                                ->orWhere('a.order_no',"like", "%$params->kueri%");
                    })
                    ->leftJoin('regular_delivery_plan as a','a.id','regular_delivery_plan_box.id_regular_delivery_plan')
                    ->leftJoin('mst_box as b','a.item_no','b.item_no')
                    ->leftJoin('mst_consignee as c','c.code','a.code_consignee')
                    ->leftJoin('mst_part as d','d.code_consignee','c.code')
                    ->leftJoin('regular_delivery_plan_set as e','e.id_delivery_plan','regular_delivery_plan_box.id_regular_delivery_plan')
                    ->groupBy('regular_delivery_plan_box.id_prospect_container_creation','a.etd_jkt','b.part_set','a.order_no','a.id')
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

            $box_result = self::getCountBox((int)$item->id_delivery_plan, $item->id_prospect_container_creation);
            
            $qty_result = explode(',',$item->qty);
            
            $item->item_name = $itemname;
            $item->item_no = $item_no;
            $item->cust_name = $custname;
            $item->qty = $qty_result;
            $item->box = $box_result;
            unset(
                $item->refRegularDeliveryPlan,
                $item->refRegularDeliveryPlanProspectContainerCreation,
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

    public static function getCountBox($id, $id_prospect_container_creation){
        $data = RegularDeliveryPlanBox::select('id_box', DB::raw('count(*) as jml'))
                ->where('id_regular_delivery_plan', $id)
                ->where('id_prospect_container_creation', $id_prospect_container_creation)
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

        $check_type_delivery = [];
        foreach ($delivery_plan as $key => $check) {
            $check_type_delivery[] = $check[$key]['id_type_delivery'];
        }
        if(count(array_unique($check_type_delivery)) !== 1) throw new \Exception("Type delivery not same", 400);

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
            $id = [];
            $id_prospect_container_creation = [];
            $id_prospect_container = [];
            foreach ($params->data as $key => $value) {
                $id[] = $value['id'];
                $id_prospect_container_creation[] = $value['id_prospect_container_creation'];
                $id_prospect_container[] = $value['id_prospect_container'];
            }

            $check = RegularDeliveryPlan::select('id_prospect_container_creation')
                ->with('manyDeliveryPlanBox')
                ->whereIn('id', $id)
                ->groupBy('id_prospect_container_creation')
                ->get();
            if(count($check) > 1) throw new \Exception("Code consignee, ETD JKT and datasource not same", 400);

            $delivery_plan_box = RegularDeliveryPlanBox::whereIn('id_prospect_container_creation', $id_prospect_container_creation)
                                    ->whereIn('id_regular_delivery_plan', $id)
                                    ->get();
            $count_delivery_plan_box = $delivery_plan_box->count();

            $prospectquery = RegularDeliveryPlanProspectContainerCreation::query();
            $prospect_previous = $prospectquery->whereIn('id',$id_prospect_container_creation)->orderBy('iteration','asc')->first();
            $nextprospect = $prospectquery->where(function ($query) use ($prospect_previous){
               $query->where('code_consignee',$prospect_previous->code_consignee);
               $query->where('datasource',$prospect_previous->datasource);
               $query->where('iteration', $prospect_previous->iteration+1);
               $query->where('etd_jkt',Carbon::parse($prospect_previous->etd_jkt)->format('Y-m-d'));
            })->first();

            if ($nextprospect) {
                if ($count_delivery_plan_box <= $nextprospect->kuota) {
                    $prospect_previous->update(['summary_box' => $prospect_previous->summary_box - $count_delivery_plan_box]);
                    $space = $nextprospect->kuota - $nextprospect->summary_box;
                    if($count_delivery_plan_box < $space) {
                        $nextprospect->update([
                            'summary_box' => $nextprospect->summary_box + $count_delivery_plan_box
                        ]);
                        foreach ($delivery_plan_box as $value) {
                            $value->update(['id_prospect_container_creation' => $nextprospect->id]);
                        }
                    } else {
                        $nextprospect->update([
                            'summary_box' => $nextprospect->summary_box + $space
                        ]);
                        foreach ($delivery_plan_box->take($space) as $value) {
                            $value->update(['id_prospect_container_creation' => $nextprospect->id]);
                        }

                        $sisa_count_delivery_plan_box = $count_delivery_plan_box - $space;

                        $creation['id_type_delivery'] = $nextprospect->id_type_delivery;
                        $creation['id_mot'] = $nextprospect->id_mot;
                        $creation['id_container'] = $nextprospect->id_container;
                        $creation['id_lsp'] =  $nextprospect->id_lsp;
                        $creation['summary_box'] = $sisa_count_delivery_plan_box;
                        $creation['code_consignee'] = $nextprospect->code_consignee;
                        $creation['etd_jkt'] = $nextprospect->etd_jkt;
                        $creation['etd_ypmi'] = $nextprospect->etd_ypmi;
                        $creation['etd_wh'] = $nextprospect->etd_wh;
                        $creation['measurement'] = $nextprospect->measurement;
                        $creation['iteration'] = $nextprospect->iteration+1;
                        $creation['id_prospect_container'] = $nextprospect->id_prospect_container;
                        $creation['kuota'] = $nextprospect->kuota;
                        $ins = $prospectquery->create($creation);
                        RegularDeliveryPlan::whereIn('id',$id)->update(['id_prospect_container_creation'=>$ins->id]);
                        foreach ($delivery_plan_box as $value) {
                            $value->update(['id_prospect_container_creation' => $ins->id]);
                        }
                    }
                } else {
                    $creation['id_type_delivery'] = $nextprospect->id_type_delivery;
                    $creation['id_mot'] = $nextprospect->id_mot;
                    $creation['id_container'] = $nextprospect->id_container;
                    $creation['id_lsp'] =  $nextprospect->id_lsp;
                    $creation['summary_box'] = $count_delivery_plan_box;
                    $creation['code_consignee'] = $nextprospect->code_consignee;
                    $creation['etd_jkt'] = $nextprospect->etd_jkt;
                    $creation['etd_ypmi'] = $nextprospect->etd_ypmi;
                    $creation['etd_wh'] = $nextprospect->etd_wh;
                    $creation['measurement'] = $nextprospect->measurement;
                    $creation['iteration'] = $nextprospect->iteration+1;
                    $creation['id_prospect_container'] = $nextprospect->id_prospect_container;
                    $creation['kuota'] = $nextprospect->kuota;
                    $ins = $prospectquery->create($creation);
                    RegularDeliveryPlan::whereIn('id',$id)->update(['id_prospect_container_creation'=>$ins->id]);
                    foreach ($delivery_plan_box as $value) {
                        $value->update(['id_prospect_container_creation' => $ins->id]);
                    }
                }
            } else {
                $creation['id_type_delivery'] = $prospect_previous->id_type_delivery;
                $creation['id_mot'] = $prospect_previous->id_mot;
                $creation['id_container'] = $prospect_previous->id_container;
                $creation['id_lsp'] =  $prospect_previous->id_lsp;
                $creation['summary_box'] = $count_delivery_plan_box;
                $creation['code_consignee'] = $prospect_previous->code_consignee;
                $creation['etd_jkt'] = $prospect_previous->etd_jkt;
                $creation['etd_ypmi'] = $prospect_previous->etd_ypmi;
                $creation['etd_wh'] = $prospect_previous->etd_wh;
                $creation['measurement'] = $prospect_previous->measurement;
                $creation['iteration'] = $prospect_previous->iteration+1;
                $creation['id_prospect_container'] = $prospect_previous->id_prospect_container;
                $creation['kuota'] = $prospect_previous->kuota;
                $ins = $prospectquery->create($creation);
                RegularDeliveryPlan::whereIn('id',$id)->update(['id_prospect_container_creation'=>$ins->id]);
                foreach ($delivery_plan_box as $value) {
                    $value->update(['id_prospect_container_creation' => $ins->id]);
                }
            }

            if($is_transaction) DB::commit();
        } catch (\Throwable $th) {
            if($is_transaction) DB::rollBack();
            throw $th;
        }
    }

    public static function detail($params)
    {
        $data = RegularDeliveryPlanProspectContainerCreation::whereIn('id_prospect_container',$params->id)
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
            'items' => $data->getCollection()->transform(function($item) use($params){

                $box = RegularDeliveryPlanBox::with('refBox')->where('id_prospect_container_creation', $item->id)->get();

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
                        $count_meas += (($set->refBox->length * $set->refBox->width * $set->refBox->height) / 1000000000);
                    } else {
                        $count_net_weight = $box_item->refBox->unit_weight_gr;
                        $count_outer_carton_weight = $box_item->refBox->outer_carton_weight;
                        $count_meas += (($box_item->refBox->length * $box_item->refBox->width * $box_item->refBox->height) / 1000000000);
                        $total_net_weight += ($count_net_weight * $box_item->qty_pcs_box)/1000;
                        $total_gross_weight += (($count_net_weight * $box_item->qty_pcs_box)/1000) + $count_outer_carton_weight;
                    }
                }

                $item->cust_name = $item->refRegularDeliveryPlanPropspectContainer->refConsignee->nick_name;
                $item->id_type_delivery = $item->id_type_delivery;
                $item->type_delivery = $item->refMstTypeDelivery->name;
                $item->lsp = $item->refMstLsp->name;
                $item->id_mot = $item->refMstMot->id;
                $item->net_weight = number_format($total_net_weight, 2);
                $item->gross_weight = number_format($total_gross_weight, 2);
                $item->measurement = number_format($count_meas,3);
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

    public static function detailAir($params)
    {
        $data = RegularDeliveryPlanProspectContainerCreation::whereIn('id_prospect_container',$params->id)->where('id_type_delivery', 4)->paginate($params->limit ?? null);
        if(!$data) throw new \Exception("Data not found", 400);
        return [
            'items' => $data->getCollection()->transform(function($item){
                $item->cust_name = $item->refRegularDeliveryPlanPropspectContainer->refConsignee->nick_name;
                $item->lsp = $item->refMstLsp->name;
                $item->id_mot = $item->refMstMot->id;
                $item->net_weight = $item->refMstContainer->net_weight;
                $item->gross_weight = $item->refMstContainer->gross_weight;
                $item->measurement = $item->refMstContainer->measurement;
                $item->container_type = $item->refMstContainer->container_type;

                unset(
                    $item->refRegularDeliveryPlanPropspectContainer,
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

    public static function simulationContainer($params){

        $data = RegularDeliveryPlanProspectContainerCreation::where('id', $params->id)->first();

        $container = MstContainer::find($data->id_container);
        $plan_box = RegularDeliveryPlanBox::select('id_regular_delivery_plan',
            'id_box', DB::raw('count(id_box) as count_box'))
            ->where('id_prospect_container_creation', $params->id)
            ->groupBy('id_box', 'id_regular_delivery_plan')
            ->orderBy('count_box','desc')
            ->get()
            ->map(function ($item, $index){
                return [
                    'id_delivery_plan' => $item->id_regular_delivery_plan,
                    'label' => $item->refBox->no_box,
                    'w' =>  floatval($item->refBox->width/1000),
                    'h' => floatval($item->refBox->height/1000),
                    'l' => floatval($item->refBox->length/1000),
                    'q' => $item->count_box,
                    'priority' => $index + 1,
                    'stackingCapacity' => $item->refBox->stack_capacity,
                    'rotations' => [
                        'base'
                    ],
                ];
            });

        $route = MstLsp::where('code_consignee', $data->code_consignee)
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
                'colis' => $plan_box
            ]
        ];
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
        ->orderBy('count_box','desc')
        ->get()
        ->map(function ($item, $index){
            return [
                'id_delivery_plan' => $item->id_regular_delivery_plan,
                'item_no' => $item->refRegularDeliveryPlan->item_no,
                'label' => $item->refBox->no_box,
                'w' => floatval($item->refBox->width/1000),
                'h' => floatval($item->refBox->height/1000),
                'l' => floatval($item->refBox->length/1000),
                'q' => $item->count_box,
                'v' => round(floatval($item->refBox->width/1000) * floatval($item->refBox->height/1000) * floatval($item->refBox->length/1000),2),
                'forkside' => $item->refBox->fork_side,
                'forklength' => $item->refBox->fork_length,
                'priority' => $index + 1,
                'stackingCapacity' => $item->refBox->stack_capacity,
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

    public static function creationSimulation($params){

        DB::beginTransaction();
        try {

            $prospect_container = Model::find($params->id);
            $mst_container = MstContainer::where('id', $params->id_container)->first();
            $lsp = MstLsp::where('code_consignee',$prospect_container->code_consignee)
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
                $stackCapacities[] = $value['stackingCapacity'];
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
                    'code_consignee' => $prospect_container->code_consignee,
                    'etd_jkt' => $prospect_container->etd_jkt,
                    'etd_ypmi' => $prospect_container->etd_ypmi,
                    'etd_wh' => $prospect_container->etd_wh,
                    'measurement' => $mst_container->measurement ?? 0,
                    'iteration' => $index,
                    'id_prospect_container' => $params->id,
                    'status_bml' => 0,
                    'datasource' => $params->datasource,
                    'kuota' => $total_boxes
                ];
                RegularProspectContainerCreation::create($creation);

                $sum = $qty - $summary_box;
                $qty = $sum;
                $index = $index + 1;
            }

            $upd = RegularProspectContainer::find($params->id);
            $upd->is_prospect = 99;
            $upd->save();

            $set = [
                'id' => $params->id,
                'colis' => $colis,
            ];

           DB::commit();

           ContainerPlan::dispatch($set);

        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }

    public static function creationCalculation($params)
    {
        DB::beginTransaction();
        try {

            $prospect_container = Model::find($params->id);
            $lsp = MstLsp::where('code_consignee',$prospect_container->code_consignee)
                ->where('id_type_delivery', ($prospect_container->id_type_delivery ?? 1))
                ->first();
            
            $plan = RegularDeliveryPlan::select('id','code_consignee','item_no')
                ->where('id_prospect_container', $params->id)
                ->orderBy('id', 'asc')
                ->get();

            $delivery_plan = [];
            foreach ($plan as $item){
                $delivery_plan[] = $item->id;
            }
            
            $delivery_plan_box = RegularDeliveryPlanBox::select('id_regular_delivery_plan',
                'id_box', DB::raw('count(id_box) as count_box'),DB::raw("SUM(regular_delivery_plan_box.qty_pcs_box) as sum_qty"))
            ->whereIn('id_regular_delivery_plan',$delivery_plan)
            ->groupBy('id_box', 'id_regular_delivery_plan')
            ->orderBy('count_box','desc')
            ->get()
            ->map(function ($item, $index){
                
                $row_length = $item->refBox->fork_side == 'Width' ? ($item->refBox->width * (int)ceil($item->count_box / 4)) : ($item->refBox->length * (int)ceil($item->count_box / 4));
                $count_box = $item->count_box;
                $box = RegularDeliveryPlanBox::where('id_regular_delivery_plan', $item->id_regular_delivery_plan)
                                                ->whereNull('id_prospect_container_creation')
                                                ->orderBy('id', 'asc')
                                                ->get();
                $box_set_count = count($box);

                return [
                    'id_delivery_plan' => $item->id_regular_delivery_plan,
                    'item_no' => $item->refBox->item_no,
                    'label' => $item->refBox->no_box,
                    'width' =>  $item->refBox->width,
                    'length' => $item->refBox->length,
                    'count_box' => $count_box,
                    'sum_qty' => $item->sum_qty,
                    'priority' => $index + 1,
                    'forkside' => $item->refBox->fork_side,
                    'stackingCapacity' => $item->refBox->stack_capacity,
                    'row' => (int)ceil($count_box / 4),
                    'first_row_length' => $item->refBox->fork_side == 'Width' ? $item->refBox->width : $item->refBox->length,
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
            foreach ($delivery_plan_box as $key => $value) {
                $box_set_count += $value['box_set_count'];
                $sum_row_length += $value['row_length'];
                $sum_count_box += $value['count_box'];
                $sum_qty_box[] = $value['sum_qty'];
                $first_row_length[] = $delivery_plan_box[$key]['first_row_length'];
                $first_row[] = $delivery_plan_box[$key]['row'];
                $first_count_box[] = $delivery_plan_box[$key]['count_box'];
                $row_length[] = $delivery_plan_box[$key]['row_length'];
                $count_box[] = $delivery_plan_box[$key]['count_box'];
                $big_row_length[] = $delivery_plan_box[$key]['first_row_length'] * $delivery_plan_box[$key]['row'];
            }

            $creation = [
                'id_type_delivery' => $lsp->id_type_delivery,
                'id_mot' => $lsp->refTypeDelivery->id_mot,
                'id_lsp' => $lsp->id,
                'code_consignee' => $prospect_container->code_consignee,
                'etd_jkt' => $prospect_container->etd_jkt,
                'etd_ypmi' => $prospect_container->etd_ypmi,
                'etd_wh' => $prospect_container->etd_wh,
                'id_prospect_container' => $params->id,
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
                    $creation['id_container'] = 3;
                    $creation['measurement'] = MstContainer::find(2)->measurement ?? 0;
                    $creation['summary_box'] = (int)$send_summary_box[$i-1]['summary_box'];
                    $creation['iteration'] = $i;
                    $creation['space'] = (int)$send_summary_box[$i-1]['space'];
                }

                $check = RegularProspectContainerCreation::where('id_prospect_container', $prospect_container->id)->where('space', null)->first();
                if($check) $check->forceDelete();
                RegularProspectContainerCreation::create($creation);
                $sum_row_length = $sum_row_length - 12031;
            }
            
            $upd = RegularProspectContainer::find($params->id);
            $upd->is_prospect = 99;
            $upd->save();

            $set = [
                'id' => $params->id,
                'colis' => $delivery_plan_box,
                'box_set_count' => $box_set_count,
                'type' => 'single'
            ];

                
            ContainerPlan::dispatch($set);

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

    public static function creationDelete($params, $id)
    {
        DB::beginTransaction();
        try {

            $data = RegularDeliveryPlanProspectContainerCreation::find($id);
            if($data->summary_box !== 0) throw new \Exception("Data tidak dapat dihapus.", 400);
            if($data->id_shipping_instruction !== null) throw new \Exception("Data tidak dapat dihapus.", 400);

            $data->delete();

            DB::commit();

        } catch (\Throwable $th) {
            DB::rollBack();
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
                $boxVolume = round($boxVolume);
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
}
