<?php

namespace App\Services;
use App\Models\User as Model;
use App\ApiHelper as Helper;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Constants\Group;

class User {

    public static function authenticateuser($params)
    {
        $required_params = [];
        if (!$params->username) $required_params[] = 'username';
        if (!$params->password) $required_params[] = 'password';
        if (count($required_params)) throw new \Exception("Parameter berikut harus diisi: " . implode(", ", $required_params));

        $user = Model::where('username',$params->username)->first();
        if(!$user) throw new \Exception("Pengguna belum terdaftar.");
        if (!Hash::check($params->password, $user->password)) throw new \Exception("Email atau password salah.");
        $user->access_token = Helper::createJwt($user);
        $user->expires_in = Helper::decodeJwt($user->access_token)->exp;
        $menu = $user->refUserRole->manyPermission ?? [];
        if($menu) {
            $menu->transform(function ($item){
                $subMenu = $item->refMenu->manyChild ?? null;
                if($subMenu) {
                    $subMenu->transform(function ($item){
                        return [
                            'icon'=>'Home',
                            'title'=>$item->name ?? null,
                            'pathname'=>$item->url ?? null
                        ];
                    });
                }
                return [
                    'icon'=>'Home',
                    'title'=>$item->refMenu->name  ?? null,
                    'subMenu'=> $subMenu,
                    'pathname'=>$item->refMenu->url  ?? null

                ];
            });
        }
        $user->menu = $menu;
        unset($user->ip_whitelist);
        unset($user->refUserRole);
        return [
            'items' => $user,
            'attributes' => null
        ];
    }

    public static function getAllData($params)
    {
        $data = Model::where(function ($query) use ($params){
            if($params->search) $query->where('username','ilike',"%{$params->search}%")
            ->orWhere('email','ilike',"%{$params->search}%")
            ->orWhere('ip_whitelist','ilike',"%{$params->search}%");
        })->paginate($params->limit ?? null);
        return [
            'items' => $data->map(function ($item){
                return [
                    'id' => $item->id,
                    'email' => $item->email,
                    'password' => $item->password,
                    'nik' => $item->nik,
                    'username' => $item->username,
                    'name' => $item->name,
                    'remember_token' => $item->remember_token,
                    'is_active' => $item->is_active,
                    'created_by' => $item->created_by,
                    'created_at' => $item->created_at,
                    'updated_by' => $item->updated_by,
                    'updated_at' => $item->updated_at,
                    'id_roles' => $item->refUserRole->id_roles ?? null,
                    'roles' => $item->refUserRole->refRole->name ?? null,
                    'id_position' => $item->refUserRole->id_position ?? null,
                    'position' => $item->refUserRole->refPosition->name ?? null,
                ];
            }),
            'attributes' => [
                'total' => $data->total(),
                'current_page' => $data->currentPage(),
                'from' => $data->currentPage(),
                'per_page' => $data->perPage(),
           ]
        ];
    }

    public static function admin($id)
    {
        return [
            'items' => Model::where('group_id',Group::ADMIN)->find($id),
            'attributes' => null
        ];
    }

    public static function byId($id)
    {
        $data = Model::find($id);
        $data->id_roles = $data->refUserRole->id_roles ?? null;
        $data->roles = $data->refUserRole->refRole->name ?? null;
        $data->id_position = $data->refUserRole->id_position ?? null;
        $data->position = $data->refUserRole->refPosition->name ?? null;
        unset($data->refUserRole);

        return [
            'items' => $data,
            'attributes' => null
        ];
    }

    public static function saveData($params)
    {
        DB::beginTransaction();
        try {

             // * validator ---- /
            Validator::extend('valid_username', function($attr, $value){
                return preg_match('/^\S*$/u', $value);
            });

            $validator = Validator::make($params->all(), [
            'username' => 'required|valid_username|min:4|unique:users,username'
            ],['valid_username' => 'please enter valid username.']);

            if (!$validator) throw new \Exception("Wrong Parameter.");

            // * end validator ----- /

            $keys = Model::where('email',$params->email)->first();
            if($keys) throw new \Exception("Email available.");
            $keys_username = Model::where('username',$params->username)->first();
            if($keys_username) throw new \Exception("Username available.");

            $insert = new Model;
            $insert->username = $params->username;
            $insert->name = $params->name;
            $insert->email = $params->email;
            $insert->is_active = $params->is_active;
            $insert->nik = $params->nik;
            $insert->password = Hash::make($params->password);
            $insert->save();

            $insert->refUserRole()->create(self::setParamUserRole($params,$insert->id));

            DB::commit();
            return [
                'items' => $insert,
                'attributes' => null
            ];
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }

    public static function updateData($params,$id)
    {
        DB::beginTransaction();
        try {
            $update = Model::find($id);
            if(!$update) throw new \Exception("id tidak ditemukan.");
            $update->fill($params->all());
            $update->save();
            $update->refUserRole ? $update->refUserRole()->update(['id_roles'=>$params->id_roles,'id_position'=>$params->id_position]) : $update->refUserRole()->create(self::setParamUserRole($params,$id));
            DB::commit();
            return [
                'items' => $update,
                'attributes' => null
            ];
        } catch (\Throwable $th) {
            DB::rollback();
            throw $th;
        }
    }

    public static function setParamUserRole($params,$id) {
        return [
            'id_users' => $id,
            'id_roles' => $params->id_roles,
            'id_position' => $params->id_position
        ];
    }
    public static function deleteData($id)
    {
        DB::beginTransaction();
        try {
            $delete = Model::destroy($id);
            DB::commit();
            return [
                'items' => $delete,
                'attributes' => null
            ];
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }
}
