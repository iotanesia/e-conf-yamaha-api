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
use Barryvdh\DomPDF\Facade\Pdf;

class QueryIregularOrderEntry extends Model {

    const cast = 'iregular-order-entry';

    public static function getAll($params, $min_status_tracking = null)
    {
        $key = self::cast.json_encode($params->query());
        return Helper::storageCache($key, function () use ($params, $min_status_tracking){
            $query = self::select('iregular_order_entry.*')
                ->leftJoin('iregular_order_entry_tracking', function($join) {
                    $join->on('iregular_order_entry_tracking.id_iregular_order_entry', '=', 'iregular_order_entry.id')
                        ->where('iregular_order_entry_tracking.id', function($subquery) {
                                $subquery->selectRaw('MAX(id)')
                                    ->from('iregular_order_entry_tracking')
                                    ->whereColumn('id_iregular_order_entry', 'iregular_order_entry.id');
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

            $token = $request->header("Authorization");
            $token = Str::replaceFirst('Bearer ', '', $token);
            $tokenData = Helper::decodeJwtSignature($token, env("JWT_SECRET"));


            $files = $params["files"];
            foreach($files as $file){
                $ext = $file->getClientOriginalExtension();
                if(!in_array($ext,['pdf'])) throw new \Exception("file format error", 400);
            }


            $order_entry = json_decode($params["order_entry"], true);
            $order_entry_checkbox = self::getParamCheckbox($order_entry);
            $insert = Model::create($order_entry);

            IregularOrderEntryTracking::create([
                "id_iregular_order_entry" => $insert->id,
                "status" => 1,
                "id_user" => $tokenData->sub->id,
                "id_role" => $tokenData->sub->id_role,
                "id_position" => $tokenData->sub->id_position,
                'description' => Constant::STS_PROCESS_IREGULAR[1]
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

    public static function storeApprovalDoc($request, $id, $is_transaction = true){
        if($is_transaction) DB::beginTransaction();
        try {
            $params = $request->all();

            $token = $request->header("Authorization");
            $token = Str::replaceFirst('Bearer ', '', $token);
            $tokenData = Helper::decodeJwtSignature($token, env("JWT_SECRET"));

            $file = $params["file"];
            $ext = $file->getClientOriginalExtension();
            if(!in_array($ext,['pdf'])) throw new \Exception("file format error", 400);
                
            $data = self::find($id);
            if(!$data) throw new \Exception("id tidak ditemukan", 400);

            // Replace multiple underscores with a single underscore
            $uuid = (string) Str::uuid();
            $filename = 'approval_doc_iregular-'.$id;
            $savedname = $uuid.'.'.$ext;

            $path = '/order-entry/iregular/approval-doc/'.$savedname;
            Storage::putFileAs(str_replace($savedname,'',$path),$file,$savedname);
            $data->update([
                'approval_doc_filename' => $filename,
                'approval_doc_path' => $path,
                'approval_doc_extension' => $ext, 
            ]);

            IregularOrderEntryTracking::create([
                "id_iregular_order_entry" => $id,
                "status" => 2,
                "id_user" => $tokenData->sub->id,
                "id_role" => $tokenData->sub->id_role,
                "id_position" => $tokenData->sub->id_position,
                'description' => Constant::STS_PROCESS_IREGULAR[2]
            ]);

            IregularOrderEntryTracking::create([
                "id_iregular_order_entry" => $id,
                "status" => 3,
                "id_user" => $tokenData->sub->id,
                "id_role" => $tokenData->sub->id_role,
                "id_position" => $tokenData->sub->id_position,
                "description" => Constant::STS_PROCESS_IREGULAR[3]
            ]);

            $delivery_plan = IregularDeliveryPlan::where(['id_iregular_order_entry' => $id])->first();
            if(!isset($delivery_plan)){
                $insert_delivery_plan = IregularDeliveryPlan::create([
                    'id_iregular_order_entry' => $id,
                ]);
            }
            
            if($is_transaction) DB::commit();
            Cache::flush([self::cast]); //delete cache
            return ['items' => ['id' => $id]];
        } catch (\Throwable $th) {
            if($is_transaction) DB::rollBack();
            throw $th;
        }
    }

    public static function getParamCheckbox($params) {
        $comodities = [];
        if(isset($params['comodities'])){
            foreach ($params['comodities'] as $value) {
                $value['type'] = 'comodities';
                array_push($comodities, $value);
            }
        }

        $good_condition = [];
        if(isset($params['good_condition'])){
            foreach ($params['good_condition'] as $value) {
                $value['type'] = 'good_condition';
                array_push($good_condition, $value);
            }
        }
        
        $good_status = [];
        if(isset($params['good_status'])){
            foreach ($params['good_status'] as $value) {
                $value['type'] = 'good_status';
                array_push($good_status, $value);
            }
        }

        $incoterms = [];
        if(isset($params['incoterms'])){
            foreach ($params['incoterms'] as $value) {
                $value['type'] = 'incoterms';
                array_push($incoterms, $value);
            }
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

    public static function getApprovalFile($params, $id_iregular_order_entry)
    {
        $data = self::find($id_iregular_order_entry);
        if(!$data) throw new \Exception("id tidak ditemukan", 400);

        $path = $data->approval_doc_filename.".".$data->approval_doc_extension;
        $filename = basename($path);

        $result["path"] = Storage::path($data->approval_doc_path);
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

            $token = $request->header("Authorization");
            $token = Str::replaceFirst('Bearer ', '', $token);
            $tokenData = Helper::decodeJwtSignature($token, env("JWT_SECRET"));

            $params = $request->all();
            $data = self::find($params['id']);
            if(!$data) throw new \Exception("id tidak ditemukan", 400);

            if(!isset($description))
                $description = Constant::STS_PROCESS_IREGULAR[$to_tracking];

            IregularOrderEntryTracking::create([
                "id_iregular_order_entry" => $data->id,
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


    public static function printFormRequest($params,$id,$filename,$pathToFile, $role = "dc")
    {
        // try {
            $data = self::find($id);
            if(!$data) throw new \Exception("id tidak ditemukan", 400);

            Pdf::loadView('pdf.iregular.order-entry.form_request',[
                "data" => self::getFormData($params, $id)["items"],
                "form" => self::getForm($params)["items"],
                "doc" => MstDoc::select('*')->get(),
                "role" => $role
            ])
            ->save($pathToFile)
            ->setPaper('F4','potrait')
            ->download($filename);

        //   } catch (\Throwable $th) {
        //       return Helper::setErrorResponse($th);
        //   }
    }
}
