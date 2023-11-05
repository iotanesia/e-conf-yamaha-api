<?php

namespace App\Query;

use App\Constants\Constant;
use App\ApiHelper as Helper;
use App\Models\MstBox;
use App\Models\MstConsignee;
use App\Models\MstShipment;
use App\Models\MstSignature;
use App\Models\RegularDeliveryPlan;
use App\Models\RegularDeliveryPlanBox;
use App\Models\RegularDeliveryPlanSet;
use App\Models\RegularFixedActualContainer;
use App\Models\RegularFixedActualContainerCreation;
use App\Models\RegularFixedPackingCreationNote;
use App\Models\RegularFixedQuantityConfirmation;
use App\Models\RegularFixedQuantityConfirmationBox;
use App\Models\RegularFixedShippingInstruction AS Model;
use App\Models\RegularFixedShippingInstruction;
use App\Models\RegularFixedShippingInstructionCreation;
use App\Models\RegularFixedShippingInstructionCreationDraft;
use App\Models\RegularFixedShippingInstructionRevision;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Barryvdh\DomPDF\Facade\Pdf;

class QueryRegularFixedShippingInstruction extends Model {

    const cast = 'regular-fixed-shipping-instruction';

    public static function shipping($params)
    {
        $data = Model::where(function ($query) use ($params){
            $category = $params->category ?? null;
            $kueri = $params->kueri ?? null;
        
            if ($category && $kueri) {
                if ($category == 'cust_name') {
                    $query->whereHas('refFixedActualContainerCreation.refMstConsignee', function ($q) use ($kueri) {
                        $q->where('nick_name', 'like', '%' . $kueri . '%');
                    });
                } elseif ($category == 'packaging_no') {
                    $query->whereHas('refFixedActualContainerCreation.refFixedActualContainer', function ($q) use ($kueri) {
                        $q->where('no_packaging', 'like', '%' . $kueri . '%');
                    });
                } else {
                    $query->where('booking_date', 'like', '%' . $kueri . '%')
                        ->orWhere('no_booking', 'like', '%' . $kueri . '%');
                }
            }

            // $filterdate
            $date_from = str_replace('-','',$params->date_from);
            $date_to = str_replace('-','',$params->date_to);
            if($params->date_from || $params->date_to) $query->whereBetween('booking_date',[$date_from, $date_to]);
        })->paginate($params->limit ?? null);

        if(!$data) throw new \Exception("Data not found", 400);

        return [
            'items' => $data->getCollection()->transform(function($item){

                if($item->status == 1) $status = 'Confirm Booked';
                if($item->status == 2) $status = 'SI Created';
                if($item->status == 3) $status = 'Send To CC Spv';
                if($item->status == 4) $status = 'Send To CC Manager';
                if($item->status == 5) $status = 'Approve';
                if($item->status == 6) $status = 'Correction';
                if($item->status == 7) $status = 'Rejection';
                $item->status = $status;
                foreach($item->refFixedActualContainerCreation as $value){
                        $item->packaging = [$value->refFixedActualContainer->no_packaging ?? null] ;
                        $item->cust_name = [$value->refMstConsignee->nick_name ?? null] ;
                }
                unset(
                    $item->refFixedActualContainerCreation,
                );

                return $item;
            }),
            'last_page' => $data->lastPage()
        ];
    }

    public static function shippingCCspv($params)
    {
        $data = Model::where('status', 3)->paginate($params->limit ?? null);
        if(!$data) throw new \Exception("Data not found", 400);

        return [
            'items' => $data->getCollection()->transform(function($item){
                if($item->status == 1) $status = 'Confirm Booked';
                if($item->status == 2) $status = 'SI Created';
                if($item->status == 3) $status = 'Send To CC Spv';
                if($item->status == 4) $status = 'Send To CC Manager';
                if($item->status == 5) $status = 'Approve';
                if($item->status == 6) $status = 'Correction';
                if($item->status == 7) $status = 'Rejection';
                $item->status = $status;
                foreach($item->refFixedActualContainerCreation as $value){
                    $item->packaging = [$value->refFixedActualContainer->no_packaging ?? null] ;
                    $item->cust_name = [$value->refMstConsignee->nick_name ?? null] ;
                }
                unset(
                    $item->refFixedActualContainerCreation,
                );

                return $item;
            }),
            'last_page' => $data->lastPage()
        ];
    }

    public static function shippingCCman($params)
    {
        $data = Model::where('status', 4)->paginate($params->limit ?? null);
        if(!$data) throw new \Exception("Data not found", 400);

        return [
            'items' => $data->getCollection()->transform(function($item){
                if($item->status == 1) $status = 'Confirm Booked';
                if($item->status == 2) $status = 'SI Created';
                if($item->status == 3) $status = 'Send To CC Spv';
                if($item->status == 4) $status = 'Send To CC Manager';
                if($item->status == 5) $status = 'Approve';
                if($item->status == 6) $status = 'Correction';
                if($item->status == 7) $status = 'Rejection';
                $item->status = $status;
                foreach($item->refFixedActualContainerCreation as $value){
                    $item->packaging = [$value->refFixedActualContainer->no_packaging ?? null] ;
                    $item->cust_name = [$value->refMstConsignee->nick_name ?? null] ;
                }
                unset(
                    $item->refFixedActualContainerCreation,
                );

                return $item;
            }),
            'last_page' => $data->lastPage()
        ];
    }

