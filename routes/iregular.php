<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\Iregular\OrderEntryController;
use App\Http\Controllers\Api\Iregular\DeliveryPlanController;
use App\Http\Controllers\Api\Iregular\PackingController;

//with middleware
Route::prefix('v1/iregular')
->namespace('Api')
->group(function () {

    // order-entry
    Route::group(['prefix' => 'order-entry'],function (){
        Route::get('/',[OrderEntryController::class,'index']);
        Route::post('/',[OrderEntryController::class,'store']);
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
        Route::get('/spv',[DeliveryPlanController::class,'getSpv']); 
        Route::get('/manager',[DeliveryPlanController::class,'getManager']);
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
        Route::get('/packing-list/{id_iregular_order_entry}',[DeliveryPlanController::class,'getPackingList']);
        Route::get('/packing-list-detail/{id_iregular_order_entry}',[DeliveryPlanController::class,'getPackingListDetail']);
        Route::post('/packing-list/{id_iregular_order_entry}',[DeliveryPlanController::class,'storePackingList']);
        Route::get('/casemark/{id_iregular_order_entry}',[DeliveryPlanController::class,'getCaseMark']);
        Route::post('/casemark/{id_iregular_order_entry}',[DeliveryPlanController::class,'storeCaseMark']);
        Route::get('/get_by_id_iregular_order_entry/{id_iregular_order_entry}',[DeliveryPlanController::class,'getByIdIregularOrderEntry']);
        Route::post('/send-to-input-invoice',[DeliveryPlanController::class,'sendToInputInvoice']);
        Route::post('/reject-by-cc-officer',[DeliveryPlanController::class,'rejectByCcOfficer']);
        Route::post('/reject-by-cc-spv',[DeliveryPlanController::class,'rejectByCcSpv']);
        Route::post('/reject-by-cc-manager',[DeliveryPlanController::class,'rejectByCcManager']);
        Route::post('/form-cc/{id_iregular_order_entry}',[DeliveryPlanController::class,'storeFormCc']);
        Route::post('/send-to-cc-spv',[DeliveryPlanController::class,'sentToCcSpv']);
        Route::post('/send-to-cc-manager',[DeliveryPlanController::class,'sentToCcManager']);
        Route::post('/approved-by-cc-spv',[DeliveryPlanController::class,'approvedByCcSpv']);
        Route::post('/approved-by-cc-manager',[DeliveryPlanController::class,'approvedByCcManager']);
        Route::post('/approved',[DeliveryPlanController::class,'approvedRequest']);
        Route::post('/reject-doc-spv',[DeliveryPlanController::class,'rejectDocSpv']);
        Route::post('/approved-doc-spv',[DeliveryPlanController::class,'approveDocSpv']);
        Route::post('/reject-doc-manager',[DeliveryPlanController::class,'rejectDocManager']);
        Route::post('/approved-doc-manager',[DeliveryPlanController::class,'approveDocManager']);
     });

    // packing
    Route::group(['prefix' => 'packing'],function (){
        Route::get('/',[PackingController::class,'index']);
        Route::get('/delivery-note/{id}',[PackingController::class,'getDeliveryNote']);
        Route::get('/delivery-note/detail/{id}',[PackingController::class,'getDeliveryNoteDetail']);
        Route::post('/delivery-note/{id}',[PackingController::class,'updateDeliveryNote']);
     });
});