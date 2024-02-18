<?php

use Illuminate\Support\Facades\Route;

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
        Route::post('/doc/{id}',[OrderEntryController::class,'storeDoc']);
        Route::get('/doc/{id}',[OrderEntryController::class,'getDoc']);
     });

});