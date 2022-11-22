<?php

namespace App\Query;

use App\Constants\Constant;
use App\Models\Role AS Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class Role {
    public static function getAll($request)
    {
        try {
            if($request->dropdown == Constant::IS_ACTIVE) $request->limit = Model::count();
            $data = Model::where(function ($query) use ($request){
                if($request->nama) $query->where('nama','ilike',"%$request->nama%");
            })->paginate($request->limit);
                return [
                    'items' => $data->getCollection()->transform(function ($item){
                        return $item;
                    }),
                    'attributes' => [
                        'total' => $data->total(),
                        'current_page' => $data->currentPage(),
                        'from' => $data->currentPage(),
                        'per_page' => (int) $data->perPage(),
                    ]
                ];
        } catch (\Throwable $th) {
            throw $th;
        }
    }
}