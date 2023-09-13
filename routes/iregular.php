<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\Iregular\DocController;
use App\Http\Controllers\Api\Iregular\DocTypeController;
use App\Http\Controllers\Api\Iregular\DutyTaxController;
use App\Http\Controllers\Api\Iregular\FreightChargeController;
use App\Http\Controllers\Api\Iregular\GoodConditionController;
use App\Http\Controllers\Api\Iregular\GoodCriteriaController;
use App\Http\Controllers\Api\Iregular\GoodPaymentController;
use App\Http\Controllers\Api\Iregular\GoodStatusController;

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

    // doc
    Route::group(['prefix' => 'doc'],function (){
        Route::get('/',[DocController::class,'index']);
        Route::post('/',[DocController::class,'store']);
        Route::put('/',[DocController::class,'update']);
        Route::get('/{id}',[DocController::class,'show']);
        Route::delete('/{id}',[DocController::class,'destroy']);
    });

    // doc type
    Route::group(['prefix' => 'doc-type'],function (){
        Route::get('/',[DocTypeController::class,'index']);
        Route::post('/',[DocTypeController::class,'store']);
        Route::put('/',[DocTypeController::class,'update']);
        Route::get('/{id}',[DocTypeController::class,'show']);
        Route::delete('/{id}',[DocTypeController::class,'destroy']);
    });

    // duty tax
    Route::group(['prefix' => 'duty-tax'],function (){
        Route::get('/',[DutyTaxController::class,'index']);
        Route::post('/',[DutyTaxController::class,'store']);
        Route::put('/',[DutyTaxController::class,'update']);
        Route::get('/{id}',[DutyTaxController::class,'show']);
        Route::delete('/{id}',[DutyTaxController::class,'destroy']);
    });

    // freight charge
    Route::group(['prefix' => 'freight-charge'],function (){
        Route::get('/',[FreightChargeController::class,'index']);
        Route::post('/',[FreightChargeController::class,'store']);
        Route::put('/',[FreightChargeController::class,'update']);
        Route::get('/{id}',[FreightChargeController::class,'show']);
        Route::delete('/{id}',[FreightChargeController::class,'destroy']);
    });

    // good condition
    Route::group(['prefix' => 'good-condition'],function (){
        Route::get('/',[GoodConditionController::class,'index']);
        Route::post('/',[GoodConditionController::class,'store']);
        Route::put('/',[GoodConditionController::class,'update']);
        Route::get('/{id}',[GoodConditionController::class,'show']);
        Route::delete('/{id}',[GoodConditionController::class,'destroy']);
    });

     // good criteria
     Route::group(['prefix' => 'good-criteria'],function (){
        Route::get('/',[GoodCriteriaController::class,'index']);
        Route::post('/',[GoodCriteriaController::class,'store']);
        Route::put('/',[GoodCriteriaController::class,'update']);
        Route::get('/{id}',[GoodCriteriaController::class,'show']);
        Route::delete('/{id}',[GoodCriteriaController::class,'destroy']);
    });

    // good payment
    Route::group(['prefix' => 'good-payment'],function (){
        Route::get('/',[GoodPaymentController::class,'index']);
        Route::post('/',[GoodPaymentController::class,'store']);
        Route::put('/',[GoodPaymentController::class,'update']);
        Route::get('/{id}',[GoodPaymentController::class,'show']);
        Route::delete('/{id}',[GoodPaymentController::class,'destroy']);
    });

    // good status
    Route::group(['prefix' => 'good-status'],function (){
        Route::get('/',[GoodStatusController::class,'index']);
        Route::post('/',[GoodStatusController::class,'store']);
        Route::put('/',[GoodStatusController::class,'update']);
        Route::get('/{id}',[GoodStatusController::class,'show']);
        Route::delete('/{id}',[GoodStatusController::class,'destroy']);
    });


});