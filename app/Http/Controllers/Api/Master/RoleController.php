<?php

namespace App\Http\Controllers\Api\Master;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\ApiHelper as ResponseInterface;
use App\Query\QueryRole;

class RoleController extends Controller
{
    public function index(Request $request) {
        return ResponseInterface::responseData(
            QueryRole::getAll($request)
        );
    }

    public function store(Request $request)
    {
        try {
            return ResponseInterface::responseData([
                "items" => QueryRole::store($request)
            ]);
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

    public function show(Request $request,$id)
    {
        try {
            return ResponseInterface::responseData([
                "items" => QueryRole::byId($id)
            ]);
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

    public function update(Request $request)
    {
        try {
            return ResponseInterface::responseData([
                "items" => QueryRole::change($request)
            ]);
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

    public function destroy(Request $request,$id)
    {
        try {

            return ResponseInterface::responseData([
                "items" => QueryRole::deleted($id)
            ]);
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }
}
