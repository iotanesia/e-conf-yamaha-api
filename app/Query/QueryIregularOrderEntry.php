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

            $paramCheckbox = self::getParamCheckbox($params);
            $insert = Model::create($params);
            
            $checkbox = [];
            foreach ($paramCheckbox as $key => $value) {
                $checkbox[] = [
                    'id_iregular_order_entry' => $insert->id,
                    'id_value' => $value['id'],
                    'value' => $value['value']
                ];
            }
            $insert->manyOrderEntryCheckbox()->createMany($checkbox);

            if($is_transaction) DB::commit();
            Cache::flush([self::cast]); //delete cache
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

    public static function storePart($request,$id,$is_transaction = true)
    {
        if($is_transaction) DB::beginTransaction();
        try {
            $params = $request->all();
            
            $data = [];
            foreach ($params['part'] as $key => $value) {
                $arr = $value;
                $id_order_entry = ['id_iregular_order_entry' => $id];
                $data[] = array_merge($arr,$id_order_entry);

            }

            IregularOrderEntryPart::create($data[0]);
            $order_entry = Model::find($id);
            $mst_doc = MstDoc::where('id_good_payment', $order_entry->id_good_payment)->first();
            IregularOrderEntryDoc::create([
                'id_iregular_order_entry' => $id,
                'id_doc' => $mst_doc->id,
                'is_completed' => 0
            ]);

            if($is_transaction) DB::commit();
            Cache::flush([self::cast]); //delete cache
        } catch (\Throwable $th) {
            if($is_transaction) DB::rollBack();
            throw $th;
        }
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
