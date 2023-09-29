<?php

namespace App\Http\Controllers\Api\Master;

use App\Http\Controllers\Controller;
use App\Query\QueryCategoryFilter;
use Illuminate\Http\Request;
use App\ApiHelper as ResponseInterface;

class CategoryFilterController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function getProspectContainer()
    {
        try {
            return ResponseInterface::responseData(
                QueryCategoryFilter::getProspectContainer()
            );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

    public function getActualContainer()
    {
        try {
            return ResponseInterface::responseData(
                QueryCategoryFilter::getActualContainer()
            );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

    public function getShippingPlaning()
    {
        try {
            return ResponseInterface::responseData(
                QueryCategoryFilter::getShippingPlaning()
            );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

    public function getPart()
    {
        try {
            return ResponseInterface::responseData(
                QueryCategoryFilter::getPart()
            );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

    public function getInquiry()
    {
        try {
            return ResponseInterface::responseData(
                QueryCategoryFilter::getInquiry()
            );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }

    public function getTracking()
    {
        try {
            return ResponseInterface::responseData(
                QueryCategoryFilter::getTracking()
            );
        } catch (\Throwable $th) {
            return ResponseInterface::setErrorResponse($th);
        }
    }
}
