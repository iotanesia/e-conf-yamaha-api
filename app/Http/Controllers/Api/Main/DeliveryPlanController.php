<?php

namespace App\Http\Controllers\Api\Main;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\ApiHelper as ResponseInterface;
use App\Query\QueryRegularDeliveryPlan;

class DeliveryPlanController extends Controller
{

    public function getDeliveryPlan(Request $request)
    {
        try {
            return ResponseInterface::responseData(
                QueryRegularDeliveryPlan::getDeliveryPlan($request)
            );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

    public function show(Request $request,$id)
    {
        try {
            return ResponseInterface::responseData(
                QueryRegularDeliveryPlan::detail($request,$id)
            );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

    public function detailBox(Request $request,$id)
    {
        try {
            return ResponseInterface::responseData(
                QueryRegularDeliveryPlan::detailBox($request,$id)
            );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

    public function showProduksi(Request $request,$id)
    {
        try {
            return ResponseInterface::responseData(
                QueryRegularDeliveryPlan::detailProduksi($request,$id)
            );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

    public function showProduksiBox(Request $request,$id)
    {
        try {
            return ResponseInterface::responseData(
                QueryRegularDeliveryPlan::detailProduksiBox($request,$id)
            );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

    public function exportExcel(Request $request,$id)
    {
        try {
            return QueryRegularDeliveryPlan::exportExcel($request,$id);
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

    public function inquiryProcess(Request $request)
    {
        try {
            return ResponseInterface::responseData([
                'items' => QueryRegularDeliveryPlan::inquiryProcess($request)
            ]);
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }


    public function getDeliveryPlanDetail(Request $request)
    {
        try {
            return ResponseInterface::responseData(
                QueryRegularDeliveryPlan::getDeliveryPlanDetail($request)
            );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

    public function noPackaging(Request $request)
    {
        try {
            return ResponseInterface::responseData(
                QueryRegularDeliveryPlan::noPackaging($request)
            );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

    public function update(Request $request)
    {
        try {
            return ResponseInterface::responseData(
                QueryRegularDeliveryPlan::changeEtd($request)
            );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

    public function labeling(Request $request,$id)
    {
        try {
            return ResponseInterface::responseData(
                QueryRegularDeliveryPlan::label($request,$id)
            );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

    public function storeLabeling(Request $request)
    {
        try {
            return ResponseInterface::responseData(
                QueryRegularDeliveryPlan::storeLabel($request)
            );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

    public function editMot(Request $request)
    {
        try {
            return ResponseInterface::responseData(
                QueryRegularDeliveryPlan::updateProspectContainerCreation($request)
            );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

    public function shippingInstruction(Request $request)
    {
        try {
            return ResponseInterface::responseData(
                QueryRegularDeliveryPlan::shipping($request)
            );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

    public function shippingInstructionDetail(Request $request,$id)
    {
        try {
            return ResponseInterface::responseData(
                QueryRegularDeliveryPlan::shippingDetail($request,$id)
            );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

    public function shippingInstructionDetailSI(Request $request)
    {
        try {
            return ResponseInterface::responseData(
                QueryRegularDeliveryPlan::shippingDetailSI($request)
            );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

    public function shippingInstructionStore(Request $request)
    {
        try {
            return ResponseInterface::responseData(
                QueryRegularDeliveryPlan::shippingStore($request)
            );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

    public function shippingInstructionUpdate(Request $request)
    {
        try {
            return ResponseInterface::responseData(
                QueryRegularDeliveryPlan::shippingUpdate($request)
            );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

    public function generateNobooking(Request $request)
    {
        try {
            return ResponseInterface::responseData(
                QueryRegularDeliveryPlan::genNoBook($request)
            );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

    public function savebooking(Request $request)
    {
        try {
            return ResponseInterface::responseData(
                QueryRegularDeliveryPlan::saveBook($request)
            );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

    public function detailById(Request $request)
    {
        try {
            return ResponseInterface::responseData(
                QueryRegularDeliveryPlan::detailById($request)
            );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

    public function shippingInstructionDownloadDoc(Request $request,$id)
    {
        try {
            $data = QueryRegularDeliveryPlan::downloadDoc($request,$id);
            $filename = 'shipping-instruction-'.$id.'.pdf';
            $pathToFile =  storage_path().'/app/shipping_instruction/'.$filename;
            return ResponseInterface::responseViewFile($pathToFile,$filename);
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

    public function shippingInstructionDownloadDocDraft(Request $request,$id)
    {
        try {
            return ResponseInterface::responseData(
                QueryRegularDeliveryPlan::downloadDocDraft($request,$id)
            );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

    public function shippingInstructionDownloadDocDraftSave(Request $request,$id,$filename)
    {
        try {
            $filename = 'shipping-instruction-'.$id.'.pdf';
            $pathToFile =  storage_path().'/app/shipping_instruction/'.$filename;
            $data = QueryRegularDeliveryPlan::downloadDocDraftSave($request,$id,$filename);
            return ResponseInterface::responseViewFile($pathToFile,$filename);
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

    public function shippingInstructionListDraft(Request $request,$id)
    {
        try {
            return ResponseInterface::responseData(
                QueryRegularDeliveryPlan::shippingDraftDok($request,$id)
            );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

    public function shippingInstructionListDraftDetail(Request $request,$id)
    {
        try {
            return ResponseInterface::responseData(
                QueryRegularDeliveryPlan::shippingDraftDokDetail($request,$id)
            );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

    public function getBml(Request $request) {
        try {
            return ResponseInterface::responseData(
                QueryRegularDeliveryPlan::bml($request)
            );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

    public function bmlDetail(Request $request) {
        try {
            return ResponseInterface::responseData(
                QueryRegularDeliveryPlan::bmlDetail($request)
            );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

}
