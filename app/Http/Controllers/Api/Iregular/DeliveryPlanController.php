<?php

namespace App\Http\Controllers\Api\Iregular;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\ApiHelper as ResponseInterface;
use App\Query\QueryIregularDeliveryPlan;

class DeliveryPlanController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        try {
            return ResponseInterface::responseData(
                QueryIregularDeliveryPlan::getAll($request)
            );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

    public function form(Request $request)
    {
        try {
            return ResponseInterface::responseData(
                QueryIregularDeliveryPlan::getForm($request)
            );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

    public function formData(Request $request, $id)
    {
        try {
            return ResponseInterface::responseData(
                QueryIregularDeliveryPlan::getFormData($request, $id)
            );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

    public function getDoc(Request $request, $id)
    {
        try {
            return ResponseInterface::responseData(
                QueryIregularDeliveryPlan::getDoc($request, $id)
            );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

}
