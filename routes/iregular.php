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
        Route::post('/reject-by-dc-spv',[OrderEntryController::class,'rejectByDcSpv']);
        Route::post('/reject-by-dc-manager',[OrderEntryController::class,'rejectByDcManager']);
        Route::get('/form-data/{id}',[OrderEntryController::class,'formData']);
        Route::get('/form',[OrderEntryController::class,'form']);
        Route::post('/part/{id}',[OrderEntryController::class,'storePart']);
        Route::post('/doc/{id}',[OrderEntryController::class,'storeDoc']);
        Route::get('/doc/{id}',[OrderEntryController::class,'getDoc']);
        Route::get('/file/{id_iregular_order_entry}/{id_doc}',[OrderEntryController::class,'getFile']);
        Route::get('/approval-file/{id}',[OrderEntryController::class,'getApprovalFile']);
        Route::get('/download-approval-doc/{id}',[OrderEntryController::class,'downloadApprovalDoc']);
        Route::post('/approval-doc/{id}',[OrderEntryController::class,'storeApprovalDoc']);
     });

     // delivery-plan
    Route::group(['prefix' => 'delivery-plan'],function (){
        Route::get('/',[DeliveryPlanController::class,'index']);
        Route::get('/shipping-instruction',[DeliveryPlanController::class,'shippingInstructionList']);
        Route::post('/shipping-instruction',[DeliveryPlanController::class,'shippingInstructionStore']);
        Route::get('/shipping-instruction/{id}',[DeliveryPlanController::class,'shippingInstruction']);
        Route::get('/shipping-instruction/draft/{id}',[DeliveryPlanController::class,'shippingInstructionDraft']); //pake id shipping instruction
        Route::get('/shipping-instruction/draft-list/{id}',[DeliveryPlanController::class,'shippingInstructionDraftList']);
        Route::get('/form-data/{id}',[DeliveryPlanController::class,'formData']);
        Route::get('/form',[DeliveryPlanController::class,'form']);
        Route::get('/download-files/{id_iregular_order_entry}',[DeliveryPlanController::class,'downloadFiles']);
        Route::get('/doc/{id}',[DeliveryPlanController::class,'getDoc']);
        Route::post('/invoice/{id_iregular_order_entry}',[DeliveryPlanController::class,'storeInvoice']);
        Route::get('/invoice/{id_iregular_order_entry}',[DeliveryPlanController::class,'getInvoice']);
        Route::get('/invoice-detail/{id_iregular_order_entry}',[DeliveryPlanController::class,'getInvoiceDetail']);
        Route::post('/send-to-input-invoice',[DeliveryPlanController::class,'sendToInputInvoice']);
        Route::post('/reject-by-cc-officer',[DeliveryPlanController::class,'rejectByCcOfficer']);
        Route::post('/form-cc/{id}',[DeliveryPlanController::class,'storeFormCc']);
     });
});