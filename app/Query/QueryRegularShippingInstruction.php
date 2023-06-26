<?php

namespace App\Query;

use App\Constants\Constant;
use App\Models\RegularDeliveryPlanShippingInsruction AS Model;
use Illuminate\Support\Facades\DB;
use App\ApiHelper as Helper;
use App\Models\MstSignature;
use App\Models\RegularProspectContainerCreation;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class QueryRegularShippingInstruction extends Model {

    public static function downloadDoc($params,$id,$filename,$pathToFile)
    {
        try {
            $data = RegularProspectContainerCreation::select(
                DB::raw("string_agg(DISTINCT regular_delivery_plan_prospect_container_creation.code_consignee::character varying, ',') as code_consignee"),
                DB::raw("string_agg(DISTINCT regular_delivery_plan_prospect_container_creation.id_shipping_instruction::character varying, ',') as id_shipping_instruction"),
                DB::raw("string_agg(DISTINCT regular_delivery_plan_prospect_container_creation.etd_ypmi::character varying, ',') as etd_ypmi"),
                DB::raw("string_agg(DISTINCT regular_delivery_plan_prospect_container_creation.etd_jkt::character varying, ',') as etd_jkt"),
                DB::raw("string_agg(DISTINCT regular_delivery_plan_prospect_container_creation.id_type_delivery::character varying, ',') as id_type_delivery"),
                DB::raw("string_agg(DISTINCT regular_delivery_plan_prospect_container_creation.id_container::character varying, ',') as id_container"),
                DB::raw("string_agg(DISTINCT regular_delivery_plan_prospect_container_creation.id_lsp::character varying, ',') as id_lsp"),
                DB::raw('COUNT(regular_delivery_plan_prospect_container_creation.id_container) AS count_container')
                )
            ->where('regular_delivery_plan_prospect_container_creation.id_shipping_instruction',$id)
            ->groupBy('regular_delivery_plan_prospect_container_creation.code_consignee')
            ->get();

            if(!$data) throw new \Exception("Data not found", 400);

            $data->transform(function ($item) {

                $city = MstSignature::where('type', 'CITY')->first();
                $approved = MstSignature::where('type', 'APPROVED')->first();
                $checked = MstSignature::where('type', 'CHECKED')->first();
                $issued = MstSignature::where('type', 'ISSUED')->first();

                $arr = RegularProspectContainerCreation::where('id_shipping_instruction',$item->id_shipping_instruction)
                                                        ->where('code_consignee',$item->code_consignee)->get();

                $etd_ypmi = [];
                $etd_jkt = [];
                $fcl_lcl = [];
                $vessel = [];
                $count_container = [];
                foreach ($arr as $value) {
                    $etd_ypmi[] = $value->etd_ypmi;
                    $etd_jkt[] = $value->etd_jkt;
                    $fcl_lcl[] = $value->refMstTypeDelivery->name;
                    $vessel[] = $value->refMstLsp->name;
                    $count_container[] = $value->id_container;
                }

                return [
                    'customer' => $item->refMstConsignee->nick_name,
                    'etd_ypmi' => $etd_ypmi,
                    'etd_jkt' => $etd_jkt,
                    'booking_plan' => [
                        'fcl_lcl' => $fcl_lcl,
                        'hc40' => count($count_container)
                    ],
                    'lsp' => [
                        'booking_no' => null,
                        'target_vessel' => $vessel
                    ],
                    'city' => $city->name,
                    'date' => Carbon::now(),
                    'approved' => $approved->name,
                    'checked' => $checked->name,
                    'issued' => $issued->name,
                ];
            });
   
            Pdf::loadView('exports.booking_doc',[
                'data' => $data->toArray()
            ])
            ->save($pathToFile)
            ->setPaper('A4','potrait')
            ->download($filename);

          } catch (\Throwable $th) {
              return Helper::setErrorResponse($th);
          }
    }

}