<?php

namespace App\Query;

use App\Constants\Constant;
use App\Models\IregularDeliveryPlan AS Model;
use App\ApiHelper as Helper;
use App\Exports\IregularCsvExport;
use App\Exports\IregularExcelExport;
use App\Models\IregularDeliveryPlan;
use App\Models\IregularDeliveryPlanInvoice;
use App\Models\IregularDeliveryPlanInvoiceDetail;
use App\Models\IregularDeliveryPlanPacking;
use App\Models\IregularDeliveryPlanPackingDetail;
use App\Models\IregularDeliveryPlanCaseMark;
use App\Models\IregularOrderEntry;
use App\Models\IregularOrderEntryCheckbox;
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
use App\Models\MstConsignee;
use App\Models\MstTypeTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;

class QueryIregularDeliveryPlan extends Model {

    const cast = 'iregular-delivery-plan';

    public static function getAll($params, $min_tracking = null)
    {
        $key = self::cast.json_encode($params->query());
        return Helper::storageCache($key, function () use ($params, $min_tracking){
            $query = self::select('iregular_delivery_plan.*')
                ->leftJoin('iregular_order_entry_tracking', function($join) {
                    $join->on('iregular_order_entry_tracking.id_iregular_order_entry', '=', 'iregular_delivery_plan.id_iregular_order_entry')
                        ->where('iregular_order_entry_tracking.id', function($subquery) {
                                $subquery->selectRaw('MAX(id)')
                                    ->from('iregular_order_entry_tracking')
                                    ->whereColumn('id_iregular_order_entry', 'iregular_delivery_plan.id_iregular_order_entry');
                        });
                });

            if (isset($min_tracking)) {
                $query->where('iregular_order_entry_tracking.status', '>=', $min_tracking);
            }

            if($params->withTrashed == 'true') $query->withTrashed();
            $totalRow = $query->count();
            $data = $query->paginate($params->limit ?? 10);
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

            IregularOrderEntryCheckbox::where(["id_iregular_order_entry" => $id, "type" => "incoterms"])->delete();
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
                $details[] = [
                    'id_iregular_packing' => $packing->id,
                    'item_name' => $item->item_name,
                    'item_no' => $item->code,
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

            $is_insert_tracking = true;
            
            if($to_tracking == 8){
                self::createShipping($data);
            } else if(in_array($to_tracking, [97,98,99])){
                $is_insert_tracking = self::backToPreviousTracking($data, $to_tracking, $tokenData, $description);
            }

            if($is_insert_tracking)
                self::createTracking($data->id_iregular_order_entry, $to_tracking, $tokenData, $description);

            if($is_transaction) DB::commit();
            Cache::flush([self::cast]); //delete cache
        } catch (\Throwable $th) {
            if($is_transaction) DB::rollBack();
            throw $th;
        }
    }

    private static function createTracking($id_iregular_order_entry, $to_tracking, $token_data, $description){
        IregularOrderEntryTracking::create([
            "id_iregular_order_entry" => $id_iregular_order_entry,
            "status" => $to_tracking,
            "id_user" => $token_data->sub->id,
            "id_role" => $token_data->sub->id_role,
            "id_position" => $token_data->sub->id_position,
            "description" => $description
        ]);
    }

    private static function createShipping($data){
        $insert = IregularShippingInstruction::create([
            "id_iregular_delivery_plan" => $data->id,
            "status" => 1
        ]);

        $deliveryPlanInvoice = IregularDeliveryPlanInvoice::where(["id_iregular_delivery_plan" => $data->id])->first();
        if(!$deliveryPlanInvoice) throw new \Exception("id tidak ditemukan", 400);

        $orderEntry = IregularOrderEntry::find($data->id_iregular_order_entry);
        if(!$orderEntry) throw new \Exception("id tidak ditemukan", 400);

        $deliveryPlanInvoiceDetail = IregularDeliveryPlanInvoiceDetail::where(["id_iregular_delivery_plan_invoice" => $deliveryPlanInvoice->id])->get();
        $summaryBox = 0;
        foreach ($deliveryPlanInvoiceDetail as $item){
            $summaryBox = $summaryBox + $item->no_package;
        }

        $orderEntryPart = IregularOrderEntryPart::where(["id_iregular_order_entry" => $data->id_iregular_order_entry])->get();
        $netWeight = 0;
        $grossWeight = 0;
        $measurement = 0;
        $qty = 0;
        foreach($orderEntryPart as $item){
            $netWeight = $netWeight + $item->net_weight; 
            $grossWeight = $grossWeight + $item->gross_weight; 
            $measurement = $measurement + $item->measurement; 
            $qty = $qty + $item->qty;
        }

        
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
            "id_shipped_by" => $orderEntry->id_shipped,
            "jenis_container" => $orderEntry->refShippedBy->name != "SEA" ? "-" : null,
            "notify_part_tel" => $orderEntry->phone_consignee,
            "notify_part_fax" => $orderEntry->fax_consignee,
            "bl" => "Please issued as Sea WayBill",
            "description_of_goods_1_detail" => $deliveryPlanInvoice->type_package,
            "description_of_goods_2_detail" => "OF ".$deliveryPlanInvoice->description_invoice,
            "summary_box" => $summaryBox,
            "net_weight" => $netWeight,
            "gross_weight" => $grossWeight,
            "measurement" => $measurement,
            "qty"   => $qty,
            "invoice_no" => sizeof($orderEntryPart) > 0 ? $orderEntryPart[0]->order_no : null,
            "packing_list_no" => sizeof($orderEntryPart) > 0 ? $orderEntryPart[0]->order_no : null
        ]);
    }

