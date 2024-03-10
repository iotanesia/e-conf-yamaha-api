<?php

namespace App\Http\Controllers\Api\Iregular;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\ApiHelper as ResponseInterface;
use App\Query\QueryIregularDeliveryPlan;
use Illuminate\Support\Facades\Storage;
use ZipArchive;


class DeliveryPlanController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        try {
            return ResponseInterface::responseData(
                QueryIregularDeliveryPlan::getAll($request)
            );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

    public function storeFormCc(Request $request, $id)
    {
        try {
            return ResponseInterface::responseData(
                QueryIregularDeliveryPlan::storeFormCc($request, $id)
            );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

    public function form(Request $request)
    {
        try {
            return ResponseInterface::responseData(
                QueryIregularDeliveryPlan::getForm($request)
            );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

    public function formData(Request $request, $id)
    {
        try {
            return ResponseInterface::responseData(
                QueryIregularDeliveryPlan::getFormData($request, $id)
            );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

    public function getDoc(Request $request, $id)
    {
        try {
            return ResponseInterface::responseData(
                QueryIregularDeliveryPlan::getDoc($request, $id)
            );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

    public function sendToInputInvoice(Request $request)
    {
        try {
            return ResponseInterface::responseData(
                QueryIregularDeliveryPlan::sendApproval($request, 5)
            );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

    public function rejectByCcOfficer(Request $request)
    {
        try {
            return ResponseInterface::responseData(
                QueryIregularDeliveryPlan::sendApproval($request, 99, "Reject CC Officer")
            );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

    public function storeInvoice(Request $request, $id_iregular_order_entry)
    {
        try {
            return ResponseInterface::responseData(
                QueryIregularDeliveryPlan::storeInvoice($request, $id_iregular_order_entry)
            );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

    public function getInvoice(Request $request, $id_iregular_order_entry)
    {
        try {
            return ResponseInterface::responseData(
                QueryIregularDeliveryPlan::getInvoice($request, $id_iregular_order_entry)
            );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

    public function downloadFiles(Request $request, $id_iregular_order_entry)
    {
        try {
            $filePaths = QueryIregularDeliveryPlan::downloadFiles($request, $id_iregular_order_entry); 
            $zipFileName = Storage::path('temp/files.zip');
            print_r($zipFileName);
            $zip = new ZipArchive();
            $zip->open($zipFileName, ZipArchive::CREATE | ZipArchive::OVERWRITE);

            foreach ($filePaths as $filePath) {
                if (Storage::exists($filePath)) {
                    $zip->addFile($filePath, basename($filePath));
                }
            }

            $zip->close();

            return response()->download($zipFileName)->deleteFileAfterSend(true);
           
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

    public function getInvoiceDetail(Request $request, $id_iregular_order_entry)
    {
        try {
            return ResponseInterface::responseData(
                QueryIregularDeliveryPlan::getInvoiceDetail($request, $id_iregular_order_entry)
            );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

    public function shippingInstructionList(Request $request)
    {
        try {
            return ResponseInterface::responseData(
                QueryIregularDeliveryPlan::getShippingInstructionList($request)
            );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

    public function shippingInstruction(Request $request, $id)
    {
        try {
            return ResponseInterface::responseData(
                QueryIregularDeliveryPlan::getShippingInstruction($request, $id)
            );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

    public function shippingInstructionDraftList(Request $request, $id)
    {
        try {
            return ResponseInterface::responseData(
                QueryIregularDeliveryPlan::getShippingInstructionDraftList($request, $id)
            );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

    public function shippingInstructionDraft(Request $request, $id)
    {
        try {
            return ResponseInterface::responseData(
                QueryIregularDeliveryPlan::getShippingInstructionDraft($request, $id)
            );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

    public function shippingInstructionStore(Request $request)
    {
        try {
            return ResponseInterface::responseData(
                QueryIregularDeliveryPlan::shippingInstructionStore($request)
            );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }


    public function getPackingList(Request $request, $id_iregular_order_entry)
    {
        try {
            return ResponseInterface::responseData(
                QueryIregularDeliveryPlan::getPackingList($request, $id_iregular_order_entry)
            );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

    public function getPackingListDetail(Request $request, $id_iregular_order_entry)
    {
        try {
            return ResponseInterface::responseData(
                QueryIregularDeliveryPlan::getPackingListDetail($request, $id_iregular_order_entry)
            );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

    public function storePackingList(Request $request, $id_iregular_order_entry)
    {
        try {
            return ResponseInterface::responseData(
                QueryIregularDeliveryPlan::storePackingList($request, $id_iregular_order_entry)
            );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

    public function getCaseMark(Request $request, $id_iregular_order_entry)
    {
        try {
            return ResponseInterface::responseData(
                QueryIregularDeliveryPlan::getCaseMark($request, $id_iregular_order_entry)
            );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

    public function storeCaseMark(Request $request, $id_iregular_order_entry)
    {
        try {
            return ResponseInterface::responseData(
                QueryIregularDeliveryPlan::storeCaseMark($request, $id_iregular_order_entry)
            );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }
}
