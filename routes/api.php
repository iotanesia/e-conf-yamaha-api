<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\Main\OrderEntryController;
use App\Http\Controllers\Master\PositionController;
use App\Http\Controllers\Master\RoleController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Spatie\Health\Http\Controllers\HealthCheckJsonResultsController;


/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/


Route::get('health', HealthCheckJsonResultsController::class);

//with middleware
Route::prefix('v1')
->namespace('Api')
->middleware('write.log')
->group(function () {
    Route::get('/test',function (Request $request){
       return "service up";
    });

    Route::post('login',[AuthController::class,'login']);
    Route::post('register',[AuthController::class,'register']);

    // main
    Route::prefix('order-entry')->group(function(){
        Route::get('/',[OrderEntryController::class,'index']);
        Route::post('/',[OrderEntryController::class,'store']);
        Route::put('/',[OrderEntryController::class,'update']);
        Route::get('/{id}',[OrderEntryController::class,'show']);
        Route::delete('/{id}',[OrderEntryController::class,'destroy']);
    });
});

require __DIR__.'/auth.php';
require __DIR__.'/master.php';
require __DIR__.'/setting.php';
require __DIR__.'/user.php';
require __DIR__.'/regular.php';
