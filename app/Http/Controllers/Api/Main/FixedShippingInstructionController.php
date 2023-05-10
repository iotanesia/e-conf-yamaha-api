<?php

namespace App\Http\Controllers\Api\Main;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\ApiHelper as ResponseInterface;
use App\Query\QueryRegularFixedShippingInstruction;

class FixedShippingInstructionController extends Controller
{
    public function shippingInstruction(Request $request)
    {
        try {
            return ResponseInterface::responseData(
                QueryRegularFixedShippingInstruction::shipping($request)
            );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

    public function shippingInstructionStore(Request $request)
    {
        try {
            return ResponseInterface::responseData(
                QueryRegularFixedShippingInstruction::shippingStore($request)
            );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

    public function shippingInstructionUpdate(Request $request)
    {
        try {
            return ResponseInterface::responseData(
                QueryRegularFixedShippingInstruction::shippingUpdate($request)
            );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

    public function shippingInstructionListDraft(Request $request,$id)
    {
        try {
            return ResponseInterface::responseData(
                QueryRegularFixedShippingInstruction::shippingDraftDok($request,$id)
            );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

    public function shippingInstructionListDraftDetail(Request $request,$id)
    {
        try {
            return ResponseInterface::responseData(
                QueryRegularFixedShippingInstruction::shippingDraftDokDetail($request,$id)
            );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

    public function shippingInstructionDownloadDoc(Request $request,$id)
    {
        try {
            $data = QueryRegularFixedShippingInstruction::downloadDoc($request,$id);
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
            $data = QueryRegularFixedShippingInstruction::downloadDocDraft($request,$id);
            $filename = 'shipping-instruction-draft'.$id.'.pdf';
            $pathToFile =  storage_path().'/app/shipping_instruction/'.$filename;
            return ResponseInterface::responseViewFile($pathToFile,$filename);
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

    public function shippingInstructionDetail(Request $request,$id)
    {
        try {
            return ResponseInterface::responseData(
                QueryRegularFixedShippingInstruction::shippingDetail($request,$id)
            );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

    public function shippingInstructionDetailSI(Request $request,$id)
    {
        try {
            return ResponseInterface::responseData(
                QueryRegularFixedShippingInstruction::shippingDetailSI($request,$id)
            );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

    public function sendccoff(Request $request)
    {
        try {
            return ResponseInterface::responseData(
                QueryRegularFixedShippingInstruction::sendccoff($request)
            );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

    public function sendccman(Request $request)
    {
        try {
            return ResponseInterface::responseData(
                QueryRegularFixedShippingInstruction::sendccman($request)
            );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

    public function approve(Request $request)
    {
        try {
            return ResponseInterface::responseData(
                QueryRegularFixedShippingInstruction::approve($request)
            );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

    public function revisi(Request $request)
    {
        try {
            return ResponseInterface::responseData(
                QueryRegularFixedShippingInstruction::revisi($request)
            );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

    public function reject(Request $request)
    {
        try {
            return ResponseInterface::responseData(
                QueryRegularFixedShippingInstruction::reject($request)
            );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }
}
