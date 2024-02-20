<?php

namespace App\Query;

use App\Constants\Constant;
use App\Models\IregularOrderEntry AS Model;
use App\ApiHelper as Helper;
use App\Models\IregularOrderEntryDoc;
use App\Models\IregularOrderEntryPart;
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
            if($params->dropdown == Constant::IS_ACTIVE) {
                $params->limit = null;
                $params->page = 1;
            }

            $data = $query->paginate($params->limit ?? null);
            return [
                'items' => $data->getCollection()->transform(function($item){

                    if ($item->tracking == 1) $tracking = 'Draft';
                    if ($item->tracking == 2) $tracking = 'Approval Dc Spv';
                    if ($item->tracking == 3) $tracking = 'Approval Dc Manager';
                    if ($item->tracking == 4) $tracking = 'Enquiry';
                    if ($item->tracking == 5) $tracking = 'Shipping';
                    if ($item->tracking == 6) $tracking = 'Approval CC Spv';
                    if ($item->tracking == 7) $tracking = 'Approval CC Manager';
                    if ($item->tracking == 8) $tracking = 'Finish';
                    
                    $item->tracking = $tracking;
    
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
            
            $checkbox = [];
            foreach ($order_entry_checkbox as $key => $value) {
                $checkbox[] = [
                    'id_iregular_order_entry' => $insert->id,
                    'id_value' => $value['id'],
                    'value' => $value['value']
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
                    $filename = 'OE-IREGULAR-'.$doc_name;

                    $ext = $files[$uploadIndex]->getClientOriginalExtension();
                    $savedname = (string) Str::uuid().'.'.$ext;

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
            $comodities = $value;
        }

        $good_condition = [];
        foreach ($params['good_condition'] as $value) {
            $good_condition = $value;
        }

        $good_status = [];
        foreach ($params['good_status'] as $value) {
            $good_status = $value;
        }

        $incoterms = [];
        foreach ($params['incoterms'] as $value) {
            $incoterms = $value;
        }

        $res = [
            $comodities,$good_condition,$good_status,$incoterms
        ];
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

}
