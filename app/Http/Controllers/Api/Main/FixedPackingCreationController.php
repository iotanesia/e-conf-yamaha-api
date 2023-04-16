<?php

namespace App\Http\Controllers\Api\Main;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\ApiHelper as ResponseInterface;
use App\Query\QueryRegularFixedPackingCreation;

class FixedPackingCreationController extends Controller
{
    public function getData(Request $request)
    {
        try {
            return ResponseInterface::responseData(
                QueryRegularFixedPackingCreation::getAll($request)
            );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }
    public function detail(Request $request,$id)
    {
        try {
            return ResponseInterface::responseData(
                QueryRegularFixedPackingCreation::byId($id)
            );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }
    public function create(Request $request)
    {
        try {
            return ResponseInterface::responseData(
                QueryRegularFixedPackingCreation::store($request)
            );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }
    public function update(Request $request,$id)
    {
        try {
            return ResponseInterface::responseData(
                QueryRegularFixedPackingCreation::change($id,$request)
            );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }
    public function delete(Request $request,$id)
    {
        try {
            return ResponseInterface::responseData(
                QueryRegularFixedPackingCreation::deleted($id,$request)
            );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }
}
