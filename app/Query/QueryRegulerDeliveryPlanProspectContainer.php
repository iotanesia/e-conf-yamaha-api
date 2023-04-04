<?php

namespace App\Query;

use App\Constants\Constant;
use App\Models\RegularDeliveryPlanProspectContainer AS Model;
use App\ApiHelper as Helper;
use App\Models\MstContainer;
use App\Models\MstLsp;
use App\Models\RegularDeliveryPlan;
use App\Models\RegularDeliveryPlanBox;
use App\Models\RegularDeliveryPlanProspectContainerCreation;
use Illuminate\Support\Facades\DB;

class QueryRegulerDeliveryPlanProspectContainer extends Model {

    const cast = 'regular-delivery-plan-prospect-container-container';

    public static function getAll($params) {
        $data = Model::paginate($params->limit ?? null);
        if(count($data) == 0) throw new \Exception("Data tidak ditemukan.", 400);

        $data->map(function ($item){
            $item->cust_name = $item->refConsignee->nick_name ?? null;

            unset(
                $item->refConsignee
            );
            return $item;
        })->toArray();

        return [
            'items' => $data->items(),
            'last_page' => $data->lastPage()
        ];
    }

    public static function byIdProspectContainer($params,$id)
    {
        $data = RegularDeliveryPlan::where('id_prospect_container_creation',$id)
        // ->whereHas('refRegularDeliveryPlanProspectContainer', function ($query){

        // })
        ->paginate($params->limit ?? null);

        if(count($data) == 0) throw new \Exception("Data tidak ditemukan.", 400);

        $data->transform(function ($item) use ($id){
            $item->item_name = $item->refPart->description ?? null;
            $item->cust_name = $item->refConsignee->nick_name ?? null;
            $regularOrderEntry = $item->refRegularOrderEntry;
            $item->regular_order_entry_period = $regularOrderEntry->period ?? null;
            $item->regular_order_entry_month = $regularOrderEntry->month ?? null;
            $item->regular_order_entry_year = $regularOrderEntry->year ?? null;
            $item->box = $item->manyDeliveryPlanBox->map(function ($item) use ($id)
            {
                return [
                    'id' => $item->id,
                    'id_prospect_container' => $id,
                    'id_box' => $item->id_box,
                    'qty' => $item->refBox->qty ?? null,
                    'width' => $item->refBox->width ?? null,
                    'height' => $item->refBox->height ?? null,
                ];
            });

            unset(
                $item->refRegularOrderEntry,
                $item->manyDeliveryPlanBox,
                $item->refPart,
                $item->refConsignee
            );

            return $item;

        });


        return [
            'items' => $data->items(),
            'last_page' => $data->lastPage(),

        ];
    }


    public static function createionProcess($params,$is_transaction = true)
    {
        if($is_transaction) DB::beginTransaction();
        try {

         Helper::requireParams([
             'id'
         ]);


         $check = RegularDeliveryPlanProspectContainerCreation::where('id_prospect_container',$params->id)
         ->count('id_prospect_container');

        //  if($check) throw new \Exception("Data already exist in table creation", 400);

        if ($check) {
            $creation = RegularDeliveryPlanProspectContainerCreation::where('id_prospect_container',$params->id)
                        ->get()
                        ->map(function ($item){
                            $item->nama_lsp = $item->refMstLsp->name ?? null;
                            $item->nama_mot = $item->refMstMot->name ?? null;
                            $item->nama_type_delivery = $item->refMstTypeDelivery->name ?? null;

                            unset(
                                $item->refMstLsp,
                                $item->refMstMot,
                                $item->refMstTypeDelivery,
                            );
                            return $item;
                        })->toArray();
            return ['items' => $creation];
        }

         $item_no = RegularDeliveryPlan::select('item_no')
         ->whereIn('id_prospect_container',$params->id)
         ->groupBy('item_no','id_prospect_container')
         ->get()
         ->toArray();


        $summary = RegularDeliveryPlanBox::select('id_regular_delivery_plan','id_box')->whereIn('id_regular_delivery_plan',function ($query) use ($params,$item_no)
        {
            $query->select('id')
            ->from(with(new RegularDeliveryPlan())->getTable())
            ->whereIn('id_prospect_container',$params->id)
            ->whereIn('item_no',$item_no);
        })
        ->get()
        ->map(function ($item){
            $item->item_no = $item->refRegularDeliveryPlan->item_no ?? null;
            $item->id_prospect_container = $item->refRegularDeliveryPlan->id_prospect_container ?? null;
            $item->code_consignee = $item->refRegularDeliveryPlan->code_consignee ?? null;
            $item->etd_jkt = $item->refRegularDeliveryPlan->etd_jkt ?? null;
            $item->etd_ypmi = $item->refRegularDeliveryPlan->etd_ypmi ?? null;
            $item->etd_wh = $item->refRegularDeliveryPlan->etd_wh ?? null;
            $item->qty_box = $item->refBox->qty ?? 0;

            unset(
                $item->refRegularDeliveryPlan,
                $item->refBox,
            );
            return $item;
        })->toArray();

        $arr =  [];
        foreach ($summary as $key => $item) {
            $arr[$item['item_no']][$key] = $item;
        }

        $summary_result = [];
        $creation = [];
        $qty = 0;

        foreach ($arr as $key =>  $item) {
            foreach ($item as $val) {
                $qty += $val['qty_box'];
            }

            $summary_result[$key] = $qty;
            $container = MstContainer::find(2);

            $lsp = MstLsp::where('code_consignee',$val['code_consignee'])
            ->where('id_type_delivery',2)
            ->first();

            $creation[] = [
                'id_type_delivery' => 2,
                'id_mot' => 1,
                'id_container' => 2, //
                'id_lsp' => $lsp->id ?? 2, // ini cari table mst lsp by code cogsingne
                'summary_box' => $qty,
                'code_consignee' => $val['code_consignee'],
                'etd_jkt' => $val['etd_jkt'],
                'etd_ypmi' => $val['etd_ypmi'],
                'etd_wh' => $val['etd_wh'],
                'measurement' => $container->measurement ?? null,
                'id_prospect_container' => $val['id_prospect_container'],
                'item_no' => $key
            ];

        }


         foreach ($creation as $item) {
             $store = RegularDeliveryPlanProspectContainerCreation::create($item);
             RegularDeliveryPlan::where([
                 'item_no' => $item['item_no'],
                 'id_prospect_container' => $item['id_prospect_container'],
             ])->get()->transform(function ($item) use ($store){
                 $item->id_prospect_container_creation = $store->id;
                 $item->save();
             });
         }

        if($is_transaction) DB::commit();
        return $creation;
        } catch (\Throwable $th) {
             if($is_transaction) DB::rollBack();
             throw $th;
        }
    }

}
