<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\ApiHelper as ResponseInterface;
use App\Query\User;
use App\Services\User as Service;
use App\Services\Signature;
use Illuminate\Support\Facades\File;

class AuthControler extends Controller
{
    public function login(Request $request) {
        return ResponseInterface::resultResponse(
            Service::authenticateuser($request)
        );
    }

    public function register(Request $request) {
        return ResponseInterface::resultResponse(
            User::register($request)
        );
    }
}
