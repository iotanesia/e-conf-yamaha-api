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
            return ResponseInterface::responseData(
                QueryRegularShippingInstruction::downloadDoc($request,$id)
            );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

}