    public static function shippingContainer($params,$id)
    {
        $data = RegularFixedActualContainerCreation::where('id_fixed_shipping_instruction',$id)
            ->orderBy('iteration', 'asc')
            ->paginate($params->limit ?? null);
        if(!$data) throw new \Exception("Data not found", 400);
        return [
            'items' => $data->getCollection()->transform(function($item){

                $quantity_confirmation = RegularFixedQuantityConfirmation::where('id_fixed_actual_container', $item->id_fixed_actual_container)->first();
                $box = RegularFixedQuantityConfirmationBox::with('refMstBox')->where('id_fixed_quantity_confirmation', $quantity_confirmation->id)->get()->toArray();

                $count_net_weight = 0;
                $count_gross_weight = 0;
                $count_meas = 0;
                foreach ($box as $box_item){
                    $count_net_weight += $box_item['ref_mst_box']['unit_weight_kg'];
                    $count_gross_weight += $box_item['ref_mst_box']['total_gross_weight'];
                    $count_meas += (($box_item['ref_mst_box']['length'] * $box_item['ref_mst_box']['width'] * $box_item['ref_mst_box']['height']) / 1000000000);
                }

                $item->cust_name = $item->refMstConsignee->nick_name;
                $item->id_type_delivery = $item->id_type_delivery;
                $item->type_delivery = $item->refMstTypeDelivery->name;
                $item->lsp = $item->refMstLsp->name;
                $item->net_weight = round($count_net_weight,1);
                $item->gross_weight = round($count_gross_weight,1);
                $item->measurement = round($count_meas,3);
                $item->container_type = $item->refMstContainer->container_type;
                $item->load_extension_length = $item->refMstContainer->long;
                $item->load_extension_width = $item->refMstContainer->wide;
                $item->load_extension_height = $item->refMstContainer->height;
                $item->load_qty = "100";
                $item->container_name = $item->refMstContainer->container_type." ".$item->refMstContainer->container_value;

                unset(
                    $item->refMstConsignee,
                    $item->refMstTypeDelivery,
                    $item->refMstLsp,
                    $item->refMstMot,
                    $item->refMstContainer,
                );

                return $item;
            }),
            'last_page' => $data->lastPage()
        ];
    }

    public static function shippingPacking($params)
    {
        return null;
    }

    public static function shippingDeliveryNote($params)
    {
        return null;
    }

    public static function shippingCasemarks($params)
    {
        return null;
    }

    public static function shippingActual($params)
    {
        return null;
    }

    public static function getNoPackaging($id){
        return RegularFixedActualContainer::find($id);
    }

