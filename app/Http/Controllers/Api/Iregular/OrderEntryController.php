<?php

namespace App\Http\Controllers\Api\Iregular;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\ApiHelper as ResponseInterface;
use App\Query\QueryIregularOrderEntry;

class OrderEntryController extends Controller
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
                QueryIregularOrderEntry::getAll($request)
            );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

    public function sendToEnquiry(Request $request)
    {
        try {
            return ResponseInterface::responseData(
                QueryIregularOrderEntry::sendApproval($request, 4)
            );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

    public function store(Request $request)
    {
        try {
            return ResponseInterface::responseData(
                QueryIregularOrderEntry::storeData($request)
            );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

    public function storeApprovalDoc(Request $request, $id)
    {
        try {
            return ResponseInterface::responseData(
                QueryIregularOrderEntry::storeApprovalDoc($request, $id)
            );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

    public function form(Request $request)
    {
        try {
            return ResponseInterface::responseData(
                QueryIregularOrderEntry::getForm($request)
            );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

    public function formData(Request $request, $id)
    {
        try {
            return ResponseInterface::responseData(
                QueryIregularOrderEntry::getFormData($request, $id)
            );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

    public function storePart(Request $request, $id)
    {
        try {
            return ResponseInterface::responseData(
                QueryIregularOrderEntry::storePart($request, $id)
            );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

    public function getDoc(Request $request, $id)
    {
        try {
            return ResponseInterface::responseData(
                QueryIregularOrderEntry::getDoc($request, $id)
            );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

    public function getFile(Request $request, $id_iregular_order_entry, $id_doc)
    {
        try {
            $result = QueryIregularOrderEntry::getFile($request, $id_iregular_order_entry, $id_doc); 
            return ResponseInterface::responseDownload(
                $result['path'],$result['filename']
            );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

    public function getApprovalFile(Request $request, $id_iregular_order_entry)
    {
        try {
            $result = QueryIregularOrderEntry::getApprovalFile($request, $id_iregular_order_entry); 
            return ResponseInterface::responseDownload(
                $result['path'],$result['filename']
            );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

    
    public function storeDoc(Request $request, $id)
    {
        try {
            return ResponseInterface::responseData(
                QueryIregularOrderEntry::storeDoc($request, $id)
            );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

    public function printFormRequest(Request $request,$id)
    {
        try {
            $filename = 'form_request-'.$id.'.pdf';
            $pathToFile =  storage_path().'/app/order-entry/iregular/form-request/'.$filename;
            $data = QueryIregularOrderEntry::printFormRequest($request,$id,$filename,$pathToFile);
            return ResponseInterface::responseViewFile($pathToFile,$filename);
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

    public function printFormRequestCc(Request $request,$id)
    {
        try {
            $filename = 'form_request_cc-'.$id.'.pdf';
            $pathToFile =  storage_path().'/app/order-entry/iregular/form-request/'.$filename;
            $data = QueryIregularOrderEntry::printFormRequest($request,$id,$filename,$pathToFile, "cc");
            return ResponseInterface::responseViewFile($pathToFile,$filename);
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }
}
