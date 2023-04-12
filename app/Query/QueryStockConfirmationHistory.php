<?php

namespace App\Query;

use App\Constants\Constant;
use App\Models\RegularStokConfirmationHistory AS Model;
use App\Models\RegularStokConfirmation;
use App\ApiHelper as Helper;
use App\Models\MstContainer;
use App\Models\MstLsp;
use App\Models\RegularDeliveryPlan;
use App\Models\RegularDeliveryPlanBox;
use App\Models\RegularDeliveryPlanProspectContainerCreation;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Str;
class QueryStockConfirmationHistory extends Model {

    const cast = 'regular-stock-confirmation-history';

    public static function deleteInStock($request,$id,$is_transaction = true)
    {
        if($is_transaction) DB::beginTransaction();
        try {
            Model::where('id_regular_delivery_plan',$id)->where('type',Constant::INSTOCK)->delete();
            RegularStokConfirmation::where('id_regular_delivery_plan',$id)->update(['in_dc'=>Constant::IS_NOL,'status_instock'=>Constant::STS_STOK]);

            if($is_transaction) DB::commit();
        } catch (\Throwable $th) {
            if($is_transaction) DB::rollBack();
            throw $th;
        }
    }
    public static function deleteOutStock($request,$id,$is_transaction = true)
    {
        if($is_transaction) DB::beginTransaction();
        try {
            Model::where('id_regular_delivery_plan',$id)->where('type',Constant::OUTSTOCK)->delete();
            RegularStokConfirmation::where('id_regular_delivery_plan',$id)->update(['in_wh'=>Constant::IS_NOL,'status_outstock'=>Constant::STS_STOK]);

            if($is_transaction) DB::commit();
        } catch (\Throwable $th) {
            if($is_transaction) DB::rollBack();
            throw $th;
        }
    }


    public static function getInStock($request)
    {
        $data = RegularStokConfirmation::where('status_instock','=',2)->where('in_dc','>',0)->paginate($request->limit ?? null);
        if(!$data) throw new \Exception("Data not found", 400);
        return [
            'items' => $data->getCollection()->transform(function($item){
                $item->id_regular_delivery_plan = $item->refRegularDeliveryPlan->id;
                $item->id_regular_order_entry = $item->refRegularDeliveryPlan->id_regular_order_entry;
                $item->code_consignee = $item->refRegularDeliveryPlan->code_consignee;
                $item->model = $item->refRegularDeliveryPlan->model;
                $item->item_no = $item->refRegularDeliveryPlan->item_no;
                $item->disburse = $item->refRegularDeliveryPlan->disburse;
                $item->delivery = $item->refRegularDeliveryPlan->delivery;
                $item->qty = $item->refRegularDeliveryPlan->qty;
                $item->status_regular_delivery_plan = $item->refRegularDeliveryPlan->status_regular_delivery_plan;
                $item->order_no = $item->refRegularDeliveryPlan->order_no;
                $item->cust_item_no = $item->refRegularDeliveryPlan->cust_item_no;
                $item->created_at = $item->refRegularDeliveryPlan->created_at;
                $item->created_by = $item->refRegularDeliveryPlan->created_by;
                $item->updated_at = $item->refRegularDeliveryPlan->updated_at;
                $item->updated_by = $item->refRegularDeliveryPlan->updated_by;
                $item->deleted_at = $item->refRegularDeliveryPlan->deleted_at;
                $item->uuid = $item->refRegularDeliveryPlan->uuid;
                $item->etd_ypmi = $item->refRegularDeliveryPlan->etd_ypmi;
                $item->etd_wh = $item->refRegularDeliveryPlan->etd_wh;
                $item->etd_jkt = $item->refRegularDeliveryPlan->etd_jkt;
                $item->is_inquiry = $item->refRegularDeliveryPlan->is_inquiry;
                $item->id_prospect_container = $item->refRegularDeliveryPlan->id_prospect_container;
                $item->id_prospect_container_creation = $item->refRegularDeliveryPlan->id_prospect_container_creation;
                $item->status_bml = $item->refRegularDeliveryPlan->status_bml;
                $item->cust_name = $item->refRegularDeliveryPlan->refConsignee->nick_name;
                $item->status_desc = 'Instock';
                $item->regular_delivery_plan_box = $item->manyDeliveryPlanBox;

                unset(
                    $item->count_box,
                    $item->created_at,
                    $item->created_by,
                    $item->updated_at,
                    $item->updated_by,
                    $item->deleted_at,
                    $item->refRegularDeliveryPlan,
                    $item->manyDeliveryPlanBox
                );

                foreach($item->regular_delivery_plan_box as $box){
                    $box->box = $box->refBox;
                    unset($box->refBox);
                }

                return $item;
            }),
            'last_page' => $data->lastPage()
        ];
    }

