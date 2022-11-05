<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\ApiHelper as ResponseInterface;
use App\Services\User as Service;

class UserControler extends Controller
{
    public function getAll(Request $request)
    {
        return ResponseInterface::responseData(
            Service::getAllData($request)
        );
    }

    public function getById(Request $request,$id)
    {
        return ResponseInterface::responseData(
            // Service::byId($id)
        );
    }

    public function save(Request $request)
    {
        return ResponseInterface::responseData(
            Service::saveData($request)
        );
    }

    public function update(Request $request,$id)
    {
        return ResponseInterface::responseData(
            Service::updateData($request,$id)
        );
    }

    public function delete(Request $request,$id)
    {
        return ResponseInterface::responseData(
            Service::deleteData($id)
        );
    }
}
