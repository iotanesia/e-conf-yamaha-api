<?php

namespace App\Http\Controllers\Api\Main;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\ApiHelper as ResponseInterface;
use App\Query\QueryStockConfirmationHistory;
use App\Query\QueryStockConfirmationOutstockNote;

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

    public function instockInquiry(Request $request)
    {
        try {
            return ResponseInterface::responseData(
                QueryStockConfirmationHistory::instockInquiryProcess($request)
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

    public function outstockInquiry(Request $request)
    {
        try {
            return ResponseInterface::responseData(
                QueryStockConfirmationHistory::outstockInquiryProcess($request)
            );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

    public function instockInquiryScan(Request $request)
    {
        try {
            return ResponseInterface::responseData(
                QueryStockConfirmationHistory::instockScanProcess($request)
            );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

    public function outstockInquiryScan(Request $request)
    {
        try {
            return ResponseInterface::responseData(
                QueryStockConfirmationHistory::outstockScanProcess($request)
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

    public function editTracking(Request $request)
    {
        try {
            return ResponseInterface::responseData(
                QueryStockConfirmationHistory::editTrackingDate($request)
            );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }


    public function fixedQuantity(Request $request)
    {
        try {
            return ResponseInterface::responseData(
                QueryStockConfirmationHistory::fixedQuantity($request)
            );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

    public function instockSubmit(Request $request)
    {
        try {
            return ResponseInterface::responseData(
                QueryStockConfirmationHistory::instockSubmit($request)
            );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

    public function outstockSubmit(Request $request)
    {
        try {
            return ResponseInterface::responseData(
                QueryStockConfirmationHistory::outstockSubmit($request)
            );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

    public function outstockDeliveryNote(Request $request)
    {
        try {
            return ResponseInterface::responseData(
                QueryStockConfirmationHistory::outstockDeliveryNote($request)
            );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

    public function outstockDeliveryNoteItems(Request $request)
    {
        try {
            return ResponseInterface::responseData(
                QueryStockConfirmationHistory::outstockDeliveryNoteItems($request)
            );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

    public function saveOutStockNote(Request $request)
    {
        try {
            return ResponseInterface::responseData(
                QueryStockConfirmationOutstockNote::storeOutStockNote($request)
            );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

    public function printOutStockNote(Request $request)
    {
        try {
            $filename = 'outstock_delivery_note-'.$request->id[0].'.pdf';
            $pathToFile =  storage_path().'/app/outstock-delivery-note/'.$filename;
            $data = QueryStockConfirmationOutstockNote::downloadOutStockNote($request,$pathToFile,$filename);
            return ResponseInterface::responseViewFile($pathToFile,$filename);
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }
}
