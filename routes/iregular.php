<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\Main\DocController;
use App\Http\Controllers\Api\Main\DocTypeController;
use App\Http\Controllers\Api\Main\DocumentController;
use App\Http\Controllers\Api\Main\OrderEntryUploadController;
use App\Http\Controllers\Api\Main\OrderEntryUploadDetailController;
use App\Http\Controllers\Api\Main\OrderEntryDcManagerController;

use App\Http\Controllers\Api\Iregular\OrderEntryController;

//with middleware
Route::prefix('v1/iregular')
->namespace('Api')
->group(function () {


     // order-entry
     Route::group(['prefix' => 'order-entry'],function (){
        Route::get('/',[OrderEntryController::class,'index']);
        Route::post('/',[OrderEntryController::class,'store']);
        Route::get('/form',[OrderEntryController::class,'form']);
        Route::post('/part/{id}',[OrderEntryController::class,'storePart']);

        Route::get('/doc/{id}',[OrderEntryController::class,'getDoc']);
     });


     Route::group(['prefix' => 'document'],function (){
        Route::get('/',[DocumentController::class,'index']);
    });



    // // doc type
    // Route::group(['prefix' => 'doc-type'],function (){
    //     Route::get('/',[DocTypeController::class,'index']);
    //     Route::post('/',[DocTypeController::class,'store']);
    //     Route::put('/',[DocTypeController::class,'update']);
    //     Route::get('/{id}',[DocTypeController::class,'show']);
    //     Route::delete('/{id}',[DocTypeController::class,'destroy']);
    // });

});