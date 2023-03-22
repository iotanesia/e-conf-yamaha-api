<?php

use App\Http\Controllers\Api\Main\OrderEntryUploadDetailController;
use App\Http\Controllers\Api\Master\BoxController;
use App\Http\Controllers\Api\Master\ConsigneeController;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\Master\ContrainerController;
use App\Http\Controllers\Api\Master\PartController;
use App\Http\Controllers\Api\Master\PortController;
use App\Http\Controllers\Api\Master\PortOfDischargeController;
use App\Http\Controllers\Api\Master\SupplierController;
use App\Http\Controllers\Api\Master\GroupProductController;
use App\Http\Controllers\Api\Master\PositionController;
use App\Http\Controllers\Api\Master\RoleController;
use App\Http\Controllers\Api\Master\DatasourceController;
use App\Query\QueryRegularOrderEntryUploadDetail;

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

    // box
    Route::group(['prefix' => 'box'],function (){
        Route::get('/',[BoxController::class,'index']);
        Route::post('/',[BoxController::class,'store']);
        Route::put('/',[BoxController::class,'update']);
        Route::get('/{id}',[BoxController::class,'show']);
        Route::delete('/{id}',[BoxController::class,'destroy']);
    });
    
    // port of discharge
    Route::group(['prefix' => 'port-of-discharge'],function (){
        Route::get('/',[PortOfDischargeController::class,'index']);
        Route::post('/',[PortOfDischargeController::class,'store']);
        Route::put('/',[PortOfDischargeController::class,'update']);
        Route::get('/{id}',[PortOfDischargeController::class,'show']);
        Route::delete('/{id}',[PortOfDischargeController::class,'destroy']);
    });

    // supplier
    Route::group(['prefix' => 'supplier'],function (){
        Route::get('/',[SupplierController::class,'index']);
        Route::post('/',[SupplierController::class,'store']);
        Route::put('/',[SupplierController::class,'update']);
        Route::get('/{id}',[SupplierController::class,'show']);
        Route::delete('/{id}',[SupplierController::class,'destroy']);
    });

    // group product
    Route::group(['prefix' => 'group-product'],function (){
        Route::get('/',[GroupProductController::class,'index']);
        Route::post('/',[GroupProductController::class,'store']);
        Route::put('/',[GroupProductController::class,'update']);
        Route::get('/{id}',[GroupProductController::class,'show']);
        Route::delete('/{id}',[GroupProductController::class,'destroy']);
    });

    // position
    Route::group(['prefix' => 'position'],function (){
        Route::get('/',[PositionController::class,'index']);
        Route::post('/',[PositionController::class,'store']);
        Route::put('/',[PositionController::class,'update']);
        Route::get('/{id}',[PositionController::class,'show']);
        Route::delete('/{id}',[PositionController::class,'destroy']);
    });

    // role
    Route::group(['prefix' => 'role'],function (){
        Route::get('/',[RoleController::class,'index']);
        Route::post('/',[RoleController::class,'store']);
        Route::put('/',[RoleController::class,'update']);
        Route::get('/{id}',[RoleController::class,'show']);
        Route::delete('/{id}',[RoleController::class,'destroy']);
    });

    // datasource
    Route::group(['prefix' => 'datasource'],function (){
        Route::get('/',[DatasourceController::class,'index']);
        Route::post('/',[DatasourceController::class,'store']);
        Route::put('/',[DatasourceController::class,'update']);
        Route::get('/{nama}',[DatasourceController::class,'show']);
        Route::delete('/{nama}',[DatasourceController::class,'destroy']);
    });

    // category pivot
    Route::group(['prefix' => 'category-pivot'],function (){
        Route::get('/',[OrderEntryUploadDetailController::class,'categoryPivot']);
    });
});
