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

    public function sendToShipping(Request $request)
    {
        try {
            return ResponseInterface::responseData(
                QueryIregularDeliveryPlan::sendApproval($request, 5)
            );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

    public function rejectByCcOfficer(Request $request)
    {
        try {
            return ResponseInterface::responseData(
                QueryIregularDeliveryPlan::sendApproval($request, 9, "Reject CC Officer")
            );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

    public function shippingInstructionList(Request $request)
    {
        try {
            return ResponseInterface::responseData(
                QueryIregularDeliveryPlan::getShippingInstructionList($request)
            );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

    public function shippingInstruction(Request $request, $id)
    {
        try {
            return ResponseInterface::responseData(
                QueryIregularDeliveryPlan::getShippingInstruction($request, $id)
            );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

    public function shippingInstructionDraftList(Request $request, $id)
    {
        try {
            return ResponseInterface::responseData(
                QueryIregularDeliveryPlan::getShippingInstructionDraftList($request, $id)
            );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

    public function shippingInstructionDraft(Request $request, $id)
    {
        try {
            return ResponseInterface::responseData(
                QueryIregularDeliveryPlan::getShippingInstructionDraft($request, $id)
            );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

    public function shippingInstructionStore(Request $request)
    {
        try {
            return ResponseInterface::responseData(
                QueryIregularDeliveryPlan::shippingInstructionStore($request)
            );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

}
