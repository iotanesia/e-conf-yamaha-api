<?php

namespace App\Query;

use App\Constants\Constant;
use App\Models\RegularFixedPackingCreation AS Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\ApiHelper as Helper;
use App\Models\MstShipment;
use App\Models\RegularFixedActualContainer;
use App\Models\RegularFixedPackingCreationNote;
use App\Models\RegularFixedPackingCreationNoteDetail;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Barryvdh\DomPDF\Facade\Pdf;

class QueryRegularFixedPackingCreation extends Model {

    const cast = 'regular-fixed-packing-creation';

    public static function getAll($params)
    {
        try {
            $key = self::cast.json_encode($params->query());
            if($params->dropdown == Constant::IS_ACTIVE) $params->limit = Model::count();

            $query = self::where(function ($query) use ($params){
                if($params->search) $query->where('name',"like", "%$params->search%")
                                            ->orWHere('nickname',"like", "%$params->search%");

             });
             if($params->withTrashed == 'true') $query->withTrashed();
             $data = $query
             ->orderBy('id','asc')
             ->paginate($params->limit ?? null);
             return [
                 'items' => $data->items(),
                 'last_page' => $data->lastPage(),
                 'attributes' => [
                     'total' => $data->total(),
                     'current_page' => $data->currentPage(),
                     'from' => $data->currentPage(),
                     'per_page' => (int) $data->perPage(),
                 ]
             ];
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public static function byId($id)
    {
        return ['items'=>self::find($id)];
    }

    public static function store($request,$is_transaction = true)
    {
        if($is_transaction) DB::beginTransaction();
        try {

            $params = $request->all();
            $insert = self::create($params);

            if($is_transaction) DB::commit();
            Cache::flush([self::cast]); //delete cache

            return ['items'=>$insert];

        } catch (\Throwable $th) {
            if($is_transaction) DB::rollBack();
            throw $th;
        }
    }

    public static function change($id,$request,$is_transaction = true)
    {
        if($is_transaction) DB::beginTransaction();
        try {

            Helper::requireParams([
                'id'
            ]);

            $params = $request->all();
            $update = self::find($id);
            if(!$update) throw new \Exception("data tidak ditemukan", 400);
            $update->fill($params);
            $update->save();
            if($is_transaction) DB::commit();
            Cache::flush([self::cast]); //delete cache
            return ['items'=>$update];
        } catch (\Throwable $th) {
            if($is_transaction) DB::rollBack();
            throw $th;
        }
    }

    public static function deleted($id,$is_transaction = true)
    {
        if($is_transaction) DB::beginTransaction();
        try {
            self::destroy($id);
            if($is_transaction) DB::commit();
            Cache::flush([self::cast]); //delete cache
        } catch (\Throwable $th) {
            if($is_transaction) DB::rollBack();
            throw $th;
        }
    }

    public static function packingCreationDeliveryNote($id,$request)
    {
        $data = self::find($id);
        if(!$data) throw new \Exception("data tidak ditemukan", 400);

        $fixed_packing_creation = Model::select(DB::raw("string_agg(DISTINCT d.name::character varying, ',') as yth"),DB::raw("string_agg(DISTINCT e.nick_name::character varying, ',') as username"),DB::raw("string_agg(DISTINCT e.name::character varying, ',') as jenis_truck"))
                        ->where('regular_fixed_packing_creation.id',$id)
                        ->join('regular_fixed_actual_container as a','a.id','regular_fixed_packing_creation.id_fixed_actual_container')
                        ->join('regular_fixed_quantity_confirmation as b','b.id_fixed_actual_container','a.id')
                        ->join('regular_fixed_actual_container_creation as c','c.id_fixed_packing_container','regular_fixed_packing_creation.id')
                        ->join('mst_lsp as d','d.id','c.id_lsp')
                        ->join('mst_consignee as e','e.code','c.code_consignee')
                        ->join('mst_type_delivery as f','f.id','c.id_type_delivery')
                        ->paginate($request->limit ?? null);

        $items = Model::select(DB::raw("string_agg(DISTINCT b.item_no::character varying, ',') as item_number"),DB::raw("string_agg(DISTINCT c.description::character varying, ',') as item_name"),DB::raw("string_agg(DISTINCT b.order_no::character varying, ',') as order_no"),DB::raw("string_agg(DISTINCT b.qty::character varying, ',') as quantity"),DB::raw("string_agg(DISTINCT e.no_packaging::character varying, ',') as no_packing_list"))
                        ->where('regular_fixed_packing_creation.id',$id)
                        ->join('regular_fixed_actual_container as a','a.id','regular_fixed_packing_creation.id_fixed_actual_container')
                        ->join('regular_fixed_quantity_confirmation as b','b.id_fixed_actual_container','a.id')
                        ->join('mst_part as c','c.item_no','b.item_no')
                        ->join('regular_delivery_plan as d','d.id','b.id_regular_delivery_plan')
                        ->join('regular_delivery_plan_prospect_container as e','e.id','d.id_prospect_container')
                        ->get();

        $packing_creation_note = RegularFixedPackingCreationNote::where('id_fixed_packing_creation', $id)->get();
        $packing_creation_note->transform(function($item){
            $item->packing_creation_note_detail = RegularFixedPackingCreationNoteDetail::where('id_fixed_packing_creation_note', $item->id)->get();
            return $item->toArray();
        });

        return [
            'items' => $fixed_packing_creation->transform(function($item) use ($items,$packing_creation_note){
                $item->shipment = MstShipment::where('is_active',Constant::IS_ACTIVE)->first()->shipment ?? null;
                $item->truck_no = null;
                $item->surat_jalan = Helper::generateCodeLetter(RegularFixedPackingCreationNote::latest()->first()) ?? null;
                $item->delivery_date = Carbon::now()->format('Y-m-d');
                $item->items = $items;

                if (count($packing_creation_note) > 0) {
                    $item->packing_creation_note = $packing_creation_note;
                }

                return $item;
            }),
            'last_page' => $fixed_packing_creation->lastPage()
        ];
    }

    public static function packingCreationDeliveryNoteSave($request,$is_transaction = true)
    {
        if($is_transaction) DB::beginTransaction();
        try {
            $lastData = RegularFixedPackingCreationNote::latest()->first();
            Helper::generateCodeLetter($lastData);
            $fixed_packing_creation = Model::select(DB::raw("string_agg(DISTINCT regular_fixed_packing_creation.id::character varying, ',') as id_fixed_packing_creation"),DB::raw("string_agg(DISTINCT d.name::character varying, ',') as yth"),DB::raw("string_agg(DISTINCT e.code::character varying, ',') as consignee"),DB::raw("string_agg(DISTINCT e.name::character varying, ',') as truck_type"))
                                        ->where('regular_fixed_packing_creation.id',$request->id)
                                        ->join('regular_fixed_actual_container as a','a.id','regular_fixed_packing_creation.id_fixed_actual_container')
                                        ->join('regular_fixed_quantity_confirmation as b','b.id_fixed_actual_container','a.id')
                                        ->join('regular_fixed_actual_container_creation as c','c.id_fixed_packing_container','regular_fixed_packing_creation.id')
                                        ->join('mst_lsp as d','d.id','c.id_lsp')
                                        ->join('mst_consignee as e','e.code','c.code_consignee')
                                        ->join('mst_type_delivery as f','f.id','c.id_type_delivery')
                                        ->paginate($request->limit ?? null);

            $dataSend = $fixed_packing_creation->transform(function($item){
                            $item->shippment = MstShipment::where('is_active',Constant::IS_ACTIVE)->first()->shipment ?? null;
                            $item->truck_no = null;
                            $item->no_letters = Helper::generateCodeLetter(RegularFixedPackingCreationNote::latest()->first()) ?? null;
                            $item->delivery_date = Carbon::now()->format('Y-m-d');

                            return $item->toArray();
                        });

            $dataSendDetail = Model::select(DB::raw("string_agg(DISTINCT b.item_no::character varying, ',') as item_no"),DB::raw("string_agg(DISTINCT b.order_no::character varying, ',') as order_no"),DB::raw("string_agg(DISTINCT b.qty::character varying, ',') as qty"),DB::raw("string_agg(DISTINCT e.no_packaging::character varying, ',') as no_packing"))
                                    ->where('regular_fixed_packing_creation.id',$request->id)
                                    ->join('regular_fixed_actual_container as a','a.id','regular_fixed_packing_creation.id_fixed_actual_container')
                                    ->join('regular_fixed_quantity_confirmation as b','b.id_fixed_actual_container','a.id')
                                    ->join('mst_part as c','c.item_no','b.item_no')
                                    ->join('regular_delivery_plan as d','d.id','b.id_regular_delivery_plan')
                                    ->join('regular_delivery_plan_prospect_container as e','e.id','d.id_prospect_container')
                                    ->get();

            if(isset($dataSend[0])) {
                $insert = RegularFixedPackingCreationNote::create($dataSend[0]);
                $insert->manyRegularFixedPackingCreationNoteDetail()->createMany(self::getParamDetail($dataSendDetail,$insert));
            }
            if($is_transaction) DB::commit();
            return ['items'=>$dataSend];
        } catch (\Throwable $th) {
            if($is_transaction) DB::rollBack();
            throw $th;
        }
    }

    public static function getParamDetail($params,$data) {
        $res = [];
        foreach ($params as $value) {
            $res[] = [
                'item_no' => $value->item_no,
                'order_no' => $value->order_no,
                'qty' => $value->qty,
                'no_packing' => $value->no_packing,
                'id_fixed_packing_creation_note' => $data->id
            ];
        }
        return $res;
    }

    public static function downloadpackingCreationDeliveryNote($id,$pathToFile,$filename)
    {
        try {
            $data = RegularFixedPackingCreationNote::where('id_fixed_packing_creation',$id)->first();

            Pdf::loadView('pdf.packing-creation.delivery_note',[
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
