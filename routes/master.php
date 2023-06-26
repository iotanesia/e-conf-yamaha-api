<?php

use App\Http\Controllers\Api\Main\OrderEntryUploadDetailController;
use App\Http\Controllers\Api\Master\BoxController;
use App\Http\Controllers\Api\Master\ConsigneeController;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\Master\ContrainerController;
use App\Http\Controllers\Api\Master\PartController;
use App\Http\Controllers\Api\Master\PortController;
use App\Http\Controllers\Api\Master\PortOfDischargeController;
use App\Http\Controllers\Api\Master\SupplierController;
use App\Http\Controllers\Api\Master\GroupProductController;
use App\Http\Controllers\Api\Master\PositionController;
use App\Http\Controllers\Api\Master\RoleController;
use App\Http\Controllers\Api\Master\DatasourceController;
use App\Http\Controllers\Api\Master\LspController;
use App\Http\Controllers\Api\Master\MotController;
use App\Http\Controllers\Api\Master\ShipmentController;
use App\Http\Controllers\Api\Master\TypeDeliveryController;
use App\Http\Controllers\Api\Master\SignatureController;
use App\Http\Controllers\Api\Master\CategoryFilterController;
use App\Http\Controllers\Api\Master\ComoditiesController;
use App\Http\Controllers\Api\Master\ConsumableController;
use App\Http\Controllers\Api\Master\DocController;
use App\Http\Controllers\Api\Master\DocTypeController;
use App\Http\Controllers\Api\Master\DutyTaxController;
use App\Http\Controllers\Api\Master\FreightChargeController;
use App\Http\Controllers\Api\Master\GoodConditionController;
use App\Http\Controllers\Api\Master\GoodCriteriaController;
use App\Http\Controllers\Api\Master\GoodPaymentController;
use App\Http\Controllers\Api\Master\GoodStatusController;
use App\Http\Controllers\Api\Master\IncotermsController;
use App\Http\Controllers\Api\Master\InlandCostController;
use App\Http\Controllers\Api\Master\InsuranceController;
use App\Http\Controllers\Api\Master\ShippedByController;
use App\Http\Controllers\Api\Master\TypeTransactionController;
use App\Query\QueryRegularOrderEntryUploadDetail;

