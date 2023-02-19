<?php

namespace App\Http\Controllers\Api\Main;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\ApiHelper as ResponseInterface;
use App\Query\QueryRegularOrderEntryUpload;

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
}