    public static function shippingStore($request,$is_transaction = true)
    {
        if($is_transaction) DB::beginTransaction();
        try {
            // $request->merge(['consignee'=>json_encode($consignee),'status'=>Constant::DRAFT]);
            $request1 = $request->except(['container_count','container_value','container_type']);
            $request2 = [
                            'container_count' => implode(',',$request->container_count),
                            'container_value' => implode(',',$request->container_value),
                            'container_type' => implode(',',$request->container_type),
                        ];
            $params = array_merge($request1,$request2);
            Helper::requireParams([
                'to',
                'cc',
            ]);

            $fixed_shipping_instruction_creation = RegularFixedShippingInstructionCreation::where('id', $request->id_fixed_shipping_instruction_creation)->first();

            if ($fixed_shipping_instruction_creation == null) {
                $insert = RegularFixedShippingInstructionCreation::create($params);
                $actual_container_creation = RegularFixedActualContainerCreation::query();
                $update_actual = $actual_container_creation->where('datasource',$request->datasource)->where('code_consignee',$request->consignee)->where('etd_jkt',$request->etd_jkt)->get();
                foreach ($update_actual as $key => $value) {
                    $value->update(['id_fixed_shipping_instruction_creation'=>$insert->id, 'status' => 2]);
                }

                if (count($actual_container_creation->where('id_fixed_shipping_instruction', $params['id_fixed_shipping_instruction'])->get()) == count($actual_container_creation->where('id_fixed_shipping_instruction', $params['id_fixed_shipping_instruction'])->where('status', 2)->get())) {
                    RegularFixedShippingInstruction::where('id', $params['id_fixed_shipping_instruction'])->update(['status' => 2]);
                }

                $params['id_fixed_shipping_instruction_creation'] = $insert->id;
                RegularFixedShippingInstructionCreationDraft::create($params);
            } else {
                $fixed_shipping_instruction_creation->update($params);
                $params['id_fixed_shipping_instruction_creation'] = $fixed_shipping_instruction_creation->id;
                RegularFixedShippingInstructionCreationDraft::create($params);
            }

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

    public static function shippingDraftDok($params)
    {
        $fixed_actual_container_creation = RegularFixedActualContainerCreation::where('code_consignee', $params->code_consignee)->where('etd_jkt', $params->etd_jkt)->where('datasource', $params->datasource)->first();

        if($fixed_actual_container_creation->id_fixed_shipping_instruction_creation == null) return ['items' => []];

        $data = RegularFixedShippingInstructionCreationDraft::select('id','consignee','created_at')
            ->where('id_fixed_shipping_instruction_creation', $fixed_actual_container_creation->id_fixed_shipping_instruction_creation)
            ->paginate($params->limit ?? null);

        if(!$data) throw new \Exception("Data not found", 400);
        return [
            'items' => $data->getCollection()->transform(function($item){

                $item->title = 'SI Draft '.$item->consignee;
                $item->date = $item->created_at;

                unset(
                    $item->refFixedShippingInstructionCreation,
                    $item->consignee,
                    $item->created_at,
                );

                return $item;
            }),
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

    public static function downloadDoc($request,$id,$filename,$pathToFile)
    {
        try {
            $data = RegularFixedShippingInstructionCreation::where('id_fixed_shipping_instruction', $id)->first();
            $data->instruction_date = Carbon::parse($data->instruction_date)->subDay(2)->format('D, M d, Y');
            $data->etd_wh = Carbon::parse($data->etd_jkt)->subDay(2)->format('D, M d, Y');
            $data->eta_destination = Carbon::parse($data->eta_destination)->subDay(2)->format('M d, Y');
            $data->etd_jkt = Carbon::parse($data->etd_jkt)->subDay(2)->format('M d, Y');
            $data->approved = MstSignature::where('type', 'APPROVED')->first()->name;
            $data->checked = MstSignature::where('type', 'CHECKED')->first()->name;
            $data->issued = MstSignature::where('type', 'ISSUED')->first()->name;

            $actual_container_creation = RegularFixedActualContainerCreation::where('id_fixed_shipping_instruction', $id)->first();
            $actual_container = RegularFixedActualContainer::where('id', $actual_container_creation->id_fixed_actual_container)->get();

            foreach ($actual_container as $key => $value) {
                $tes = $value->manyFixedQuantityConfirmation;
            }

            $box = [];
            foreach ($tes as $key => $item) {
                $box[] = RegularDeliveryPlanBox::with('refBox')->where('id_regular_delivery_plan', $item['id_regular_delivery_plan'])->get()->toArray();
            }

            Pdf::loadView('pdf.fixed_shipping_instruction',[
              'data' => $data,
              'actual_container' => $actual_container,
              'box' => $box
            ])
            ->save($pathToFile)
            ->setPaper('A4','potrait')
            ->download($filename);
          } catch (\Throwable $th) {
              return Helper::setErrorResponse($th);
          }
    }

    public static function downloadDocDraft($request,$id,$filename,$pathToFile)
    {
        try {
            $data = RegularFixedShippingInstructionCreation::find($id);
            $data->instruction_date = Carbon::parse($data->instruction_date)->subDay(2)->format('D, M d, Y');
            $data->etd_wh = Carbon::parse($data->etd_jkt)->subDay(2)->format('D, M d, Y');
            $data->eta_destination = Carbon::parse($data->eta_destination)->subDay(2)->format('M d, Y');
            $data->etd_jkt = Carbon::parse($data->etd_jkt)->subDay(2)->format('M d, Y');
            $data->approved = MstSignature::where('type', 'APPROVED')->first()->name;
            $data->checked = MstSignature::where('type', 'CHECKED')->first()->name;
            $data->issued = MstSignature::where('type', 'ISSUED')->first()->name;
            $data->pod = $data->port_of_discharge;
            $data->pol = $data->port_of_loading;

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
        $data = RegularFixedActualContainerCreation::select('regular_fixed_actual_container_creation.code_consignee','regular_fixed_actual_container_creation.etd_jkt'
        ,DB::raw('COUNT(regular_fixed_actual_container_creation.etd_jkt) AS summary_container')
        ,DB::raw("string_agg(DISTINCT regular_fixed_actual_container_creation.code_consignee::character varying, ',') as code_consignee")
        ,DB::raw("string_agg(DISTINCT regular_fixed_actual_container_creation.datasource::character varying, ',') as datasource")
        ,DB::raw("string_agg(DISTINCT regular_fixed_actual_container_creation.etd_wh::character varying, ',') as etd_wh")
        ,DB::raw("string_agg(DISTINCT regular_fixed_actual_container_creation.etd_ypmi::character varying, ',') as etd_ypmi"))
        ->where('regular_fixed_actual_container_creation.id_fixed_shipping_instruction',$id)
        ->groupBy('regular_fixed_actual_container_creation.code_consignee','regular_fixed_actual_container_creation.etd_jkt')
        ->paginate($params->limit ?? null);
        if(!$data) throw new \Exception("Data not found", 400);

        $data->transform(function ($item) {
            return [
                'cust_name' => $item->refMstConsignee->nick_name,
                'etd_jkt' => $item->etd_jkt,
                'etd_wh' => $item->etd_wh,
                'etd_ypmi' => $item->etd_ypmi,
                'summary_container' => $item->summary_container,
                'code_consignee' => $item->code_consignee,
                'datasource' => $item->datasource,
            ];
        });

        return [
            'items' => $data->items(),
            'last_page' => $data->lastPage()
        ];
    }

    public static function shippingDetailSI($params)
    {
        $data = RegularFixedActualContainerCreation::select('regular_fixed_actual_container_creation.id_fixed_shipping_instruction'
        ,DB::raw('COUNT(regular_fixed_actual_container_creation.etd_jkt) AS summary_container')
        ,DB::raw("string_agg(DISTINCT regular_fixed_actual_container_creation.id_fixed_shipping_instruction_creation::character varying, ',') as id_fixed_shipping_instruction_creation")
        ,DB::raw("string_agg(DISTINCT regular_fixed_actual_container_creation.id_lsp::character varying, ',') as id_lsp")
        ,DB::raw("string_agg(DISTINCT regular_fixed_actual_container_creation.etd_wh::character varying, ',') as etd_wh")
        ,DB::raw("string_agg(DISTINCT regular_fixed_actual_container_creation.etd_jkt::character varying, ',') as etd_jkt")
        ,DB::raw("string_agg(DISTINCT regular_fixed_actual_container_creation.code_consignee::character varying, ',') as code_consignee")
        ,DB::raw("string_agg(DISTINCT regular_fixed_actual_container_creation.datasource::character varying, ',') as datasource")
        ,DB::raw("string_agg(DISTINCT b.hs_code::character varying, ',') as hs_code")
        ,DB::raw("string_agg(DISTINCT c.name::character varying, ',') as mot")
        ,DB::raw("string_agg(DISTINCT d.port::character varying, ',') as port")
        ,DB::raw("string_agg(DISTINCT e.name::character varying, ',') as type_delivery")
        ,DB::raw("string_agg(DISTINCT f.container_type::character varying, ',') as container_type")
        ,DB::raw("string_agg(DISTINCT f.container_value::character varying, ',') as container_value")
        ,DB::raw("string_agg(DISTINCT g.status::character varying, ',') as status")
        ,DB::raw("string_agg(DISTINCT h.tel::character varying, ',') as tel_consignee")
        ,DB::raw("string_agg(DISTINCT h.fax::character varying, ',') as fax_consignee")
        ,DB::raw("string_agg(DISTINCT h.address1::character varying, ',') as consignee_address")
        ,DB::raw("string_agg(DISTINCT i.no_packaging::character varying, ',') as no_packaging")
        ,DB::raw("string_agg(DISTINCT j.id::character varying, ',') as id_fixed_shipping_instruction")
        ,DB::raw("string_agg(DISTINCT regular_fixed_actual_container_creation.id_fixed_actual_container::character varying, ',') as id_fixed_actual_container")
        ,DB::raw("SUM(f.net_weight) as net_weight")
        ,DB::raw("SUM(f.gross_weight) as gross_weight")
        ,DB::raw("SUM(f.measurement) as measurement")
        ,DB::raw("SUM(regular_fixed_actual_container_creation.summary_box) as summary_box_sum"))
        ->where('regular_fixed_actual_container_creation.code_consignee', $params->code_consignee)
        ->where('regular_fixed_actual_container_creation.etd_jkt', $params->etd_jkt)
        ->where('regular_fixed_actual_container_creation.datasource', $params->datasource)
        ->leftJoin('mst_part as b','regular_fixed_actual_container_creation.item_no','b.item_no')
        ->leftJoin('mst_mot as c','regular_fixed_actual_container_creation.id_mot','c.id')
        ->leftJoin('mst_port_of_discharge as d','regular_fixed_actual_container_creation.code_consignee','d.code_consignee')
        ->leftJoin('mst_port_of_loading as e','regular_fixed_actual_container_creation.id_type_delivery','e.id_type_delivery')
        ->leftJoin('mst_container as f','regular_fixed_actual_container_creation.id_container','f.id')
        ->leftJoin('regular_delivery_plan_shipping_instruction_creation as g','regular_fixed_actual_container_creation.id_fixed_shipping_instruction_creation','g.id')
        ->leftJoin('mst_consignee as h','regular_fixed_actual_container_creation.code_consignee','h.code')
        ->leftJoin('regular_fixed_actual_container as i','regular_fixed_actual_container_creation.id_fixed_actual_container','i.id')
        ->leftJoin('regular_fixed_shipping_instruction as j','regular_fixed_actual_container_creation.id_fixed_shipping_instruction','j.id')
        ->groupBy('regular_fixed_actual_container_creation.id_fixed_shipping_instruction')
        ->paginate(1);
        if(!$data) throw new \Exception("Data not found", 400);

        $data->transform(function ($item) {
            if ($item->id_fixed_shipping_instruction_creation) {
                $SI = RegularFixedShippingInstructionCreation::where('id',$item->id_fixed_shipping_instruction_creation)->paginate(1);

                $summary_box = RegularFixedActualContainerCreation::where('code_consignee', $item->code_consignee)
                                                                            ->where('etd_jkt', $item->etd_jkt)
                                                                            ->where('datasource', $item->datasource)
                                                                            ->get()->map(function($q){
                                                                                $items = $q->summary_box;
                                                                                return $items;
                                                                            });

                $SI->transform(function ($si_item) use ($summary_box) {
                    $si_item->container_value = explode(',', $si_item->container_value);
                    $si_item->container_count = explode(',', $si_item->container_count);
                    $si_item->container_type = explode(',', $si_item->container_type);
                    $si_item->summary_box = array_sum($summary_box->toArray());

                    return $si_item;
                });

                return $SI->items()[0];
            } else {

                $mst_shipment = MstShipment::where('is_active', 1)->first();

                $data = RegularFixedActualContainer::where('id', $item->id_fixed_actual_container)->get();

                foreach ($data as $key => $value) {
                    $plan_box = $value->manyFixedQuantityConfirmation;
                }

                $box = [];
                foreach ($plan_box as $key => $val) {
                    $box[] = RegularDeliveryPlanBox::with('refBox')->where('id_regular_delivery_plan', $val['id_regular_delivery_plan'])->whereNotNull('qrcode')->get()->toArray();
                }

                $count_qty = 0;
                $count_net_weight = 0;
                $count_gross_weight = 0;
                $count_meas = 0;
                foreach (array_merge(...$box) as $box_item){
                    $count_qty += $box_item['qty_pcs_box'];
                    $count_net_weight += $box_item['ref_box']['unit_weight_kg'];
                    $count_gross_weight += $box_item['ref_box']['total_gross_weight'];
                    $count_meas += (($box_item['ref_box']['length'] * $box_item['ref_box']['width'] * $box_item['ref_box']['height']) / 1000000000);
                }

                $summary_box = RegularFixedActualContainerCreation::where('code_consignee', $item->code_consignee)
                                                                            ->where('etd_jkt', $item->etd_jkt)
                                                                            ->where('datasource', $item->datasource)
                                                                            ->get()->map(function($q){
                                                                                $items = $q->summary_box;
                                                                                return $items;
                                                                            });

                return [
                    'code_consignee' => $item->code_consignee,
                    'consignee_address' => $item->refMstConsignee->name.'<br>'.$item->refMstConsignee->address1.'<br>'.$item->refMstConsignee->address2.'<br>'.$item->refMstConsignee->tel.'<br>'.$item->refMstConsignee->fax,
                    'customer_name' => $item->refMstConsignee->nick_name ?? null,
                    'etd_jkt' => $item->etd_jkt,
                    'etd_wh' => $item->etd_wh,
                    'summary_container' => $item->summary_container,
                    'hs_code' => $item->hs_code,
                    'via' => $item->mot,
                    'freight_charge' => 'COLLECT',
                    'incoterm' => 'FOB',
                    'shipped_by' => $item->mot,
                    'container_value' => explode(',', $item->container_type),
                    'container_count' => [count($summary_box->toArray())],
                    'container_type' => $item->container_value,
                    'net_weight' => round($count_net_weight,1),
                    'gross_weight' => round($count_gross_weight,1),
                    'measurement' => round($count_meas,3),
                    'port_of_discharge' => $item->port,
                    'port_of_loading' => $item->type_delivery,
                    'type_delivery' => $item->type_delivery,
                    'count' => $item->summary_container,
                    'summary_box' => count($summary_box->toArray()),
                    'to' => $item->refMstLsp->name ?? null,
                    'status' => $item->status ?? null,
                    'id_fixed_shipping_instruction_creation' => $item->id_fixed_shipping_instruction_creation ?? null,
                    'id_fixed_shipping_instruction' => $item->id_fixed_shipping_instruction ?? null,
                    'invoice_no' => $item->no_packaging,
                    'shipper' => $mst_shipment->shipment ?? null,
                    'tel' => $mst_shipment->telp ?? null,
                    'fax' => $mst_shipment->fax ?? null,
                    'fax_id' => $mst_shipment->fax_id ?? null,
                    'tel_consignee' => $item->tel_consignee,
                    'fax_consignee' => $item->fax_consignee,
                    // 'consignee_address' => $item->consignee_address,
                    'notify_part' => '',
                    'tel_notify_part' => '',
                    'fax_notify_part' => '',
                    'description_of_goods_1' => '',
                    'description_of_goods_2' => $count_qty,
                    'seal_no' => '',
                    'connecting_vessel' => '',
                    'carton_box_qty' => count($box)
                ];
            }
        });

        return [
            'items' => $data->items()[0],
            'last_page' => $data->lastPage()
        ];
    }

    public static function sendccoff($request,$is_transaction = true)
    {
        if($is_transaction) DB::beginTransaction();
        try {
            Helper::requireParams([
                'id'
            ]);

            $data = RegularFixedShippingInstruction::whereIn('id', $request->id)->get();
            foreach ($data as $value) {
                $value->update(['status' => 3]);
            }

            if($is_transaction) DB::commit();
            Cache::flush([self::cast]); //delete cache

        } catch (\Throwable $th) {
            if($is_transaction) DB::rollBack();
            throw $th;
        }
    }

    public static function sendccman($request,$is_transaction = true)
    {
        if($is_transaction) DB::beginTransaction();
        try {
            Helper::requireParams([
                'id'
            ]);
            RegularFixedShippingInstruction::where('id', $request->id)->update(['status' => 4]);
            if($is_transaction) DB::commit();
            Cache::flush([self::cast]); //delete cache
        } catch (\Throwable $th) {
            if($is_transaction) DB::rollBack();
            throw $th;
        }
    }

    public static function approve($request,$is_transaction = true)
    {
        if($is_transaction) DB::beginTransaction();
        try {
            Helper::requireParams([
                'id'
            ]);
            RegularFixedShippingInstruction::where('id', $request->id)->update(['status' => 5]);
            if($is_transaction) DB::commit();
            Cache::flush([self::cast]); //delete cache
        } catch (\Throwable $th) {
            if($is_transaction) DB::rollBack();
            throw $th;
        }
    }

    public static function revisi($request,$is_transaction = true)
    {
        if($is_transaction) DB::beginTransaction();
        try {
            Helper::requireParams([
                'id',
                'id_user',
                'note'
            ]);
            $data = [
                'id_fixed_shipping_instruction' => $request->id,
                'id_user' => $request->id_user,
                'note' => $request->note,
                'type' => 'REVISI',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            RegularFixedShippingInstructionRevision::insert($data);
            RegularFixedShippingInstruction::where('id', $request->id)->update(['status' => 6]);
            if($is_transaction) DB::commit();
            Cache::flush([self::cast]); //delete cache
        } catch (\Throwable $th) {
            if($is_transaction) DB::rollBack();
            throw $th;
        }
    }

    public static function reject($request,$is_transaction = true)
    {
        if($is_transaction) DB::beginTransaction();
        try {
            Helper::requireParams([
                'id',
                'id_user',
                'note'
            ]);
            $data = [
                'id_fixed_shipping_instruction' => $request->id,
                'id_user' => $request->id_user,
                'note' => $request->note,
                'type' => 'REJECT',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            RegularFixedShippingInstructionRevision::insert($data);
            RegularFixedShippingInstruction::where('id', $request->id)->update(['status' => 7]);
            if($is_transaction) DB::commit();
            Cache::flush([self::cast]); //delete cache
        } catch (\Throwable $th) {
            if($is_transaction) DB::rollBack();
            throw $th;
        }
    }

    public static function printPackagingShipping($request,$id,$pathToFile,$filename)
    {
        try {
            $cek = RegularFixedActualContainerCreation::where('id_fixed_shipping_instruction', $id)->get();
            foreach ($cek  as $value) {
                $data = RegularFixedActualContainer::where('id', $value->id_fixed_actual_container)->get();
            }
            $deliv_plan = RegularDeliveryPlan::find($data[0]->manyFixedQuantityConfirmation[0]->id_regular_delivery_plan);

            foreach ($data as $key => $value) {
                $plan_box = $value->manyFixedQuantityConfirmation;
            }

            $box = [];
            foreach ($plan_box as $key => $item) {
                $box[] = RegularDeliveryPlanBox::with('refBox')->where('id_regular_delivery_plan', $item['id_regular_delivery_plan'])->get()->toArray();
            }

            $count_qty = 0;
            $count_net_weight = 0;
            $count_gross_weight = 0;
            $count_meas = 0;
            foreach (array_merge(...$box) as $box_item){
                $count_qty += $box_item['qty_pcs_box'];
                $count_net_weight += $box_item['ref_box']['unit_weight_kg'];
                $count_gross_weight += $box_item['ref_box']['total_gross_weight'];
                $count_meas += (($box_item['ref_box']['length'] * $box_item['ref_box']['width'] * $box_item['ref_box']['height']) / 1000000000);
            }

            if ($deliv_plan->item_no == null) {
                $plan_set = RegularDeliveryPlanSet::where('id_delivery_plan',$deliv_plan->id)->get();
                $deliv_plan_box = RegularDeliveryPlanBox::where('id_regular_delivery_plan',$deliv_plan->id)
                                                    ->orderBy('qty_pcs_box','desc')
                                                    ->orderBy('id','asc')
                                                    ->get();
                $item_no = [];
                $set_qty = [];
                $item_no_series = [];
                foreach ($plan_set as $key => $value) {
                    $item_no[] = $value->item_no;
                    $set_qty[] = $value->qty;
                    $item_no_series[] = $value->refBox->item_no_series;
                }

                $mst_box = MstBox::where('part_set', 'set')->whereIn('item_no', $item_no)->get();
                $qty_box = [];
                $sum_qty = [];
                $unit_weight_kg = [];
                $total_gross_weight = '';
                $length = '';
                $width = '';
                $height = '';
                foreach ($mst_box as $key => $value) {
                    $qty_box[] = $value->qty;
                    $sum_qty[] = $value->qty;
                    $unit_weight_kg[] = $value->unit_weight_kg;
                    $total_gross_weight = $value->total_gross_weight;
                    $length = $value->length;
                    $width = $value->width;
                    $height = $value->height;
                }
    
                $id_deliv_box = [];
                $qty_pcs_box = [];
                $qty = 0;
                $group = [];
                $group_qty = [];
                foreach ($deliv_plan_box as $key => $value) {
                    $qty += $value->qty_pcs_box;
                    $group[] = $value->id;
                    $group_qty[] = $value->qty_pcs_box;
    
                    if ($qty >= (array_sum($sum_qty) * count($item_no))) {
                        $id_deliv_box[] = $group;
                        $qty_pcs_box[] = $group_qty;
                        $qty = 0;
                        $group = [];
                        $group_qty = [];
                    }
                }
    
                if (!empty($group)) {
                    $id_deliv_box[] = $group;
                }
                if (!empty($group_qty)) {
                    $qty_pcs_box[] = $group_qty;
                }

                $res_qty = [];
                foreach ($set_qty as $key => $value) {
                    if ($value == max($set_qty)) {
                        $val = array_sum($qty_pcs_box[$key]) / count($item_no);
                    } else {
                        $val = null;
                    }

                    $res_qty[] = $val;
                }
    
                $box = [];
                for ($i=0; $i < count($id_deliv_box); $i++) { 
                    $check = array_sum($qty_pcs_box[0]) / count($item_no);
                    $box[] = [
                        'item_no' => $item_no,
                        'qty_pcs_box' => $check == array_sum($qty_pcs_box[$i]) / count($item_no) ? $qty_box : $res_qty,
                        'item_no_series' => $item_no_series,
                        'unit_weight_kg' => $unit_weight_kg,
                        'total_gross_weight' => $total_gross_weight,
                        'length' => $length,
                        'width' => $width,
                        'height' => $height,
                    ];
                }
                
                $count_qty = 0;
                $count_net_weight = 0;
                $count_gross_weight = 0;
                $count_meas = 0;
                foreach ($box as $box_item){
                    $count_qty += array_sum($box_item['qty_pcs_box']);
                    $count_net_weight += array_sum($box_item['unit_weight_kg']);
                    $count_gross_weight += $box_item['total_gross_weight'];
                    $count_meas += (($box_item['length'] * $box_item['width'] * $box_item['height']) / 1000000000);
                }
            }

            Pdf::loadView('pdf.packaging.packaging_doc',[
                'check' => $deliv_plan->item_no,
                'set_count' => $deliv_plan->item_no == null ? count($item_no) : 1,
                'data' => $data,
                'box' => array_merge(...$box),
                'count_qty' => $count_qty,
                'count_net_weight' => $count_net_weight,
                'count_gross_weight' => $count_gross_weight,
                'count_meas' => $count_meas
            ])
            ->save($pathToFile)
            ->setPaper('A4','potrait')
            ->download($filename);

        } catch (\Throwable $th) {
            return Helper::setErrorResponse($th);
        }
    }

    public static function packingCreationDeliveryNoteHead($request,$id)
    {
        $cek = RegularFixedActualContainerCreation::where('id_fixed_shipping_instruction', $id)->get();
        foreach ($cek  as $value) {
            $data = RegularFixedActualContainer::find($value->id_fixed_actual_container);
        }
        if(!$data) throw new \Exception("data tidak ditemukan", 400);

        $fixed_packing_creation = RegularFixedActualContainer::
        select(DB::raw("string_agg(DISTINCT d.name::character varying, ',') as yth"),
            DB::raw("string_agg(DISTINCT e.nick_name::character varying, ',') as username"),
            DB::raw("string_agg(DISTINCT g.container_type::character varying, ',') as jenis_truck")
        )->where('regular_fixed_actual_container.id',$id)
            ->join('regular_fixed_quantity_confirmation as b','b.id_fixed_actual_container','regular_fixed_actual_container.id')
            ->join('regular_fixed_actual_container_creation as c','regular_fixed_actual_container.id','c.id_fixed_actual_container')
            ->join('mst_lsp as d','d.id','c.id_lsp')
            ->join('mst_consignee as e','e.code','c.code_consignee')
            ->join('mst_type_delivery as f','f.id','c.id_type_delivery')
            ->join('mst_container as g','g.id','c.id_container')
            ->first();

        $ret['yth'] = $fixed_packing_creation->yth;
        $ret['username'] = $fixed_packing_creation->username;
        $ret['jenis_truck'] = $fixed_packing_creation->jenis_truck." HC";
        $ret['surat_jalan'] = Helper::generateCodeLetter(RegularFixedPackingCreationNote::latest()->first());
        $ret['delivery_date'] = date('d-m-Y');
        $ret['shipped'] = MstShipment::Where('is_active', 1)->first()->shipment ?? null;

        return [
            'items' => $ret,
            'last_page' => 0
        ];
    }

    public static function packingCreationDeliveryNotePart($params,$id)
    {
        $cek = RegularFixedActualContainerCreation::where('id_fixed_shipping_instruction', $id)->get();
        foreach ($cek  as $value) {
              $id_fixed_actual_container[] = $value->id_fixed_actual_container;
        }
        $data = RegularFixedQuantityConfirmation::whereIn('id_fixed_actual_container', $id_fixed_actual_container)
            ->paginate($params->limit ?? null);
        if(!$data) throw new \Exception("data tidak ditemukan", 400);
        return [
            'items' => $data->getCollection()->transform(function($item){
                $item->item_name = trim($item->refRegularDeliveryPlan->refPart->description);
                $item->cust_name = $item->refRegularDeliveryPlan->refConsignee->nick_name;
                $item->no_invoice = $item->refFixedActualContainer->no_packaging;
                unset(
                    $item->refRegularDeliveryPlan,
                    $item->refFixedActualContainer
                );

                return $item;
            }),
            'last_page' => $data->lastPage()
        ];
    }

    public static function printCasemarks($request,$id,$pathToFile,$filename)
    {
        try {
            $cek = RegularFixedActualContainerCreation::where('id_fixed_shipping_instruction', $id)->get();
            foreach ($cek  as $value) {
                $id_fixed_actual_container[] = $value->id_fixed_actual_container;
            }
            
            $data = RegularFixedActualContainer::whereIn('id', $id_fixed_actual_container)->get();

            $deliv_plan = RegularDeliveryPlan::find($data[0]->manyFixedQuantityConfirmation[0]->id_regular_delivery_plan);
            $box = [];
            foreach ($data[0]->manyFixedQuantityConfirmation as $key => $item) {
                $box[] = RegularDeliveryPlanBox::with('refBox.refPart')->where('id_regular_delivery_plan', $item['id_regular_delivery_plan'])->get()->toArray();
            }

            $box = array_merge(...$box);

            if ($deliv_plan->item_no == null) {
                $plan_set = RegularDeliveryPlanSet::where('id_delivery_plan',$deliv_plan->id)->get();
                $deliv_plan_box = RegularDeliveryPlanBox::where('id_regular_delivery_plan',$deliv_plan->id)
                                                    ->orderBy('qty_pcs_box','desc')
                                                    ->orderBy('id','asc')
                                                    ->get();
                $item_no = [];
                $set_qty = [];
                $item_no_series = [];
                foreach ($plan_set as $key => $value) {
                    $item_no[] = $value->item_no;
                    $set_qty[] = $value->qty;
                    $item_no_series[] = $value->refBox->item_no_series;
                }
                $mst_box = MstBox::where('part_set', 'set')->whereIn('item_no', $item_no)->get();
                $qty_box = [];
                $sum_qty = [];
                $unit_weight_kg = [];
                $total_gross_weight = '';
                foreach ($mst_box as $key => $value) {
                    $qty_box[] = $value->qty;
                    $sum_qty[] = $value->qty;
                    $unit_weight_kg[] = $value->unit_weight_kg;
                    $total_gross_weight = $value->total_gross_weight;
                }
    
                $id_deliv_box = [];
                $qty_pcs_box = [];
                $qty = 0;
                $group = [];
                $group_qty = [];
                foreach ($deliv_plan_box as $key => $value) {
                    $qty += $value->qty_pcs_box;
                    $group[] = $value->id;
                    $group_qty[] = $value->qty_pcs_box;
    
                    if ($qty >= (array_sum($sum_qty) * count($item_no))) {
                        $id_deliv_box[] = $group;
                        $qty_pcs_box[] = $group_qty;
                        $qty = 0;
                        $group = [];
                        $group_qty = [];
                    }
                }
    
                if (!empty($group)) {
                    $id_deliv_box[] = $group;
                }
                if (!empty($group_qty)) {
                    $qty_pcs_box[] = $group_qty;
                }

                $box = [];
                for ($i=0; $i < count($id_deliv_box); $i++) { 
                    $box[] = [
                        'item_no' => $item_no,
                        'qty_pcs_box' => $qty_box,
                        'item_no_series' => $item_no_series,
                        'total_gross_weight' => $total_gross_weight,
                    ];
                }
            }

            Pdf::loadView('pdf.casemarks.casemarks_doc',[
                'check' => $deliv_plan->item_no,
                'data' => $data,
                'box' => $box
            ])
                ->save($pathToFile)
                ->setPaper('A4','potrait')
                ->download($filename);

        } catch (\Throwable $th) {
            return Helper::setErrorResponse($th);
        }
    }

    public static function printShippingActual($request,$id,$filename,$pathToFile)
    {
        try {
            $cek = RegularFixedActualContainerCreation::where('id_fixed_shipping_instruction', $id)->first();
            $data = RegularFixedShippingInstructionCreation::find($cek->id_fixed_shipping_instruction_creation);
            $data->instruction_date = Carbon::parse($data->instruction_date)->subDay(2)->format('D, M d, Y');
            $data->etd_wh = Carbon::parse($data->etd_jkt)->subDay(2)->format('D, M d, Y');
            $data->eta_destination = Carbon::parse($data->eta_destination)->subDay(2)->format('M d, Y');
            $data->etd_jkt = Carbon::parse($data->etd_jkt)->subDay(2)->format('M d, Y');
            $data->approved = MstSignature::where('type', 'APPROVED')->first()->name;
            $data->checked = MstSignature::where('type', 'CHECKED')->first()->name;
            $data->issued = MstSignature::where('type', 'ISSUED')->first()->name;

            Pdf::loadView('pdf.shipping_actual',[
                'data' => $data
            ])->save($pathToFile)
                ->setPaper('A4','potrait')
                ->download($filename);

        } catch (\Throwable $th) {
            return Helper::setErrorResponse($th);
        }
    }

}
