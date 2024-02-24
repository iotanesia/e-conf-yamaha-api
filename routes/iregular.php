<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\Iregular\OrderEntryController;
use App\Http\Controllers\Api\Iregular\DeliveryPlanController;

//with middleware
Route::prefix('v1/iregular')
->namespace('Api')
->group(function () {

    // order-entry
    Route::group(['prefix' => 'order-entry'],function (){
        Route::get('/',[OrderEntryController::class,'index']);
        Route::get('/dc-spv',[OrderEntryController::class,'getDcSpv']);
        Route::get('/dc-manager',[OrderEntryController::class,'getDcManager']);
        Route::post('/',[OrderEntryController::class,'store']);
        Route::post('/send-to-dc-spv',[OrderEntryController::class,'sendToDcSpv']);
        Route::post('/send-to-dc-manager',[OrderEntryController::class,'sendToDcManager']);
        Route::post('/send-to-enquiry',[OrderEntryController::class,'sendToEnquiry']);
        Route::post('/reject-by-dc-spv',[OrderEntryController::class,'rejectByDcSpv']);
        Route::post('/reject-by-dc-manager',[OrderEntryController::class,'rejectByDcManager']);
        Route::get('/form-data/{id}',[OrderEntryController::class,'formData']);
        Route::get('/form',[OrderEntryController::class,'form']);
        Route::post('/part/{id}',[OrderEntryController::class,'storePart']);
        Route::post('/doc/{id}',[OrderEntryController::class,'storeDoc']);
        Route::get('/doc/{id}',[OrderEntryController::class,'getDoc']);
     });

     // delivery-plan
    Route::group(['prefix' => 'delivery-plan'],function (){
        Route::get('/',[DeliveryPlanController::class,'index']);
        Route::get('/form-data/{id}',[DeliveryPlanController::class,'formData']);
        Route::get('/form',[DeliveryPlanController::class,'form']);
        Route::get('/doc/{id}',[DeliveryPlanController::class,'getDoc']);
     });
});