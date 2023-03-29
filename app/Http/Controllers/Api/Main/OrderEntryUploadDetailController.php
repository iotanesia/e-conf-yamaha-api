<?php

namespace App\Http\Controllers\Api\Main;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\ApiHelper as ResponseInterface;
use App\Query\QueryRegularOrderEntryUploadDetail;

class OrderEntryUploadDetailController extends Controller
{
    public function index(Request $request)
    {
        try {
            return ResponseInterface::responseData(
                QueryRegularOrderEntryUploadDetail::getAll($request)
            );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

    public function show(Request $request,$id)
    {
        try {
            return ResponseInterface::responseData(
                QueryRegularOrderEntryUploadDetail::byId($request,$id)
            );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

    public function update(Request $request)
    {
        try {
            return ResponseInterface::responseData([
                "items" => QueryRegularOrderEntryUploadDetail::change($request)
            ]);
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

    public function editPivot(Request $request)
    {
        try {
            // return ResponseInterface::responseDataPivot([
            //     "items" => QueryRegularOrderEntryUploadDetail::getItem(),
            //     "column" => QueryRegularOrderEntryUploadDetail::getColumn()
            // ]);
            return ResponseInterface::responseDataPivotNew(QueryRegularOrderEntryUploadDetail::getPivotDetail($request));
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

    public function categoryPivot(Request $request)
    {
        return ResponseInterface::responseData([
            "items" => QueryRegularOrderEntryUploadDetail::getCategoryPivot($request)
        ]);
    }
}
