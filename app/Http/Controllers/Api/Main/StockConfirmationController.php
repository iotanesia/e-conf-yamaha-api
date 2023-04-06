<?php

namespace App\Http\Controllers\Api\Main;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\ApiHelper as ResponseInterface;
use App\Query\QueryStockConfirmationHistory;

class StockConfirmationController extends Controller
{
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
}