    public static function getOutStock($request)
    {
        $data = RegularStokConfirmation::where('status_outstock','=',2)->where('in_wh','>',0)->paginate($request->limit ?? null);
        if(!$data) throw new \Exception("Data not found", 400);
        return [
            'items' => $data->getCollection()->transform(function($item){
                $item->id_regular_delivery_plan = $item->refRegularDeliveryPlan->id;
                $item->id_regular_order_entry = $item->refRegularDeliveryPlan->id_regular_order_entry;
                $item->code_consignee = $item->refRegularDeliveryPlan->code_consignee;
                $item->model = $item->refRegularDeliveryPlan->model;
                $item->item_no = $item->refRegularDeliveryPlan->item_no;
                $item->disburse = $item->refRegularDeliveryPlan->disburse;
                $item->delivery = $item->refRegularDeliveryPlan->delivery;
                $item->qty = $item->refRegularDeliveryPlan->qty;
                $item->status_regular_delivery_plan = $item->refRegularDeliveryPlan->status_regular_delivery_plan;
                $item->order_no = $item->refRegularDeliveryPlan->order_no;
                $item->cust_item_no = $item->refRegularDeliveryPlan->cust_item_no;
                $item->created_at = $item->refRegularDeliveryPlan->created_at;
                $item->created_by = $item->refRegularDeliveryPlan->created_by;
                $item->updated_at = $item->refRegularDeliveryPlan->updated_at;
                $item->updated_by = $item->refRegularDeliveryPlan->updated_by;
                $item->deleted_at = $item->refRegularDeliveryPlan->deleted_at;
                $item->uuid = $item->refRegularDeliveryPlan->uuid;
                $item->etd_ypmi = $item->refRegularDeliveryPlan->etd_ypmi;
                $item->etd_wh = $item->refRegularDeliveryPlan->etd_wh;
                $item->etd_jkt = $item->refRegularDeliveryPlan->etd_jkt;
                $item->is_inquiry = $item->refRegularDeliveryPlan->is_inquiry;
                $item->id_prospect_container = $item->refRegularDeliveryPlan->id_prospect_container;
                $item->id_prospect_container_creation = $item->refRegularDeliveryPlan->id_prospect_container_creation;
                $item->status_bml = $item->refRegularDeliveryPlan->status_bml;
                $item->cust_name = $item->refRegularDeliveryPlan->refConsignee->nick_name;
                $item->status_desc = 'Outstock';
                $item->regular_delivery_plan_box = $item->manyDeliveryPlanBox;

                unset(
                    $item->id_regular_delivery_plan,
                    $item->count_box,
                    $item->created_at,
                    $item->created_by,
                    $item->updated_at,
                    $item->updated_by,
                    $item->deleted_at,
                    $item->refRegularDeliveryPlan,
                    $item->manyDeliveryPlanBox
                );

                foreach($item->regular_delivery_plan_box as $box){
                    $box->box = $box->refBox;
                    unset($box->refBox);
                }

                return $item;
            }),
            'last_page' => $data->lastPage()
        ];
    }