    private static function backToPreviousTracking($data, $to_tracking, $token_data, $description){
        $revision = $data->refOrderEntry->revision;
        if($revision >= Constant::MAX_IREGULAR_REVISION)
            return true;

        IregularDeliveryPlanCaseMark::where(["id_iregular_delivery_plan" => $data->id])->delete();
        $delivery_plan_invoice = IregularDeliveryPlanInvoice::where(["id_iregular_delivery_plan" => $data->id])->first();
        if(isset($delivery_plan_invoice)){
            IregularDeliveryPlanInvoiceDetail::where(["id_iregular_delivery_plan_invoice" => $delivery_plan_invoice->id])->delete();
            IregularDeliveryPlanInvoice::where(["id_iregular_delivery_plan" => $data->id])->delete();
        }
        $delivery_plan_packing = IregularDeliveryPlanPacking::where(["id_iregular_delivery_plan" => $data->id])->first();
        if(isset($delivery_plan_packing)){
            IregularDeliveryPlanPackingDetail::where(["id_iregular_delivery_plan_packing" => $delivery_plan_packing->id])->delete();
            IregularDeliveryPlanPacking::where(["id_iregular_delivery_plan" => $data->id])->delete();
        }
        $shipping_instruction = IregularShippingInstruction::where(["id_iregular_delivery_plan" => $data->id])->first();
        if(isset($shipping_instruction)){
            IregularShippingInstructionCreation::where(["id_iregular_shipping_instruction" => $shipping_instruction->id])->delete();
            IregularShippingInstruction::where(["id_iregular_delivery_plan" => $data->id])->delete();
        }

        IregularOrderEntryCheckbox::where(["id_iregular_order_entry" => $data->id_iregular_order_entry, "type" => "incoterms"])->delete();
        IregularOrderEntry::find($data->id_iregular_order_entry)->update([
            "invoice_no" => null,
            "entity_site" => null,
            "rate" => null,
            "delivery_site" => null,
            "receive_date" => null,
            "etd_date" => null,
            "currency" => null,
            "id_freight" => null,
            "id_good_criteria" => null,
            "revision"  => ($revision + 1)
        ]);
        
        if($to_tracking == 97){
            $to_tracking = 1;         
            IregularDeliveryPlan::where(["id" => $data->id])->delete();
        }
        else if($to_tracking == 98 || $to_tracking == 99){
            $to_tracking = 3;
        }

        self::createTracking($data->id_iregular_order_entry, $to_tracking, $token_data, $description);
        return false;
    }


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
            $invoice_data->attn = "Logistic Division";
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
            $column = [
                DB::raw('COUNT(id) as total_item'),
                DB::raw('SUM(qty) as qty'),
                DB::raw('SUM(price*2) as price'),
                DB::raw('order_no'),
                DB::raw('item_code'),
                DB::raw('item_name')
            ];
            $order_entry_part = IregularOrderEntryPart::select($column)
                    ->where("id_iregular_order_entry", $id_iregular_order_entry)
                    ->groupBy("order_no", "item_code", "item_name")
                    ->get();

