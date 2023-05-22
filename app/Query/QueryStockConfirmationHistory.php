<?php

namespace App\Query;

use App\Constants\Constant;
use App\Models\RegularStokConfirmationHistory AS Model;
use App\Models\RegularStokConfirmation;
use App\ApiHelper as Helper;
use App\Models\MstContainer;
use App\Models\MstLsp;
use App\Models\MstShipment;
use App\Models\RegularDeliveryPlan;
use App\Models\RegularDeliveryPlanBox;
use App\Models\RegularDeliveryPlanProspectContainerCreation;
use App\Models\RegularFixedQuantityConfirmation;
use App\Models\RegularFixedQuantityConfirmationBox;
use App\Models\RegularStokConfirmationOutstockNote;
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
            $stock = Model::where('id_regular_delivery_plan',$id)->where('type',Constant::INSTOCK)->first();
            $update = RegularStokConfirmation::where('id_regular_delivery_plan',$id)->first();

            $update->update([
                'in_dc'=>Constant::IS_NOL,
                'status_instock'=>Constant::STS_STOK,
                'production' => $update->production + $stock->qty_pcs_perbox
            ]);
            $stock->delete();

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
            $stock = Model::where('id_regular_delivery_plan',$id)->where('type',Constant::OUTSTOCK)->first();
            $update = RegularStokConfirmation::where('id_regular_delivery_plan',$id)->first();

            $update->update([
                'in_wh'=>Constant::IS_NOL,
                'status_outstock'=>Constant::STS_STOK,
                'production' => $update->production + $stock->qty_pcs_perbox
            ]);
            $stock->delete();

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
        $data = RegularStokConfirmation::paginate($request->limit ?? null);

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
        $data = RegularFixedQuantityConfirmation::where('is_actual',Constant::IS_NOL)->paginate($request->limit ?? null);
        if(!$data) throw new \Exception("Data not found", 400);

        return [
            'items' => $data->items(),
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
            $in_dc_total = $in_stock_dc + $delivery_plan_box->qty_pcs_box;

            $stock_confirmation->in_dc = $in_dc_total;
            $stock_confirmation->production = $qty - $in_dc_total - $stock_confirmation->in_wh;
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
            $stock_confirmation = $delivery_plan_box->refRegularDeliveryPlan->refRegularStockConfirmation;
            $qty = $stock_confirmation->qty;
            $status = $stock_confirmation->status;
            $in_stock_wh = $stock_confirmation->in_wh;
            $in_wh_total = $in_stock_wh + $delivery_plan_box->qty_pcs_box;
            $in_dc_total = $stock_confirmation->in_dc - $delivery_plan_box->qty_pcs_box;
            $stock_confirmation->in_wh = $in_wh_total;
            $stock_confirmation->in_dc = $in_dc_total;
            $stock_confirmation->status_outstock = $status == Constant::IS_ACTIVE ? 2 : 2;
            $stock_confirmation->save();

            if ($stock_confirmation->in_dc == 0 && $stock_confirmation->in_wh == $stock_confirmation->qty && $stock_confirmation->production == 0) {
                $stock_confirmation->status_instock = 3;
                $stock_confirmation->status_outstock = 3;
                $stock_confirmation->save();

                $fixed_quantity_confirmation = new RegularFixedQuantityConfirmation;
                $fixed_quantity_confirmation->id_regular_delivery_plan = $stock_confirmation->id_regular_delivery_plan;
                $fixed_quantity_confirmation->datasource = $fixed_quantity_confirmation->refRegularDeliveryPlan->datasource;
                $fixed_quantity_confirmation->code_consignee = $fixed_quantity_confirmation->refRegularDeliveryPlan->code_consignee;
                $fixed_quantity_confirmation->model = $fixed_quantity_confirmation->refRegularDeliveryPlan->model;
                $fixed_quantity_confirmation->item_no = $fixed_quantity_confirmation->refRegularDeliveryPlan->item_no;
                $fixed_quantity_confirmation->item_serial = $fixed_quantity_confirmation->refRegularDeliveryPlan->refPart->item_serial;
                $fixed_quantity_confirmation->disburse = $fixed_quantity_confirmation->refRegularDeliveryPlan->disburse;
                $fixed_quantity_confirmation->delivery = $fixed_quantity_confirmation->refRegularDeliveryPlan->delivery;
                $fixed_quantity_confirmation->qty = $fixed_quantity_confirmation->refRegularDeliveryPlan->qty;
                $fixed_quantity_confirmation->order_no = $fixed_quantity_confirmation->refRegularDeliveryPlan->order_no;
                $fixed_quantity_confirmation->cust_item_no = $fixed_quantity_confirmation->refRegularDeliveryPlan->cust_item_no;
                $fixed_quantity_confirmation->etd_ypmi = $fixed_quantity_confirmation->refRegularDeliveryPlan->etd_ypmi;
                $fixed_quantity_confirmation->etd_wh = $fixed_quantity_confirmation->refRegularDeliveryPlan->etd_wh;
                $fixed_quantity_confirmation->etd_jkt = $fixed_quantity_confirmation->refRegularDeliveryPlan->etd_jkt;
                $fixed_quantity_confirmation->in_dc = $stock_confirmation->in_dc;
                $fixed_quantity_confirmation->in_wh = $stock_confirmation->in_wh;
                $fixed_quantity_confirmation->production = $stock_confirmation->production;
                $fixed_quantity_confirmation->is_actual = 0;
                $fixed_quantity_confirmation->status = 1;
                $fixed_quantity_confirmation->save();

                foreach ($fixed_quantity_confirmation->refRegularDeliveryPlan->manyDeliveryPlanBox as $item_box) {
                    $fixed_quantity_confirmation_box = new RegularFixedQuantityConfirmationBox;
                    $fixed_quantity_confirmation_box->id_fixed_quantity_confirmation = $fixed_quantity_confirmation->id;
                    $fixed_quantity_confirmation_box->id_regular_delivery_plan = $fixed_quantity_confirmation->id_regular_delivery_plan;
                    $fixed_quantity_confirmation_box->id_regular_delivery_plan_box = $item_box->id;
                    $fixed_quantity_confirmation_box->id_box = $item_box->id_box;
                    $fixed_quantity_confirmation_box->id_proc = $item_box->id_proc;
                    $fixed_quantity_confirmation_box->qty_pcs_box = $item_box->qty_pcs_box;
                    $fixed_quantity_confirmation_box->lot_packing = $item_box->lot_packing;
                    $fixed_quantity_confirmation_box->packing_date = $item_box->packing_date;
                    $fixed_quantity_confirmation_box->qrcode = $item_box->qrcode;
                    $fixed_quantity_confirmation_box->is_labeling = $item_box->is_labeling;
                    $fixed_quantity_confirmation_box->save();
                }

             }

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

    public static function outstockDeliveryNote($request)
    {
        $data = RegularStokConfirmation::select(DB::raw("string_agg(DISTINCT c.name::character varying, ',') as yth"),DB::raw("string_agg(DISTINCT d.nick_name::character varying, ',') as username"),DB::raw("string_agg(DISTINCT e.name::character varying, ',') as jenis_truck"))
                        ->whereIn('regular_stock_confirmation.id',$request->id_stock_confirmation)
                        ->join('regular_delivery_plan as a','a.id','regular_stock_confirmation.id_regular_delivery_plan')
                        ->join('regular_delivery_plan_prospect_container_creation as b','b.id','a.id_prospect_container_creation')
                        ->join('mst_lsp as c','c.id','b.id_lsp')
                        ->join('mst_consignee as d','d.code','b.code_consignee')
                        ->join('mst_type_delivery as e','e.id','b.id_type_delivery')
                        ->paginate($request->limit ?? null);

        if(!$data) throw new \Exception("Data not found", 400);

        $items = RegularStokConfirmation::select(DB::raw("string_agg(DISTINCT a.item_no::character varying, ',') as item_number"),DB::raw("string_agg(DISTINCT c.description::character varying, ',') as item_name"),DB::raw("string_agg(DISTINCT a.order_no::character varying, ',') as order_no"),DB::raw("SUM(CAST(regular_stock_confirmation.in_wh as INT)) as quantity"),DB::raw("string_agg(DISTINCT b.no_packaging::character varying, ',') as no_packing_list"))
                        ->whereIn('regular_stock_confirmation.id',$request->id_stock_confirmation)
                        ->join('regular_delivery_plan as a','a.id','regular_stock_confirmation.id_regular_delivery_plan')
                        ->join('regular_delivery_plan_prospect_container as b','b.id','a.id_prospect_container')
                        ->join('mst_part as c','c.item_no','a.item_no')
                        ->groupBy('a.id')
                        ->get();

        return [
            'items' => $data->transform(function($item) use ($items){
                $item->shipment = MstShipment::where('is_active',Constant::IS_ACTIVE)->first()->shipment ?? null;
                $item->truck_no = null;
                $item->surat_jalan = Helper::generateCodeLetter(RegularStokConfirmationOutstockNote::latest()->first()) ?? null;
                $item->delivery_date = Carbon::now()->format('Y-m-d');
                $item->items = $items;

                return $item;
            }),
            'last_page' => $data->lastPage()
        ];
    }
}