//with middleware
Route::prefix('v1/master')
->namespace('Api')
// ->middleware('access')
->group(function () {

     // container
     Route::group(['prefix' => 'container'],function (){
        Route::get('/',[ContrainerController::class,'index']);
        Route::post('/',[ContrainerController::class,'store']);
        Route::put('/',[ContrainerController::class,'update']);
        Route::get('/{id}',[ContrainerController::class,'show']);
        Route::delete('/{id}',[ContrainerController::class,'destroy']);
     });

    // consignee
    Route::group(['prefix' => 'consignee'],function (){
        Route::get('/',[ConsigneeController::class,'index']);
        Route::post('/',[ConsigneeController::class,'store']);
        Route::put('/',[ConsigneeController::class,'update']);
        Route::get('/{id}',[ConsigneeController::class,'show']);
        Route::delete('/{id}',[ConsigneeController::class,'destroy']);
    });

    // part
    Route::group(['prefix' => 'part'],function (){
        Route::get('/',[PartController::class,'index']);
        Route::post('/',[PartController::class,'store']);
        Route::put('/',[PartController::class,'update']);
        Route::get('/{id}',[PartController::class,'show']);
        Route::delete('/{id}',[PartController::class,'destroy']);
    });

    // port
    Route::group(['prefix' => 'port'],function (){
        Route::get('/',[PortController::class,'index']);
        Route::post('/',[PortController::class,'store']);
        Route::put('/',[PortController::class,'update']);
        Route::get('/{id}',[PortController::class,'show']);
        Route::delete('/{id}',[PortController::class,'destroy']);
    });

    // box
    Route::group(['prefix' => 'box'],function (){
        Route::get('/',[BoxController::class,'index']);
        Route::post('/',[BoxController::class,'store']);
        Route::put('/',[BoxController::class,'update']);
        Route::get('/{id}',[BoxController::class,'show']);
        Route::delete('/{id}',[BoxController::class,'destroy']);
    });

    // port of discharge
    Route::group(['prefix' => 'port-of-discharge'],function (){
        Route::get('/',[PortOfDischargeController::class,'index']);
        Route::post('/',[PortOfDischargeController::class,'store']);
        Route::put('/',[PortOfDischargeController::class,'update']);
        Route::get('/{id}',[PortOfDischargeController::class,'show']);
        Route::delete('/{id}',[PortOfDischargeController::class,'destroy']);
    });

    // supplier
    Route::group(['prefix' => 'supplier'],function (){
        Route::get('/',[SupplierController::class,'index']);
        Route::post('/',[SupplierController::class,'store']);
        Route::put('/',[SupplierController::class,'update']);
        Route::get('/{id}',[SupplierController::class,'show']);
        Route::delete('/{id}',[SupplierController::class,'destroy']);
    });

    // group product
    Route::group(['prefix' => 'group-product'],function (){
        Route::get('/',[GroupProductController::class,'index']);
        Route::post('/',[GroupProductController::class,'store']);
        Route::put('/',[GroupProductController::class,'update']);
        Route::get('/{id}',[GroupProductController::class,'show']);
        Route::delete('/{id}',[GroupProductController::class,'destroy']);
    });

    // position
    Route::group(['prefix' => 'position'],function (){
        Route::get('/',[PositionController::class,'index']);
        Route::post('/',[PositionController::class,'store']);
        Route::put('/',[PositionController::class,'update']);
        Route::get('/{id}',[PositionController::class,'show']);
        Route::delete('/{id}',[PositionController::class,'destroy']);
    });

    // role
    Route::group(['prefix' => 'role'],function (){
        Route::get('/',[RoleController::class,'index']);
        Route::post('/',[RoleController::class,'store']);
        Route::put('/',[RoleController::class,'update']);
        Route::get('/{id}',[RoleController::class,'show']);
        Route::delete('/{id}',[RoleController::class,'destroy']);
    });

    // datasource
    Route::group(['prefix' => 'datasource'],function (){
        Route::get('/',[DatasourceController::class,'index']);
        Route::post('/',[DatasourceController::class,'store']);
        Route::put('/',[DatasourceController::class,'update']);
        Route::get('/{nama}',[DatasourceController::class,'show']);
        Route::delete('/{nama}',[DatasourceController::class,'destroy']);
    });

    // category pivot
    Route::group(['prefix' => 'category-pivot'],function (){
        Route::get('/',[OrderEntryUploadDetailController::class,'categoryPivot']);
    });

    Route::group(['prefix' => 'filter'],function (){
        Route::group(['prefix' => 'plan'],function (){
            Route::get('/prospect-container',[CategoryFilterController::class,'getProspectContainer']);
            Route::get('/part',[CategoryFilterController::class,'getPart']);
            Route::get('/inquiry',[CategoryFilterController::class,'getInquiry']);
        });
    });


    // mot
    Route::group(['prefix' => 'mot'],function (){
        Route::get('/',[MotController::class,'index']);
        Route::post('/',[MotController::class,'store']);
        Route::put('/',[MotController::class,'update']);
        Route::get('/{id}',[MotController::class,'show']);
        Route::delete('/{id}',[MotController::class,'destroy']);
    });

    // Lsp
    Route::group(['prefix' => 'lsp'],function (){
        Route::get('/',[LspController::class,'index']);
        Route::post('/',[LspController::class,'store']);
        Route::put('/',[LspController::class,'update']);
        Route::get('/{id}',[LspController::class,'show']);
        Route::delete('/{id}',[LspController::class,'destroy']);
    });

    // type delivery
    Route::group(['prefix' => 'type-delivery'],function (){
        Route::get('/',[TypeDeliveryController::class,'index']);
        Route::post('/',[TypeDeliveryController::class,'store']);
        Route::put('/',[TypeDeliveryController::class,'update']);
        Route::get('/{id}',[TypeDeliveryController::class,'show']);
        Route::delete('/{id}',[TypeDeliveryController::class,'destroy']);
    });

    // shipment
    Route::group(['prefix' => 'shipment'],function (){
        Route::get('/',[ShipmentController::class,'index']);
        Route::post('/',[ShipmentController::class,'store']);
        Route::put('/',[ShipmentController::class,'update']);
        Route::get('/{id}',[ShipmentController::class,'show']);
        Route::delete('/{id}',[ShipmentController::class,'destroy']);
        Route::get('/active/shipment-active',[ShipmentController::class,'isActive']);
    });

    // signature
    Route::group(['prefix' => 'signature'],function (){
        Route::get('/',[SignatureController::class,'index']);
        Route::post('/',[SignatureController::class,'store']);
        Route::put('/',[SignatureController::class,'update']);
        Route::get('/{id}',[SignatureController::class,'show']);
        Route::delete('/{id}',[SignatureController::class,'destroy']);
    });

    // type transaction
    Route::group(['prefix' => 'type-transaction'],function (){
        Route::get('/',[TypeTransactionController::class,'index']);
        Route::post('/',[TypeTransactionController::class,'store']);
        Route::put('/',[TypeTransactionController::class,'update']);
        Route::get('/{id}',[TypeTransactionController::class,'show']);
        Route::delete('/{id}',[TypeTransactionController::class,'destroy']);
    });

    // comodities
    Route::group(['prefix' => 'comodities'],function (){
        Route::get('/',[ComoditiesController::class,'index']);
        Route::post('/',[ComoditiesController::class,'store']);
        Route::put('/',[ComoditiesController::class,'update']);
        Route::get('/{id}',[ComoditiesController::class,'show']);
        Route::delete('/{id}',[ComoditiesController::class,'destroy']);
    });

    // consumable
    Route::group(['prefix' => 'consumable'],function (){
        Route::get('/',[ConsumableController::class,'index']);
        Route::post('/',[ConsumableController::class,'store']);
        Route::put('/',[ConsumableController::class,'update']);
        Route::get('/{id}',[ConsumableController::class,'show']);
        Route::delete('/{id}',[ConsumableController::class,'destroy']);
    });

    // good condition
    Route::group(['prefix' => 'good-condition'],function (){
        Route::get('/',[GoodConditionController::class,'index']);
        Route::post('/',[GoodConditionController::class,'store']);
        Route::put('/',[GoodConditionController::class,'update']);
        Route::get('/{id}',[GoodConditionController::class,'show']);
        Route::delete('/{id}',[GoodConditionController::class,'destroy']);
    });

    // good status
    Route::group(['prefix' => 'good-status'],function (){
        Route::get('/',[GoodStatusController::class,'index']);
        Route::post('/',[GoodStatusController::class,'store']);
        Route::put('/',[GoodStatusController::class,'update']);
        Route::get('/{id}',[GoodStatusController::class,'show']);
        Route::delete('/{id}',[GoodStatusController::class,'destroy']);
    });

    // good payment
    Route::group(['prefix' => 'good-payment'],function (){
        Route::get('/',[GoodPaymentController::class,'index']);
        Route::post('/',[GoodPaymentController::class,'store']);
        Route::put('/',[GoodPaymentController::class,'update']);
        Route::get('/{id}',[GoodPaymentController::class,'show']);
        Route::delete('/{id}',[GoodPaymentController::class,'destroy']);
    });

    // freight charge
    Route::group(['prefix' => 'freight-charge'],function (){
        Route::get('/',[FreightChargeController::class,'index']);
        Route::post('/',[FreightChargeController::class,'store']);
        Route::put('/',[FreightChargeController::class,'update']);
        Route::get('/{id}',[FreightChargeController::class,'show']);
        Route::delete('/{id}',[FreightChargeController::class,'destroy']);
    });

    // insurance
    Route::group(['prefix' => 'insurance'],function (){
        Route::get('/',[InsuranceController::class,'index']);
        Route::post('/',[InsuranceController::class,'store']);
        Route::put('/',[InsuranceController::class,'update']);
        Route::get('/{id}',[InsuranceController::class,'show']);
        Route::delete('/{id}',[InsuranceController::class,'destroy']);
    });

    // duty tax
    Route::group(['prefix' => 'duty-tax'],function (){
        Route::get('/',[DutyTaxController::class,'index']);
        Route::post('/',[DutyTaxController::class,'store']);
        Route::put('/',[DutyTaxController::class,'update']);
        Route::get('/{id}',[DutyTaxController::class,'show']);
        Route::delete('/{id}',[DutyTaxController::class,'destroy']);
    });

    // inland cost
    Route::group(['prefix' => 'inland-cost'],function (){
        Route::get('/',[InlandCostController::class,'index']);
        Route::post('/',[InlandCostController::class,'store']);
        Route::put('/',[InlandCostController::class,'update']);
        Route::get('/{id}',[InlandCostController::class,'show']);
        Route::delete('/{id}',[InlandCostController::class,'destroy']);
    });

    // shipped by
    Route::group(['prefix' => 'shipped-by'],function (){
        Route::get('/',[ShippedByController::class,'index']);
        Route::post('/',[ShippedByController::class,'store']);
        Route::put('/',[ShippedByController::class,'update']);
        Route::get('/{id}',[ShippedByController::class,'show']);
        Route::delete('/{id}',[ShippedByController::class,'destroy']);
    });

    // incoterms
    Route::group(['prefix' => 'incoterms'],function (){
        Route::get('/',[IncotermsController::class,'index']);
        Route::post('/',[IncotermsController::class,'store']);
        Route::put('/',[IncotermsController::class,'update']);
        Route::get('/{id}',[IncotermsController::class,'show']);
        Route::delete('/{id}',[IncotermsController::class,'destroy']);
    });

    // good criteria
    Route::group(['prefix' => 'good-criteria'],function (){
        Route::get('/',[GoodCriteriaController::class,'index']);
        Route::post('/',[GoodCriteriaController::class,'store']);
        Route::put('/',[GoodCriteriaController::class,'update']);
        Route::get('/{id}',[GoodCriteriaController::class,'show']);
        Route::delete('/{id}',[GoodCriteriaController::class,'destroy']);
    });

    // doc
    Route::group(['prefix' => 'doc'],function (){
        Route::get('/',[DocController::class,'index']);
        Route::post('/',[DocController::class,'store']);
        Route::put('/',[DocController::class,'update']);
        Route::get('/{id}',[DocController::class,'show']);
        Route::delete('/{id}',[DocController::class,'destroy']);
    });

    // doc type
    Route::group(['prefix' => 'doc-type'],function (){
        Route::get('/',[DocTypeController::class,'index']);
        Route::post('/',[DocTypeController::class,'store']);
        Route::put('/',[DocTypeController::class,'update']);
        Route::get('/{id}',[DocTypeController::class,'show']);
        Route::delete('/{id}',[DocTypeController::class,'destroy']);
    });
});
