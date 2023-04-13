<?php

namespace App\Query;

use App\Constants\Constant;
use App\ApiHelper as Helper;
use App\Models\MstConsignee;
use App\Models\MstShipment;
use App\Models\RegularFixedActualContainerCreation;
use App\Models\RegularFixedShippingInstruction AS Model;
use App\Models\RegularFixedShippingInstructionCreation;
use App\Models\RegularFixedShippingInstructionCreationDraft;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Barryvdh\DomPDF\Facade\Pdf;

class QueryRegularFixedShippingInstruction extends Model {
    
    const cast = 'regular-fixed-shipping-instruction';

    public static function shipping($params)
    {
        $data = self::paginate($params->limit ?? null);
        if(!$data) throw new \Exception("Data not found", 400);

        return [
            'items' => $data->items(),
            'last_page' => $data->lastPage()
        ];
    }

    public static function shippingStore($request,$is_transaction = true)
    {
        if($is_transaction) DB::beginTransaction();
        try {
            $consignee = MstConsignee::where('code',$request->code_consignee)->first();
            $request->merge(['consignee'=>json_encode($consignee),'status'=>Constant::DRAFT]);
            $params = $request->all();
            Helper::requireParams([
                'to',
                'cc',
            ]);
            $insert = RegularFixedShippingInstructionCreation::create($params);
            RegularFixedActualContainerCreation::where('code_consignee',$request->code_consignee)->where('etd_jkt',$request->etd_jkt)->update(['id_fixed_shipping_instruction_creation'=>$insert->id]);
            $params['id_fixed_shipping_instruction_creation'] = $insert->id;
            RegularFixedShippingInstructionCreationDraft::create($params);
            if($is_transaction) DB::commit();
            Cache::flush([self::cast]); //delete cache
        } catch (\Throwable $th) {
            if($is_transaction) DB::rollBack();
            throw $th;
        }
    }

    public static function shippingUpdate($request,$is_transaction = true)
    {
        if($is_transaction) DB::beginTransaction();
        try {
            $update = RegularFixedShippingInstructionCreation::find($request->id);
            if(!$update) throw new \Exception("Data not found", 400);
            $update->status = Constant::FINISH;
            $update->save();

            if($is_transaction) DB::commit();
            return ['items'=>$update];
            Cache::flush([self::cast]); //delete cache
        } catch (\Throwable $th) {
            if($is_transaction) DB::rollBack();
            throw $th;
        }
    }

    public static function shippingDraftDok($params,$id)
    {
        $data = RegularFixedShippingInstructionCreationDraft::select('id','no_draft','created_at')
            ->where('id_fixed_shipping_instruction_creation',$id)
            ->paginate($params->limit ?? null);

        if(!$data) throw new \Exception("Data not found", 400);
        return [
            'items' => $data->items(),
            'last_page' => $data->lastPage()
        ];
    }

    public static function shippingDraftDokDetail($params,$id)
    {
        $data = RegularFixedShippingInstructionCreationDraft::where('id',$id)->first();

        if(!$data) throw new \Exception("Data not found", 400);
        return [
            'items' => $data
        ];
    }

    public static function downloadDoc($params,$id)
    {
        try {
            $data = RegularFixedShippingInstructionCreation::find($id);
            $data->instruction_date = Carbon::parse($data->instruction_date)->subDay(2)->format('D, M d, Y');
            $data->etd_wh = Carbon::parse($data->etd_jkt)->subDay(2)->format('D, M d, Y');
            $data->eta_destination = Carbon::parse($data->eta_destination)->subDay(2)->format('M d, Y');
            $data->etd_jkt = Carbon::parse($data->etd_jkt)->subDay(2)->format('M d, Y');
            $filename = 'shipping-instruction-'.$id.'.pdf';
            $pathToFile = storage_path().'/app/shipping_instruction/'.$filename;
            Pdf::loadView('pdf.shipping_instruction',[
              'data' => $data
            ])
            ->save($pathToFile)
            ->setPaper('A4','potrait')
            ->download($filename);
          } catch (\Throwable $th) {
              return Helper::setErrorResponse($th);
          }
    }

    public static function downloadDocDraft($params,$id)
    {
        try {
            $data = RegularFixedShippingInstructionCreation::find($id);
            $data->instruction_date = Carbon::parse($data->instruction_date)->subDay(2)->format('D, M d, Y');
            $data->etd_wh = Carbon::parse($data->etd_jkt)->subDay(2)->format('D, M d, Y');
            $data->eta_destination = Carbon::parse($data->eta_destination)->subDay(2)->format('M d, Y');
            $data->etd_jkt = Carbon::parse($data->etd_jkt)->subDay(2)->format('M d, Y');
            $filename = 'shipping-instruction-draft'.$id.'.pdf';
            $pathToFile = storage_path().'/app/shipping_instruction/'.$filename;
            Pdf::loadView('pdf.shipping_instruction',[
              'data' => $data
            ])
            ->save($pathToFile)
            ->setPaper('A4','potrait')
            ->download($filename);
          } catch (\Throwable $th) {
              return Helper::setErrorResponse($th);
          }
    }