            foreach($order_entry_part as $part){
                $item = new  \stdClass;
                $item->order_no = $part->order_no;
                $item->no_package = $part->total_item;
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

    public static function printInvoice($request,$id_iregular_order_entry,$pathToFile,$filename){
        try {
            $data = self::getInvoiceDetail($request, $id_iregular_order_entry);

            $package = 0;
            $qty = 0;
            $unit_price = 0;
            $amount = 0;
            $type_package = null;
            $id_iregular_delivery_plan_invoice = 0;
            foreach ($data['items'] as $value) {
                $package += $value->no_package;
                $qty += $value->qty;
                $unit_price += $value->unit_price;
                $amount += (int)$value->amount;
                $type_package = $value->refDeliveryPlanInvoice->type_package ?? null;
                $id_iregular_delivery_plan_invoice = $value->id_iregular_delivery_plan_invoice;
            }
            $total = [
                'packages' => $package,
                'qty' => $qty,
                'unit_price' => $unit_price,
                'amount' => $amount,
                'type_package' => $type_package
            ];

            $invoice_data = IregularDeliveryPlanInvoice::where('id_iregular_delivery_plan', $id_iregular_delivery_plan_invoice)->first();
            $delivery_plan = IregularDeliveryPlan::where('id_iregular_order_entry', $id_iregular_order_entry)->first();

            Pdf::loadView('pdf.iregular.invoice.invoice', [
                'data' => $data['items'],
                'total' => $total,
                'invoice_data' => $invoice_data,
                'delivery_plan' => $delivery_plan
            ])
            ->save($pathToFile)
            ->setPaper('A4','potrait')
            ->download($filename);
        } catch (\Throwable $th) {
            return Helper::setErrorResponse($th);
        }
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

    public static function exportExcel($request, $id_iregular_order_entry)
    {
        $orderEntryPart = IregularOrderEntryPart::select('iregular_order_entry_part.item_code',
                        DB::raw('SUM(iregular_order_entry_part.net_weight) as nett_weight'),
                        DB::raw('SUM(iregular_order_entry_part.gross_weight) as gross_weight'),
                        DB::raw('SUM(iregular_order_entry_part.measurement) as measurement'),
                        )
                        ->where('iregular_order_entry_part.id_iregular_order_entry', $id_iregular_order_entry)
                        ->groupBy('iregular_order_entry_part.item_code')
                        ->get();
        if(!$orderEntryPart) throw new \Exception("id tidak ditemukan", 400);

        $total = [];
        foreach ($orderEntryPart as $item) {
            $total[$item->item_code] = [
                'nett_weight' => $item->nett_weight,
                'gross_weight' => $item->gross_weight,
                'measurement' => $item->measurement,
            ];
        }

        $invoice_data = self::getInvoiceDetail($request, $id_iregular_order_entry);
        $data = [];
        foreach ($invoice_data['items'] as $key => $value) {
            $data[] = [
                'hs_code' => $value->hs_code,
                'description' => $value->description,
                'qty' => $value->qty,
                'total_package' => $value->no_package,
                'total_price' => $value->amount,
                'measurement' => $total[explode(' ',$value->description)[0]]['measurement'],
                'nett_weight' => $total[explode(' ',$value->description)[0]]['nett_weight'],
                'gross_weight' => $total[explode(' ',$value->description)[0]]['gross_weight']
            ];
        }

        $filename = 'excel-'.Carbon::now()->format('Ymd');
        return Excel::download(new IregularExcelExport($data), $filename.'.xlsx');
    }

    public static function exportCSV($request, $id_iregular_order_entry)
    {
        $delivery_plan = IregularDeliveryPlan::where('id_iregular_order_entry', $id_iregular_order_entry)->first();
        if(!$delivery_plan) throw new \Exception("id tidak ditemukan", 400);
        
        $orderEntryPart = IregularOrderEntryPart::select('iregular_order_entry_part.item_code',
                        DB::raw('SUM(iregular_order_entry_part.net_weight) as nett_weight'),
                        DB::raw('SUM(iregular_order_entry_part.gross_weight) as gross_weight'),
                        DB::raw('SUM(iregular_order_entry_part.measurement) as measurement'),
                        DB::raw("string_agg(DISTINCT iregular_order_entry_part.length::character varying, ',') as length"),
                        DB::raw("string_agg(DISTINCT iregular_order_entry_part.width::character varying, ',') as width"),
                        DB::raw("string_agg(DISTINCT iregular_order_entry_part.height::character varying, ',') as height"),
                        )
                        ->where('iregular_order_entry_part.id_iregular_order_entry', $id_iregular_order_entry)
                        ->groupBy('iregular_order_entry_part.item_code')
                        ->get();
        if(!$orderEntryPart) throw new \Exception("id tidak ditemukan", 400);

        $arr = [];
        foreach ($orderEntryPart as $item) {
            $arr[$item->item_code] = [
                'nett_weight' => $item->nett_weight,
                'gross_weight' => $item->gross_weight,
                'measurement' => $item->measurement,
                'length' => $item->length,
                'width' => $item->width,
                'height' => $item->height,
            ];
        }

        $casemark_data = IregularDeliveryPlanCaseMark::where('id_iregular_delivery_plan', $delivery_plan->id)->get();
        $model_code = [];
        foreach ($casemark_data as $val) {
            $model_code[$val->item_no] = [
                'model_code' => $val->model_code
            ];
        }

        $invoice_data = IregularDeliveryPlanInvoice::where('id_iregular_delivery_plan', $delivery_plan->id)->first();
        $invoiceDetail = self::getInvoiceDetail($request, $id_iregular_order_entry);
        $data = [];
        foreach ($invoiceDetail['items'] as $key => $value) {
            $data[] = [
                'gl_account' => 'G/L Account',
                'coa' => '4421000010',
                'cost_center' => $delivery_plan->refOrderEntry->cost_center ?? null,
                'container' => null,
                'kosong' => null,
                'po_no' => $value->order_no,
                'part_no' => explode(' ', $value->description)[0],
                'qty' => $value->qty,
                'no' =>  $key +1,
                'nett_weight' => $arr[explode(' ',$value->description)[0]]['nett_weight'],
                'gross_weight' => $arr[explode(' ',$value->description)[0]]['gross_weight'],
                'model_code' => $model_code[explode(' ',$value->description)[0]]['model_code'],
                'type_box' => $invoice_data->type_package,
                'length' => $arr[explode(' ',$value->description)[0]]['length'],
                'width' => $arr[explode(' ',$value->description)[0]]['width'],
                'height' => $arr[explode(' ',$value->description)[0]]['height'],
                'hs_code' => $value->hs_code
            ];
        }

        $filename = 'file-'.Carbon::now()->format('Ymd');
        return Excel::download(new IregularCsvExport($data), $filename.'.csv');
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
                $item->length = $part->length;
                $item->width = $part->width;
                $item->height = $part->height;

                array_push($packing_detail_data, $item);
            }
        } else {
            $packing_detail_data = IregularDeliveryPlanPackingDetail::where('id_iregular_delivery_plan_packing', $packing_data->id)->get();
        }      

        return [ 'items' => $packing_detail_data ];
    }
    
