<?php

namespace App\Query;

use App\Constants\Constant;
use App\Models\RegularDeliveryPlanProspectContainer AS Model;
use App\ApiHelper as Helper;
use Illuminate\Support\Facades\DB;

class QueryRegulerDeliveryPlanProspectContainer extends Model {

    const cast = 'regular-delivery-plan-prospect-container-container';



    public static function createionProcess($params,$is_transaction = true)
    {
        if($is_transaction) DB::beginTransaction();
        try {

         Helper::requireParams([
             'data'
         ]);

         $ids = array_map(function ($item){
             return $item['id'];
         },$params->data);

        //  $summary = RegularProspectContainerDetail::select('regular_prospect_container_detail.id','item_no')
        //  ->whereHas('refRegularProspectContainer',function ($query) use ($ids){
        //      $query->whereIn('regular_prospect_container.id',$ids);
        //  })
        //  ->get();

        //  $sums_data = [];
        //  $sum = 0;
        //  foreach ($summary as $val) {
        //      foreach ($val->manyBox as $box) {
        //          $qty = $box->refBox->qty ?? 0;
        //          $sum += $qty;
        //      }
        //      $sums_data[$val->item_no] =  $sum;
        //  }

        //  $data = RegularProspectContainerDetail::select('id_prospect_container','item_no')
        //  ->whereHas('refRegularProspectContainer',function ($query) use ($ids){
        //      $query->whereIn('regular_prospect_container.id',$ids);
        //  })
        //  ->groupBy('id_prospect_container','item_no')
        //  ->get()
        //  ->transform(function ($item) use ($params,$sums_data){
        //      $container = MstContainer::find(2);
        //      $lsp = MstLsp::where('code_consignee',$item->refRegularProspectContainer->code_consignee)
        //            ->where('id_type_delivery',2)
        //            ->first();
        //      return [
        //          "id_prospect_container" => $item->id_prospect_container,
        //          "id_type_delivery" => 2,
        //          "id_mot" => 1,
        //          "id_lsp" => $lsp->id ?? 2, // ini cari table mst lsp by code cogsingne
        //          "id_container" => 2, //
        //          "summary_box" => $sums_data[$item->item_no] ?? null,
        //          "measurement" => $container->measurement ?? null, // ini cari table container ambil column measurement
        //          "item_no" => $item->item_no,
        //      ];
        //  })->toArray();

        //  foreach ($data as $item) {
        //      $store = RegularProspectContainerFifo::create($item);
        //      RegularProspectContainerDetail::where([
        //          'item_no' => $item['item_no'],
        //          'id_prospect_container' => $item['id_prospect_container'],
        //      ])->get()->transform(function ($item) use ($store){
        //          $item->id_prospect_container_fifo = $store->id;
        //          $item->save();
        //      });
        //  }

        if($is_transaction) DB::commit();
        } catch (\Throwable $th) {
             if($is_transaction) DB::rollBack();
             throw $th;
        }
    }

}
