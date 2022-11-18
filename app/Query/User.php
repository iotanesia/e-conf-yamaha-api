<?php

namespace App\Query;
use App\Models\User AS Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class User {
    public static function register($param) {
        $data = $param->all();
        $data['password'] = Hash::make($param->password);

        DB::beginTransaction();
        try {
            Model::create($data);
            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }

    }
}