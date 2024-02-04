<?php

namespace App\Http\Controllers\Api\Main;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\ApiHelper as ResponseInterface;
use App\Query\QueryRegularFixedQuantityConfirmation;

class FixedQuantityConfirmationController extends Controller
{
    public function getFixedQuantity(Request $request)
    {
        try {
            return ResponseInterface::responseData(
                QueryRegularFixedQuantityConfirmation::getFixedQuantity($request)
            );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

    public function noPackaging(Request $request)
    {
        try {
            return ResponseInterface::responseData(
                QueryRegularFixedQuantityConfirmation::noPackaging($request)
            );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

    public function inquiryProcess(Request $request)
    {
        try {
            return ResponseInterface::responseData(
                QueryRegularFixedQuantityConfirmation::inquiryProcess($request)
            );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

    public function update(Request $request)
    {
        try {
            return ResponseInterface::responseData(
                QueryRegularFixedQuantityConfirmation::changeEtd($request)
            );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

    public function getActualContainer(Request $request)
    {
        try {
            return ResponseInterface::responseData(
                QueryRegularFixedQuantityConfirmation::getActualContainer($request)
            );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

    public function simulation(Request $request)
    {
        try {
            return ResponseInterface::responseData(
                QueryRegularFixedQuantityConfirmation::simulation($request)
            );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

    public function creation(Request $request)
    {
        try {
            return ResponseInterface::responseData(
                QueryRegularFixedQuantityConfirmation::creationCalculation($request)
            );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

    public function editMot(Request $request)
    {
        try {
            return ResponseInterface::responseData(
                QueryRegularFixedQuantityConfirmation::updateProspectContainerCreation($request)
            );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

    public function creationDetail(Request $request)
    {
        try {
            return ResponseInterface::responseData(
                QueryRegularFixedQuantityConfirmation::creationDetail($request)
            );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

    public function getCreationMove(Request $request, $id)
    {
        try {
            return ResponseInterface::responseData(
                QueryRegularFixedQuantityConfirmation::getCreationMove($request, $id)
            );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

    public function getCreationMoveContainer(Request $request, $id)
    {
        try {
            return ResponseInterface::responseData(
                QueryRegularFixedQuantityConfirmation::getCreationMoveContainer($request, $id)
            );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

    public function creationMove(Request $request)
    {
        try {
            return ResponseInterface::responseData(
                QueryRegularFixedQuantityConfirmation::creationMove($request)
            );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

    public function creationDownloadDoc(Request $request)
    {
        try {
            $filename = 'simulation.pdf';
            $pathToFile =  storage_path().'/app/actual_container/'.$filename;
            $data = QueryRegularFixedQuantityConfirmation::creationDownloadDoc($request,$pathToFile,$filename);
            return ResponseInterface::responseViewFile($pathToFile,$filename);
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

    public function generateNobooking(Request $request)
    {
        try {
            return ResponseInterface::responseData(
                QueryRegularFixedQuantityConfirmation::generateNobooking($request)
            );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

    public function show(Request $request,$id)
    {
        try {
            return ResponseInterface::responseData(
                QueryRegularFixedQuantityConfirmation::byIdProspectContainer($request,$id)
            );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

    public function detailById(Request $request)
    {
        try {
            return ResponseInterface::responseData(
                QueryRegularFixedQuantityConfirmation::detailById($request)
            );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

    public function savebooking(Request $request)
    {
        try {
            return ResponseInterface::responseData(
                QueryRegularFixedQuantityConfirmation::savebooking($request)
            );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

    public function getCasemarks(Request $request)
    {
        try {
            return ResponseInterface::responseData(
                QueryRegularFixedQuantityConfirmation::getCasemarks($request)
            );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

    public function printCasemarks(Request $request, $id)
    {
        try {
            $filename = 'casemarks-'.$id.'.pdf';
            $pathToFile =  storage_path().'/app/casemarks/'.$filename;
            $data = QueryRegularFixedQuantityConfirmation::printCasemarks($request,$id,$pathToFile,$filename);
            return ResponseInterface::responseViewFile($pathToFile,$filename);
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

    public function printPackaging(Request $request, $id)
    {
        try {
            $filename = 'packaging-'.$id.'.pdf';
            $pathToFile =  storage_path().'/app/casemarks/'.$filename;
            $data = QueryRegularFixedQuantityConfirmation::printPackaging($request,$id,$pathToFile,$filename);
            return ResponseInterface::responseViewFile($pathToFile,$filename);
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

    public function exportCSV(Request $request, $id)
    {
        try {
            return QueryRegularFixedQuantityConfirmation::exportCSV($request, $id);
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

    public function exportPEB(Request $request, $id)
    {
        try {
            return QueryRegularFixedQuantityConfirmation::exportPEB($request, $id);
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

}