    public static function printPackingList($request,$id_iregular_order_entry,$pathToFile,$filename){
        try {
            $data = self::getPackingListDetail($request, $id_iregular_order_entry);

            $qty = 0;
            $nett_weight = 0;
            $gross_weight = 0;
            $measurement = 0;
            $id_iregular_delivery_plan_packing = 0;
            foreach ($data['items'] as $value) {
                $qty += $value->qty;
                $nett_weight += (float)$value->nett_weight;
                $gross_weight += (float)$value->gross_weight;
                $measurement += (float)$value->measurement;
                $id_iregular_delivery_plan_packing = $value->id_iregular_delivery_plan_packing;
            }

            $total = [
                'qty' => $qty,
                'nett_weight' => $nett_weight,
                'gross_weight' => $gross_weight,
                'measurement' => $measurement
            ];

            $packing_data = IregularDeliveryPlanPacking::where('id', $id_iregular_delivery_plan_packing)->first();

            Pdf::loadView('pdf.iregular.packing.packing_list', [
                'data' => $data['items'],
                'packing_data' => $packing_data,
                'total' => $total
            ])
            ->save($pathToFile)
            ->setPaper('A4','potrait')
            ->download($filename);
        } catch (\Throwable $th) {
            return Helper::setErrorResponse($th);
        }
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

        $casemark_data = IregularDeliveryPlanCaseMark::where('id_iregular_delivery_plan', $delivery_plan->id)->get();
        $order_entry = IregularOrderEntry::find($id_iregular_order_entry);
        if(!$order_entry) throw new \Exception("id tidak ditemukan", 400);


        if(sizeof($casemark_data) == 0){
            $casemark_data = [];
            $order_entry_part = IregularOrderEntryPart::where("id_iregular_order_entry", $id_iregular_order_entry)->get();
            foreach($order_entry_part as $part){
                $item = new  \stdClass;
                $item->qty = $part->qty;
                $item->gross_weight = $part->gross_weight;
                $item->item_no = $part->item_code;
                $item->customer = $order_entry->entity_site;
                $item->model_code = "";
                $item->destination = "";

                array_push($casemark_data, $item);
            }
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
    
            foreach($params as $casemark){
                $casemark["id_iregular_delivery_plan"] = $delivery_plan->id;
                $casemark_data = IregularDeliveryPlanCaseMark::create($casemark);
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

    public static function printCaseMark($request,$id_iregular_order_entry,$pathToFile,$filename)
    {
        try {
            $delivery_plan = IregularDeliveryPlan::where('id_iregular_order_entry', $id_iregular_order_entry)->first();
            if(!$delivery_plan) throw new \Exception("id tidak ditemukan", 400);

            $casemark_data = self::getCaseMark($request, $id_iregular_order_entry);

            Pdf::loadView('pdf.iregular.casemarks.casemarks_doc',[
                'data' => $casemark_data['items'],
                'entity_site' => $delivery_plan->refOrderEntry->entity_site ?? null,
                'invoice_no' => $delivery_plan->refOrderEntry->invoice_no ?? null,
                'part' => $delivery_plan->refOrderEntry->manyOrderEntryPart ?? null
            ])
            ->save($pathToFile)
            ->setPaper('A4','potrait')
            ->download($filename);
        } catch (\Throwable $th) {
            return Helper::setErrorResponse($th);
        }
    }

    public static function approveDoc($request, $role, $is_transaction = true)
    {
        if($is_transaction) DB::beginTransaction();
        try {
            $token = $request->header("Authorization");
            $token = Str::replaceFirst('Bearer ', '', $token);
            $tokenData = Helper::decodeJwtSignature($token, env("JWT_SECRET"));

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
            $data = self::where('id_iregular_order_entry', $params['id_iregular_order_entry'])->first();
            $rejected = self::checkApprovalDocument($role, $data, $tokenData);

            if($is_transaction) DB::commit();
            Cache::flush([self::cast]); //delete cache
            return ['items' => ['rejected' => $rejected]];
        } catch (\Throwable $th) {
            if($is_transaction) DB::rollBack();
            throw $th;
        }
    }

    public static function rejectDoc($request, $role, $is_transaction = true)
    {
        if($is_transaction) DB::beginTransaction();
        try {
            $token = $request->header("Authorization");
            $token = Str::replaceFirst('Bearer ', '', $token);
            $tokenData = Helper::decodeJwtSignature($token, env("JWT_SECRET"));

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
            $data = self::where('id_iregular_order_entry', $params['id_iregular_order_entry'])->first();
            $rejected = self::checkApprovalDocument($role, $data, $tokenData);
            

            if($is_transaction) DB::commit();
            Cache::flush([self::cast]); //delete cache
            return ['items' => ['rejected' => $rejected]];
        } catch (\Throwable $th) {
            if($is_transaction) DB::rollBack();
            throw $th;
        }
    }

    private static function checkApprovalDocument($role, $data, $tokenData){
        $rejected = false;
        if($role == "spv"){
            if(
                (
                    $data->status_invoice_spv != null && 
                    $data->status_packing_list_spv != null && 
                    $data->status_casemark_spv != null && 
                    $data->status_excel_converter_spv != null && 
                    $data->status_csv_spv != null
                ) && 
                (
                    $data->status_invoice_spv == 2 || 
                    $data->status_packing_list_spv == 2 || 
                    $data->status_casemark_spv == 2 || 
                    $data->status_excel_converter_spv == 2 || 
                    $data->status_csv_spv == 2
                )
            ) {
                self::backToPreviousTracking($data, 98, $tokenData, 'Document rejected by CC Supervisor');
                $rejected = true;
            }
        } else if($role == "manager"){
            if(
                (
                    $data->status_invoice_mgr != null && 
                    $data->status_packing_list_mgr != null && 
                    $data->status_casemark_mgr != null && 
                    $data->status_excel_converter_mgr != null && 
                    $data->status_csv_mgr != null
                ) && 
                (
                    $data->status_invoice_mgr == 2 || 
                    $data->status_packing_list_mgr == 2 || 
                    $data->status_casemark_mgr == 2 || 
                    $data->status_excel_converter_mgr == 2 || 
                    $data->status_csv_mgr == 2
                )
            ) {
                self::backToPreviousTracking($data, 99, $tokenData, 'Document rejected by CC Supervisor');
                $rejected = true;
            }
        }

        return $rejected;
    }

}
