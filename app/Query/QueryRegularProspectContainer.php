<?php

namespace App\Query;

use App\Constants\Constant;
use App\Models\RegularPropspectContainer AS Model;
use App\ApiHelper as Helper;

class QueryRegularProspectContainer extends Model {

    const cast = 'regular-prospect-container';

    public static function getAll($params)
    {
        $key = self::cast.json_encode($params->query());
        return Helper::storageCache($key, function () use ($params){
            $query = self::where(function ($query) use ($params){
               if($params->search)
                    $query->where('filename', 'like', "'%$params->search%'");
            });

            if($params->status) $query->where('status', "$params->status");
            if($params->dropdown == Constant::IS_ACTIVE) {
                $params->limit = null;
                $params->page = 1;
            }
            if($params->withTrashed == 'true') $query->withTrashed();

            $data = $query
            ->orderBy('id','desc')
            ->paginate($params->limit ?? null);
            return [
                'items' => $data->map(function ($item){
                    return $item;
                }),
                'attributes' => [
                    'total' => $data->total(),
                    'current_page' => $data->currentPage(),
                    'from' => $data->currentPage(),
                    'per_page' => (int) $data->perPage(),
                ]
            ];
        });
    }
}