    public static function tracking($request)
    {
        $data = RegularStokConfirmation::whereIn('status_instock',Constant::TRACKING)
        ->paginate($request->limit ?? null);

        if(!$data) throw new \Exception("Data not found", 400);

        return [
            'items' => $data->getCollection()->transform(function($item){

                if (Carbon::now() <= Carbon::parse($item->refRegularDeliveryPlan->etd_ypmi)) {
                    if ($item->status_instock == 1 || $item->status_instock == 2 && $item->status_outstock == 1 || $item->status_outstock == 2 && $item->in_dc = 0 && $item->in_wh == 0) $status = 'In Process';
                    if ($item->status_instock == 3 && $item->status_outstock == 3) $status = 'Finish Production';
                } else {
                    $status = 'Out Of Date';
                }

                $item->status_tracking = $status ?? null;
                $item->cust_name = $item->refRegularDeliveryPlan->refConsignee->nick_name;
                $item->item_no = $item->refRegularDeliveryPlan->refPart->item_serial;
                $item->item_name = $item->refRegularDeliveryPlan->refPart->description;
                $item->cust_item_no = $item->refRegularDeliveryPlan->cust_item_no;
                $item->cust_order_no = $item->refRegularDeliveryPlan->order_no;
                $item->qty = $item->refRegularDeliveryPlan->qty;
                $item->etd_ypmi = $item->refRegularDeliveryPlan->etd_ypmi;
                $item->etd_wh = $item->refRegularDeliveryPlan->etd_wh;
                $item->etd_jkt = $item->refRegularDeliveryPlan->etd_jkt;
                $item->production = $item->production;

                unset(
                    $item->refRegularDeliveryPlan,
                );

                return $item;
            }),
            'last_page' => $data->lastPage()
        ];
    }

    public static function fixedQuantity($request)
    {
        $data = RegularStokConfirmation::where('is_actual',Constant::IS_NOL)->paginate($request->limit ?? null);
        if(!$data) throw new \Exception("Data not found", 400);

        return [
            'items' => $data->getCollection()->transform(function($item){

                if (Carbon::now() <= Carbon::parse($item->refRegularDeliveryPlan->etd_ypmi)) {
                    if ($item->status_instock == 1 || $item->status_instock == 2 && $item->status_outstock == 1 || $item->status_outstock == 2 && $item->in_dc = 0 && $item->in_wh == 0) $status = 'In Process';
                    if ($item->status_instock == 3 && $item->status_outstock == 3) $status = 'Finish Production';
                } else {
                    $status = 'Out Of Date';
                }

                $item->status_tracking = $status ?? null;
                $item->cust_name = $item->refRegularDeliveryPlan->refConsignee->nick_name;
                $item->item_no = $item->refRegularDeliveryPlan->refPart->item_serial;
                $item->item_name = $item->refRegularDeliveryPlan->refPart->description;
                $item->cust_item_no = $item->refRegularDeliveryPlan->cust_item_no;
                $item->cust_order_no = $item->refRegularDeliveryPlan->order_no;
                $item->qty = $item->refRegularDeliveryPlan->qty;
                $item->etd_ypmi = $item->refRegularDeliveryPlan->etd_ypmi;
                $item->etd_wh = $item->refRegularDeliveryPlan->etd_wh;
                $item->etd_jkt = $item->refRegularDeliveryPlan->etd_jkt;
                $item->production = ($item->qty) - ($item->in_dc);

                unset(
                    $item->refRegularDeliveryPlan,
                );

                return $item;
            }),
            'last_page' => $data->lastPage()
        ];
    }

