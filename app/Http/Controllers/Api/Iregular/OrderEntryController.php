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
}
