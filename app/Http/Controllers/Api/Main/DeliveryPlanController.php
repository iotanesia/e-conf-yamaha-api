<?php

namespace App\Http\Controllers\Api\Main;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\ApiHelper as ResponseInterface;
use App\Query\QueryRegularDeliveryPlan;

class DeliveryPlanController extends Controller
{
   
    public function getDeliveryPlan(Request $request)
    {
        try {
            return ResponseInterface::responseData(
                QueryRegularDeliveryPlan::getDeliveryPlan($request)
            );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

    public function getDeliveryPlanDetail(Request $request)
    {
        try {
            return ResponseInterface::responseData(
                QueryRegularDeliveryPlan::getDeliveryPlanDetail($request)
            );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }
}
