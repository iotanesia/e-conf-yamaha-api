<?php

namespace App\Http\Controllers\Api\Main;

use App\Http\Controllers\Controller;
use App\Query\QueryRegularFixedQuantityConfirmation;
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

    public function shippingInstructionCcspv(Request $request)
    {
        try {
            return ResponseInterface::responseData(
                QueryRegularFixedShippingInstruction::shippingCCspv($request)
            );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

    public function shippingInstructionCcman(Request $request)
    {
        try {
            return ResponseInterface::responseData(
                QueryRegularFixedShippingInstruction::shippingCCman($request)
            );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

    public function shippingInstructionContainer(Request $request,$id)
    {
        try {
            return ResponseInterface::responseData(
                QueryRegularFixedShippingInstruction::shippingContainer($request,$id)
            );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

    public function shippingInstructionContainerDetail(Request $request,$id)
    {
        try {
            return ResponseInterface::responseData(
                QueryRegularFixedShippingInstruction::shippingContainerDetail($request,$id)
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

    public function shippingInstructionListDraft(Request $request)
    {
        try {
            return ResponseInterface::responseData(
                QueryRegularFixedShippingInstruction::shippingDraftDok($request)
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
            $filename = 'shipping-instruction-'.$id.'.pdf';
            $pathToFile =  storage_path().'/app/shipping_instruction/'.$filename;
            $data = QueryRegularFixedShippingInstruction::downloadDoc($request,$id,$filename,$pathToFile);
            return ResponseInterface::responseViewFile($pathToFile,$filename);
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

    public function shippingInstructionDownloadDocDraft(Request $request,$id)
    {
        try {
            $filename = 'shipping-instruction-draft-'.$id.'.pdf';
            $pathToFile =  storage_path().'/app/shipping_instruction/'.$filename;
            $data = QueryRegularFixedShippingInstruction::downloadDocDraft($request,$id,$filename,$pathToFile);
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

    public function shippingInstructionDetailSI(Request $request)
    {
        try {
            return ResponseInterface::responseData(
                QueryRegularFixedShippingInstruction::shippingDetailSI($request)
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

    public function shippingInstructionPacking(Request $request, $id)
    {
        try {
            $filename = 'packaging-'.$id.'-'.uniqid().'.pdf';
            $pathToFile =  storage_path().'/app/casemarks/'.$filename;
            $data = QueryRegularFixedShippingInstruction::printPackagingShipping($request,$id,$pathToFile,$filename);
            return ResponseInterface::responseViewFile($pathToFile,$filename);
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

    public function shippingInstructionDeliveryNoteHead(Request $request,$id)
    {
        try {
            return ResponseInterface::responseData(
                QueryRegularFixedShippingInstruction::packingCreationDeliveryNoteHead($request,$id)
            );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

    public function shippingInstructionDeliveryNotePart(Request $request,$id)
    {
        try {
            return ResponseInterface::responseData(
                QueryRegularFixedShippingInstruction::packingCreationDeliveryNotePart($request,$id)
            );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

    public function shippingInstructionCasemarks(Request $request,$id)
    {
        try {
            $filename = 'casemarks-'.$id.'-'.uniqid().'.pdf';;
            $pathToFile =  storage_path().'/app/casemarks/'.$filename;
            $data = QueryRegularFixedShippingInstruction::printCasemarks($request,$id,$pathToFile,$filename);
            return ResponseInterface::responseViewFile($pathToFile,$filename);
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

    public function shippingInstructionActual(Request $request,$id)
    {
        try {
            $filename = 'shipping-instruction-'.$id.'.pdf';
            $pathToFile =  storage_path().'/app/shipping_instruction/'.$filename;
            $data = QueryRegularFixedShippingInstruction::printShippingActual($request,$id,$filename,$pathToFile);
            return ResponseInterface::responseViewFile($pathToFile,$filename);
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

}
