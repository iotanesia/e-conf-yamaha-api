<?php

namespace App\Http\Controllers\Api\Main;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\ApiHelper as ResponseInterface;
use App\Exports\BookingExport;
use App\Query\QueryRegularProspectContainer;
use Maatwebsite\Excel\Facades\Excel;

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
                QueryRegularProspectContainer::fifoProcess($request)
            );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }
}
