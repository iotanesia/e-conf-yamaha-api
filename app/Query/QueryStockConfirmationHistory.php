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
            RegularStokConfirmation::where('id_regular_delivery_plan',$id)->update(['in_dc'=>Constant::IS_NOL]);

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
            RegularStokConfirmation::where('id_regular_delivery_plan',$id)->update(['in_wh'=>Constant::IS_NOL]);

            if($is_transaction) DB::commit();
        } catch (\Throwable $th) {
            if($is_transaction) DB::rollBack();
            throw $th;
        }
    }


    public static function getInStock($request)
    {
        $data = RegularStokConfirmation::where('status','>',1)->paginate($request->limit ?? null);
        if(!$data) throw new \Exception("Data not found", 400);
        return [
            'items' => $data->getCollection()->transform(function($item){
                $item->regular_delivery_plan = $item->refRegularDeliveryPlan;
                $item->cust_name = $item->refRegularDeliveryPlan->refConsignee->nick_name;
                $item->status_instock = 'default';
                unset(
                    $item->id,
                    $item->id_regular_delivery_plan,
                    $item->count_box,
                    $item->in_wh,
                    $item->created_at,
                    $item->created_by,
                    $item->updated_at,
                    $item->updated_by,
                    $item->deleted_at,
                    $item->refRegularDeliveryPlan,
                    $item->regular_delivery_plan->refConsignee
                );

                return $item;
            }),
            'last_page' => $data->lastPage()
        ];
    }

    public static function getOutStock($request)
    {
        $data = RegularStokConfirmation::where('status','<',1)->paginate($request->limit ?? null);
        if(!$data) throw new \Exception("Data not found", 400);
        return [
            'items' => $data->getCollection()->transform(function($item){
                $item->regular_delivery_plan = $item->refRegularDeliveryPlan;
                $item->cust_name = $item->refRegularDeliveryPlan->refConsignee->nick_name;
                $item->status_instock = 'default';
                unset(
                    $item->id,
                    $item->id_regular_delivery_plan,
                    $item->count_box,
                    $item->in_wh,
                    $item->created_at,
                    $item->created_by,
                    $item->updated_at,
                    $item->updated_by,
                    $item->deleted_at,
                    $item->refRegularDeliveryPlan,
                    $item->regular_delivery_plan->refConsignee
                );

                return $item;
            }),
            'last_page' => $data->lastPage()
        ];
    }

    public static function tracking($request)
    {
        $data = RegularStokConfirmation::paginate($request->limit ?? null);
        if(!$data) throw new \Exception("Data not found", 400);

        return [
            'items' => $data->getCollection()->transform(function($item){

                if ($item->status == 1) $status = 'in process';
                if ($item->status == 2 && $item->in_dc > 0 && $item->in_wh == 0) $status = 'in stock';
                if ($item->status == 2 && $item->in_dc > 0 && $item->in_wh > 0) $status = 'out stock';
                if ($item->status == 3) $status = 'finish production';

                $item->status_tracking = $status;
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
            $qty = $delivery_plan_box->refBox->qty;
            $stock_confirmation = $delivery_plan_box->refRegularDeliveryPlan->refRegularStockConfirmation;
            $status = $stock_confirmation->status;
            $in_stock_dc = $stock_confirmation->in_dc;
            $in_dc_total = $in_stock_dc + $qty;

            $stock_confirmation->in_dc = $in_dc_total;
            $stock_confirmation->status = $status == Constant::IS_ACTIVE ? 2 : 2;
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
            $qty = $delivery_plan_box->refBox->qty;
            $stock_confirmation = $delivery_plan_box->refRegularDeliveryPlan->refRegularStockConfirmation;
            $status = $stock_confirmation->status;
            $in_stock_dc = $stock_confirmation->in_dc;
            $in_dc_total = $in_stock_dc + $qty;

            $stock_confirmation->in_dc = $in_dc_total;
            $stock_confirmation->status = $status == Constant::IS_ACTIVE ? 2 : 2;
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
}
