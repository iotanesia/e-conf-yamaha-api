<?php

namespace App\Http\Controllers\Api\Main;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\ApiHelper as ResponseInterface;
use App\Query\QueryRegularOrderEntryUpload;
use App\Query\QueryRegularOrderEntryUploadRevision;

class OrderEntryUploadController extends Controller
{
    public function index(Request $request)
    {
        try {
            return ResponseInterface::responseData(
                QueryRegularOrderEntryUpload::getAll($request)
            );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

    public function show(Request $request,$id)
    {
        try {
            return ResponseInterface::responseData([
                "items" => QueryRegularOrderEntryUpload::byId($id)
            ]);
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

    public function sendPc(Request $request)
    {
        try {
            return ResponseInterface::responseData([
                "items" => QueryRegularOrderEntryUpload::updateStatusSendToPc($request)
            ]);
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

    public function delete(Request $request,$id)
    {
        try {
            return ResponseInterface::responseData([
                "items" => QueryRegularOrderEntryUpload::destroyz($id)
            ]);
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

    public function revision(Request $request)
    {
        try {
            return ResponseInterface::responseData([
                "items" => QueryRegularOrderEntryUploadRevision::retrive($request)
            ]);
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

    public function sendRevision(Request $request)
    {
        try {
            return ResponseInterface::responseData([
                "items" => QueryRegularOrderEntryUploadRevision::sendRevision($request)
            ]);
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }
}
