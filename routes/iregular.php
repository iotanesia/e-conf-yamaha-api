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
        Route::get('/form',[OrderEntryController::class,'form']);
     });

});