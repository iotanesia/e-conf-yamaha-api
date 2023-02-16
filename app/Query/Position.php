<?php

namespace App\Query;

use App\Constants\Constant;
use App\Models\Position AS Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class Position {
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
                    'last_page' => $data->lastPage(),
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
