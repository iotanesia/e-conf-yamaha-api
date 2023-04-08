<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\Main\OrderEntryController;
use App\Http\Controllers\Api\Main\OrderEntryUploadController;
use App\Http\Controllers\Api\Main\OrderEntryUploadDetailController;
use App\Http\Controllers\Api\Main\DeliveryPlanController;
use App\Http\Controllers\Api\Main\OrderEntryDcManagerController;
use App\Http\Controllers\Api\Main\OrderEntryPcController;
use App\Http\Controllers\Api\Main\ProspectContainerController;
use App\Http\Controllers\Api\Main\PlanController;
use App\Http\Controllers\Api\Main\StockConfirmationController;

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

    //plan
    Route::group(['prefix' => 'plan'],function (){
        Route::get('/pc',[PlanController::class,'indexPc']);
        Route::get('/dc-spv',[PlanController::class,'indexDcSpv']);
        Route::get('/dc-off',[PlanController::class,'indexDcOff']);
        Route::get('/revision',[OrderEntryUploadController::class,'revision']);
        Route::post('/finish',[OrderEntryUploadController::class,'finish']);
        Route::post('/send-approve',[OrderEntryUploadController::class,'sendApprove']);
        Route::post('/send-dc-spv',[OrderEntryUploadController::class,'sendDcSpv']);
        Route::post('/revision',[OrderEntryUploadController::class,'sendRevision']);
        Route::post('/rejected',[OrderEntryUploadController::class,'sendRejected']);
        Route::get('/detail',[OrderEntryUploadDetailController::class,'index']);
    });

     // order-entry-upload
     Route::group(['prefix' => 'order-entry-upload'],function (){
         Route::get('/',[OrderEntryUploadController::class,'index']);
         Route::post('/send-pc',[OrderEntryUploadController::class,'sendPc']);
         Route::get('/{id}',[OrderEntryUploadController::class,'show']);
         Route::delete('/{id}',[OrderEntryUploadController::class,'delete']);
     });

     // order-entry-uplaod-detail
     Route::group(['prefix' => 'order-entry-upload-detail'],function (){
         Route::get('/',[OrderEntryUploadDetailController::class,'index']);
         Route::put('/',[OrderEntryUploadDetailController::class,'update']);
         Route::post('/edit-pivot',[OrderEntryUploadDetailController::class,'editPivot']);
         Route::get('/box-pivot',[OrderEntryUploadDetailController::class,'editPivot']);
         Route::get('/{id}',[OrderEntryUploadDetailController::class,'show']);
      });

      // delivery-plan
     Route::group(['prefix' => 'delivery-plan'],function (){
        Route::get('/',[DeliveryPlanController::class,'getDeliveryPlan']);
        Route::post('/no-packaging',[DeliveryPlanController::class,'noPackaging']);
        Route::post('/inquiry',[DeliveryPlanController::class,'inquiryProcess']);
        Route::post('/edit',[DeliveryPlanController::class,'update']);

        Route::group(['prefix' => 'produksi'],function (){
            Route::post('/labeling',[DeliveryPlanController::class,'storeLabeling']);
            Route::get('/labeling/{id}',[DeliveryPlanController::class,'labeling']);
        });

        Route::group(['prefix' => 'prospect-container'],function (){
            Route::post('/creation',[ProspectContainerController::class,'creation']);
            Route::put('/edit-mot',[DeliveryPlanController::class,'editMot']);
            Route::post('/fifo',[ProspectContainerController::class,'fifo']);
            Route::get('/simulation',[ProspectContainerController::class,'simulation']);
            Route::post('/creation/detail',[ProspectContainerController::class,'detail']);
            Route::get('/fifo/{id}',[ProspectContainerController::class,'show']);
        });

        Route::group(['prefix' => 'shipping-instruction'],function (){
            Route::get('/',[DeliveryPlanController::class,'shippingInstruction']);
            Route::post('/',[DeliveryPlanController::class,'shippingInstructionStore']);
            Route::post('/update-status',[DeliveryPlanController::class,'shippingInstructionUpdate']);
            Route::get('/list-dok-draft/{id}',[DeliveryPlanController::class,'shippingInstructionListDraft']);
            Route::post('/download-dok/{id}',[DeliveryPlanController::class,'shippingInstructionDownloadDoc']);
            Route::post('/download-dok-draft/{id}',[DeliveryPlanController::class,'shippingInstructionDownloadDocDraft']);
            Route::get('/{id}',[DeliveryPlanController::class,'shippingInstructionDetail']);

        });

        Route::group(['prefix' => 'booking'],function(){
            Route::post('/detail',[DeliveryPlanController::class,'detailById']);
            Route::post('/generate-no-booking',[DeliveryPlanController::class,'generateNobooking']);
            Route::post('/save-booking',[DeliveryPlanController::class,'savebooking']);
        });

        Route::group(['prefix' => 'bml'],function(){
            Route::get('/',[DeliveryPlanController::class,'getBml']);
            Route::get('/{id}',[DeliveryPlanController::class,'bmlDetail']);
        });

        Route::get('/{id}',[DeliveryPlanController::class,'show']);
      });

      // delivery-plan-detail
     Route::group(['prefix' => 'delivery-plan-detail'],function (){
        Route::get('/',[DeliveryPlanController::class,'getDeliveryPlanDetail']);
      });

      // order entry pc
    Route::group(['prefix' => 'prospect-container'],function (){
        Route::get('/',[ProspectContainerController::class,'index']);
        Route::post('/booking',[ProspectContainerController::class,'booking']);
    });

    // tracking ss
    Route::group(['prefix' => 'tracking'],function (){
        Route::get('/',[StockConfirmationController::class,'tracking']);
    });

    // stock confirmation
    Route::group(['prefix'=>'stock-confirmation'],function(){
        Route::group(['prefix'=>'instock'],function(){
            Route::get('/',[StockConfirmationController::class,'getInStock']);
            Route::post('/submit',[StockConfirmationController::class,'instockSubmit']);
            Route::post('/inquiry',[StockConfirmationController::class,'instockInquiry']);
            Route::post('/inquiry-scan',[StockConfirmationController::class,'instockInquiryScan']);
            Route::post('/delete/{id}',[StockConfirmationController::class,'deleteInStock']);
        });
        Route::group(['prefix'=>'outstock'],function(){
            Route::get('/',[StockConfirmationController::class,'getOutStock']);
            Route::post('/submit',[StockConfirmationController::class,'outstockSubmit']);
            Route::post('/inquiry',[StockConfirmationController::class,'outstockInquiry']);
            Route::post('/inquiry-scan',[StockConfirmationController::class,'outstockInquiryScan']);
            Route::post('/delete/{id}',[StockConfirmationController::class,'deleteInStock']);
        });
        Route::group(['prefix'=>'outstock-note'],function(){
            Route::post('/',[StockConfirmationController::class,'saveOutStockNote']);
        });

    });


});
