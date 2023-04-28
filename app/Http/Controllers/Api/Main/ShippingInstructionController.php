<?php

namespace App\Http\Controllers\APi\Main;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\ApiHelper as ResponseInterface;
use App\Query\QueryRegularShippingInstruction;

class ShippingInstructionController extends Controller
{
    public function downloadDoc(Request $request,$id)
    {
        try {
            $filename = 'booking_doc-'.$id.'.pdf';
            $pathToFile =  storage_path().'/app/booking-doc/'.$filename;
            $data = QueryRegularShippingInstruction::downloadDoc($request,$id,$filename,$pathToFile);
            return ResponseInterface::responseViewFile($pathToFile,$filename);
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

}
