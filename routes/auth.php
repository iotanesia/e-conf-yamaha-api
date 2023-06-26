<?php

use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Route;


Route::prefix('v1/auth')
->namespace('Api')
->group(function () {

     Route::post('/forgot-password',[AuthController::class,'forgotPassword']);
     Route::get('/reset-password',[AuthController::class,'resetPassword']);

});
