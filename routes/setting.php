<?php

use App\Http\Controllers\Api\Setting\MenuController;
use App\Http\Controllers\Api\Setting\PermissionController;
use Illuminate\Support\Facades\Route;


//with middleware
Route::prefix('v1/setting')
->namespace('Api')
->group(function () {

    Route::group(['prefix' => 'menu'],function (){
        Route::get('/',[MenuController::class,'index']);
        Route::post('/',[MenuController::class,'store']);
        Route::put('/',[MenuController::class,'update']);
        Route::get('/{id}',[MenuController::class,'show']);
        Route::delete('/{id}',[MenuController::class,'destroy']);
    });

    Route::group(['prefix' => 'permission'],function (){
        Route::get('/',[PermissionController::class,'index']);
        Route::post('/',[PermissionController::class,'store']);
        Route::put('/',[PermissionController::class,'update']);
        Route::get('/{id}',[PermissionController::class,'show']);
        Route::delete('/{id}',[PermissionController::class,'destroy']);
    });
});
