<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\Main\OrderEntryController;
use App\Http\Controllers\Api\Main\OrderEntryUploadController;
use App\Http\Controllers\Api\Main\OrderEntryUploadDetailController;
use App\Http\Controllers\Api\Main\DeliveryPlanController;
use App\Http\Controllers\Api\Main\DocumentController;
use App\Http\Controllers\Api\Main\FixedPackingCreationController;
use App\Http\Controllers\Api\Main\FixedQuantityConfirmationController;
use App\Http\Controllers\Api\Main\FixedShippingInstructionController;
use App\Http\Controllers\Api\Main\OrderEntryDcManagerController;
use App\Http\Controllers\Api\Main\OrderEntryPcController;
use App\Http\Controllers\Api\Main\ProspectContainerController;
use App\Http\Controllers\Api\Main\PlanController;
use App\Http\Controllers\APi\Main\ShippingInstructionController;
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
         Route::post('retry/list',[OrderEntryUploadController::class,'retry']);
         Route::post('retry/info',[OrderEntryUploadController::class,'retryInfo']);
         Route::post('revision',[OrderEntryController::class,'revision']);
     });

     // order-entry-uplaod-detail
     Route::group(['prefix' => 'order-entry-upload-detail'],function (){
         Route::get('/',[OrderEntryUploadDetailController::class,'index']);
         Route::put('/',[OrderEntryUploadDetailController::class,'update']);
         Route::post('/edit-pivot',[OrderEntryUploadDetailController::class,'editPivot']);
         Route::get('/box-pivot/{id}',[OrderEntryUploadDetailController::class,'boxPivot']);
         Route::post('/box-pivot/{id}',[OrderEntryUploadDetailController::class,'boxPivotEdit']);
         Route::get('/{id}',[OrderEntryUploadDetailController::class,'show']);
      });

      // delivery-plan
     Route::group(['prefix' => 'delivery-plan'],function (){
        Route::get('/',[DeliveryPlanController::class,'getDeliveryPlan']);
        Route::post('/no-packaging',[DeliveryPlanController::class,'noPackaging']);
        Route::post('/inquiry',[DeliveryPlanController::class,'inquiryProcess']);
        Route::post('/edit',[DeliveryPlanController::class,'update']);

        Route::group(['prefix' => 'produksi'],function (){
            Route::get('/{id}',[DeliveryPlanController::class,'showProduksi']);
            Route::get('box/{id}',[DeliveryPlanController::class,'showProduksiBox']);
            Route::post('/labeling',[DeliveryPlanController::class,'storeLabeling']);
            Route::get('box/labeling/{id}',[DeliveryPlanController::class,'labeling']);
        });

        Route::group(['prefix' => 'prospect-container'],function (){
            Route::post('/creation',[ProspectContainerController::class,'creation']);
            Route::delete('/creation/delete/{id}',[ProspectContainerController::class,'creationDelete']);
            Route::post('/creation/move',[ProspectContainerController::class,'creationMove']);
            Route::put('/edit-mot',[DeliveryPlanController::class,'editMot']);
            Route::post('/fifo',[ProspectContainerController::class,'fifo']);
            Route::get('/simulation',[ProspectContainerController::class,'simulation']);
            Route::get('/simulationex',[ProspectContainerController::class,'simulationex']);
            Route::post('/creationsimulation',[ProspectContainerController::class,'creationSimulation']);
            Route::post('/creation/detail',[ProspectContainerController::class,'detail']);
            Route::post('/creation/detail/air',[ProspectContainerController::class,'detailAir']);
            Route::get('/fifo/{id}',[ProspectContainerController::class,'show']);
        });

        Route::group(['prefix' => 'shipping-instruction'],function (){
            Route::get('/',[DeliveryPlanController::class,'shippingInstruction']);
            Route::post('/',[DeliveryPlanController::class,'shippingInstructionStore']);
            Route::post('/update-status',[DeliveryPlanController::class,'shippingInstructionUpdate']);
            Route::get('/draft/{id}',[DeliveryPlanController::class,'getShippingInstructionListDraft']);
            Route::get('/draft/detail/{id}',[DeliveryPlanController::class,'shippingInstructionListDraftDetail']);
            Route::post('/download-dok/{id}',[DeliveryPlanController::class,'shippingInstructionDownloadDoc']);
            Route::post('/download-dok-draft/{id}',[DeliveryPlanController::class,'shippingInstructionDownloadDocDraft']);
            Route::get('/download-dok-draft/{id}/{filename}',[DeliveryPlanController::class,'shippingInstructionDownloadDocDraftSave']);
            Route::get('/{id}',[DeliveryPlanController::class,'shippingInstructionDetail']);
            Route::post('/detail',[DeliveryPlanController::class,'shippingInstructionDetailSI']);
            Route::post('/draft',[DeliveryPlanController::class,'shippingInstructionListDraft']);
        });

        Route::group(['prefix' => 'booking'],function(){
            Route::post('/detail',[DeliveryPlanController::class,'detailById']);
            Route::post('/generate-no-booking',[DeliveryPlanController::class,'generateNobooking']);
            Route::post('/save-booking',[DeliveryPlanController::class,'savebooking']);
            Route::get('/download/{id}',[ShippingInstructionController::class,'downloadDoc']);
        });

        Route::group(['prefix' => 'bml'],function(){
            Route::get('/',[DeliveryPlanController::class,'getBml']);
            Route::get('/{id}',[DeliveryPlanController::class,'bmlDetail']);
        });

        Route::get('/{id}',[DeliveryPlanController::class,'show']);
        Route::get('/{id}/detail-box',[DeliveryPlanController::class,'detailBox']);
        Route::get('/{id}/export',[DeliveryPlanController::class,'exportExcel']);
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
            Route::post('/delete/{id}',[StockConfirmationController::class,'deleteOutStock']);
            Route::post('/delivery-note',[StockConfirmationController::class,'outstockDeliveryNote']);
            Route::post('/delivery-note/items',[StockConfirmationController::class,'outstockDeliveryNoteItems']);
            Route::post('/delivery-note/save',[StockConfirmationController::class,'saveOutStockNote']);
            Route::post('/delivery-note/print',[StockConfirmationController::class,'printOutStockNote']);
        });

    });

    // tracking ss
    Route::group(['prefix' => 'fixed-quantity'],function (){
        Route::get('/',[StockConfirmationController::class,'fixedQuantity']);
    });

    //fixed
    Route::group(['prefix' => 'fixed'],function (){
        Route::group(['prefix' => 'quantity-confirmation'],function (){
            Route::get('/',[FixedQuantityConfirmationController::class,'getFixedQuantity']);
            Route::post('/no-packaging',[FixedQuantityConfirmationController::class,'noPackaging']);
            Route::post('/inquiry',[FixedQuantityConfirmationController::class,'inquiryProcess']);
            Route::post('/edit',[FixedQuantityConfirmationController::class,'update']);
        });

        Route::group(['prefix' => 'actual-container'],function (){
            Route::get('/',[FixedQuantityConfirmationController::class,'getActualContainer']);
            Route::get('/simulation',[FixedQuantityConfirmationController::class,'simulation']);
            Route::post('/creation',[FixedQuantityConfirmationController::class,'creation']);
            Route::put('/edit-mot',[FixedQuantityConfirmationController::class,'editMot']);
            Route::post('/creation/detail',[FixedQuantityConfirmationController::class,'creationDetail']);
            Route::get('/creation/move/{id}',[FixedQuantityConfirmationController::class,'getCreationMove']);
            Route::get('/creation/move/container/{id}',[FixedQuantityConfirmationController::class,'getCreationMoveContainer']);
            Route::post('/creation/move',[FixedQuantityConfirmationController::class,'creationMove']);
            Route::get('/download',[FixedQuantityConfirmationController::class,'creationDownloadDoc']);
            Route::get('/fifo/{id}',[FixedQuantityConfirmationController::class,'show']);
        });

        Route::group(['prefix' => 'booking'],function (){
            Route::post('/generate-no-booking',[FixedQuantityConfirmationController::class,'generateNobooking']);
            Route::post('/detail',[FixedQuantityConfirmationController::class,'detailById']);
            Route::post('/save-booking',[FixedQuantityConfirmationController::class,'savebooking']);
        });

        Route::group(['prefix' => 'shipping-instruction'],function (){
            Route::get('/',[FixedShippingInstructionController::class,'shippingInstruction']);
            Route::post('/',[FixedShippingInstructionController::class,'shippingInstructionStore']);
            Route::post('/update-status',[FixedShippingInstructionController::class,'shippingInstructionUpdate']);
            Route::post('/draft',[FixedShippingInstructionController::class,'shippingInstructionListDraft']);
            Route::get('/draft/detail/{id}',[FixedShippingInstructionController::class,'shippingInstructionListDraftDetail']);
            Route::post('/download-dok/{id}',[FixedShippingInstructionController::class,'shippingInstructionDownloadDoc']);
            Route::post('/download-dok-draft/{id}',[FixedShippingInstructionController::class,'shippingInstructionDownloadDocDraft']);
            Route::get('/creation/{id}',[FixedShippingInstructionController::class,'shippingInstructionDetail']);
            Route::post('/detail',[FixedShippingInstructionController::class,'shippingInstructionDetailSI']);
            Route::post('/send-ccoff', [FixedShippingInstructionController::class,'sendccoff']);
            Route::post('/send-ccman', [FixedShippingInstructionController::class,'sendccman']);
            Route::post('/approve', [FixedShippingInstructionController::class,'approve']);
            Route::post('/revisi', [FixedShippingInstructionController::class,'revisi']);
            Route::post('/reject', [FixedShippingInstructionController::class,'reject']);
            Route::get('/ccspv',[FixedShippingInstructionController::class,'shippingInstructionCcspv']);
            Route::get('/ccman',[FixedShippingInstructionController::class,'shippingInstructionCcman']);
            Route::get('/container/{id}',[FixedShippingInstructionController::class,'shippingInstructionContainer']);
            Route::get('/container/{id}/detail',[FixedShippingInstructionController::class,'shippingInstructionContainerDetail']);
            Route::get('/packing/{id}',[FixedShippingInstructionController::class,'shippingInstructionPacking']);
            Route::get('/deliverynote/head/{id}',[FixedShippingInstructionController::class,'shippingInstructionDeliveryNoteHead']);
            Route::get('/deliverynote/part/{id}',[FixedShippingInstructionController::class,'shippingInstructionDeliveryNotePart']);
            Route::get('/casemarks/{id}',[FixedShippingInstructionController::class,'shippingInstructionCasemarks']);
            Route::get('/sia/{id}',[FixedShippingInstructionController::class,'shippingInstructionActual']);
        });

        Route::group(['prefix' => 'packing'],function(){
            Route::get('/',[FixedPackingCreationController::class,'getData']);
            Route::post('/',[FixedPackingCreationController::class,'create']);
            Route::post('/{id}',[FixedPackingCreationController::class,'update']);
            Route::delete('/{id}',[FixedPackingCreationController::class,'delete']);
            Route::get('/{id}',[FixedPackingCreationController::class,'detail']);
            Route::get('/delivery-note/{id}',[FixedPackingCreationController::class,'packingCreationDeliveryNote']);
            Route::get('/delivery-note/part/{id}',[FixedPackingCreationController::class,'packingCreationDeliveryNotePart']);
            Route::post('/delivery-note/save',[FixedPackingCreationController::class,'packingCreationDeliveryNoteSave']);
            Route::get('/delivery-note/print/{id}',[FixedPackingCreationController::class,'packingCreationDeliveryNotePrint']);
            Route::get('/download/{id}',[FixedQuantityConfirmationController::class,'printPackaging']);
        });

        Route::group(['prefix' => 'casemarks'],function(){
            Route::get('/',[FixedQuantityConfirmationController::class,'getCasemarks']);
            Route::get('/{id}',[FixedQuantityConfirmationController::class,'printCasemarks']);
        });
    });

    Route::group(['prefix' => 'document'],function (){
        Route::get('/',[DocumentController::class,'index']);
    });
});