    public static function shippingDetail($params,$id)
    {
        $data = RegularFixedActualContainerCreation::select('regular_fixed_actual_container_creation.code_consignee','regular_fixed_actual_container_creation.etd_jkt','regular_fixed_actual_container_creation.etd_wh','regular_fixed_actual_container_creation.id_lsp','g.status','id_fixed_shipping_instruction_creation','f.measurement','f.net_weight','f.gross_weight','f.container_value','f.container_type','e.name','c.name','b.hs_code','d.port'
        ,DB::raw('COUNT(regular_fixed_actual_container_creation.etd_jkt) AS summary_container')
        ,DB::raw("string_agg(DISTINCT b.hs_code::character varying, ',') as hs_code")
        ,DB::raw("string_agg(DISTINCT c.name::character varying, ',') as mot")
        ,DB::raw("string_agg(DISTINCT d.port::character varying, ',') as port")
        ,DB::raw("string_agg(DISTINCT e.name::character varying, ',') as type_delivery")
        ,DB::raw("string_agg(DISTINCT f.container_type::character varying, ',') as container_type")
        ,DB::raw("string_agg(DISTINCT f.container_value::character varying, ',') as container_value")
        ,DB::raw("SUM(f.net_weight) as net_weight")
        ,DB::raw("SUM(f.gross_weight) as gross_weight")
        ,DB::raw("SUM(f.measurement) as measurement")
        ,DB::raw("SUM(regular_fixed_actual_container_creation.summary_box) as summary_box_sum"))
        ->where('regular_fixed_actual_container_creation.id_fixed_shipping_instruction',$id)
        ->leftJoin('mst_part as b','regular_fixed_actual_container_creation.item_no','b.item_no')
        ->leftJoin('mst_mot as c','regular_fixed_actual_container_creation.id_mot','c.id')
        ->leftJoin('mst_port_of_discharge as d','regular_fixed_actual_container_creation.code_consignee','d.code_consignee')
        ->leftJoin('mst_port_of_loading as e','regular_fixed_actual_container_creation.id_type_delivery','e.id_type_delivery')
        ->leftJoin('mst_container as f','regular_fixed_actual_container_creation.id_container','f.id')
        ->leftJoin('regular_delivery_plan_shipping_instruction_creation as g','regular_fixed_actual_container_creation.id_fixed_shipping_instruction_creation','g.id')
        ->groupBy('regular_fixed_actual_container_creation.code_consignee','regular_fixed_actual_container_creation.etd_jkt','regular_fixed_actual_container_creation.etd_wh','regular_fixed_actual_container_creation.id_lsp','g.status','id_fixed_shipping_instruction_creation','f.measurement','f.net_weight','f.gross_weight','f.container_value','f.container_type','e.name','c.name','b.hs_code','d.port')
        ->paginate($params->limit ?? null);
        if(!$data) throw new \Exception("Data not found", 400);

        $data->transform(function ($item) {
            return [
                'code_consignee' => $item->code_consignee,
                'consignee' => $item->refMstConsignee->name.'<br>'.$item->refMstConsignee->address1.'<br>'.$item->refMstConsignee->address2,
                'customer_name' => $item->refMstConsignee->nick_name,
                'etd_jkt' => $item->etd_jkt,
                'etd_wh' => $item->etd_wh,
                'summary_container' => $item->summary_container,
                'hs_code' => $item->hs_code,
                'via' => $item->mot,
                'freight_chart' => 'COLLECT',
                'incoterm' => 'FOB',
                'shipped_by' => $item->mot,
                'container_value' => intval($item->container_type),
                'container_type' => $item->container_value,
                'net_weight' => $item->net_weight,
                'gross_weight' => $item->gross_weight,
                'measurement' => $item->measurement,
                'port' => $item->port,
                'type_delivery' => $item->type_delivery,
                'count' => $item->summary_container,
                'summary_box' => $item->summary_box_sum,
                'to' => $item->refMstLsp->name ?? null,
                'status' => $item->status ?? null,
                'id_fixed_shipping_instruction_creation' => $item->id_fixed_shipping_instruction_creation ?? null,
                'shipment' => MstShipment::where('is_active',1)->first()->shipment ?? null,
            ];
        });

        return [
            'items' => $data->items(),
            'last_page' => $data->lastPage()
        ];
    }

}