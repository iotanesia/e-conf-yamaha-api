<?php

namespace App\Query;

use App\Constants\Constant;
use App\Models\IregularDeliveryPlan AS Model;
use App\ApiHelper as Helper;
use App\Models\IregularDeliveryPlan;
use App\Models\IregularDeliveryPlanInvoice;
use App\Models\IregularDeliveryPlanInvoiceDetail;
use App\Models\IregularDeliveryPlanShippingInstruction;
use App\Models\IregularDeliveryPlanShippingInstructionDraft;
use App\Models\IregularOrderEntry;
use App\Models\IregularOrderEntryDoc;
use App\Models\IregularOrderEntryPart;
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

    public static function getAll($params, $min_status_tracking = null)
    {
        $key = self::cast.json_encode($params->query());
        return Helper::storageCache($key, function () use ($params, $min_status_tracking){
            $query = self::select('iregular_order_entry.*')
                ->leftJoin('iregular_order_entry', 'iregular_order_entry.id', '=', 'iregular_delivery_plan.id_iregular_order_entry')
                ->leftJoin('iregular_order_entry_tracking', function($join) {
                    $join->on('iregular_order_entry_tracking.id_iregular_order_entry', '=', 'iregular_delivery_plan.id_iregular_order_entry')
                        ->where('iregular_order_entry_tracking.id', function($subquery) {
                                $subquery->selectRaw('MAX(id)')
                                    ->from('iregular_order_entry_tracking')
                                    ->whereColumn('id_iregular_order_entry', 'iregular_delivery_plan.id_iregular_order_entry');
                        });
                });

            if (isset($min_status_tracking)) {
                $query->where('iregular_order_entry_tracking.status', '>=', $min_status_tracking);
            }

            $category = $params->category ?? null;
            if ($category) {
                $query->where('iregular_order_entry.' . $category, 'ilike', $params->kueri);
            }
            
            if ($params->withTrashed == 'true') {
                $query->withTrashed();
            }
            
            $data = $query->paginate($params->limit ?? 10);
            
            if (isset($min_status_tracking)) {
                $totalRow = $query->where('iregular_order_entry_tracking.status', '>=', $min_status_tracking)->count();
            } else {
                $totalRow = self::count();
            }

            $lastPage = ceil($totalRow/($params->limit ?? 10));
            return [
                'items' => $data->getCollection()->transform(function($item){
                    $item->shipped_by = MstShippedBy::find($item->id_shipped);
                    $item->type_transaction = MstTypeTransaction::find($item->id_type_transaction);
                    $item->tracking = IregularOrderEntryTracking::where('id_iregular_order_entry', $item->id)->orderBy('id', 'desc')->get();
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

    public static function storeFormCc($request,$id, $is_transaction = true)
    {
        if($is_transaction) DB::beginTransaction();
        try {
            $params = $request->all();

            $token = $request->header("Authorization");
            $token = Str::replaceFirst('Bearer ', '', $token);
            $tokenData = Helper::decodeJwtSignature($token, env("JWT_SECRET"));


            $data = IregularDeliveryPlan::where(["id_iregular_order_entry" => $id])->first();
            if(!$data) throw new \Exception("id tidak ditemukan", 400);

            $order_entry = $params;

            $data->update($order_entry);

            IregularOrderEntryTracking::create([
                "id_iregular_order_entry" => $data->id,
                "status" => 4,
                "id_user" => $tokenData->sub->id,
                "id_role" => $tokenData->sub->id_role,
                "id_position" => $tokenData->sub->id_position,
                'description' => Constant::STS_PROCESS_IREGULAR[4]
            ]);
            
            if($is_transaction) DB::commit();
            Cache::flush([self::cast]); //delete cache
            return ['items' => ['id' => $data->id]];
        } catch (\Throwable $th) {
            if($is_transaction) DB::rollBack();
            throw $th;
        }
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
                'id_iregular_order_entry',
            ]);
            
            $token = $request->header("Authorization");
            $token = Str::replaceFirst('Bearer ', '', $token);
            $tokenData = Helper::decodeJwtSignature($token, env("JWT_SECRET"));

            $params = $request->all();
            $data = self::where('id_iregular_order_entry', $params['id_iregular_order_entry'])->first();
            if(!$data) throw new \Exception("id tidak ditemukan", 400);

            if(!isset($description))
                $description = Constant::STS_PROCESS_IREGULAR[$to_tracking];

            IregularOrderEntryTracking::create([
                "id_iregular_order_entry" => $data->id_iregular_order_entry,
                "status" => $to_tracking,
                "id_user" => $tokenData->sub->id,
                "id_role" => $tokenData->sub->id_role,
                "id_position" => $tokenData->sub->id_position,
                "description" => $description
            ]);

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

    public static function storeInvoice($request, $id_iregular_order_entry, $is_transaction = true)
    {
        if($is_transaction) DB::beginTransaction();
        try {
            $params = $request->all();
            
            $delivery_plan = IregularDeliveryPlan::where('id_iregular_order_entry', $id_iregular_order_entry)->first();
            if(!$delivery_plan) throw new \Exception("id tidak ditemukan", 400);
    
            $invoice_data = IregularDeliveryPlanInvoice::where('id_iregular_delivery_plan', $delivery_plan->id)->first();
            $invoice_id = null;
    
            if(!isset($invoice_data)){
                $params["invoice"]["id_iregular_delivery_plan"] = $delivery_plan->id;
                $invoice_data = IregularDeliveryPlanInvoice::create($params["invoice"]);
            } else {
                $invoice_data->update($params["invoice"]);          
            }

            $invoice_id = $invoice_data->id;

            foreach($params["invoice_detail"] as $item){
                if(isset($item["id"])){
                    IregularDeliveryPlanInvoiceDetail::find($item["id"])->update($item);
                } else {
                    $item["id_iregular_delivery_plan_invoice"] = $invoice_id;
                    IregularDeliveryPlanInvoiceDetail::create($item);
                }
            }

           
            if($is_transaction) DB::commit();
            Cache::flush([self::cast]); //delete cache
        } catch (\Throwable $th) {
            if($is_transaction) DB::rollBack();
            throw $th;
        }
    }


    public static function getInvoice($params, $id_iregular_order_entry)
    {
        $delivery_plan = IregularDeliveryPlan::where('id_iregular_order_entry', $id_iregular_order_entry)->first();
        if(!$delivery_plan) throw new \Exception("id tidak ditemukan", 400);

        $invoice_data = IregularDeliveryPlanInvoice::where('id_iregular_delivery_plan', $delivery_plan->id)->first();

        if(!isset($invoice_data)){
            $order_entry = IregularOrderEntry::find($id_iregular_order_entry);
            if(!$order_entry) throw new \Exception("id tidak ditemukan", 400);

            $invoice_data = new \stdClass;
            $invoice_data->messrs = $order_entry->company_consignee;
            $invoice_data->date = $order_entry->stuffing_date;
            $invoice_data->bill_to = $order_entry->requestor;
            $invoice_data->invoice_no = $order_entry->invoice_no;
            $invoice_data->shipped_by = MstShippedBy::find($order_entry->id_shipped)->name;
            $invoice_data->shipped_to = $order_entry->entity_site;
            $invoice_data->logistic_division = $order_entry->section;
            $invoice_data->phone_no = $order_entry->phone_consignee;
            $invoice_data->fax = $order_entry->fax_consignee;
        }

        return [ 'items' => $invoice_data ];
    }

    public static function getInvoiceDetail($params, $id_iregular_order_entry)
    {
        $delivery_plan = IregularDeliveryPlan::where('id_iregular_order_entry', $id_iregular_order_entry)->first();
        if(!$delivery_plan) throw new \Exception("id tidak ditemukan", 400);

        $invoice_data = IregularDeliveryPlanInvoice::where('id_iregular_delivery_plan', $delivery_plan->id)->first();
        $invoice_detail_data = [];

        if(!isset($invoice_data)){
            $order_entry_part = IregularOrderEntryPart::where("id_iregular_order_entry", $id_iregular_order_entry)->get();
            foreach($order_entry_part as $part){
                $item = new  \stdClass;
                $item->order_no = $part->order_no;
                $item->no_package = 0;
                $item->description = $part->item_code."   ".$part->item_name;
                $item->qty = $part->qty;
                $item->unit_price = $part->price;
                $item->amount = $part->qty * $part->price;

                array_push($invoice_detail_data, $item);
            }
        } else {
            $invoice_detail_data = IregularDeliveryPlanInvoiceDetail::where('id_iregular_delivery_plan_invoice', $invoice_data->id)->get();
        }      

        return [ 'items' => $invoice_detail_data ];
    }

    public static function downloadFiles($params, $id_iregular_order_entry)
    {
        $order_entry_doc = IregularOrderEntryDoc::where('id_iregular_order_entry', $id_iregular_order_entry)->get();
        $filepath = [];
        foreach($order_entry_doc as $doc){
            $filepath[] = Storage::path($doc->path);
        }



        return $filepath;
    }

}
