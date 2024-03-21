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

    public function getSpv(Request $request)
    {
        try { 
            return ResponseInterface::responseData(
                QueryIregularDeliveryPlan::getAll($request, 8)
            );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

    public function getManager(Request $request)
    {
        try {
            return ResponseInterface::responseData(
                QueryIregularDeliveryPlan::getAll($request, 10)
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

    public function sentToCcSpv(Request $request)
    {
        try {
            return ResponseInterface::responseData(
                QueryIregularDeliveryPlan::sendApproval($request, 8)
            );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

    public function sentToCcManager(Request $request)
    {
        try {
            return ResponseInterface::responseData(
                QueryIregularDeliveryPlan::sendApproval($request, 10)
            );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

    public function approvedRequest(Request $request)
    {
        try {
            return ResponseInterface::responseData(
                QueryIregularDeliveryPlan::sendApproval($request, 12)
            );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

    public function approvedByCcSpv(Request $request)
    {
        try {
            return ResponseInterface::responseData(
                QueryIregularDeliveryPlan::sendApproval($request, 9)
            );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }


    public function approvedByCcManager(Request $request)
    {
        try {
            return ResponseInterface::responseData(
                QueryIregularDeliveryPlan::sendApproval($request, 11)
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

    
    public function getByIdIregularOrderEntry(Request $request, $id_iregular_order_entry)
    {
        try {
            return ResponseInterface::responseData(
                QueryIregularDeliveryPlan::getByIdIregularOrderEntry($request, $id_iregular_order_entry)
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
                QueryIregularDeliveryPlan::sendApproval($request, 97, "Reject CC Officer")
            );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

    public function rejectByCcSpv(Request $request)
    {
        try {
            return ResponseInterface::responseData(
                QueryIregularDeliveryPlan::sendApproval($request, 98, "Reject CC Supervisor")
            );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

    public function rejectByCcManager(Request $request)
    {
        try {
            return ResponseInterface::responseData(
                QueryIregularDeliveryPlan::sendApproval($request, 99, "Reject CC Manager")
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

    public function exportExcel(Request $request, $id_iregular_order_entry)
    {
        try {
            return QueryIregularDeliveryPlan::exportExcel($request, $id_iregular_order_entry);
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

    public function exportCSV(Request $request, $id_iregular_order_entry)
    {
        try {
            return QueryIregularDeliveryPlan::exportCSV($request, $id_iregular_order_entry);
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

    public function printInvoice(Request $request, $id_iregular_order_entry)
    {
        try {
            $filename = 'invoice-'.$id_iregular_order_entry.'.pdf';
            $pathToFile =  storage_path().'/app/invoice/iregular/'.$filename;
            $data = QueryIregularDeliveryPlan::printInvoice($request,$id_iregular_order_entry,$pathToFile,$filename);
            return ResponseInterface::responseViewFile($pathToFile,$filename);
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

    public function printPackingList(Request $request, $id_iregular_order_entry)
    {
        try {
            $filename = 'packing_list-'.$id_iregular_order_entry.'.pdf';
            $pathToFile =  storage_path().'/app/packing-list/iregular/'.$filename;
            $data = QueryIregularDeliveryPlan::printPackingList($request,$id_iregular_order_entry,$pathToFile,$filename);
            return ResponseInterface::responseViewFile($pathToFile,$filename);
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

    public function printCaseMark(Request $request, $id_iregular_order_entry)
    {
        try {
            $filename = 'casemarks-'.$id_iregular_order_entry.'.pdf';
            $pathToFile =  storage_path().'/app/casemarks/iregular/'.$filename;
            $data = QueryIregularDeliveryPlan::printCaseMark($request,$id_iregular_order_entry,$pathToFile,$filename);
            return ResponseInterface::responseViewFile($pathToFile,$filename);
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

    public function approveDocSpv(Request $request)
    {
        try {
            return ResponseInterface::responseData(
                QueryIregularDeliveryPlan::approveDoc($request, "spv")
            );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

    public function approveDocManager(Request $request)
    {
        try {
            return ResponseInterface::responseData(
                QueryIregularDeliveryPlan::approveDoc($request, "manager")
            );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

    public function rejectDocSpv(Request $request)
    {
        try {
            return ResponseInterface::responseData(
                QueryIregularDeliveryPlan::rejectDoc($request, "spv")
            );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

    public function rejectDocManager(Request $request)
    {
        try {
            return ResponseInterface::responseData(
                QueryIregularDeliveryPlan::rejectDoc($request, "manager")
            );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }
}
