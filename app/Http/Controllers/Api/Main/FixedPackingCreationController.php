<?php

namespace App\Http\Controllers\Api\Main;

use App\Http\Controllers\Controller;
use App\Query\QueryRegularFixedQuantityConfirmation;
use Illuminate\Http\Request;
use App\ApiHelper as ResponseInterface;
use App\Query\QueryRegularFixedPackingCreation;

class FixedPackingCreationController extends Controller
{
    public function getData(Request $request)
    {
        try {
            return ResponseInterface::responseData(
                QueryRegularFixedPackingCreation::getAll($request)
            );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }
    public function detail(Request $request,$id)
    {
        try {
            return ResponseInterface::responseData(
                QueryRegularFixedPackingCreation::byId($id)
            );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }
    public function create(Request $request)
    {
        try {
            return ResponseInterface::responseData(
                QueryRegularFixedPackingCreation::store($request)
            );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }
    public function update(Request $request,$id)
    {
        try {
            return ResponseInterface::responseData(
                QueryRegularFixedPackingCreation::change($id,$request)
            );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }
    public function delete(Request $request,$id)
    {
        try {
            return ResponseInterface::responseData(
                QueryRegularFixedPackingCreation::deleted($id,$request)
            );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }
    public function packingCreationDeliveryNote(Request $request,$id)
    {
        try {
            return ResponseInterface::responseData(
                QueryRegularFixedPackingCreation::packingCreationDeliveryNote($id,$request)
            );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

    public function packingCreationDeliveryNotePart(Request $request,$id)
    {
        try {
            return ResponseInterface::responseData(
                QueryRegularFixedPackingCreation::packingCreationDeliveryNotePart($id,$request)
            );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

    public function packingCreationDeliveryNoteSave(Request $request)
    {
        try {
            return ResponseInterface::responseData(
                QueryRegularFixedPackingCreation::packingCreationDeliveryNoteSave($request)
            );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

    public function packingCreationDeliveryNotePrint(Request $request, $id)
    {
        try {
            $filename = 'packing_creation_delivery_note-'.$id.'.pdf';
            $pathToFile =  storage_path().'/app/packing-creation-delivery-note/'.$filename;
            $data = QueryRegularFixedPackingCreation::downloadpackingCreationDeliveryNote($id,$pathToFile,$filename);
            return ResponseInterface::responseViewFile($pathToFile,$filename);
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }
}
