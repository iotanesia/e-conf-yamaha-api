<?php

namespace App\Query;

use App\Constants\Constant;
use App\Models\IregularOrderEntry AS Model;
use App\ApiHelper as Helper;
use App\Models\IregularOrderEntryDoc;
use App\Models\IregularOrderEntryPart;
use App\Models\IregularOrderEntryCheckbox;
use App\Models\IregularDeliveryPlan;
use App\Models\IregularDeliveryPlanDoc;
use App\Models\IregularDeliveryPlanPart;
use App\Models\IregularDeliveryPlanCheckbox;
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

class QueryIregularOrderEntry extends Model {

    const cast = 'iregular-order-entry';

    public static function getAll($params, $status_tracking = null)
    {
        $key = self::cast.json_encode($params->query());
        return Helper::storageCache($key.(isset($status_tracking) ? json_encode($status_tracking) : ''), function () use ($params, $status_tracking){
            $query = self::select('iregular_order_entry.*')
                ->leftJoin('iregular_order_entry_tracking', function($join) {
                    $join->on('iregular_order_entry_tracking.id_iregular_order_entry', '=', 'iregular_order_entry.id')
                        ->where('iregular_order_entry_tracking.id', function($subquery) {
                                $subquery->selectRaw('MAX(id)')
                                    ->from('iregular_order_entry_tracking')
                                    ->whereColumn('id_iregular_order_entry', 'iregular_order_entry.id');
                        });
                });

            if (isset($status_tracking)) {
                $query->where('iregular_order_entry_tracking.status', $status_tracking);
            }

            // if ($item->tracking == 1) $tracking = 'Draft';
            // if ($item->tracking == 2) $tracking = 'Approval Dc Spv';
            // if ($item->tracking == 3) $tracking = 'Approval Dc Manager';
            // if ($item->tracking == 4) $tracking = 'Enquiry';
            // if ($item->tracking == 5) $tracking = 'Shipping';
            // if ($item->tracking == 6) $tracking = 'Approval CC Spv';
            // if ($item->tracking == 7) $tracking = 'Approval CC Manager';
            // if ($item->tracking == 8) $tracking = 'Finish';
                
            
            $category = $params->category ?? null;
            if ($category) {
                $query->where('iregular_order_entry.' . $category, 'ilike', $params->kueri);
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
                    $item->shipped_by = $item->refShippedBy;
                    unset($item->refShippedBy);
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

    public static function storeData($request,$is_transaction = true)
    {
        if($is_transaction) DB::beginTransaction();
        try {
            $params = $request->all();

            $files = $params["files"];
            foreach($files as $file){
                $ext = $file->getClientOriginalExtension();
                if(!in_array($ext,['pdf'])) throw new \Exception("file format error", 400);
            }


            $order_entry = json_decode($params["order_entry"], true);
            $order_entry_checkbox = self::getParamCheckbox($order_entry);
            $insert = Model::create($order_entry);

            IregularOrderEntryTracking::create([
                'id_iregular_order_entry' => $insert->id,
                'status' => 1,
                'description' => 'Draft'
            ]);
            
            $checkbox = [];
            foreach ($order_entry_checkbox as $item) {
                $checkbox[] = [
                    'id_iregular_order_entry' => $insert->id,
                    'id_value' => $item['id'],
                    'type' => $item['type'],
                    'value' => $item['value']
                ];
            }
            $insert->manyOrderEntryCheckbox()->createMany($checkbox);

            $id = $insert->id;          

            // part
            $i = 0;
            $part = json_decode($params["part"], true);
            foreach ($part as $key => $value) {
                $arr = $value;
                $id_order_entry = ['id_iregular_order_entry' => $id];
                IregularOrderEntryPart::create(array_merge($arr,$id_order_entry));
                $i++;
            }

            // document
            
            $uploadIndex = 0;
            $datas = json_decode($params["document"], true); 

            foreach($datas as $data){

                if($data["is_upload"] == true){
                    // Remove special characters and spaces and replace them with underscores
                    $doc_name = preg_replace('/[^A-Za-z0-9\-]/', '_', $data["name_doc"]);
                    // Replace multiple underscores with a single underscore
                    $doc_name = preg_replace('/_+/', '_', $doc_name);
                    $uuid = (string) Str::uuid();

                    $filename = 'OE-IREGULAR-'.$doc_name."-".$uuid;
                    $savedname = $uuid.'.'.$ext;

                    $ext = $files[$uploadIndex]->getClientOriginalExtension();

                    $path = '/order-entry/iregular/'.date('Y').date('m').date('d').'/'.$savedname;
                    Storage::putFileAs(str_replace($savedname,'',$path),$files[$uploadIndex],$savedname);

                    IregularOrderEntryDoc::create([
                        'filename' => $filename,
                        'id_iregular_order_entry' => $id,
                        'id_doc' => $data["id_doc"],
                        'path' => $path,
                        'extension' => $ext,
                        'is_completed' => 0
                    ]);

                    $uploadIndex++;

                } else {
                    IregularOrderEntryDoc::create([
                        'id_iregular_order_entry' => $id,
                        'id_doc' => $data["id_doc"],
                        'is_completed' => 0
                    ]);
                }

            }


            if($is_transaction) DB::commit();
            Cache::flush([self::cast]); //delete cache
            return ['items' => ['id' => $insert->id]];
        } catch (\Throwable $th) {
            if($is_transaction) DB::rollBack();
            throw $th;
        }
    }

    public static function getParamCheckbox($params) {
        $comodities = [];
        foreach ($params['comodities'] as $value) {
            $value['type'] = 'comodities';
            array_push($comodities, $value);
        }

        $good_condition = [];
        foreach ($params['good_condition'] as $value) {
            $value['type'] = 'good_condition';
            array_push($good_condition, $value);
        }

        $good_status = [];
        foreach ($params['good_status'] as $value) {
            $value['type'] = 'good_status';
            array_push($good_status, $value);
        }

        $incoterms = [];
        foreach ($params['incoterms'] as $value) {
            $value['type'] = 'incoterms';
            array_push($incoterms, $value);
        }

        $res = array_merge($comodities,$good_condition,$good_status,$incoterms);
        return $res;
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
        $data->checkbox = $data->manyOrderEntryCheckbox;
        $data->doc = $data->manyOrderEntryDoc;
        $data->part = $data->manyOrderEntryPart;
        $data->tracking = $data->manyTracking;

        unset($data->refTypeTransaction);
        unset($data->manyOrderEntryCheckbox);
        unset($data->manyOrderEntryDoc);
        unset($data->manyOrderEntryPart);
        unset($data->manyTracking);
                    
        return [
            'items' => $data,
        ];
    }
    
    public static function getDoc($params, $id)
    {
        $data = IregularOrderEntryDoc::where('id_iregular_order_entry', $id)->paginate($params->limit ?? null);

        return [
            'items' => $data->getCollection()->transform(function($item){
                
                $item->name_doc = $item->MstDoc->name;
                unset(
                    $item->MstDoc,
                    $item->id_iregular_order_entry,
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


    public static function getFile($params, $id_iregular_order_entry, $id_doc)
    {
        $data = IregularOrderEntryDoc::where(['id_iregular_order_entry'=> $id_iregular_order_entry, 'id_doc' => $id_doc])->first();
        if(!$data) throw new \Exception("id tidak ditemukan", 400);

        $path = $data->filename.".".$data->extension;
        $filename = basename($path);

        $result["path"] = Storage::path($data->path);
        $result["filename"] = $filename;
        return $result;
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
                "id_iregular_order_entry" => $data->id,
                "status" => $to_tracking,
                "description" => $description
            ]);

            if($to_tracking == 4){
                $delivery_plan = [
                    'id_iregular_order_entry' => $data->id,
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
    
                $insert_delivery_plan = IregularDeliveryPlan::create($delivery_plan);
    
                $order_entry_checkbox = IregularOrderEntryCheckbox::where('id_iregular_order_entry', $data->id)->get();
                $checkbox = [];
                foreach ($order_entry_checkbox as $item) {
                    $checkbox[] = [
                        'id_iregular_delivery_plan' => $insert_delivery_plan->id,
                        'id_value' => $item['id'],
                        'type' => $item['type'],
                        'value' => $item['value']
                    ];
                }
                $insert_delivery_plan->manyDeliveryPlanCheckbox()->createMany($checkbox);
    
                $order_entry_part = IregularOrderEntryPart::where('id_iregular_order_entry', $data->id)->get();
                $part = [];
                foreach ($order_entry_part as $item) {
                    $part[] = [
                        "id_iregular_delivery_plan" => $insert_delivery_plan->id,
                        "item_code" => $item->item_code,
                        "item_name" => $item->item_name,
                        "order_no" => $item->order_no,
                        "qty" => $item->qty,
                        "price" => $item->price,
                        "net_weight" => $item->net_weight,
                        "gross_weight" => $item->gross_weight,
                        "measurement" => $item->measurement,
                        "summary_box" => $item->summary_box,
                    ];
                }
                $insert_delivery_plan->manyDeliveryPlanPart()->createMany($part);
    
                $order_entry_doc = IregularOrderEntryDoc::where('id_iregular_order_entry', $data->id)->get();
                $doc = [];
                foreach ($order_entry_doc as $item) {
                    $doc[] = [
                        'id_doc' => $item->id_doc,
                        'path' => $item->path,
                        'extension' => $item->extension,
                        'filename' => $item->filename,
                        'is_completed' => $item->is_completed,
                    ];
                }
    
                $insert_delivery_plan->manyDeliveryPlanDoc()->createMany($doc);
            }
            
            if($is_transaction) DB::commit();
            Cache::flush([self::cast]); //delete cache
        } catch (\Throwable $th) {
            if($is_transaction) DB::rollBack();
            throw $th;
        }
    }

}
