<?php

namespace App\Query;

use App\Constants\Constant;
use App\ApiHelper as Helper;
use App\Models\MstBox;
use App\Models\MstConsignee;
use App\Models\MstPart;
use App\Models\MstShipment;
use App\Models\MstSignature;
use App\Models\RegularDeliveryPlan;
use App\Models\RegularDeliveryPlanBox;
use App\Models\RegularDeliveryPlanSet;
use App\Models\RegularFixedActualContainer;
use App\Models\RegularFixedActualContainerCreation;
use App\Models\RegularFixedPackingCreationNote;
use App\Models\RegularFixedQuantityConfirmation;
use App\Models\RegularFixedQuantityConfirmationBox;
use App\Models\RegularFixedShippingInstruction AS Model;
use App\Models\RegularFixedShippingInstruction;
use App\Models\RegularFixedShippingInstructionCreation;
use App\Models\RegularFixedShippingInstructionCreationDraft;
use App\Models\RegularFixedShippingInstructionRevision;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Barryvdh\DomPDF\Facade\Pdf;

class QueryRegularFixedShippingInstruction extends Model {

    const cast = 'regular-fixed-shipping-instruction';

    public static function shipping($params)
    {
        $data = Model::where(function ($query) use ($params){
            $query->whereNotNull('no_booking');
            $category = $params->category ?? null;
            $kueri = $params->kueri ?? null;
        
            if ($category && $kueri) {
                if ($category == 'cust_name') {
                    $query->whereHas('refFixedActualContainerCreation.refMstConsignee', function ($q) use ($kueri) {
                        $q->where('nick_name', 'like', '%' . $kueri . '%');
                    });
                } elseif ($category == 'packaging_no') {
                    $query->whereHas('refFixedActualContainerCreation.refFixedActualContainer', function ($q) use ($kueri) {
                        $q->where('no_packaging', 'like', '%' . $kueri . '%');
                    });
                } else {
                    $query->where('booking_date', 'like', '%' . $kueri . '%')
                        ->orWhere('no_booking', 'like', '%' . $kueri . '%');
                }
            }

            // $filterdate
            $date_from = str_replace('-','',$params->date_from);
            $date_to = str_replace('-','',$params->date_to);
            if($params->date_from || $params->date_to) $query->whereBetween('booking_date',[$date_from, $date_to]);
        })->paginate($params->limit ?? null);

        if(!$data) throw new \Exception("Data not found", 400);

        return [
            'items' => $data->getCollection()->transform(function($item){

                if($item->status == 1) $status = 'Confirm Booked';
                if($item->status == 2) $status = 'SI Created';
                if($item->status == 3) $status = 'Send To CC Spv';
                if($item->status == 4) $status = 'Send To CC Manager';
                if($item->status == 5) $status = 'Approve';
                if($item->status == 6) $status = 'Correction';
                if($item->status == 7) $status = 'Rejection';
                $item->status = $status;
                foreach($item->refFixedActualContainerCreation as $value){
                        $item->packaging = [$value->refFixedActualContainer->no_packaging ?? null] ;
                        $item->cust_name = [$value->refMstConsignee->nick_name ?? null] ;
                }
                unset(
                    $item->refFixedActualContainerCreation,
                );

                return $item;
            }),
            'last_page' => $data->lastPage()
        ];
    }

    public static function shippingCCspv($params)
    {
        $data = Model::where('status', 3)->paginate($params->limit ?? null);
        if(!$data) throw new \Exception("Data not found", 400);

        return [
            'items' => $data->getCollection()->transform(function($item){
                if($item->status == 1) $status = 'Confirm Booked';
                if($item->status == 2) $status = 'SI Created';
                if($item->status == 3) $status = 'Send To CC Spv';
                if($item->status == 4) $status = 'Send To CC Manager';
                if($item->status == 5) $status = 'Approve';
                if($item->status == 6) $status = 'Correction';
                if($item->status == 7) $status = 'Rejection';
                $item->status = $status;
                foreach($item->refFixedActualContainerCreation as $value){
                    $item->packaging = [$value->refFixedActualContainer->no_packaging ?? null] ;
                    $item->cust_name = [$value->refMstConsignee->nick_name ?? null] ;
                }
                unset(
                    $item->refFixedActualContainerCreation,
                );

                return $item;
            }),
            'last_page' => $data->lastPage()
        ];
    }

    public static function shippingCCman($params)
    {
        $data = Model::where('status', 4)->paginate($params->limit ?? null);
        if(!$data) throw new \Exception("Data not found", 400);

        return [
            'items' => $data->getCollection()->transform(function($item){
                if($item->status == 1) $status = 'Confirm Booked';
                if($item->status == 2) $status = 'SI Created';
                if($item->status == 3) $status = 'Send To CC Spv';
                if($item->status == 4) $status = 'Send To CC Manager';
                if($item->status == 5) $status = 'Approve';
                if($item->status == 6) $status = 'Correction';
                if($item->status == 7) $status = 'Rejection';
                $item->status = $status;
                foreach($item->refFixedActualContainerCreation as $value){
                    $item->packaging = [$value->refFixedActualContainer->no_packaging ?? null] ;
                    $item->cust_name = [$value->refMstConsignee->nick_name ?? null] ;
                }
                unset(
                    $item->refFixedActualContainerCreation,
                );

                return $item;
            }),
            'last_page' => $data->lastPage()
        ];
    }

