<?php

namespace App\Http\Controllers\Api\Iregular;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\ApiHelper as ResponseInterface;
use App\Query\QueryIregularPacking;
use Illuminate\Support\Facades\Storage;
use ZipArchive;


class PackingController extends Controller
{
    public function index(Request $request)
    {
        try {
            return ResponseInterface::responseData(
                QueryIregularPacking::getAll($request)
            );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

    public function getDeliveryNote(Request $request, $id)
    {
        try {
            return ResponseInterface::responseData(
                QueryIregularPacking::getDeliveryNote($request, $id)
            );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

    public function printDeliveryNote(Request $request, $id)
    {
        try {
            $filename = 'outstock_delivery_note-'.$id.'.pdf';
            $pathToFile =  storage_path().'/app/outstock-delivery-note/'.$filename;
            $data = QueryIregularPacking::printDeliveryNote($id,$pathToFile,$filename);
            return ResponseInterface::responseViewFile($pathToFile,$filename);
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }


    public function getDeliveryNoteDetail(Request $request, $id)
    {
        try {
            return ResponseInterface::responseData(
                QueryIregularPacking::getDeliveryNoteDetail($request, $id)
            );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

    public function updateDeliveryNote(Request $request, $id)
    {
        try {
            return ResponseInterface::responseData(
                QueryIregularPacking::updateDeliveryNote($request, $id)
            );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }



}
