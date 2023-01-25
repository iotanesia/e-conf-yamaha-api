<?php

use App\Http\Controllers\Api\Master\ConsigneeController;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\Master\ContrainerController;
use App\Http\Controllers\Api\Master\PartController;
use App\Http\Controllers\Api\Master\PortController;

//with middleware
Route::prefix('v1/master')
->namespace('Api')
->group(function () {

     // container
     Route::group(['prefix' => 'container'],function (){
        Route::get('/',[ContrainerController::class,'index']);
        Route::post('/',[ContrainerController::class,'store']);
        Route::put('/',[ContrainerController::class,'update']);
        Route::get('/{id}',[ContrainerController::class,'show']);
        Route::delete('/{id}',[ContrainerController::class,'destroy']);
     });

    // consignee
    Route::group(['prefix' => 'consignee'],function (){
        Route::get('/',[ConsigneeController::class,'index']);
        Route::post('/',[ConsigneeController::class,'store']);
        Route::put('/',[ConsigneeController::class,'update']);
        Route::get('/{id}',[ConsigneeController::class,'show']);
        Route::delete('/{id}',[ConsigneeController::class,'destroy']);
    });

    // part
    Route::group(['prefix' => 'part'],function (){
        Route::get('/',[PartController::class,'index']);
        Route::post('/',[PartController::class,'store']);
        Route::put('/',[PartController::class,'update']);
        Route::get('/{id}',[PartController::class,'show']);
        Route::delete('/{id}',[PartController::class,'destroy']);
    });

    // port
    Route::group(['prefix' => 'port'],function (){
        Route::get('/',[PortController::class,'index']);
        Route::post('/',[PortController::class,'store']);
        Route::put('/',[PortController::class,'update']);
        Route::get('/{id}',[PortController::class,'show']);
        Route::delete('/{id}',[PortController::class,'destroy']);
    });
});