    public static function shippingContainer($params,$id)
    {
        $data = RegularFixedActualContainerCreation::where('id_fixed_shipping_instruction',$id)
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
                $total_net_weight_mst = 0;
                $total_gross_weight = 0;
                $total_gross_weight_mst = 0;
                foreach ($box as $key => $box_item){
                    if ($box_item->refRegularDeliveryPlan->item_no == null) {
                        $master = [];
                        $check = [];
                        foreach ($box_item->refRegularDeliveryPlan->manyDeliveryPlanSet as $set) {
                            $master[] = $set->refBox->qty;
                            $check[] = $box->pluck('qty_pcs_box')->toArray()[$key];
                            $total_net_weight += ((($set->refBox->unit_weight_gr * ((array_sum($box->pluck('qty_pcs_box')->toArray()) / count($box) / count($box_item->refRegularDeliveryPlan->manyDeliveryPlanSet)))/1000)));
                            $total_gross_weight += (((($set->refBox->unit_weight_gr * ((array_sum($box->pluck('qty_pcs_box')->toArray()) / count($box)) / count($box_item->refRegularDeliveryPlan->manyDeliveryPlanSet)))/1000) + ($set->refBox->outer_carton_weight / count($box_item->refRegularDeliveryPlan->manyDeliveryPlanSet))));
                            $total_net_weight_mst += (($set->refBox->unit_weight_gr * $set->refBox->qty) / 1000);
                            $total_gross_weight_mst += ($set->refBox->unit_weight_gr * $set->refBox->qty / 1000) + ($set->refBox->outer_carton_weight / count($box_item->refRegularDeliveryPlan->manyDeliveryPlanSet));
                        } 
                        $res_check = (array_sum($check) / count($box_item->refRegularDeliveryPlan->manyDeliveryPlanSet)) / count($box_item->refRegularDeliveryPlan->manyDeliveryPlanSet);
                        $res_master =  array_sum($master) / count($box_item->refRegularDeliveryPlan->manyDeliveryPlanSet);
                        if ($res_check == $res_master) {
                            $total_net_weight = $total_net_weight_mst;
                            $total_gross_weight = $total_gross_weight_mst;
                        } else {
                            $total_net_weight = $total_net_weight;
                            $total_gross_weight = $total_gross_weight;
                        }
                        $count_meas += ($box_item->refMstBox->length * $box_item->refMstBox->width * $box_item->refMstBox->height) / 1000000000;
                    } else {
                        $count_net_weight = $box_item->refMstBox->unit_weight_gr;
                        $count_outer_carton_weight = $box_item->refMstBox->outer_carton_weight;
                        $count_meas += (($box_item->refMstBox->length * $box_item->refMstBox->width * $box_item->refMstBox->height) / 1000000000);
                        $total_net_weight += ($count_net_weight * $box_item->qty_pcs_box)/1000;
                        $total_gross_weight += (($count_net_weight * $box_item->qty_pcs_box)/1000) + $count_outer_carton_weight;
                    }
                }

                $item->cust_name = $item->refMstConsignee->nick_name;
                $item->id_type_delivery = $item->id_type_delivery;
                $item->type_delivery = $item->refMstTypeDelivery->name;
                $item->lsp = $item->refMstLsp->name;
                $item->net_weight = number_format($total_net_weight, 2);
                $item->gross_weight = number_format($total_gross_weight, 2);
                $item->measurement = number_format($count_meas,3);
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

    public static function shippingContainerDetail($params,$id)
    {
        $check = RegularFixedQuantityConfirmationBox::where('id_prospect_container_creation', $id)->first();

        if ($check->refRegularDeliveryPlan->item_no !== null) {
            $data = RegularFixedQuantityConfirmationBox::select('regular_fixed_quantity_confirmation_box.id_prospect_container_creation', 'a.id',
                        DB::raw("string_agg(DISTINCT regular_fixed_quantity_confirmation_box.id_regular_delivery_plan::character varying, ',') as id_delivery_plan"),
                        DB::raw('MAX(regular_fixed_quantity_confirmation_box.id_fixed_quantity_confirmation) as id_fixed_quantity_confirmation'),
                        DB::raw("string_agg(DISTINCT regular_fixed_quantity_confirmation_box.id_box::character varying, ',') as id_box"),
                        DB::raw("string_agg(DISTINCT a.code_consignee::character varying, ',') as code_consignee"),
                        DB::raw("string_agg(DISTINCT a.cust_item_no::character varying, ',') as cust_item_no"),
                        DB::raw("string_agg(DISTINCT a.order_no::character varying, ',') as order_no"),
                        DB::raw("string_agg(DISTINCT a.etd_ypmi::character varying, ',') as etd_ypmi"),
                        DB::raw("string_agg(DISTINCT a.etd_wh::character varying, ',') as etd_wh"),
                        DB::raw("string_agg(DISTINCT a.etd_jkt::character varying, ',') as etd_jkt"),
                        DB::raw("string_agg(DISTINCT regular_fixed_quantity_confirmation_box.qty_pcs_box::character varying, ',') as qty"),
                        DB::raw("string_agg(DISTINCT a.item_no::character varying, ',') as item_no")
                        )
                        ->where('regular_fixed_quantity_confirmation_box.id_prospect_container_creation', $id)
                        ->whereNotNull('regular_fixed_quantity_confirmation_box.qrcode')
                        ->whereNotNull('c.id_fixed_actual_container')
                        ->leftJoin('regular_delivery_plan as a', 'a.id', 'regular_fixed_quantity_confirmation_box.id_regular_delivery_plan')
                        ->leftJoin('regular_fixed_quantity_confirmation as c', 'c.id', 'regular_fixed_quantity_confirmation_box.id_fixed_quantity_confirmation')
                        ->groupBy('regular_fixed_quantity_confirmation_box.id_prospect_container_creation', 'a.id')
                        ->distinct() // Make the entire result set distinct
                        ->paginate($params->limit ?? null);

        } else {
            $data = RegularFixedQuantityConfirmationBox::select('regular_fixed_quantity_confirmation_box.id_prospect_container_creation','b.id_delivery_plan',
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
                        DB::raw("string_agg(DISTINCT b.item_no::character varying, ',') as item_no")
                        )
                        ->where('regular_fixed_quantity_confirmation_box.id_prospect_container_creation', $id)
                        ->whereNotNull('regular_fixed_quantity_confirmation_box.qrcode')
                        ->whereNotNull('c.id_fixed_actual_container')
                        ->leftJoin('regular_delivery_plan as a','a.id','regular_fixed_quantity_confirmation_box.id_regular_delivery_plan')
                        ->leftJoin('regular_delivery_plan_set as b','b.id_delivery_plan','regular_fixed_quantity_confirmation_box.id_regular_delivery_plan')
                        ->leftJoin('regular_fixed_quantity_confirmation as c','c.id','regular_fixed_quantity_confirmation_box.id_fixed_quantity_confirmation')
                        ->groupBy('regular_fixed_quantity_confirmation_box.id_prospect_container_creation','b.id_delivery_plan')
                        ->paginate($params->limit ?? null);
        }

        $data->transform(function ($item) use ($check){
            $custname = self::getCustName($item->code_consignee);
            $itemname = [];
            foreach (explode(',', $item->item_no) as $value) {
                $itemname[] = self::getPart($value);
            }
            $item_no = [];
            foreach (explode(',', $item->item_no) as $value) {
                $item_no[] = self::getItemSerial($value);
            }

            if (count($item_no) > 1 || $check->refRegularDeliveryPlan->item_no == null) {
                $item_no_set = $check->refRegularDeliveryPlan->manyDeliveryPlanSet->pluck('item_no');

                $mst_box = MstBox::where('part_set', 'set')
                            ->whereIn('item_no', $item_no_set)
                            ->get()->map(function ($item){
                                $qty = [
                                    $item->id.'id' => $item->qty
                                ];
                            
                                return array_merge($qty);
                            });

                $box_scan = RegularFixedQuantityConfirmationBox::select(DB::raw("string_agg(DISTINCT regular_fixed_quantity_confirmation_box.qrcode::character varying, ',') as qrcode"),
                                                            DB::raw("string_agg(DISTINCT regular_fixed_quantity_confirmation_box.id_box::character varying, ',') as id_box"),
                                                            DB::raw("SUM(regular_fixed_quantity_confirmation_box.qty_pcs_box) as qty"),
                                                            )
                                                            ->whereIn('id_fixed_quantity_confirmation', explode(',', $item->id_fixed_quantity_confirmation))
                                                            ->whereNotNull('qrcode')
                                                            ->groupBy('regular_fixed_quantity_confirmation_box.qrcode')
                                                            ->get()->map(function ($item) use($item_no_set){
                                                                $qty = [
                                                                    $item->id_box.'id' => ($item->qty / count($item_no_set)) ?? 0
                                                                ];
                                                            
                                                                return array_merge($qty);
                                                            });

                $qty = [];
                $qty_sum = [];
                foreach ($mst_box as $key => $value) {
                    $arary_key = array_keys($value)[0];
                    $box_scan_per_id = array_merge(...$box_scan)[$arary_key] ?? 0;
                    $qty[] = $box_scan_per_id / $value[$arary_key];
                    $qty_sum[] = $value[$arary_key];
                }
                $max_qty[] = (int)ceil(max($qty)) / count($item_no_set);
        
                $box = [
                    'qty' =>  array_sum($qty_sum)." x ".count($box_scan),
                    'length' =>  "",
                    'width' =>  "",
                    'height' =>  "",
                ];

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

    public static function shippingPacking($params)
    {
        return null;
    }

    public static function shippingDeliveryNote($params)
    {
        return null;
    }

    public static function shippingCasemarks($params)
    {
        return null;
    }

    public static function shippingActual($params)
    {
        return null;
    }

    public static function getNoPackaging($id){
        return RegularFixedActualContainer::find($id);
    }

    public static function shippingStore($request,$is_transaction = true)
    {
        if($is_transaction) DB::beginTransaction();
        try {
            // $request->merge(['consignee'=>json_encode($consignee),'status'=>Constant::DRAFT]);
            $request1 = $request->except(['container_count','container_value','container_type']);
            $request2 = [
                            'count_container' => implode(',',$request->container_count),
                            'container_value' => implode(',',$request->container_value),
                            'container_type' => implode(',',$request->container_type),
                        ];
            $params = array_merge($request1,$request2);
            Helper::requireParams([
                'to',
                'cc',
            ]);

            $fixed_shipping_instruction_creation = RegularFixedShippingInstructionCreation::where('id_fixed_shipping_instruction', $request->id_fixed_shipping_instruction)->first();

            if ($fixed_shipping_instruction_creation == null) {
                $insert = RegularFixedShippingInstructionCreation::create($params);
                $actual_container_creation = RegularFixedActualContainerCreation::query();
                $update_actual = $actual_container_creation->whereIn('id',explode(',',$request->id_actual_container_creation))->where('datasource',$request->datasource)->where('code_consignee',$request->consignee)->where('etd_jkt',$request->etd_jkt)->get();
                foreach ($update_actual as $key => $value) {
                    $value->update(['id_fixed_shipping_instruction_creation'=>$insert->id, 'status' => 2]);
                }

                if (count($actual_container_creation->where('id_fixed_shipping_instruction', $params['id_fixed_shipping_instruction'])->get()) == count($actual_container_creation->where('id_fixed_shipping_instruction', $params['id_fixed_shipping_instruction'])->where('status', 2)->get())) {
                    RegularFixedShippingInstruction::where('id', $params['id_fixed_shipping_instruction'])->update(['status' => 2]);
                }

                $params['id_fixed_shipping_instruction_creation'] = $insert->id;
                RegularFixedShippingInstructionCreationDraft::create($params);
            } else {
                $fixed_shipping_instruction_creation->update($params);
                $params['id_fixed_shipping_instruction_creation'] = $fixed_shipping_instruction_creation->id;
                RegularFixedShippingInstructionCreationDraft::create($params);
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
            $update = RegularFixedShippingInstructionCreation::find($request->id);
            if(!$update) throw new \Exception("Data not found", 400);
            $update->status = Constant::FINISH;
            $update->save();

            if($is_transaction) DB::commit();
            return ['items'=>$update];
            Cache::flush([self::cast]); //delete cache
        } catch (\Throwable $th) {
            if($is_transaction) DB::rollBack();
            throw $th;
        }
    }

    public static function shippingDraftDok($params)
    {
        $fixed_actual_container_creation = RegularFixedActualContainerCreation::where('code_consignee', $params->code_consignee)->where('etd_jkt', $params->etd_jkt)->where('datasource', $params->datasource)->first();

        if($fixed_actual_container_creation->id_fixed_shipping_instruction_creation == null) return ['items' => []];

        $data = RegularFixedShippingInstructionCreationDraft::select('id','consignee','created_at')
            ->where('id_fixed_shipping_instruction_creation', $fixed_actual_container_creation->id_fixed_shipping_instruction_creation)
            ->paginate($params->limit ?? null);

        if(!$data) throw new \Exception("Data not found", 400);
        return [
            'items' => $data->getCollection()->transform(function($item){

                $item->title = 'SI Draft '.$item->consignee;
                $item->date = $item->created_at;

                unset(
                    $item->refFixedShippingInstructionCreation,
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
        $data = RegularFixedShippingInstructionCreationDraft::where('id',$id)->first();

        if(!$data) throw new \Exception("Data not found", 400);
        return [
            'items' => $data
        ];
    }

    public static function downloadDoc($request,$id,$filename,$pathToFile)
    {
        try {
            $data = RegularFixedShippingInstructionCreation::where('id_fixed_shipping_instruction', $id)->first();
            $data->instruction_date = Carbon::parse($data->instruction_date)->subDay(2)->format('D, M d, Y');
            $data->etd_wh = Carbon::parse($data->etd_jkt)->subDay(2)->format('D, M d, Y');
            $data->eta_destination = Carbon::parse($data->eta_destination)->subDay(2)->format('M d, Y');
            $data->etd_jkt = Carbon::parse($data->etd_jkt)->subDay(2)->format('M d, Y');

            $actual_container_creation = RegularFixedActualContainerCreation::where('id_fixed_shipping_instruction', $id)->first();
            $actual_container = RegularFixedActualContainer::where('id', $actual_container_creation->id_fixed_actual_container)->get();

            foreach ($actual_container as $key => $value) {
                $tes = $value->manyFixedQuantityConfirmation;
            }

            $box = [];
            foreach ($tes as $key => $item) {
                $box[] = RegularDeliveryPlanBox::with('refBox')->where('id_regular_delivery_plan', $item['id_regular_delivery_plan'])->get()->toArray();
            }

            Pdf::loadView('pdf.fixed_shipping_instruction',[
              'data' => $data,
              'actual_container' => $actual_container,
              'box' => $box
            ])
            ->save($pathToFile)
            ->setPaper('A4','potrait')
            ->download($filename);
          } catch (\Throwable $th) {
              return Helper::setErrorResponse($th);
          }
    }

    public static function downloadDocDraft($request,$id,$filename,$pathToFile)
    {
        try {
            $data = RegularFixedShippingInstructionCreation::find($id);
            $data->instruction_date = Carbon::parse($data->instruction_date)->subDay(2)->format('D, M d, Y');
            $data->etd_wh = Carbon::parse($data->etd_jkt)->subDay(2)->format('D, M d, Y');
            $data->eta_destination = Carbon::parse($data->eta_destination)->subDay(2)->format('M d, Y');
            $data->etd_jkt = Carbon::parse($data->etd_jkt)->subDay(2)->format('M d, Y');
            $data->approved = MstSignature::where('type', 'APPROVED')->first()->name;
            $data->checked = MstSignature::where('type', 'CHECKED')->first()->name;
            $data->issued = MstSignature::where('type', 'ISSUED')->first()->name;
            $data->pod = $data->port_of_discharge;
            $data->pol = $data->port_of_loading;

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

    public static function shippingDetail($params,$id)
    {
        $data = RegularFixedActualContainerCreation::select('regular_fixed_actual_container_creation.code_consignee','regular_fixed_actual_container_creation.etd_jkt'
        ,DB::raw('COUNT(regular_fixed_actual_container_creation.etd_jkt) AS summary_container')
        ,DB::raw("string_agg(DISTINCT regular_fixed_actual_container_creation.summary_box::character varying, ',') as summary_box")
        ,DB::raw("string_agg(DISTINCT regular_fixed_actual_container_creation.code_consignee::character varying, ',') as code_consignee")
        ,DB::raw("string_agg(DISTINCT regular_fixed_actual_container_creation.id::character varying, ',') as id_actual_container_creation")
        ,DB::raw("string_agg(DISTINCT regular_fixed_actual_container_creation.datasource::character varying, ',') as datasource")
        ,DB::raw("string_agg(DISTINCT regular_fixed_actual_container_creation.etd_wh::character varying, ',') as etd_wh")
        ,DB::raw("string_agg(DISTINCT regular_fixed_actual_container_creation.etd_ypmi::character varying, ',') as etd_ypmi"))
        ->where('regular_fixed_actual_container_creation.id_fixed_shipping_instruction',$id)
        ->groupBy('regular_fixed_actual_container_creation.code_consignee','regular_fixed_actual_container_creation.etd_jkt')
        ->paginate($params->limit ?? null);
        if(!$data) throw new \Exception("Data not found", 400);

        $data->transform(function ($item) {
            return [
                'id' => $item->id_actual_container_creation,
                'cust_name' => $item->refMstConsignee->nick_name,
                'etd_jkt' => $item->etd_jkt,
                'etd_wh' => $item->etd_wh,
                'etd_ypmi' => $item->etd_ypmi,
                'summary_container' => $item->summary_container,
                'code_consignee' => $item->code_consignee,
                'datasource' => $item->datasource,
            ];
        });

        return [
            'items' => $data->items(),
            'last_page' => $data->lastPage()
        ];
    }

    public static function shippingDetailSI($params)
    {
        $data = RegularFixedActualContainerCreation::select('regular_fixed_actual_container_creation.id_fixed_shipping_instruction'
        ,DB::raw('COUNT(regular_fixed_actual_container_creation.etd_jkt) AS summary_container')
        ,DB::raw("string_agg(DISTINCT regular_fixed_actual_container_creation.id_fixed_shipping_instruction_creation::character varying, ',') as id_fixed_shipping_instruction_creation")
        ,DB::raw("string_agg(DISTINCT regular_fixed_actual_container_creation.id_lsp::character varying, ',') as id_lsp")
        ,DB::raw("string_agg(DISTINCT regular_fixed_actual_container_creation.etd_wh::character varying, ',') as etd_wh")
        ,DB::raw("string_agg(DISTINCT regular_fixed_actual_container_creation.etd_jkt::character varying, ',') as etd_jkt")
        ,DB::raw("string_agg(DISTINCT regular_fixed_actual_container_creation.code_consignee::character varying, ',') as code_consignee")
        ,DB::raw("string_agg(DISTINCT regular_fixed_actual_container_creation.datasource::character varying, ',') as datasource")
        ,DB::raw("string_agg(DISTINCT b.hs_code::character varying, ',') as hs_code")
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
        ,DB::raw("string_agg(DISTINCT j.id::character varying, ',') as id_fixed_shipping_instruction")
        ,DB::raw("string_agg(DISTINCT regular_fixed_actual_container_creation.id_fixed_actual_container::character varying, ',') as id_fixed_actual_container")
        ,DB::raw("SUM(f.net_weight) as net_weight")
        ,DB::raw("SUM(f.gross_weight) as gross_weight")
        ,DB::raw("SUM(f.measurement) as measurement")
        ,DB::raw("SUM(regular_fixed_actual_container_creation.summary_box) as summary_box_sum"))
        ->whereIn('regular_fixed_actual_container_creation.id', explode(',',$params->id))
        ->where('regular_fixed_actual_container_creation.code_consignee', $params->code_consignee)
        ->where('regular_fixed_actual_container_creation.etd_jkt', $params->etd_jkt)
        ->where('regular_fixed_actual_container_creation.datasource', $params->datasource)
        ->leftJoin('mst_part as b','regular_fixed_actual_container_creation.item_no','b.item_no')
        ->leftJoin('mst_mot as c','regular_fixed_actual_container_creation.id_mot','c.id')
        ->leftJoin('mst_port_of_discharge as d','regular_fixed_actual_container_creation.code_consignee','d.code_consignee')
        ->leftJoin('mst_port_of_loading as e','regular_fixed_actual_container_creation.id_type_delivery','e.id_type_delivery')
        ->leftJoin('mst_container as f','regular_fixed_actual_container_creation.id_container','f.id')
        ->leftJoin('regular_delivery_plan_shipping_instruction_creation as g','regular_fixed_actual_container_creation.id_fixed_shipping_instruction_creation','g.id')
        ->leftJoin('mst_consignee as h','regular_fixed_actual_container_creation.code_consignee','h.code')
        ->leftJoin('regular_fixed_actual_container as i','regular_fixed_actual_container_creation.id_fixed_actual_container','i.id')
        ->leftJoin('regular_fixed_shipping_instruction as j','regular_fixed_actual_container_creation.id_fixed_shipping_instruction','j.id')
        ->groupBy('regular_fixed_actual_container_creation.id_fixed_shipping_instruction')
        ->paginate(1);
        if(!$data) throw new \Exception("Data not found", 400);

        $data->transform(function ($item) use($params){
            if ($item->id_fixed_shipping_instruction_creation) {
                $SI = RegularFixedShippingInstructionCreation::where('id',$item->id_fixed_shipping_instruction_creation)->paginate(1);

                $summary_box = RegularFixedActualContainerCreation::where('code_consignee', $item->code_consignee)
                                                                            ->where('etd_jkt', $item->etd_jkt)
                                                                            ->where('datasource', $item->datasource)
                                                                            ->whereIn('id',explode(',',$params->id))
                                                                            ->get()->map(function($q){
                                                                                $items = $q->summary_box;
                                                                                return $items;
                                                                            });

                $SI->transform(function ($si_item) use ($summary_box) {
                    $si_item->container_value = explode(',', $si_item->container_value);
                    $si_item->container_count = explode(',', $si_item->count_container);
                    $si_item->container_type = explode(',', $si_item->container_type);
                    $si_item->summary_box = array_sum($summary_box->toArray());;

                    return $si_item;
                });

                return $SI->items()[0];
            } else {

                $mst_shipment = MstShipment::where('is_active', 1)->first();

                $data = RegularFixedActualContainer::where('id', $item->id_fixed_actual_container)->get();
                $id_delivery_plan = [];
                foreach ($data[0]->manyFixedQuantityConfirmation as $id_delivery) {
                    $id_delivery_plan[] = $id_delivery->id_regular_delivery_plan;
                }
                $deliv_plan = RegularDeliveryPlan::with('manyFixedQuantityConfirmationBox')->orderBy('item_no','asc')->whereIn('id',$id_delivery_plan)->get();

                $res_box_single = [];
                $res_box_set = [];
                $id_fixed_actual = $item->id_fixed_actual_container;
                foreach ($deliv_plan as $key => $deliv_value) {
                    if ($deliv_value->item_no !== null) {
                        $res = $deliv_value->manyFixedQuantityConfirmationBox->map(function($item) use($id_fixed_actual){
                            if ($item->refFixedQuantityConfirmation->id_fixed_actual_container == $id_fixed_actual) {
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
                        foreach ($res as $key => $item_res) {
                            if ($item_res !== null && $item_res['qrcode'] !== null && !in_array($item_res, $box_single)) {
                                $box_single[] = $item_res;
                            }
                        }
                        
                        $res_box_single[] = $box_single;
                    }
                    
                    if ($deliv_value->item_no == null) {
                        $plan_set = RegularDeliveryPlanSet::where('id_delivery_plan',$deliv_value->id)->get();
                        $deliv_plan_box = $deliv_value->manyFixedQuantityConfirmationBox()
                                            ->whereHas('refFixedQuantityConfirmation', function ($q) use ($id_fixed_actual) {
                                                $q->where('id_fixed_actual_container', $id_fixed_actual);
                                            })
                                            ->where('id_regular_delivery_plan',$deliv_value->id)->where('qrcode','!=',null)->get();
                        // $deliv_plan_box = RegularFixedQuantityConfirmationBox::where('id_regular_delivery_plan',$deliv_value->id)
                        //                                     ->whereIn('id_prospect_container_creation', explode(',', $params->id))
                        //                                     ->where('qrcode','!=',null)
                        //                                     ->orderBy('qty_pcs_box','desc')
                        //                                     ->orderBy('id','asc')
                        //                                     ->get();
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
                            // $check = array_sum($qty_pcs_box[0]) / count($item_no);
                            $check = array_sum($mst_box->pluck('qty')->toArray());
                            $box_set[] = [
                                'item_no' => $item_no,
                                // 'qty_pcs_box' => $check == array_sum($qty_pcs_box[$i]) / count($item_no) ? $qty_box : $res_qty,
                                'qty_pcs_box' => [$deliv_plan_box->pluck('qty_pcs_box')->toArray()[$i]],
                                'item_no_series' => $item_no_series,
                                'unit_weight_kg' =>  $deliv_plan_box->pluck('qty_pcs_box')->toArray()[$i] > $check ? $unit_weight_kg : $unit_weight_kg_mst,
                                'total_gross_weight' =>  $deliv_plan_box->pluck('qty_pcs_box')->toArray()[$i] > $check ? $total_gross_weight : $total_gross_weight_mst,
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
                    $count_gross_weight += array_sum($box_item['total_gross_weight']);
                    $count_meas += (($box_item['length'] * $box_item['width'] * $box_item['height']) / 1000000000);
                }

                $summary_box = RegularFixedActualContainerCreation::where('code_consignee', $item->code_consignee)
                                                                            ->where('etd_jkt', $item->etd_jkt)
                                                                            ->where('datasource', $item->datasource)
                                                                            ->whereIn('id',explode(',',$params->id))
                                                                            ->get()->map(function($q){
                                                                                $items = $q->summary_box;
                                                                                return $items;
                                                                            });

                return [
                    'id_actual_container_creation' => $params->id,
                    'code_consignee' => $item->code_consignee,
                    'consignee_address' => $item->refMstConsignee->name.'<br>'.$item->refMstConsignee->address1.'<br>'.$item->refMstConsignee->address2.'<br>'.$item->refMstConsignee->tel.'<br>'.$item->refMstConsignee->fax,
                    'customer_name' => $item->refMstConsignee->nick_name ?? null,
                    'etd_jkt' => $item->etd_jkt,
                    'etd_wh' => $item->etd_wh,
                    'summary_container' => $item->summary_container,
                    'hs_code' => $item->hs_code,
                    'via' => $item->mot,
                    'freight_charge' => 'COLLECT',
                    'incoterm' => 'FOB',
                    'shipped_by' => $item->mot,
                    'container_value' => explode(',', $item->container_type),
                    // 'container_count' => [array_sum($summary_box->toArray())],
                    'container_count' => [count($summary_box)],
                    'container_type' => $item->container_value,
                    'net_weight' => round($count_net_weight,1),
                    'gross_weight' => round($count_gross_weight,1),
                    'measurement' => round($count_meas,3),
                    'port_of_discharge' => $item->port,
                    'port_of_loading' => $item->type_delivery,
                    'type_delivery' => $item->type_delivery,
                    'count' => $item->summary_container,
                    'summary_box' => array_sum($summary_box->toArray()),
                    'to' => $item->refMstLsp->name ?? null,
                    'status' => $item->status ?? null,
                    'id_fixed_shipping_instruction_creation' => $item->id_fixed_shipping_instruction_creation ?? null,
                    'id_fixed_shipping_instruction' => $item->id_fixed_shipping_instruction ?? null,
                    'invoice_no' => $item->no_packaging,
                    'shipper' => $mst_shipment->shipment ?? null,
                    'tel' => $mst_shipment->telp ?? null,
                    'fax' => $mst_shipment->fax ?? null,
                    'fax_id' => $mst_shipment->fax_id ?? null,
                    'tel_consignee' => $item->tel_consignee,
                    'fax_consignee' => $item->fax_consignee,
                    // 'consignee_address' => $item->consignee_address,
                    'notify_part' => '',
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

    public static function sendccoff($request,$is_transaction = true)
    {
        if($is_transaction) DB::beginTransaction();
        try {
            Helper::requireParams([
                'id'
            ]);

            $data = RegularFixedShippingInstruction::whereIn('id', $request->id)->get();
            foreach ($data as $value) {
                $value->update(['status' => 3]);
            }

            if($is_transaction) DB::commit();
            Cache::flush([self::cast]); //delete cache

        } catch (\Throwable $th) {
            if($is_transaction) DB::rollBack();
            throw $th;
        }
    }

    public static function sendccman($request,$is_transaction = true)
    {
        if($is_transaction) DB::beginTransaction();
        try {
            Helper::requireParams([
                'id'
            ]);
            RegularFixedShippingInstruction::where('id', $request->id)->update(['status' => 4]);
            RegularFixedShippingInstructionCreation::where('id_fixed_shipping_instruction', $request->id)->update(['checked' => $request->checked]);
            if($is_transaction) DB::commit();
            Cache::flush([self::cast]); //delete cache
        } catch (\Throwable $th) {
            if($is_transaction) DB::rollBack();
            throw $th;
        }
    }

    public static function approve($request,$is_transaction = true)
    {
        if($is_transaction) DB::beginTransaction();
        try {
            Helper::requireParams([
                'id'
            ]);
            RegularFixedShippingInstruction::where('id', $request->id)->update(['status' => 5]);
            RegularFixedShippingInstructionCreation::where('id_fixed_shipping_instruction', $request->id)->update(['approved' => $request->approved]);
            if($is_transaction) DB::commit();
            Cache::flush([self::cast]); //delete cache
        } catch (\Throwable $th) {
            if($is_transaction) DB::rollBack();
            throw $th;
        }
    }

    public static function revisi($request,$is_transaction = true)
    {
        if($is_transaction) DB::beginTransaction();
        try {
            Helper::requireParams([
                'id',
                'id_user',
                'note'
            ]);
            $data = [
                'id_fixed_shipping_instruction' => $request->id,
                'id_user' => $request->id_user,
                'note' => $request->note,
                'type' => 'REVISI',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            RegularFixedShippingInstructionRevision::insert($data);
            RegularFixedShippingInstruction::where('id', $request->id)->update(['status' => 6]);
            if($is_transaction) DB::commit();
            Cache::flush([self::cast]); //delete cache
        } catch (\Throwable $th) {
            if($is_transaction) DB::rollBack();
            throw $th;
        }
    }

    public static function reject($request,$is_transaction = true)
    {
        if($is_transaction) DB::beginTransaction();
        try {
            Helper::requireParams([
                'id',
                'id_user',
                'note'
            ]);
            $data = [
                'id_fixed_shipping_instruction' => $request->id,
                'id_user' => $request->id_user,
                'note' => $request->note,
                'type' => 'REJECT',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            RegularFixedShippingInstructionRevision::insert($data);
            RegularFixedShippingInstruction::where('id', $request->id)->update(['status' => 7]);
            if($is_transaction) DB::commit();
            Cache::flush([self::cast]); //delete cache
        } catch (\Throwable $th) {
            if($is_transaction) DB::rollBack();
            throw $th;
        }
    }

    public static function printPackagingShipping($request,$id,$pathToFile,$filename)
    {
        try {
            $cek = RegularFixedActualContainerCreation::where('id_fixed_shipping_instruction', $id)->get();
            foreach ($cek  as $value) {
                $data = RegularFixedActualContainer::where('id', $value->id_fixed_actual_container)->get();
            }
            $id_delivery_plan = [];
            foreach ($data[0]->manyFixedQuantityConfirmation as $id_delivery) {
                $id_delivery_plan[] = $id_delivery->id_regular_delivery_plan;
            }
            $deliv_plan = RegularDeliveryPlan::with('manyFixedQuantityConfirmationBox')->orderBy('item_no','asc')->whereIn('id',$id_delivery_plan)->get();

            $res_box_single = [];
            $res_box_set = [];
            $id_fixed_actual = $data[0]->id;
            foreach ($deliv_plan as $key => $deliv_value) {
                if ($deliv_value->item_no !== null) {
                    $res = $deliv_value->manyFixedQuantityConfirmationBox->map(function($item) use($id_fixed_actual) {
                        if ($item->refFixedQuantityConfirmation->id_fixed_actual_container == $id_fixed_actual) {
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
                                        ->whereHas('refFixedQuantityConfirmation', function ($q) use ($id_fixed_actual) {
                                            $q->where('id_fixed_actual_container', $id_fixed_actual);
                                        })
                                        ->where('id_regular_delivery_plan',$deliv_value->id)->where('qrcode','!=',null)->get();
                    // $deliv_plan_box = RegularFixedQuantityConfirmationBox::select(
                    //                                     'id_fixed_quantity_confirmation', 
                    //                                     DB::raw("SUM(regular_fixed_quantity_confirmation_box.qty_pcs_box) as qty_pcs_box"),
                    //                                     DB::raw("string_agg(DISTINCT regular_fixed_quantity_confirmation_box.qrcode::character varying, ',') as qrcode"),
                    //                                     DB::raw("string_agg(DISTINCT regular_fixed_quantity_confirmation_box.id::character varying, ',') as id_quantity_confirmation_box"),
                    //                                     )
                    //                                     ->where('id_regular_delivery_plan',$deliv_value->id)
                    //                                     ->where('qrcode','!=',null)
                    //                                     ->groupBy('id_fixed_quantity_confirmation')
                    //                                     ->orderBy('qty_pcs_box','desc')
                    //                                     ->orderBy('id_quantity_confirmation_box','asc')
                    //                                     ->get();
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
                        $count_outer_carton_weight = $value->outer_carton_weight;
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
                        $group[] = $value->id_quantity_confirmation_box;
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
                        // $check = array_sum($qty_pcs_box[0]) / count($item_no);
                        $check = array_sum($mst_box->pluck('qty')->toArray());
                        $box_set[] = [
                            'item_no' => $item_no,
                            'qty_pcs_box' => $deliv_plan_box->pluck('qty_pcs_box')->toArray()[$i] > $check ? $res_qty : $qty_box,
                            'item_no_series' => $item_no_series,
                            'unit_weight_kg' => $deliv_plan_box->pluck('qty_pcs_box')->toArray()[$i] > $check ? $unit_weight_kg : $unit_weight_kg_mst,
                            'total_gross_weight' => $deliv_plan_box->pluck('qty_pcs_box')->toArray()[$i] > $check ? $total_gross_weight : $total_gross_weight_mst,
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

            Pdf::loadView('pdf.packaging.packaging_doc',[
                'count_data' => count($count_data),
                'data' => $data,
                'box' => $box,
                'gross_weight_per_part' => $gross_weight_per_part,
                'count_qty' => $count_qty,
                'count_net_weight' => $count_net_weight,
                'count_gross_weight' => $count_gross_weight,
                'count_meas' => $count_meas
            ])
            ->save($pathToFile)
            ->setPaper('A4','potrait')
            ->download($filename);

        } catch (\Throwable $th) {
            return Helper::setErrorResponse($th);
        }
    }

    public static function packingCreationDeliveryNoteHead($request,$id)
    {
        $data = RegularFixedActualContainerCreation::where('id_fixed_shipping_instruction', $id)->first();
        if(!$data) throw new \Exception("data tidak ditemukan", 400);

        $ret['yth'] = $data->refMstLsp->name;
        $ret['username'] = $data->refMstConsignee->name;
        $ret['jenis_truck'] = $data->refMstContainer->container_type." HC";
        $ret['surat_jalan'] = Helper::generateCodeLetter(RegularFixedPackingCreationNote::latest()->first());
        $ret['delivery_date'] = date('d-m-Y');
        $ret['shipped'] = MstShipment::Where('is_active', 1)->first()->shipment ?? null;

        return [
            'items' => $ret,
            'last_page' => 0
        ];
    }

    public static function packingCreationDeliveryNotePart($params,$id)
    {
        $cek = RegularFixedActualContainerCreation::where('id_fixed_shipping_instruction', $id)->get();
        foreach ($cek  as $value) {
              $id_fixed_actual_container[] = $value->id_fixed_actual_container;
        }

        $data = RegularFixedQuantityConfirmation::select('id_regular_delivery_plan',
                DB::raw("string_agg(DISTINCT regular_fixed_quantity_confirmation.id_fixed_actual_container::character varying, ',') as id_fixed_actual_container"),
                DB::raw("string_agg(DISTINCT regular_fixed_quantity_confirmation.item_no::character varying, ',') as item_no"),
                DB::raw("string_agg(DISTINCT regular_fixed_quantity_confirmation.order_no::character varying, ',') as order_no"),
                DB::raw("string_agg(DISTINCT regular_fixed_quantity_confirmation.id::character varying, ',') as id_quantity_confirmation"),
                DB::raw('MAX(regular_fixed_quantity_confirmation.in_wh) as in_wh'),
                DB::raw('count(regular_fixed_quantity_confirmation.id) as count'),
            )
            ->whereIn('id_fixed_actual_container', $id_fixed_actual_container)
            ->groupBy('id_regular_delivery_plan')
            ->paginate($params->limit ?? null);
        if(!$data) throw new \Exception("data tidak ditemukan", 400);
        return [
            'items' => $data->getCollection()->transform(function($item){

                $item_name_set = [];
                foreach ($item->refRegularDeliveryPlan->manyDeliveryPlanSet as $key => $value) {
                    $item_name_set[] = $value->refPart->description;
                }
                
                $qty_pcs_box = RegularFixedQuantityConfirmationBox::whereIn('id_fixed_quantity_confirmation', explode(',', $item->id_quantity_confirmation))->get();

                $item->item_name = $item->refRegularDeliveryPlan->item_no == null ? $item_name_set : trim($item->refRegularDeliveryPlan->refPart->description);
                $item->item_no = $item->refRegularDeliveryPlan->item_no == null ? $item->refRegularDeliveryPlan->manyDeliveryPlanSet->pluck('item_no') : $item->refRegularDeliveryPlan->item_no;
                $item->cust_name = $item->refRegularDeliveryPlan->refConsignee->nick_name;
                $item->no_invoice = $item->refFixedActualContainer->no_packaging;
                $item->in_wh = count(explode(',', $item->count)) . ' x ' . array_sum($qty_pcs_box->pluck('qty_pcs_box')->toArray());
                unset(
                    $item->refRegularDeliveryPlan,
                    $item->refFixedActualContainer
                );

                return $item;
            }),
            'last_page' => $data->lastPage()
        ];
    }

    public static function printCasemarks($request,$id,$pathToFile,$filename)
    {
        try {
            $cek = RegularFixedActualContainerCreation::where('id_fixed_shipping_instruction', $id)->get();
            foreach ($cek  as $value) {
                $id_fixed_actual_container[] = $value->id_fixed_actual_container;
            }
            
            $data = RegularFixedActualContainer::whereIn('id', $id_fixed_actual_container)->get();

            $id_delivery_plan = [];
            foreach ($data[0]->manyFixedQuantityConfirmation as $id_delivery) {
                $id_delivery_plan[] = $id_delivery->id_regular_delivery_plan;
            }
            $deliv_plan = RegularDeliveryPlan::with('manyFixedQuantityConfirmationBox')->orderBy('item_no','asc')->whereIn('id',$id_delivery_plan)->orderBy('item_no','asc')->get();

            $res_box_single = [];
            $res_box_set = [];
            $id_fixed_actual = $data[0]->id;
            foreach ($deliv_plan as $key => $deliv_value) {
                if ($deliv_value->item_no !== null) {
                    $res = $deliv_value->manyFixedQuantityConfirmationBox->map(function($item) use($id_fixed_actual) {
                        if ($item->refFixedQuantityConfirmation->id_fixed_actual_container == $id_fixed_actual) {
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
                                        ->whereHas('refFixedQuantityConfirmation', function ($q) use ($id_fixed_actual) {
                                            $q->where('id_fixed_actual_container', $id_fixed_actual);
                                        })
                                        ->where('id_regular_delivery_plan',$deliv_value->id)->where('qrcode','!=',null)->get();
                    // $deliv_plan_box = RegularFixedQuantityConfirmationBox::select(
                    //     'id_fixed_quantity_confirmation', 
                    //     DB::raw("SUM(regular_fixed_quantity_confirmation_box.qty_pcs_box) as qty_pcs_box"),
                    //     DB::raw("string_agg(DISTINCT regular_fixed_quantity_confirmation_box.qrcode::character varying, ',') as qrcode"),
                    //     DB::raw("string_agg(DISTINCT regular_fixed_quantity_confirmation_box.id::character varying, ',') as id_quantity_confirmation_box"),
                    //     )
                    //     ->where('id_regular_delivery_plan',$deliv_value->id)
                    //     ->where('qrcode','!=',null)
                    //     ->groupBy('id_fixed_quantity_confirmation')
                    //     ->orderBy('qty_pcs_box','desc')
                    //     ->orderBy('id_quantity_confirmation_box','asc')
                    //     ->get();

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
                        $count_outer_carton_weight = $value->outer_carton_weight;
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
                    for ($i=0; $i < count($id_deliv_box); $i++) { 
                        $check = array_sum($qty_pcs_box[0]) / count($item_no);
                        $box_set[] = [
                            'item_no' => $item_no,
                            // 'qty_pcs_box' => $check == array_sum($qty_pcs_box[$i]) / count($item_no) ? $qty_box : $res_qty,
                            'qty_pcs_box' => $qty_pcs_box[$i],
                            'item_no_series' => $item_no_series,
                            'unit_weight_kg' =>  $check == array_sum($qty_pcs_box[$i]) / count($item_no) ? $unit_weight_kg_mst : $unit_weight_kg,
                            'total_gross_weight' =>  $check == array_sum($qty_pcs_box[$i]) / count($item_no) ? $total_gross_weight_mst : $total_gross_weight,
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

    public static function printShippingActual($request,$id,$filename,$pathToFile)
    {
        try {
            $cek = RegularFixedActualContainerCreation::where('id_fixed_shipping_instruction', $id)->first();
            $data = RegularFixedShippingInstructionCreation::find($cek->id_fixed_shipping_instruction_creation);
            $data->instruction_date = Carbon::parse($data->instruction_date)->subDay(2)->format('D, M d, Y');
            $data->etd_wh = Carbon::parse($data->etd_jkt)->subDay(2)->format('D, M d, Y');
            $data->eta_destination = Carbon::parse($data->eta_destination)->subDay(2)->format('M d, Y');
            $data->etd_jkt = Carbon::parse($data->etd_jkt)->subDay(2)->format('M d, Y');

            $actual_container_creation = RegularFixedActualContainerCreation::where('id_fixed_shipping_instruction', $id)->first();
            $actual_container = RegularFixedActualContainer::where('id', $actual_container_creation->id_fixed_actual_container)->get();

            foreach ($actual_container as $key => $value) {
                $tes = $value->manyFixedQuantityConfirmation;
            }

            $box = [];
            foreach ($tes as $key => $item) {
                $box[] = RegularDeliveryPlanBox::with('refBox')->where('id_regular_delivery_plan', $item['id_regular_delivery_plan'])->get()->toArray();
            }

            Pdf::loadView('pdf.shipping_actual',[
                'data' => $data,
                'actual_container' => $actual_container,
                'box' => $box
            ])->save($pathToFile)
                ->setPaper('A4','potrait')
                ->download($filename);

        } catch (\Throwable $th) {
            return Helper::setErrorResponse($th);
        }
    }

}
