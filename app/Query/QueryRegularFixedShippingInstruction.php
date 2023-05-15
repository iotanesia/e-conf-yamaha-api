<?php

namespace App\Query;

use App\Constants\Constant;
use App\ApiHelper as Helper;
use App\Models\MstConsignee;
use App\Models\MstShipment;
use App\Models\MstSignature;
use App\Models\RegularFixedActualContainer;
use App\Models\RegularFixedActualContainerCreation;
use App\Models\RegularFixedPackingCreationNote;
use App\Models\RegularFixedQuantityConfirmation;
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
        $data = Model::where('status', '!=', 5)->paginate($params->limit ?? null);
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
        $data = RegularFixedActualContainerCreation::where('id_fixed_shipping_instruction',$id)->paginate($params->limit ?? null);
        if(!$data) throw new \Exception("Data not found", 400);
        return [
            'items' => $data->getCollection()->transform(function($item){
                $item->cust_name = $item->refMstConsignee->nick_name;
                $item->type_delivery = $item->refMstTypeDelivery->name;
                $item->lsp = $item->refMstLsp->name;
                $item->net_weight = $item->refMstContainer->net_weight;
                $item->gross_weight = $item->refMstContainer->gross_weight;
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
            $consignee = MstConsignee::where('nick_name',$request->consignee)->first();
            // $request->merge(['consignee'=>json_encode($consignee),'status'=>Constant::DRAFT]);
            $params = $request->all();
            Helper::requireParams([
                'to',
                'cc',
            ]);

            $fixed_shipping_instruction_creation = RegularFixedShippingInstructionCreation::where('id', $request->id_fixed_shipping_instruction_creation)->first();

            if ($fixed_shipping_instruction_creation == null) {
                $insert = RegularFixedShippingInstructionCreation::create($params);
                RegularFixedActualContainerCreation::where('code_consignee',$request->code_consignee)->where('etd_jkt',$request->etd_jkt)->update(['id_fixed_shipping_instruction_creation'=>$insert->id, 'status' => 2]);
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
            $data = RegularFixedShippingInstructionCreation::find($id);
            $data->instruction_date = Carbon::parse($data->instruction_date)->subDay(2)->format('D, M d, Y');
            $data->etd_wh = Carbon::parse($data->etd_jkt)->subDay(2)->format('D, M d, Y');
            $data->eta_destination = Carbon::parse($data->eta_destination)->subDay(2)->format('M d, Y');
            $data->etd_jkt = Carbon::parse($data->etd_jkt)->subDay(2)->format('M d, Y');
            $data->approved = MstSignature::where('type', 'APPROVED')->first()->name;
            $data->checked = MstSignature::where('type', 'CHECKED')->first()->name;
            $data->issued = MstSignature::where('type', 'ISSUED')->first()->name;

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
        $data = RegularFixedActualContainerCreation::select('regular_fixed_actual_container_creation.code_consignee','regular_fixed_actual_container_creation.etd_jkt','regular_fixed_actual_container_creation.etd_wh','regular_fixed_actual_container_creation.id_lsp','g.status','id_fixed_shipping_instruction_creation','f.measurement','f.net_weight','f.gross_weight','f.container_value','f.container_type','e.name','c.name','b.hs_code','d.port'
        ,DB::raw('COUNT(regular_fixed_actual_container_creation.etd_jkt) AS summary_container')
        ,DB::raw("string_agg(DISTINCT regular_fixed_actual_container_creation.id_fixed_shipping_instruction_creation::character varying, ',') as id_fixed_shipping_instruction_creation")
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
        ->where('regular_fixed_actual_container_creation.code_consignee', $params->code_consignee)
        ->where('regular_fixed_actual_container_creation.etd_jkt', $params->etd_jkt)
        ->where('regular_fixed_actual_container_creation.datasource', $params->datasource)
        ->leftJoin('mst_part as b','regular_fixed_actual_container_creation.item_no','b.item_no')
        ->leftJoin('mst_mot as c','regular_fixed_actual_container_creation.id_mot','c.id')
        ->leftJoin('mst_port_of_discharge as d','regular_fixed_actual_container_creation.code_consignee','d.code_consignee')
        ->leftJoin('mst_port_of_loading as e','regular_fixed_actual_container_creation.id_type_delivery','e.id_type_delivery')
        ->leftJoin('mst_container as f','regular_fixed_actual_container_creation.id_container','f.id')
        ->leftJoin('regular_delivery_plan_shipping_instruction_creation as g','regular_fixed_actual_container_creation.id_fixed_shipping_instruction_creation','g.id')
        ->groupBy('regular_fixed_actual_container_creation.code_consignee','regular_fixed_actual_container_creation.etd_jkt','regular_fixed_actual_container_creation.etd_wh','regular_fixed_actual_container_creation.id_lsp','g.status','id_fixed_shipping_instruction_creation','f.measurement','f.net_weight','f.gross_weight','f.container_value','f.container_type','e.name','c.name','b.hs_code','d.port')
        ->paginate(1);
        if(!$data) throw new \Exception("Data not found", 400);

        $data->transform(function ($item) {
            if ($item->id_fixed_shipping_instruction_creation) {
                $data = RegularFixedShippingInstructionCreation::find($item->id_fixed_shipping_instruction_creation);
                return $data->toArray();
            } else {
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
                $data = RegularFixedActualContainer::find($value->id_fixed_actual_container);
            }
            Pdf::loadView('pdf.packaging.packaging_doc',[
                'data' => $data
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

    public static function packingCreationDeliveryNotePart($request,$id)
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
            $data = RegularFixedQuantityConfirmation::whereIn('id_fixed_actual_container',$id_fixed_actual_container)->get();
            Pdf::loadView('pdf.casemarks.casemarks_doc',[
                'data' => $data
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
            $data = RegularFixedShippingInstructionCreation::where('id_fixed_shipping_instruction',$id)->first();
            $data->instruction_date = Carbon::parse($data->instruction_date)->subDay(2)->format('D, M d, Y');
            $data->etd_wh = Carbon::parse($data->etd_jkt)->subDay(2)->format('D, M d, Y');
            $data->eta_destination = Carbon::parse($data->eta_destination)->subDay(2)->format('M d, Y');
            $data->etd_jkt = Carbon::parse($data->etd_jkt)->subDay(2)->format('M d, Y');
            $data->approved = MstSignature::where('type', 'APPROVED')->first()->name;
            $data->checked = MstSignature::where('type', 'CHECKED')->first()->name;
            $data->issued = MstSignature::where('type', 'ISSUED')->first()->name;

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

}
