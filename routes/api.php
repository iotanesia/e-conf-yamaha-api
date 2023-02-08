<?php

use App\Http\Controllers\Api\AuthController;
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

    Route::prefix('master')->group(function(){
        Route::get('role',[RoleController::class,'index']);
        Route::get('position',[PositionController::class,'index']);

    });
});

require __DIR__.'/auth.php';
require __DIR__.'/master.php';
require __DIR__.'/setting.php';
require __DIR__.'/user.php';


