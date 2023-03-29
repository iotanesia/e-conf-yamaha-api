<?php

namespace App\Query;
use App\Models\User AS Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\ApiHelper as Helper;
use App\Constants\Constant;
use App\Mail\ForgotPassword;
use Illuminate\Support\Facades\Mail;
use stdClass;

class User extends Model {

    public static function authenticateuser($params)
    {
        $required_params = [];
        if (!$params->username) {
            if(!$params->nik) $required_params[] = 'nik';
        }

        if (!$params->nik) {
            if(!$params->username) $required_params[] = 'username';
        }

        if (!$params->password) $required_params[] = 'password';
        if (count($required_params)) throw new \Exception("Parameter berikut harus diisi: " . implode(", ", $required_params));

        $user = Model::where(function ($query) use ($params)
        {
            $query->where('username',$params->username);
            $query->orWhere('nik',$params->nik);
        })
        ->with(['refUserRole' => function ($query){
            $query->with(['manyPermission' => function ($permission){
                $permission->with(['refMenu' => function ($menu){
                    $menu->whereNull('parent');
                }]);
            }]);
        }])
        ->whereHas('refUserRole',function ($query){
            $query->orderBy('is_active','desc');
        })
        ->first();
        if(!$user) throw new \Exception("Pengguna belum terdaftar.",200);
        if (!Hash::check($params->password, $user->password)) throw new \Exception("Email atau password salah.",200);
        $user->id_role = $user->refUserRole->id_roles;
        $user->role = $user->refUserRole->refRole->name ?? null;
        $user->position = $user->refUserRole->refPosition->name ?? null;
        $user->id_position = $user->refUserRole->id_position ?? null;
        $paramSetUSer = new stdClass;
        $paramSetUSer->id = $user->id;
        $paramSetUSer->id_position = $user->id_position;
        $paramSetUSer->position = $user->position;
        $paramSetUSer->role = $user->role;
        $paramSetUSer->id_role = $user->id_role;
        $paramSetUSer->email = $user->email;
        $paramSetUSer->name = $user->name;
        $paramSetUSer->username = $user->username;
        $paramSetUSer->nik = $user->nik;
        $user->access_token = Helper::createJwt($paramSetUSer);
        $user->expires_in = Helper::decodeJwt($user->access_token)->exp;
        $user->menu = QueryPermission::getParentMenu($user->id_role);
        unset($user->ip_whitelist);
        unset($user->refUserRole);
        return [
            'items' => $user,
            'attributes' => null
        ];
    }

    public static function register($param) {
        $data = $param->all();
        $data['password'] = Hash::make($param->password);

        DB::beginTransaction();
        try {
            $model = Model::create($data);
            $data['id_users'] = $model->id;
            $model->refUserRole()->create($data);
            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }

    public static function forgotPassword($request)
    {
        try {

            $user = self::where('email',$request->email)->first();
            if(!$user) throw new \Exception("email tidak ditemukan.", 400);

            list($token, $expired_at) = Helper::createVerificationToken([
                'email' => $request->email,
            ]);

            $mail_to = $request->email;
            $mail_data = [
                "token" => $token,
                "email" => $request->email,

            ];
            Mail::to($mail_to)->queue(new ForgotPassword($mail_data));

        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
