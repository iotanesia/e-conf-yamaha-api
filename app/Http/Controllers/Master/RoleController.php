<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\ApiHelper as ResponseInterface;
use App\Query\Role;
use App\Services\User as Service;

class RoleController extends Controller
{
    public function index(Request $request) {
        return ResponseInterface::resultResponse(
            Role::getAll($request)
        );
    }
}
