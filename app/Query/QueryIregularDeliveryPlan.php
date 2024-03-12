<?php

namespace App\Query;

use App\Constants\Constant;
use App\Models\IregularDeliveryPlan AS Model;
use App\ApiHelper as Helper;
use App\Models\IregularDeliveryPlan;
use App\Models\IregularDeliveryPlanInvoice;
use App\Models\IregularDeliveryPlanInvoiceDetail;
use App\Models\IregularDeliveryPlanPacking;
use App\Models\IregularDeliveryPlanPackingDetail;
use App\Models\IregularDeliveryPlanCaseMark;
use App\Models\IregularOrderEntry;
use App\Models\IregularOrderEntryDoc;
use App\Models\IregularOrderEntryPart;
use App\Models\IregularOrderEntryTracking;
use App\Models\IregularPacking;
use App\Models\IregularShippingInstruction;
use App\Models\IregularShippingInstructionCreation;
use App\Models\MstComodities;
use App\Models\MstDoc;
use App\Models\MstPart;
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

    public static function getAll($params)
    {
        $key = self::cast.json_encode($params->query());
        return Helper::storageCache($key, function () use ($params){
            $query = self::where(function ($query) use ($params){

                $category = $params->category ?? null;
                if($category) {
                    $query->where($category, 'ilike', $params->kueri);
                }

            });

            if($params->withTrashed == 'true') $query->withTrashed();
            $data = $query->paginate($params->limit ?? 10);
            $totalRow = self::count();
            $lastPage = ceil($totalRow/($params->limit ?? 10));

            return [
                'items' => $data->getCollection()->transform(function($item){
                    $item->order_entry = $item->refOrderEntry;
                    $item->order_entry->shipped_by = $item->refOrderEntry->refShippedBy;
                    $item->order_entry->type_transaction = $item->refOrderEntry->refTypeTransaction;
                    $item->order_entry->tracking = $item->refOrderEntry->manyTracking;

                    unset($item->order_entry->refShippedBy);
                    unset($item->order_entry->refTypeTransaction);
                    unset($item->order_entry->manyTracking);
                    unset($item->refOrderEntry);
                    
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


            $data = IregularOrderEntry::find($id);
            $delivery_plan = self::where(["id_iregular_order_entry" => $id])->first();
            if(!$data || !$delivery_plan) throw new \Exception("id tidak ditemukan", 400);

            $order_entry = $params;

            $data->update($order_entry);

            
            $checkbox = [];
            foreach ($params["incoterms"] as $item) {
                $checkbox[] = [
                    'id_iregular_order_entry' => $data->id,
                    'id_value' => $item['id'],
                    'type' => 'incoterms',
                    'value' => $item['value']
                ];
            }
            $data->manyOrderEntryCheckbox()->createMany($checkbox);            
            
            $packing = IregularPacking::create([
                "id_iregular_delivery_plan" => $delivery_plan->id,
                "status" => 1,
                "jenis_truck" => "LCL",
                "yth" => $data->requestor,
                "username" => $data->company_consignee,
                "delivery_date" => $data->etd_date,
                "ref_invoice_no" => $params["invoice_no"]
            ]);
 
            $details = [];
            $parts = IregularOrderEntryPart::where(["id_iregular_order_entry" => $id])->get();
            foreach ($parts as $item) {
                $part = MstPart::where(["item_no" => $item->item_code])->first();

                $details[] = [
                    'id_iregular_packing' => $packing->id,
                    'item_name' => $part  ? $part->description : $item->item_name,
                    'item_no' => $part  ? $part->item_serial : $item->code,
                    'qty' => $item->qty,
                    'po_no' => $item->order_no,
                    'invoice_no' => $params["invoice_no"]
                ];
            }
            $packing->manyDetail()->createMany($details);


            IregularOrderEntryTracking::create([
                "id_iregular_order_entry" => $id,
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

    public static function getByIdIregularOrderEntry($params, $id_iregular_order_entry)
    {
        $data = self::where('id_iregular_order_entry', $params['id_iregular_order_entry'])->first();
        if(!$data) throw new \Exception("id tidak ditemukan", 400);

        return ['items' => $data];
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

            if($to_tracking == 8){
                $insert = IregularShippingInstruction::create([
                    "id_iregular_delivery_plan" => $data->id,
                    "status" => 1
                ]);

                $deliveryPlanInvoice = IregularDeliveryPlanInvoice::where(["id_iregular_delivery_plan" => $data->id])->first();
                if(!$deliveryPlanInvoice) throw new \Exception("id tidak ditemukan", 400);

                $orderEntry = IregularOrderEntry::find($params['id_iregular_order_entry']);
                if(!$orderEntry) throw new \Exception("id tidak ditemukan", 400);
                
                IregularShippingInstructionCreation::create([
                    "id_iregular_shipping_instruction" => $insert->id,
                    "shipper_address" => "PT. YAMAHA MOTOR PARTS MANUFACTURING INDONESIA
                    JL. PERMATA RAYA LOT F2 & F6
                    KAWASAN INDUSTRI KIIC. KARAWANG 41361
                    PO. BOX 157. WEST JAVA - INDONESIA",
                    "shipper_tel" => "+6221-8904581",
                    "shipper_fax" => "+6221-8904241",
                    "shipper_tax_id" => "01.071.650.4-055.000",
                    "si_number" => $deliveryPlanInvoice->invoice_no,
                    "consignee_address" => "$orderEntry->company_consignee
                    $orderEntry->address_consignee",
                    "consignee_tel" => $orderEntry->phone_consignee,
                    "consignee_fax" => $orderEntry->fax_consignee,
                    "notify_part_address" => "$orderEntry->company_consignee
                    $orderEntry->address_consignee",
                    "notify_part_tel" => $orderEntry->phone_consignee,
                    "notify_part_fax" => $orderEntry->fax_consignee,
                    "bl" => "Please issued as Sea WayBill",
                    "description_of_goods_1_detail" => $deliveryPlanInvoice->type_package,
                    "description_of_goods_2_detail" => "OF ".$deliveryPlanInvoice->description_invoice
                ]);
            }

            if($is_transaction) DB::commit();
            Cache::flush([self::cast]); //delete cache
        } catch (\Throwable $th) {
            if($is_transaction) DB::rollBack();
            throw $th;
        }
    }


    // public static function getShippingInstructionList($params, $status_tracking = null)
    // {
    //     $key = self::cast.json_encode($params->query());
    //     return Helper::storageCache('shippinginstruction-'.$key.(isset($status_tracking) ? json_encode($status_tracking) : ''), function () use ($params, $status_tracking){
    //         $query = IregularDeliveryPlanShippingInstruction::select('iregular_delivery_plan_shipping_instruction.*')
    //             ->leftJoin('iregular_delivery_plan', function($join){
    //                 $join->on('iregular_delivery_plan.id', '=', 'iregular_delivery_plan_shipping_instruction.id_iregular_delivery_plan');
    //             })
    //             ->leftJoin('iregular_order_entry_tracking', function($join) {
    //                 $join->on('iregular_order_entry_tracking.id_iregular_order_entry', '=', 'iregular_delivery_plan.id_iregular_order_entry')
    //                     ->where('iregular_order_entry_tracking.id', function($subquery) {
    //                             $subquery->selectRaw('MAX(id)')
    //                                 ->from('iregular_order_entry_tracking')
    //                                 ->whereColumn('id_iregular_order_entry', 'iregular_delivery_plan.id_iregular_order_entry');
    //                     });
    //             });

    //         if (isset($status_tracking)) {
    //             $query->where('iregular_order_entry_tracking.status', $status_tracking);
    //         }

    //         $category = $params->category ?? null;
    //         if ($category) {
    //             $query->where('iregular_delivery_plan_shipping_instruction.' . $category, 'ilike', $params->kueri);
    //         }
            
    //         if ($params->withTrashed == 'true') {
    //             $query->withTrashed();
    //         }
            
    //         $data = $query->paginate($params->limit ?? 10);
            
    //         if (isset($status_tracking)) {
    //             $totalRow = $query->where('iregular_order_entry_tracking.status', $status_tracking)->count();
    //         } else {
    //             $totalRow = IregularDeliveryPlanShippingInstruction::count();
    //         }

    //         $lastPage = ceil($totalRow/($params->limit ?? 10));
    //         return [
    //             'items' => $data->getCollection()->transform(function($item){

    //                 $item->delivery_plan = $item->refDeliveryPlan;
    //                 $item->type_transaction = $item->refTypeTransaction;
    //                 if(isset($item->delivery_plan)){
    //                     $item->delivery_plan->tracking = $item->delivery_plan->manyTracking;
    //                     unset($item->delivery_plan->manyTracking);
    //                 }
    //                 unset($item->refTypeTransaction);
    //                 unset($item->refDeliveryPlan);
                    
    //                 return $item;
    //             }),
    //             'last_page' => $lastPage,
    //             'attributes' => [
    //                 'total' => $data->total(),
    //                 'current_page' => $data->currentPage(),
    //                 'from' => $data->currentPage(),
    //                 'per_page' => (int) $data->perPage(),
    //             ]
    //         ];
    //     });
    // }

    // public static function getShippingInstruction($params, $id)
    // {
    //     $data = IregularDeliveryPlanShippingInstruction::find($id);
    //     $data->type_transaction = $data->refTypeTransaction;
    //     $data->good_payment = $data->refGoodPayment;
    //     $data->doc_type = $data->refDocType;
    //     $data->freight_charge = $data->refFreightCharge;
    //     $data->insurance = $data->refInsurance;
    //     $data->duty_tax = $data->refDutyTax;
    //     $data->inland_cost = $data->refInlandCost;
    //     $data->shipped_by = $data->refShippedBy;
    //     $data->freight = $data->refFreight;
    //     $data->good_criteria = $data->refGoodCriteria;

    //     unset($data->refTypeTransaction);
    //     unset($data->refGoodPayment);
    //     unset($data->refDocType);
    //     unset($data->refFreightCharge);
    //     unset($data->refInsurance);
    //     unset($data->refDutyTax);
    //     unset($data->refInlandCost);
    //     unset($data->refShippedBy);
    //     unset($data->refFreight);
    //     unset($data->refGoodCriteria);
    //     return [
    //         'items' => $data
    //     ];
    // }

    // public static function getShippingInstructionDraftList($params, $id){
    //     $data = IregularDeliveryPlanShippingInstructionDraft::where('id_iregular_delivery_plan_shipping_instruction', $id)->get();
    //     return [
    //         'items' => $data
    //     ];
    // }

    // public static function getShippingInstructionDraft($params, $id){
    //     $data = IregularDeliveryPlanShippingInstructionDraft::where('id_iregular_delivery_plan_shipping_instruction', $id)->first();
    //     return [
    //         'items' => $data
    //     ];
    // }

    // public static function shippingInstructionStore($request,$is_transaction = true)
    // {
    //     if($is_transaction) DB::beginTransaction();
    //     try {
    //         $request1 = $request->except(['container_count','container_value','container_type']);
    //         $request2 = [
    //                         'container_count' => implode(',',$request->container_count) == "" ? null : implode(',',$request->container_count),
    //                         'container_value' => implode(',',$request->container_value) == "" ? null : implode(',',$request->container_value),
    //                         'container_type' => implode(',',$request->container_type) == "" ? null : implode(',',$request->container_type),
    //                     ];
    //         $params = array_merge($request1,$request2);
    //         Helper::requireParams([
    //             'to',
    //             'cc',
    //         ]);

    //         $data = IregularDeliveryPlanShippingInstructionDraft::where('id_iregular_delivery_plan_shipping_instruction', $params["id_iregular_delivery_plan_shipping_instruction"])->first();
    //         if(isset($data)){
    //             $update = $data->update($params);
    //         } else {
    //             $insert = IregularDeliveryPlanShippingInstructionDraft::create($params);
    //         }
    //         if($is_transaction) DB::commit();
    //         Cache::flush([self::cast]); //delete cache
    //     } catch (\Throwable $th) {
    //         if($is_transaction) DB::rollBack();
    //         throw $th;
    //     }
    // }

    public static function storeInvoice($request, $id_iregular_order_entry, $is_transaction = true)
    {
        if($is_transaction) DB::beginTransaction();
        try {
            $params = $request->all();

            $token = $request->header("Authorization");
            $token = Str::replaceFirst('Bearer ', '', $token);
            $tokenData = Helper::decodeJwtSignature($token, env("JWT_SECRET"));

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

            IregularOrderEntryTracking::create([
                "id_iregular_order_entry" => $id_iregular_order_entry,
                "status" => 5,
                "id_user" => $tokenData->sub->id,
                "id_role" => $tokenData->sub->id_role,
                "id_position" => $tokenData->sub->id_position,
                'description' => Constant::STS_PROCESS_IREGULAR[5]
            ]);
           
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
            $invoice_data->to = $order_entry->requestor;
            $invoice_data->date = $order_entry->stuffing_date;
            $invoice_data->bill_to = $order_entry->name_consignee;
            $invoice_data->invoice_no = "";
            $invoice_data->shipped_by = $order_entry->company_consignee;
            $invoice_data->shipped_to = $order_entry->entity_site;
            $invoice_data->attn = "Logistic Devision";
            $invoice_data->phone_no = $order_entry->phone_consignee;
            $invoice_data->fax = $order_entry->fax_consignee;
            $invoice_data->email = $order_entry->email_consignee;
            $invoice_data->trading_term = $order_entry->invoice_no;
            $invoice_data->city = $order_entry->entity_site;
        }

        return [ 'items' => $invoice_data ];
    }

    public static function getInvoiceDetail($params, $id_iregular_order_entry)
    {
        $delivery_plan = IregularDeliveryPlan::where('id_iregular_order_entry', $id_iregular_order_entry)->first();
        if(!$delivery_plan) throw new \Exception("id tidak ditemukan", 400);

        $order_entry = IregularOrderEntry::find($id_iregular_order_entry);
        if(!$order_entry) throw new \Exception("id tidak ditemukan", 400);

        $invoice_data = IregularDeliveryPlanInvoice::where('id_iregular_delivery_plan', $delivery_plan->id)->first();
        $invoice_detail_data = [];

        if(!isset($invoice_data)){
            $order_entry_part = IregularOrderEntryPart::where("id_iregular_order_entry", $id_iregular_order_entry)->get();
            foreach($order_entry_part as $part){
                $item = new  \stdClass;
                $item->order_no = $part->order_no;
                $item->no_package = 0;
                $_part = MstPart::where(['item_no' => $part->item_code])->first();
                $item->hs_code = isset($_part) ? $_part->hs_code : "";
                $item->description = $part->item_code."   ".$part->item_name;
                $item->qty = $part->qty;
                $item->currency = $order_entry->currency;
                $item->unit_price = $part->price / $order_entry->rate;
                $item->amount = $part->qty * $part->price / $order_entry->rate;

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

    public static function getPackingList($params, $id_iregular_order_entry)
    {
        $delivery_plan = IregularDeliveryPlan::where('id_iregular_order_entry', $id_iregular_order_entry)->first();
        if(!$delivery_plan) throw new \Exception("id tidak ditemukan", 400);

        $packing_data = IregularDeliveryPlanPacking::where('id_iregular_delivery_plan', $delivery_plan->id)->first();

        if(!isset($packing_data)){
            $order_entry = IregularOrderEntry::find($id_iregular_order_entry);
            if(!$order_entry) throw new \Exception("id tidak ditemukan", 400);

            $packing_data = new \stdClass;
            $packing_data->to = $order_entry->requestor;
            $packing_data->date = $order_entry->stuffing_date;
            $packing_data->shipped_by = $order_entry->company_consignee;
            $packing_data->shipped_to = $order_entry->entity_site;
            $packing_data->attn = $order_entry->section;
            $packing_data->phone_no = $order_entry->phone_consignee;
            $packing_data->fax = $order_entry->fax_consignee;
            $packing_data->city = $order_entry->entity_site;
        }

        return [ 'items' => $packing_data ];
    }

    public static function getPackingListDetail($params, $id_iregular_order_entry)
    {
        $delivery_plan = IregularDeliveryPlan::where('id_iregular_order_entry', $id_iregular_order_entry)->first();
        if(!$delivery_plan) throw new \Exception("id tidak ditemukan", 400);

        $order_entry = IregularOrderEntry::find($id_iregular_order_entry);
        if(!$order_entry) throw new \Exception("id tidak ditemukan", 400);

        $packing_data = IregularDeliveryPlanPacking::where('id_iregular_delivery_plan', $delivery_plan->id)->first();
        $packing_detail_data = [];

        if(!isset($packing_data)){
            $order_entry_part = IregularOrderEntryPart::where("id_iregular_order_entry", $id_iregular_order_entry)->get();
            foreach($order_entry_part as $part){
                $item = new  \stdClass;
                $item->description = $part->item_code."   ".$part->item_name;
                $item->qty = $part->qty;
                $item->nett_weight = $part->net_weight;
                $item->gross_weight = $part->gross_weight;
                $item->measurement = $part->measurement;

                array_push($packing_detail_data, $item);
            }
        } else {
            $packing_detail_data = IregularDeliveryPlanPackingDetail::where('id_iregular_delivery_plan_packing', $packing_data->id)->get();
        }      

        return [ 'items' => $packing_detail_data ];
    }

    public static function storePackingList($request, $id_iregular_order_entry, $is_transaction = true)
    {
        if($is_transaction) DB::beginTransaction();
        try {
            $params = $request->all();

            $token = $request->header("Authorization");
            $token = Str::replaceFirst('Bearer ', '', $token);
            $tokenData = Helper::decodeJwtSignature($token, env("JWT_SECRET"));

            $delivery_plan = IregularDeliveryPlan::where('id_iregular_order_entry', $id_iregular_order_entry)->first();
            if(!$delivery_plan) throw new \Exception("id tidak ditemukan", 400);
    
            $packing_data = IregularDeliveryPlanPacking::where('id_iregular_delivery_plan', $delivery_plan->id)->first();
            $invoice_id = null;
    
            if(!isset($packing_data)){
                $params["packing_list"]["id_iregular_delivery_plan"] = $delivery_plan->id;
                $packing_data = IregularDeliveryPlanPacking::create($params["packing_list"]);
            } else {
                $packing_data->update($params["packing_list"]);          
            }

            $packing_id = $packing_data->id;

            foreach($params["packing_list_detail"] as $item){
                if(isset($item["id"])){
                    IregularDeliveryPlanPackingDetail::find($item["id"])->update($item);
                } else {
                    $item["id_iregular_delivery_plan_packing"] = $packing_id;
                    IregularDeliveryPlanPackingDetail::create($item);
                }
            }

            IregularOrderEntryTracking::create([
                "id_iregular_order_entry" => $id_iregular_order_entry,
                "status" => 6,
                "id_user" => $tokenData->sub->id,
                "id_role" => $tokenData->sub->id_role,
                "id_position" => $tokenData->sub->id_position,
                'description' => Constant::STS_PROCESS_IREGULAR[6]
            ]);
           
            if($is_transaction) DB::commit();
            Cache::flush([self::cast]); //delete cache
        } catch (\Throwable $th) {
            if($is_transaction) DB::rollBack();
            throw $th;
        }
    }

    public static function getCaseMark($params, $id_iregular_order_entry)
    {
        $delivery_plan = IregularDeliveryPlan::where('id_iregular_order_entry', $id_iregular_order_entry)->first();
        if(!$delivery_plan) throw new \Exception("id tidak ditemukan", 400);

        $casemark_data = IregularDeliveryPlanCaseMark::where('id_iregular_delivery_plan', $delivery_plan->id)->first();

        if(!isset($casemark_data)){
            $order_entry = IregularOrderEntry::find($id_iregular_order_entry);
            if(!$order_entry) throw new \Exception("id tidak ditemukan", 400);

            $casemark_data = new \stdClass;
            $casemark_data->customer = "";
        }

        return [ 'items' => $casemark_data ];
    }


    public static function storeCaseMark($request, $id_iregular_order_entry, $is_transaction = true)
    {
        if($is_transaction) DB::beginTransaction();
        try {
            $params = $request->all();

            $token = $request->header("Authorization");
            $token = Str::replaceFirst('Bearer ', '', $token);
            $tokenData = Helper::decodeJwtSignature($token, env("JWT_SECRET"));

            $delivery_plan = IregularDeliveryPlan::where('id_iregular_order_entry', $id_iregular_order_entry)->first();
            if(!$delivery_plan) throw new \Exception("id tidak ditemukan", 400);
    
            $casemark_data = IregularDeliveryPlanCaseMark::where('id_iregular_delivery_plan', $delivery_plan->id)->first();
            if(!isset($casemark_data)){
                $params["id_iregular_delivery_plan"] = $delivery_plan->id;
                $casemark_data = IregularDeliveryPlanCaseMark::create($params);
            } else {
                $casemark_data->update($params);
            }

            IregularOrderEntryTracking::create([
                "id_iregular_order_entry" => $id_iregular_order_entry,
                "status" => 7,
                "id_user" => $tokenData->sub->id,
                "id_role" => $tokenData->sub->id_role,
                "id_position" => $tokenData->sub->id_position,
                'description' => Constant::STS_PROCESS_IREGULAR[7]
            ]);
           
            if($is_transaction) DB::commit();
            Cache::flush([self::cast]); //delete cache
        } catch (\Throwable $th) {
            if($is_transaction) DB::rollBack();
            throw $th;
        }
    }

    public static function approveDoc($request, $role, $is_transaction = true)
    {
        if($is_transaction) DB::beginTransaction();
        try {
            // $token = $request->header("Authorization");
            // $token = Str::replaceFirst('Bearer ', '', $token);
            // $tokenData = Helper::decodeJwtSignature($token, env("JWT_SECRET"));

            $params = $request->all();
            $data = self::where('id_iregular_order_entry', $params['id_iregular_order_entry'])->first();
            if(!$data) throw new \Exception("id tidak ditemukan", 400);

            $update = [];
            $addon = "";
            if($role == "spv"){
                $addon = "spv";
            } else if($role == "manager"){
                $addon = "mgr";
            }

            if($params["type"] == "invoice") $update["status_invoice_".$addon] = 1;
            else if ($params["type"] == "packing-list") $update["status_packing_list_".$addon] = 1;
            else if ($params["type"] == "casemark") $update["status_casemark_".$addon] = 1;
            else if ($params["type"] == "excel-converter") $update["status_excel_converter_".$addon] = 1;
            else if ($params["type"] == "csv") $update["status_csv_".$addon] = 1;

            $data->update($update);

            if($is_transaction) DB::commit();
            Cache::flush([self::cast]); //delete cache
        } catch (\Throwable $th) {
            if($is_transaction) DB::rollBack();
            throw $th;
        }
    }

    public static function rejectDoc($request, $role, $is_transaction = true)
    {
        if($is_transaction) DB::beginTransaction();
        try {
            // $token = $request->header("Authorization");
            // $token = Str::replaceFirst('Bearer ', '', $token);
            // $tokenData = Helper::decodeJwtSignature($token, env("JWT_SECRET"));

            $params = $request->all();
            $data = self::where('id_iregular_order_entry', $params['id_iregular_order_entry'])->first();
            if(!$data) throw new \Exception("id tidak ditemukan", 400);

            $update = [];
            $addon = "";
            if($role == "spv"){
                $addon = "spv";
            } else if($role == "manager"){
                $addon = "mgr";
            }

            if($params["type"] == "invoice") {
                $update["status_invoice_".$addon] = 2;
                $update["remark_invoice_".$addon] = $params["reason"];
            } else if ($params["type"] == "packing-list") {
                $update["status_packing_list_".$addon] = 2;
                $update["remark_packing_list_".$addon] = $params["reason"];
            } else if ($params["type"] == "casemark") {
                $update["status_casemark_".$addon] = 2;
                $update["remark_casemark_".$addon] = $params["reason"];
            } else if ($params["type"] == "excel-converter") {
                $update["status_excel_converter_".$addon] = 2;
                $update["remark_excel_converter_".$addon] = $params["reason"];
            } else if ($params["type"] == "csv") {
                $update["status_csv_".$addon] = 2;
                $update["remark_csv_".$addon] = $params["reason"];
            }

            $data->update($update);

            if($is_transaction) DB::commit();
            Cache::flush([self::cast]); //delete cache
        } catch (\Throwable $th) {
            if($is_transaction) DB::rollBack();
            throw $th;
        }
    }

}
