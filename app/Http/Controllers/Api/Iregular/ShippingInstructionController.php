<?php

namespace App\Http\Controllers\Api\Iregular;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\ApiHelper as ResponseInterface;
use App\Query\QueryIregularShippingInstruction;
use Illuminate\Support\Facades\Storage;


class ShippingInstructionController extends Controller
{
    public function index(Request $request)
    {
        try {
            return ResponseInterface::responseData(
                QueryIregularShippingInstruction::getAll($request)
            );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

    public function getSpv(Request $request)
    {
        try {
            return ResponseInterface::responseData(
                QueryIregularShippingInstruction::getAll($request, 2)
            );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

    public function getManager(Request $request)
    {
        try {
            return ResponseInterface::responseData(
                QueryIregularShippingInstruction::getAll($request, 3)
            );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

    public function getById(Request $request, $id)
    {
        try {
            return ResponseInterface::responseData(
                QueryIregularShippingInstruction::getById($request, $id)
            );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

    public function getCreation(Request $request, $id)
    {
        try {
            return ResponseInterface::responseData(
                QueryIregularShippingInstruction::getCreation($request, $id)
            );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

    public function store(Request $request)
    {
        try {
            return ResponseInterface::responseData(
                QueryIregularShippingInstruction::storeData($request)
            );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }


    public function approvedByCcSpv(Request $request)
    {
        try {
            return ResponseInterface::responseData(
                QueryIregularShippingInstruction::updateStatus($request, 3)
            );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }


    public function approvedByCcManager(Request $request)
    {
        try {
            return ResponseInterface::responseData(
                QueryIregularShippingInstruction::updateStatus($request, 4)
            );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

    public function rejectByCcSpv(Request $request)
    {
        try {
            return ResponseInterface::responseData(
                QueryIregularShippingInstruction::updateStatus($request, 98)
            );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }


    public function rejectByCcManager(Request $request)
    {
        try {
            return ResponseInterface::responseData(
                QueryIregularShippingInstruction::updateStatus($request, 99)
            );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }
}
