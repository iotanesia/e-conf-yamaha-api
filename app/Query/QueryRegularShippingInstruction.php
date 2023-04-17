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

    public static function downloadDoc($params,$id)
    {
        try {
            $data = RegularProspectContainerCreation::select(
                'regular_delivery_plan_prospect_container_creation.code_consignee',
                'regular_delivery_plan_prospect_container_creation.id_shipping_instruction',
                'regular_delivery_plan_prospect_container_creation.etd_ypmi',
                'regular_delivery_plan_prospect_container_creation.etd_jkt',
                'regular_delivery_plan_prospect_container_creation.id_type_delivery',
                'regular_delivery_plan_prospect_container_creation.id_container',
                'regular_delivery_plan_prospect_container_creation.id_lsp',
                )
            ->where('regular_delivery_plan_prospect_container_creation.id_shipping_instruction',$id)
            ->groupBy('regular_delivery_plan_prospect_container_creation.code_consignee','regular_delivery_plan_prospect_container_creation.etd_ypmi','regular_delivery_plan_prospect_container_creation.etd_jkt','regular_delivery_plan_prospect_container_creation.id_type_delivery','regular_delivery_plan_prospect_container_creation.id_container','regular_delivery_plan_prospect_container_creation.id_lsp')
            ->groupBy('regular_delivery_plan_prospect_container_creation.id_shipping_instruction')
            ->paginate($params->limit ?? null);

            if(!$data) throw new \Exception("Data not found", 400);

            $data->transform(function ($item) {

                if($item->refMstTypeDelivery->id == 2) $type_delivery = 'fcl';
                if($item->refMstTypeDelivery->id == 3) $type_delivery = 'lcl';

                $city = MstSignature::where('type', 'CITY')->first();
                $approved = MstSignature::where('type', 'APPROVED')->first();
                $checked = MstSignature::where('type', 'CHECKED')->first();
                $issued = MstSignature::where('type', 'ISSUED')->first();

                return [
                    'customer' => $item->refMstConsignee->nick_name,
                    'etd_ypmi' => $item->etd_ypmi,
                    'etd_jkt' => $item->etd_jkt,
                    'booking_plan' => [
                        $type_delivery => $item->refMstTypeDelivery->name,
                        '40hc' => $item->refMstContainer->container_type.' '.$item->refMstContainer->container_value
                    ],
                    'lsp' => [
                        'booking_no' => null,
                        'target_vessel' => $item->refMstLsp->name
                    ],
                    'city' => $city->name,
                    'approved' => $approved->name,
                    'checked' => $checked->name,
                    'issued' => $issued->name,
                ];
            });
    
            return [
                'items' => $data->items(),
                'last_page' => $data->lastPage()
            ];
          } catch (\Throwable $th) {
              return Helper::setErrorResponse($th);
          }
    }

}