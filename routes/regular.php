<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\Main\OrderEntryController;
use App\Http\Controllers\Api\Main\OrderEntryUploadController;
use App\Http\Controllers\Api\Main\OrderEntryUploadDetailController;
use App\Http\Controllers\Api\Main\DeliveryPlanController;
use App\Http\Controllers\Api\Main\OrderEntryDcManagerController;
use App\Http\Controllers\Api\Main\OrderEntryPcController;
use App\Http\Controllers\Api\Main\ProspectContainerController;

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

    // order entry pc
    Route::group(['prefix' => 'order-entry-dc-manager'],function (){
        Route::get('/',[OrderEntryDcManagerController::class,'index']);
    });

     // order-entry-upload
     Route::group(['prefix' => 'order-entry-upload'],function (){
         Route::get('/',[OrderEntryUploadController::class,'index']);
         Route::get('/revision',[OrderEntryUploadController::class,'revision']);
         Route::post('/finish',[OrderEntryUploadController::class,'finish']);
         Route::post('/send-pc',[OrderEntryUploadController::class,'sendPc']);
         Route::post('/send-approve',[OrderEntryUploadController::class,'sendApprove']);
         Route::post('/send-dc-manager',[OrderEntryUploadController::class,'sendDcManager']);
         Route::post('/revision',[OrderEntryUploadController::class,'sendRevision']);
         Route::get('/{id}',[OrderEntryUploadController::class,'show']);
         Route::delete('/{id}',[OrderEntryUploadController::class,'delete']);
     });

     // order-entry-uplaod-detail
     Route::group(['prefix' => 'order-entry-upload-detail'],function (){
         Route::get('/',[OrderEntryUploadDetailController::class,'index']);
         Route::get('/{id}',[OrderEntryUploadDetailController::class,'show']);
         Route::put('/',[OrderEntryUploadDetailController::class,'update']);
         Route::post('/edit-pivot',[OrderEntryUploadDetailController::class,'editPivot']);
         Route::get('/box-pivot',[OrderEntryUploadDetailController::class,'editPivot']);
      });

      // delivery-plan
     Route::group(['prefix' => 'delivery-plan'],function (){
        Route::get('/',[DeliveryPlanController::class,'getDeliveryPlan']);
      });

      // delivery-plan-detail
     Route::group(['prefix' => 'delivery-plan-detail'],function (){
        Route::get('/',[DeliveryPlanController::class,'getDeliveryPlanDetail']);
      });

      // order entry pc
    Route::group(['prefix' => 'prospect-container'],function (){
        Route::get('/',[ProspectContainerController::class,'index']);
    });

});
