<?php

namespace App\Query;

use App\Constants\Constant;
use App\Models\IregularDeliveryPlan AS Model;
use App\ApiHelper as Helper;
use App\Models\IregularDeliveryPlan;
use App\Models\IregularDeliveryPlanDoc;
use App\Models\IregularDeliveryPlanPart;
use App\Models\IregularDeliveryPlanCheckbox;
use App\Models\IregularDeliveryPlanShippingInstruction;
use App\Models\IregularDeliveryPlanShippingInstructionDraft;
use App\Models\IregularOrderEntryTracking;
use App\Models\MstComodities;
use App\Models\MstDoc;
use App\Models\MstDutyTax;
use App\Models\MstFreight;
use App\Models\MstFreightCharge;
use App\Models\MstGoodCondition;
use App\Models\MstGoodCriteria;
use App\Models\MstGoodPayment;
use App\Models\MstGoodStatus;
use App\Models\MstIncoterms;
use App\Models\MstInlandCost;
use App\Models\MstInsurance;
use App\Models\MstShippedBy;
use App\Models\MstTypeTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class QueryIregularDeliveryPlan extends Model {

    const cast = 'iregular-delivery-plan';

    public static function getAll($params, $status_tracking = null)
    {
        $key = self::cast.json_encode($params->query());
        return Helper::storageCache($key.(isset($status_tracking) ? json_encode($status_tracking) : ''), function () use ($params, $status_tracking){
            $query = self::select('iregular_delivery_plan.*')
                ->leftJoin('iregular_order_entry_tracking', function($join) {
                    $join->on('iregular_order_entry_tracking.id_iregular_order_entry', '=', 'iregular_delivery_plan.id_iregular_order_entry')
                        ->where('iregular_order_entry_tracking.id', function($subquery) {
                                $subquery->selectRaw('MAX(id)')
                                    ->from('iregular_order_entry_tracking')
                                    ->whereColumn('id_iregular_order_entry', 'iregular_delivery_plan.id_iregular_order_entry');
                        });
                });

            if (isset($status_tracking)) {
                $query->where('iregular_order_entry_tracking.status', $status_tracking);
            }

            $category = $params->category ?? null;
            if ($category) {
                $query->where('iregular_delivery_plan.' . $category, 'ilike', $params->kueri);
            }
            
            if ($params->withTrashed == 'true') {
                $query->withTrashed();
            }
            
            $data = $query->paginate($params->limit ?? 10);
            
            if (isset($status_tracking)) {
                $totalRow = $query->where('iregular_order_entry_tracking.status', $status_tracking)->count();
            } else {
                $totalRow = self::count();
            }

            $lastPage = ceil($totalRow/($params->limit ?? 10));
            return [
                'items' => $data->getCollection()->transform(function($item){

                    $item->tracking = $item->manyTracking;
                    $item->type_transaction = $item->refTypeTransaction;
                    unset($item->refTypeTransaction);
                    unset($item->manyTracking);
                    
                    return $item;
                }),
                'last_page' => $lastPage,
                'attributes' => [
                    'total' => $data->total(),
                    'current_page' => $data->currentPage(),
                    'from' => $data->currentPage(),
                    'per_page' => (int) $data->perPage(),
                ]
            ];
        });
    }

    public static function getForm($request)
    {
        $type_transaction = MstTypeTransaction::select('id','name')->get();
        $comodities = MstComodities::select('id','name')->get();
        $good_condition = MstGoodCondition::select('id','name')->get();
        $good_status = MstGoodStatus::select('id','name')->get();
        $good_payment = MstGoodPayment::select('id','name')->get();
        $freight_charge = MstFreightCharge::select('id','name')->get();
        $insurance = MstInsurance::select('id','name')->get();
        $duty_tax = MstDutyTax::select('id','name')->get();
        $inland_cost = MstInlandCost::select('id','name')->get();
        $shipped_by = MstShippedBy::select('id','name')->get();
        $incoterms = MstIncoterms::select('id','name')->get();
        $freight = MstFreight::select('id','name')->get();
        $good_criteria = MstGoodCriteria::select('id','name')->get();

        return [
            'items' => [
                'type_transaction' => $type_transaction,
                'comodities' => $comodities,
                'good_condition' => $good_condition,
                'good_status' => $good_status,
                'good_payment' => $good_payment,
                'freight_charge' => $freight_charge,
                'insurance' => $insurance,
                'duty_tax' => $duty_tax,
                'inland_cost' => $inland_cost,
                'shipped_by' => $shipped_by,
                'incoterms' => $incoterms,
                'freight' => $freight,
                'good_criteria' => $good_criteria,
            ]
        ];
    }

    public static function getFormData($request, $id)
    {
        $data = self::find($id);
        if(!$data) throw new \Exception("id tidak ditemukan", 400);
        
        $data->type_transaction = $data->refTypeTransaction;
        $data->checkbox = $data->manyDeliveryPlanCheckbox;
        $data->doc = $data->manyDeliveryPlanDoc;
        $data->part = $data->manyDeliveryPlanPart;
        $data->tracking = $data->manyTracking;

        unset($data->refTypeTransaction);
        unset($data->manyDeliveryPlanCheckbox);
        unset($data->manyDeliveryPlanDoc);
        unset($data->manyDeliveryPlanPart);
        unset($data->manyTracking);
                    
        return [
            'items' => $data,
        ];
    }
    
    public static function getDoc($params, $id)
    {
        $data = IregularDeliveryPlanDoc::where('id_iregular_delivery_plan', $id)->paginate($params->limit ?? null);

        return [
            'items' => $data->getCollection()->transform(function($item){
                
                $item->name_doc = $item->MstDoc->name;
                unset(
                    $item->MstDoc,
                    $item->id_iregular_delivery_plan,
                    $item->id_doc,
                    $item->path,
                    $item->extension,
                    $item->filename,
                    $item->created_by,
                    $item->updated_at,
                    $item->updated_by,
                    $item->deleted_at,
                );

                return $item;
            }),
            'last_page' => $data->lastPage()
        ];
    }

    public static function sendApproval($request, $to_tracking, $description = null, $is_transaction = true)
    {
        if($is_transaction) DB::beginTransaction();
        try {
            Helper::requireParams([
                'id',
            ]);

            $params = $request->all();
            $data = self::find($params['id']);
            if(!$data) throw new \Exception("id tidak ditemukan", 400);

            if(!isset($description)){
                if ($to_tracking == 1) $description = 'Draft';
                else if ($to_tracking == 2) $description = 'Approval DC Spv';
                else if ($to_tracking == 3) $description = 'Approval DC Manager';
                else if ($to_tracking == 4) $description = 'Enquiry';
                else if ($to_tracking == 5) $description = 'Shipping';
                else if ($to_tracking == 6) $description = 'Approval CC Spv';
                else if ($to_tracking == 7) $description = 'Approval CC Manager';
                else if ($to_tracking == 8) $description = 'Finish';
                else if ($to_tracking == 9) $description = 'Reject';
            }            

            IregularOrderEntryTracking::create([
                "id_iregular_order_entry" => $data->id_iregular_order_entry,
                "status" => $to_tracking,
                "description" => $description
            ]);

            if($to_tracking == 5){
                $shipping_instruction = [
                    'id_iregular_delivery_plan' => $data->id,
                    'requestor' => $data->requestor,
                    'ext' => $data->ext,
                    'cost_center' => $data->cost_center,
                    'section' => $data->section,
                    'id_type_transaction' => $data->id_type_transaction,
                    'id_good_payment' => $data->id_good_payment,
                    'id_doc_type' => $data->id_doc_type,
                    'reason_foc' => $data->reason_foc,
                    'id_freight_charge' => $data->id_freight_charge,
                    'id_insurance' => $data->id_insurance,
                    'id_duty_tax' => $data->id_duty_tax,
                    'id_inland_cost' => $data->id_inland_cost,
                    'id_shipped' => $data->id_shipped,
                    'pick_up_location' => $data->pick_up_location,
                    'name_consignee' => $data->name_consignee,
                    'company_consignee' => $data->company_consignee,
                    'email_consignee' => $data->email_consignee,
                    'phone_consignee' => $data->phone_consignee,
                    'fax_consignee' => $data->fax_consignee,
                    'address_consignee' => $data->address_consignee,
                    'description_goods' => $data->description_goods,
                    'invoice_no' => $data->invoice_no,
                    'entity_site' => $data->entity_site,
                    'rate' => $data->rate,
                    'receive_date' => $data->receive_date,
                    'delivery_date' => $data->delivery_date,
                    'etd_date' => $data->etd_date,
                    'stuffing_date' => $data->stuffing_date,
                    'id_freight' => $data->id_freight,
                    'id_good_criteria' => $data->id_good_criteria
                ];
    
                $insert_shipping_instruction = IregularDeliveryPlanShippingInstruction::create($shipping_instruction);
            }
            
            if($is_transaction) DB::commit();
            Cache::flush([self::cast]); //delete cache
        } catch (\Throwable $th) {
            if($is_transaction) DB::rollBack();
            throw $th;
        }
    }


    public static function getShippingInstructionList($params, $status_tracking = null)
    {
        $key = self::cast.json_encode($params->query());
        return Helper::storageCache('shippinginstruction-'.$key.(isset($status_tracking) ? json_encode($status_tracking) : ''), function () use ($params, $status_tracking){
            $query = IregularDeliveryPlanShippingInstruction::select('iregular_delivery_plan_shipping_instruction.*')
                ->leftJoin('iregular_delivery_plan', function($join){
                    $join->on('iregular_delivery_plan.id', '=', 'iregular_delivery_plan_shipping_instruction.id_iregular_delivery_plan');
                })
                ->leftJoin('iregular_order_entry_tracking', function($join) {
                    $join->on('iregular_order_entry_tracking.id_iregular_order_entry', '=', 'iregular_delivery_plan.id_iregular_order_entry')
                        ->where('iregular_order_entry_tracking.id', function($subquery) {
                                $subquery->selectRaw('MAX(id)')
                                    ->from('iregular_order_entry_tracking')
                                    ->whereColumn('id_iregular_order_entry', 'iregular_delivery_plan.id_iregular_order_entry');
                        });
                });

            if (isset($status_tracking)) {
                $query->where('iregular_order_entry_tracking.status', $status_tracking);
            }

            $category = $params->category ?? null;
            if ($category) {
                $query->where('iregular_delivery_plan_shipping_instruction.' . $category, 'ilike', $params->kueri);
            }
            
            if ($params->withTrashed == 'true') {
                $query->withTrashed();
            }
            
            $data = $query->paginate($params->limit ?? 10);
            
            if (isset($status_tracking)) {
                $totalRow = $query->where('iregular_order_entry_tracking.status', $status_tracking)->count();
            } else {
                $totalRow = IregularDeliveryPlanShippingInstruction::count();
            }

            $lastPage = ceil($totalRow/($params->limit ?? 10));
            return [
                'items' => $data->getCollection()->transform(function($item){

                    $item->delivery_plan = $item->refDeliveryPlan;
                    $item->type_transaction = $item->refTypeTransaction;
                    if(isset($item->delivery_plan)){
                        $item->delivery_plan->tracking = $item->delivery_plan->manyTracking;
                        unset($item->delivery_plan->manyTracking);
                    }
                    unset($item->refTypeTransaction);
                    unset($item->refDeliveryPlan);
                    
                    return $item;
                }),
                'last_page' => $lastPage,
                'attributes' => [
                    'total' => $data->total(),
                    'current_page' => $data->currentPage(),
                    'from' => $data->currentPage(),
                    'per_page' => (int) $data->perPage(),
                ]
            ];
        });
    }

    public static function getShippingInstruction($params, $id)
    {
        $data = IregularDeliveryPlanShippingInstruction::find($id);
        $data->type_transaction = $data->refTypeTransaction;
        $data->good_payment = $data->refGoodPayment;
        $data->doc_type = $data->refDocType;
        $data->freight_charge = $data->refFreightCharge;
        $data->insurance = $data->refInsurance;
        $data->duty_tax = $data->refDutyTax;
        $data->inland_cost = $data->refInlandCost;
        $data->shipped_by = $data->refShippedBy;
        $data->freight = $data->refFreight;
        $data->good_criteria = $data->refGoodCriteria;

        unset($data->refTypeTransaction);
        unset($data->refGoodPayment);
        unset($data->refDocType);
        unset($data->refFreightCharge);
        unset($data->refInsurance);
        unset($data->refDutyTax);
        unset($data->refInlandCost);
        unset($data->refShippedBy);
        unset($data->refFreight);
        unset($data->refGoodCriteria);
        return [
            'items' => $data
        ];
    }

    public static function getShippingInstructionDraftList($params, $id){
        $data = IregularDeliveryPlanShippingInstructionDraft::where('id_iregular_delivery_plan_shipping_instruction', $id)->get();
        return [
            'items' => $data
        ];
    }

    public static function getShippingInstructionDraft($params, $id){
        $data = IregularDeliveryPlanShippingInstructionDraft::where('id_iregular_delivery_plan_shipping_instruction', $id)->first();
        return [
            'items' => $data
        ];
    }

    public static function shippingInstructionStore($request,$is_transaction = true)
    {
        if($is_transaction) DB::beginTransaction();
        try {
            $request1 = $request->except(['container_count','container_value','container_type']);
            $request2 = [
                            'container_count' => implode(',',$request->container_count) == "" ? null : implode(',',$request->container_count),
                            'container_value' => implode(',',$request->container_value) == "" ? null : implode(',',$request->container_value),
                            'container_type' => implode(',',$request->container_type) == "" ? null : implode(',',$request->container_type),
                        ];
            $params = array_merge($request1,$request2);
            Helper::requireParams([
                'to',
                'cc',
            ]);

            $data = IregularDeliveryPlanShippingInstructionDraft::where('id_iregular_delivery_plan_shipping_instruction', $params["id_iregular_delivery_plan_shipping_instruction"])->first();
            if(isset($data)){
                $update = $data->update($params);
            } else {
                $insert = IregularDeliveryPlanShippingInstructionDraft::create($params);
            }
            if($is_transaction) DB::commit();
            Cache::flush([self::cast]); //delete cache
        } catch (\Throwable $th) {
            if($is_transaction) DB::rollBack();
            throw $th;
        }
    }

}
