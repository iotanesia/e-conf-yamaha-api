<?php

use App\Http\Controllers\Api\UserControler;
use Illuminate\Support\Facades\Route;


//with middleware
Route::prefix('v1/user')
->namespace('Api')
->group(function () {

    Route::get('/',[UserControler::class,'getAll']);
    Route::post('/',[UserControler::class,'save']);
    Route::put('/{id}',[UserControler::class,'update']);
    Route::get('/{id}',[UserControler::class,'getById']);
    Route::delete('/{id}',[UserControler::class,'delete']);

});
