<?php

namespace App\Http\Controllers\Api\Main;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\ApiHelper as ResponseInterface;
use App\Query\QueryRegularProspectContainer;

class ProspectContainerController extends Controller
{
    public function index(Request $request)
    {
        try {
            return ResponseInterface::responseData(
                QueryRegularProspectContainer::getAll($request)
            );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }
}
