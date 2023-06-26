<?php

namespace App\Http\Controllers\Api\Master;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\ApiHelper as ResponseInterface;
use App\Query\QueryMstDatasource;

class DatasourceController extends Controller
{
    public function index(Request $request)
    {
        try {
            return ResponseInterface::responseData(
                QueryMstDatasource::getAll($request)
            );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

    public function store(Request $request)
    {
        try {
            return ResponseInterface::responseData([
                "items" => QueryMstDatasource::store($request)
            ]);
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }


    public function show(Request $request,$nama)
    {
        try {
            return ResponseInterface::responseData([
                "items" => QueryMstDatasource::byNama($nama)
            ]);
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

    public function update(Request $request)
    {
        try {
            return ResponseInterface::responseData([
                "items" => QueryMstDatasource::change($request)
            ]);
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

    public function destroy(Request $request,$nama)
    {
        try {

            return ResponseInterface::responseData([
                "items" => QueryMstDatasource::deleted($nama)
            ]);
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }
}