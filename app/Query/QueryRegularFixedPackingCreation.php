<?php

namespace App\Query;

use App\Constants\Constant;
use App\Models\RegularFixedPackingCreation AS Model;
use App\Models\RegularFixedQuantityConfirmation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\ApiHelper as Helper;
use App\Models\MstPart;
use App\Models\MstShipment;
use App\Models\RegularDeliveryPlanSet;
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
        $data = RegularFixedActualContainer::where(function ($query) use ($params){
            $category = $params->category ?? null;
            $kueri = $params->kueri ?? null;
        
            if ($category && $kueri) {
                if ($category == 'cust_name') {
                    $query->whereHas('refConsignee', function ($q) use ($kueri) {
                        $q->where('nick_name', 'like', '%' . $kueri . '%');
                    });
                }elseif ($category == 'etd_ypmi') {
                    $query->where('etd_ypmi', 'like', '%' . $kueri . '%');
                }elseif ($category == 'etd_wh') {
                    $query->where('etd_wh', 'like', '%' . $kueri . '%');
                }elseif ($category == 'etd_jkt') {
                    $query->where('etd_jkt', 'like', '%' . $kueri . '%');
                } else {
                    $query->where('etd_jkt', 'like', '%' . $kueri . '%')
                        ->orWhere('no_packaging', 'like', '%' . $kueri . '%')
                        ->orWhere('etd_ypmi', 'like', '%' . $kueri . '%')
                        ->orWhere('etd_wh', 'like', '%' . $kueri . '%');
                }
            }

            if($params->date_start || $params->date_finish)
                $query->whereBetween('etd_jkt',[$params->date_start, $params->date_finish]);


        })->paginate($params->limit ?? null);

        $data->map(function ($item){
            $item->cust_name = $item->refConsignee->nick_name ?? null;
            $item->mot = $item->refMot->name ?? null;
            $item->status_desc = 'Confirmed';

            unset(
                $item->refConsignee,
                $item->refMot
            );
            return $item;
        })->toArray();

        return [
            'items' => $data->items(),
            'last_page' => $data->lastPage()
        ];
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
        $data = RegularFixedActualContainer::find($id);
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
        $ret['username'] = $data->refConsignee->name;
        $ret['jenis_truck'] = "LCL";
        $ret['surat_jalan'] = Helper::generateCodeLetter(RegularFixedPackingCreationNote::latest()->first());
        $ret['delivery_date'] = date('d-m-Y');
        $ret['shipped'] = MstShipment::Where('is_active', 1)->first()->shipment ?? null;

        return [
            'items' => $ret,
            'last_page' => 0
        ];
    }

    public static function packingCreationDeliveryNotePart($id,$params)
    {
        $data = RegularFixedQuantityConfirmation::select('id_regular_delivery_plan',
            DB::raw("string_agg(DISTINCT regular_fixed_quantity_confirmation.id_fixed_actual_container::character varying, ',') as id_fixed_actual_container"),
            DB::raw("string_agg(DISTINCT regular_fixed_quantity_confirmation.item_no::character varying, ',') as item_no"),
            DB::raw("string_agg(DISTINCT regular_fixed_quantity_confirmation.order_no::character varying, ',') as order_no"),
            DB::raw('MAX(regular_fixed_quantity_confirmation.in_wh) as in_wh'),
            )
            ->where('id_fixed_actual_container', $id)
            ->groupBy('id_regular_delivery_plan')
            ->paginate($params->limit ?? null);
        if(!$data) throw new \Exception("data tidak ditemukan", 400);
        return [
            'items' => $data->getCollection()->transform(function($item){

                if ($item->refRegularDeliveryPlan->item_no == null) {
                    $part_set = RegularDeliveryPlanSet::where('id_delivery_plan', $item->refRegularDeliveryPlan->id)->get()->pluck('item_no');
                    $mst_part = MstPart::whereIn('item_no', $part_set->toArray())->get()->pluck('description');
                }

                $item->item_no = $item->refRegularDeliveryPlan->item_no == null ? $part_set : [$item->item_no];
                $item->item_name = $item->refRegularDeliveryPlan->item_no == null ? $mst_part->toArray() : trim($item->refRegularDeliveryPlan->refPart->description);
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
            $actual = RegularFixedActualContainer::find($id);

            $data = RegularFixedActualContainer::
                    select(DB::raw("string_agg(DISTINCT d.name::character varying, ',') as yth"),
                                DB::raw("string_agg(DISTINCT e.name::character varying, ',') as username"),
                                DB::raw("string_agg(DISTINCT g.container_type::character varying, ',') as jenis_truck")
                    )->where('regular_fixed_actual_container.id',$id)
                        ->join('regular_fixed_quantity_confirmation as b','b.id_fixed_actual_container','regular_fixed_actual_container.id')
                        ->join('regular_fixed_actual_container_creation as c','regular_fixed_actual_container.id','c.id_fixed_actual_container')
                        ->join('mst_lsp as d','d.id','c.id_lsp')
                        ->join('mst_consignee as e','e.code','c.code_consignee')
                        ->join('mst_type_delivery as f','f.id','c.id_type_delivery')
                        ->join('mst_container as g','g.id','c.id_container')
                        ->first();
                        
            $data->no_letters = Helper::generateCodeLetter(RegularFixedPackingCreationNote::latest()->first());
            $data->delivery_date = date('d-m-Y');
            $data->truck_type = $data->jenis_truck." HC";
            $data->yth = $data->yth;
            $data->nick_name = $data->username;
            $data->shipper = MstShipment::Where('is_active', 1)->first()->shipment ?? null;

            Pdf::loadView('pdf.packing-creation.delivery_note',[
              'data' => $data,
              'actual' => $actual,
            ])
            ->save($pathToFile)
            ->setPaper('A4','potrait')
            ->download($filename);
          } catch (\Throwable $th) {
              return Helper::setErrorResponse($th);
          }
    }
}
