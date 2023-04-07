<?php

namespace App\Http\Controllers\Api\Main;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\ApiHelper as ResponseInterface;
use App\Query\QueryStockConfirmationHistory;

class StockConfirmationController extends Controller
{
    public function getInStock(Request $request)
    {
        try {
            return ResponseInterface::responseData(
                QueryStockConfirmationHistory::getInStock($request)
            );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

    public function getOutStock(Request $request)
    {
        try {
            return ResponseInterface::responseData(
                QueryStockConfirmationHistory::getOutStock($request)
            );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

    public function deleteInStock(Request $request,$id)
    {
        try {
            return ResponseInterface::responseData(
                QueryStockConfirmationHistory::deleteInStock($request,$id)
            );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }
    public function deleteOutStock(Request $request,$id)
    {
        try {
            return ResponseInterface::responseData(
                QueryStockConfirmationHistory::deleteOutStock($request,$id)
            );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

    public function tracking(Request $request)
    {
        try {
            return ResponseInterface::responseData(
                QueryStockConfirmationHistory::tracking($request)
            );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

}
