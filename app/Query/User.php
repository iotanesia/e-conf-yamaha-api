<?php

namespace App\Query;
use App\Models\User AS Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\ApiHelper as Helper;
use App\Mail\ForgotPassword;
use Illuminate\Support\Facades\Mail;

class User extends Model {

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
