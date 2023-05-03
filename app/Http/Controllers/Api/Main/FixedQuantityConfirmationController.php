<?php

namespace App\Http\Controllers\Api\Main;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\ApiHelper as ResponseInterface;
use App\Query\QueryRegularFixedQuantityConfirmation;

class FixedQuantityConfirmationController extends Controller
{
    public function getFixedQuantity(Request $request)
    {
        try {
            return ResponseInterface::responseData(
                QueryRegularFixedQuantityConfirmation::getFixedQuantity($request)
            );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

    public function noPackaging(Request $request)
    {
        try {
            return ResponseInterface::responseData(
                QueryRegularFixedQuantityConfirmation::noPackaging($request)
            );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

    public function inquiryProcess(Request $request)
    {
        try {
            return ResponseInterface::responseData(
                QueryRegularFixedQuantityConfirmation::inquiryProcess($request)
            );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

    public function update(Request $request)
    {
        try {
            return ResponseInterface::responseData(
                QueryRegularFixedQuantityConfirmation::changeEtd($request)
            );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

    public function getActualContainer(Request $request)
    {
        try {
            return ResponseInterface::responseData(
                QueryRegularFixedQuantityConfirmation::getActualContainer($request)
            );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

    public function simulation(Request $request)
    {
        try {
            return ResponseInterface::responseData(
                QueryRegularFixedQuantityConfirmation::simulation($request)
            );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

    public function creation(Request $request)
    {
        try {
            return ResponseInterface::responseData(
                QueryRegularFixedQuantityConfirmation::creationProcess($request)
            );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

    public function creationDetail(Request $request)
    {
        try {
            return ResponseInterface::responseData(
                QueryRegularFixedQuantityConfirmation::creationDetail($request)
            );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

    public function generateNobooking(Request $request)
    {
        try {
            return ResponseInterface::responseData(
                QueryRegularFixedQuantityConfirmation::generateNobooking($request)
            );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

    public function detailById(Request $request)
    {
        try {
            return ResponseInterface::responseData(
                QueryRegularFixedQuantityConfirmation::detailById($request)
            );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

}
