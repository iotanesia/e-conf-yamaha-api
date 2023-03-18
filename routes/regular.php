<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\Main\OrderEntryController;
use App\Http\Controllers\Api\Main\OrderEntryUploadController;
use App\Http\Controllers\Api\Main\OrderEntryUploadDetailController;
use App\Http\Controllers\Api\Main\DeliveryPlanController;
use App\Http\Controllers\Api\Main\OrderEntryPcController;

//with middleware
Route::prefix('v1/regular')
->namespace('Api')
->group(function () {

     // order-entry
     Route::group(['prefix' => 'order-entry'],function (){
        Route::get('/',[OrderEntryController::class,'index']);
        Route::post('/',[OrderEntryController::class,'store']);
        Route::post('/{id}',[OrderEntryController::class,'update']);
     });

     // order entry pc
    Route::group(['prefix' => 'order-entry-pc'],function (){
        Route::get('/',[OrderEntryPcController::class,'index']);
    });

     // order-entry-upload
     Route::group(['prefix' => 'order-entry-upload'],function (){
         Route::get('/',[OrderEntryUploadController::class,'index']);
         Route::get('/delete',[OrderEntryUploadController::class,'revision']);
         Route::post('/send-pc',[OrderEntryUploadController::class,'sendPc']);
         Route::get('/{id}',[OrderEntryUploadController::class,'show']);
         Route::delete('/{id}',[OrderEntryUploadController::class,'delete']);
     });

     // order-entry-uplaod-detail
     Route::group(['prefix' => 'order-entry-upload-detail'],function (){
         Route::get('/',[OrderEntryUploadDetailController::class,'index']);
         Route::get('/{id}',[OrderEntryUploadDetailController::class,'show']);
         Route::put('/',[OrderEntryUploadDetailController::class,'update']);
         Route::post('/edit-pivot',[OrderEntryUploadDetailController::class,'editPivot']);
      });

      // delivery-plan
     Route::group(['prefix' => 'delivery-plan'],function (){
        Route::get('/',[DeliveryPlanController::class,'getDeliveryPlan']);
      });

      // delivery-plan-detail
     Route::group(['prefix' => 'delivery-plan-detail'],function (){
        Route::get('/',[DeliveryPlanController::class,'getDeliveryPlanDetail']);
      });


});