    public static function instockScanProcess($params, $is_transaction =  true)
    {
        if($is_transaction) DB::beginTransaction();
        try {

            Helper::requireParams([
                'qr_code'
            ]);


            $key = explode('|',$params->qr_code);

            $id = str_replace(' ','',$key[0]);

            $delivery_plan_box = RegularDeliveryPlanBox::find($id);
            if(!$delivery_plan_box) throw new \Exception("data not found", 400);
            $stock_confirmation = $delivery_plan_box->refRegularDeliveryPlan->refRegularStockConfirmation;
            if(!$stock_confirmation) throw new \Exception("stock has not arrived", 400);
            $data = RegularDeliveryPlanBox::where('id',$id)->paginate($params->limit ?? null);
            $data->transform(function ($item)
            {
                $no = $item->refBox->no_box ?? null;
                $qty = $item->refBox->qty ?? null;

                $datasource = $item->refRegularDeliveryPlan->refRegularOrderEntry->datasource ?? null;

                $qr_name = (string) Str::uuid().'.png';
                $qr_key = $item->id. " | ".$item->id_box. " | ".$datasource. " | ".$item->refRegularDeliveryPlan->etd_jkt. " | ".$item->qty_pcs_box;
                QrCode::format('png')->generate($qr_key,storage_path().'/app/qrcode/label/'.$qr_name);

                $item->qrcode = $qr_name;
                $item->save();

                return [
                    'id' => $item->id,
                    'item_name' => $item->refRegularDeliveryPlan->refPart->description ?? null,
                    'cust_name' => $item->refRegularDeliveryPlan->refConsignee->nick_name ?? null,
                    'item_no' => $item->refRegularDeliveryPlan->item_no ?? null,
                    'order_no' => $item->refRegularDeliveryPlan->order_no ?? null,
                    'qty_pcs_box' => $item->qty_pcs_box,
                    'namebox' => $no. " ".$qty. " pcs" ,
                    'qrcode' => route('file.download').'?filename='.$qr_name.'&source=qr_labeling',
                    'lot_packing' => $item->lot_packing,
                    'packing_date' => $item->packing_date,
                    'no_box' => $item->refBox->no_box ?? null,
                ];
            });



            return [
                'items' => $data[0],
                'last_page' => null
            ];



        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public static function instockInquiryProcess($params, $is_transaction = true)
    {
        if($is_transaction) DB::beginTransaction();
        try {

            Helper::requireParams([
                'id'
            ]);

            $delivery_plan_box = RegularDeliveryPlanBox::find($params->id);
            if(!$delivery_plan_box) throw new \Exception("Data not found", 400);
            $stock_confirmation = $delivery_plan_box->refRegularDeliveryPlan->refRegularStockConfirmation;
            $qty = $stock_confirmation->qty;
            $status = $stock_confirmation->status;
            $in_stock_dc = $stock_confirmation->in_dc;
            $in_dc_total = $in_stock_dc + $qty;

            $stock_confirmation->in_dc = $in_dc_total;
            $stock_confirmation->status_instock = $status == Constant::IS_ACTIVE ? 2 : 2;
            $stock_confirmation->save();

            self::create([
                'id_regular_delivery_plan' => $delivery_plan_box->id_regular_delivery_plan,
                'id_regular_delivery_plan_box' => $delivery_plan_box->id,
                'id_stock_confirmation' => $stock_confirmation->id,
                'id_box' => $delivery_plan_box->id_box,
                'type' => 'INSTOCK',
                'qty_pcs_perbox' => $qty,
            ]);

        if($is_transaction) DB::commit();
        } catch (\Throwable $th) {
            if($is_transaction) DB::rollBack();
            throw $th;
        }
    }

    public static function outstockInquiryProcess($params, $is_transaction = true)
    {
        if($is_transaction) DB::beginTransaction();
        try {

            Helper::requireParams([
                'id'
            ]);

            $delivery_plan_box = RegularDeliveryPlanBox::find($params->id);
            if(!$delivery_plan_box) throw new \Exception("Data not found", 400);
            $qty = $delivery_plan_box->qty;
            $stock_confirmation = $delivery_plan_box->refRegularDeliveryPlan->refRegularStockConfirmation;
            $status = $stock_confirmation->status;
            $in_stock_wh = $stock_confirmation->in_wh;
            $in_wh_total = $in_stock_wh + $qty;
            $in_dc_total = $stock_confirmation->in_dc - $in_wh_total;
            $stock_confirmation->in_wh = $in_wh_total;
            $stock_confirmation->in_dc = $in_dc_total;
            $stock_confirmation->status_outstock = $status == Constant::IS_ACTIVE ? 2 : 2;
            $stock_confirmation->save();

            self::create([
                'id_regular_delivery_plan' => $delivery_plan_box->id_regular_delivery_plan,
                'id_regular_delivery_plan_box' => $delivery_plan_box->id,
                'id_stock_confirmation' => $stock_confirmation->id,
                'id_box' => $delivery_plan_box->id_box,
                'type' => 'OUTSTOCK',
                'qty_pcs_perbox' => $qty,
            ]);

        if($is_transaction) DB::commit();
        } catch (\Throwable $th) {
            if($is_transaction) DB::rollBack();
            throw $th;
        }

    }


    public static function outstockScanProcess($params, $is_transaction =  true)
    {
        if($is_transaction) DB::beginTransaction();
        try {

            Helper::requireParams([
                'qr_code'
            ]);


            $key = explode('|',$params->qr_code);

            $id = str_replace(' ','',$key[0]);

            $delivery_plan_box = RegularDeliveryPlanBox::find($id);
            if(!$delivery_plan_box) throw new \Exception("data not found", 400);
            $stock_confirmation = $delivery_plan_box->refRegularDeliveryPlan->refRegularStockConfirmation;
            if(!$stock_confirmation) throw new \Exception("stock has not arrived", 400);
            $data = RegularDeliveryPlanBox::where('id',$id)->paginate($params->limit ?? null);
            $data->transform(function ($item)
            {
                $no = $item->refBox->no_box ?? null;
                $qty = $item->refBox->qty ?? null;

                $datasource = $item->refRegularDeliveryPlan->refRegularOrderEntry->datasource ?? null;

                $qr_name = (string) Str::uuid().'.png';
                $qr_key = $item->id. " | ".$item->id_box. " | ".$datasource. " | ".$item->refRegularDeliveryPlan->etd_jkt. " | ".$item->qty_pcs_box;
                QrCode::format('png')->generate($qr_key,storage_path().'/app/qrcode/label/'.$qr_name);

                $item->qrcode = $qr_name;
                $item->save();

                return [
                    'id' => $item->id,
                    'item_name' => $item->refRegularDeliveryPlan->refPart->description ?? null,
                    'cust_name' => $item->refRegularDeliveryPlan->refConsignee->nick_name ?? null,
                    'item_no' => $item->refRegularDeliveryPlan->item_no ?? null,
                    'order_no' => $item->refRegularDeliveryPlan->order_no ?? null,
                    'qty_pcs_box' => $item->qty_pcs_box,
                    'namebox' => $no. " ".$qty. " pcs" ,
                    'qrcode' => route('file.download').'?filename='.$qr_name.'&source=qr_labeling',
                    'lot_packing' => $item->lot_packing,
                    'packing_date' => $item->packing_date,
                    'no_box' => $item->refBox->no_box ?? null,
                ];
            });



            return [
                'items' => $data[0],
                'last_page' => null
            ];



        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public static function instockSubmit($params,$is_transaction = true)
    {
        if($is_transaction) DB::beginTransaction();
        try {

            Helper::requireParams([
                'id'
            ]);

            $data = RegularStokConfirmation::whereIn('id',$params->id)->get()->map(function ($item){
                    $item->status_instock = 3;
                    $item->save();
                    return $item;
            });

            if($is_transaction) DB::commit();
        } catch (\Throwable $th) {
            if($is_transaction) DB::rollBack();
            throw $th;
        }
    }

    public static function outstockSubmit($params,$is_transaction = true)
    {
        if($is_transaction) DB::beginTransaction();
        try {

            Helper::requireParams([
                'id'
            ]);

            $data = RegularStokConfirmation::whereIn('id',$params->id)->get()->map(function ($item){
                    $item->status_outstock = 3;
                    $item->save();
                    return $item;
            });

            if($is_transaction) DB::commit();
        } catch (\Throwable $th) {
            if($is_transaction) DB::rollBack();
            throw $th;
        }
    }
}
