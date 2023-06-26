<?php

namespace App\Http\Controllers\Api\Master;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\ApiHelper as ResponseInterface;
use App\Query\QueryMstPosition;

class PositionController extends Controller
{
    public function index(Request $request) {
        return ResponseInterface::responseData(
            QueryMstPosition::getAll($request)
        );
    }

    public function store(Request $request)
    {
        try {
            return ResponseInterface::responseData([
                "items" => QueryMstPosition::store($request)
            ]);
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

    public function show(Request $request,$id)
    {
        try {
            return ResponseInterface::responseData([
                "items" => QueryMstPosition::byId($id)
            ]);
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

    public function update(Request $request)
    {
        try {
            return ResponseInterface::responseData([
                "items" => QueryMstPosition::change($request)
            ]);
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

    public function destroy(Request $request,$id)
    {
        try {

            return ResponseInterface::responseData([
                "items" => QueryMstPosition::deleted($id)
            ]);
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }
}
