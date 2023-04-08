<?php

namespace App\Http\Controllers\Api\Main;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\ApiHelper as ResponseInterface;
use App\Exports\BookingExport;
use App\Query\QueryRegularProspectContainer;
use App\Query\QueryRegulerDeliveryPlanProspectContainer;
use Maatwebsite\Excel\Facades\Excel;

class ProspectContainerController extends Controller
{
    public function index(Request $request)
    {
        try {
            return ResponseInterface::responseData(
                QueryRegulerDeliveryPlanProspectContainer::getAll($request)
            );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

    public function show(Request $request,$id)
    {
        try {
            return ResponseInterface::responseData(
                QueryRegulerDeliveryPlanProspectContainer::byIdProspectContainer($request,$id)
            );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

    public function booking(Request $request)
    {
        try {

           return Excel::download(new BookingExport, 'booking.xlsx');
            // return ResponseInterface::responseData(

            // );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

    public function fifo(Request $request)
    {
        try {
            return ResponseInterface::responseData(
                QueryRegulerDeliveryPlanProspectContainer::fifoProcess($request)
            );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

    public static function creation(Request $request)
    {
        try {

            return ResponseInterface::responseData(
                QueryRegulerDeliveryPlanProspectContainer::createionProcess($request)
            );

        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

    public static function simulation(Request $request)
    {
        try {

            return ResponseInterface::responseData(
                QueryRegulerDeliveryPlanProspectContainer::simulation($request)
            );

        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }


}
