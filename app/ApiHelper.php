<?php

namespace App;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use App\Constants\ErrorCode as EC;
use App\Constants\ErrorMessage as EM;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
class ApiHelper {

     static function storageCache($key, $callback, $ttl = null )
    {
        if (Cache::has($key)) {
            return Cache::get($key);
        }

        $result = call_user_func($callback);
        $time = $ttl ?? Carbon::now()->addMinutes(1);
        // save cache
        Cache::put($key, $result, $time);

        return $result;
    }

    static function resultResponse($data,$statusCode = 200,$eksternalId = null){
        $headers = [
            'Access-Control-Allow-Origin'      => '*',
            'Access-Control-Allow-Methods'     => 'GET, POST, PUT, PATCH, DELETE',
            'Access-Control-Allow-Credentials' => 'true',
            'Access-Control-Max-Age'           => '86400',
            'Access-Control-Allow-Headers'     => 'X-TIMESTAMP,X-CLIENT-KEY,X-CLIENT-SECRET,Content-Type,X-SIGNATURE,Accept,Authorization,Authorization-Customer,ORIGIN,X-PARTNER-ID,X-EXTERNAL-ID,X-IP-ADDRESS,X-DEVICE-ID,CHANNEL-ID,X-LATITUDE,X-LONGITUDE',
            'originalExternalId' => self::setEksternalId()
        ];
        return response()->json($data, $statusCode,$headers);
    }

     static function setEksternalId()
    {
        return rand(0,999999999);
    }

    static function createErrorResponse($data,$statusCode = 400){
        $headers = [
            'Access-Control-Allow-Origin'      => '*',
            'Access-Control-Allow-Methods'     => 'HEAD, POST, GET, OPTIONS, PUT, DELETE',
            'Access-Control-Allow-Credentials' => 'true',
            'Access-Control-Max-Age'           => '86400',
            'Access-Control-Allow-Headers'     => 'X-TIMESTAMP,X-CLIENT-KEY,X-CLIENT-SECRET,Content-Type,X-SIGNATURE,Accept,Authorization,Authorization-Customer,ORIGIN,X-PARTNER-ID,X-EXTERNAL-ID,X-IP-ADDRESS,X-DEVICE-ID,CHANNEL-ID,X-LATITUDE,X-LONGITUDE'

        ];
        $codeSt = $statusCode == 0 ? 500 : $statusCode;
        $code = 500;
        return response()->json([
            "responseCode" => $code ?? $codeSt,
            "responseMessage" => $data
        ],$codeSt,$headers);
    }

    static function setErrorResponse($th){
        $headers = [
            'Access-Control-Allow-Origin'      => '*',
            'Access-Control-Allow-Methods'     => 'HEAD, POST, GET, OPTIONS, PUT, DELETE',
            'Access-Control-Allow-Credentials' => 'true',
            'Access-Control-Max-Age'           => '86400',
            'Access-Control-Allow-Headers'     => 'X-TIMESTAMP,X-CLIENT-KEY,X-CLIENT-SECRET,Content-Type,X-SIGNATURE,Accept,Authorization,Authorization-Customer,ORIGIN,X-PARTNER-ID,X-EXTERNAL-ID,X-IP-ADDRESS,X-DEVICE-ID,CHANNEL-ID,X-LATITUDE,X-LONGITUDE'

        ];

        $codeSt = $th->getCode() == 0 ? 500 : $th->getCode();
        $result = json_decode($th->getMessage());
        if($codeSt == 500) $result = [
            "responseCode" => $result->responseCode ?? $codeSt,
            "responseMessage" => self::getMessageForPatner($th->getMessage()),
            // "infoError" => $th
        ];
        return response()->json($result,$codeSt,$headers);
    }

    static function responseData($data = false){
        $response = [
            "meta" => [
                "error" => EC::NOTHING,
                "message" => EM::NONE,
                "page" => $data['attributes'] ?? null
            ],
            "data" => $data['items'] ?? null
        ];
        return response()->json($response, 200);
    }

    static function createResponse($EC, $EM, $data = false) {
        if (!$data && [] !== $data) $data = json_decode("{}");

        $data = [
            "meta" => ['error' => $EC, 'message' => $EM ],
            "data" => $data
        ];

        if ($EC > 0 || is_string($EC)) unset($data['data']);
        return response()->json($data, 200);
    }

    static function errorResponse($EC, $EM, $data = false) {
        if (!$data && [] !== $data) $data = json_decode("{}");

        $data = [
            "meta" => ['error' => $EC, 'message' => $EM ],
            "data" => $data
        ];

        if ($EC > 0 || is_string($EC)) unset($data['data']);
        return response()->json($data, 500);
    }

    static function unauthorizedResponse($EC, $EM, $data = false) {
        if (!$data && [] !== $data) $data = json_decode("{}");

        $data = [
            "meta" => ['error' => $EC, 'message' => $EM ],
            "data" => $data
        ];

        if ($EC > 0 || is_string($EC)) unset($data['data']);
        return response()->json($data, 401);
    }

    static function responseDownload($pathToFile,$filename)
    {
         $headers =['Access-Control-Allow-Origin'      => '*',
                    'Access-Control-Allow-Methods'     => 'POST, GET, OPTIONS, PUT, DELETE',
                    'Access-Control-Allow-Credentials' => 'true',
                    'Access-Control-Max-Age'           => '86400',
                    'Access-Control-Allow-Headers'     => 'Content-Type, Accept, Authorization, X-Requested-With, Application, Origin, Authorization, APIKey, Timestamp, AccessToken',
                    'Content-Disposition' => 'attachment',
                    'Pragma' => 'public',
                    'Content-Transfer-Encoding' => 'binary',
                    'Content-Type' =>   self::getContentType($pathToFile),
                    'Content-Length' => filesize($pathToFile)];

        return response()->download($pathToFile,$filename, $headers);
    }

    static function createJwt($data = NULL, $is_refresh_token = FALSE) {
        $issued_at = time();
        $payload = [
            'iss' => "sicana-2022", // Issuer of the token
            'sub' => $data, // Subject of the token
            'iat' => $issued_at, // Time when JWT was issued.
            'exp' => $is_refresh_token
                ?($issued_at + 60*60*24*30) // Waktu kadaluarsa 30 hari
                :($issued_at + 60*60*4) // Waktu kadaluarsa 1 jam
        ];

        JWT::$leeway = 60; // $leeway dalam detik
        // dd(env('JWT_SECRET'));
        return JWT::encode($payload, 'LINK_AJA','HS256');
    }

    static function createJwtSignature($data = NULL, $is_refresh_token = FALSE) {
        $issued_at = time();
        $payload = [
            'jti' => Str::uuid(),
            'iss' => "jwt-iuser", // Issuer of the token
            'sub' => "link-aja", // Subject of the token
            'aud' => [
                'https://www.linkaja.id',
                'https://www.linkaja.id/mitra',
            ],
            'clientid' => null,
            'iat' => $issued_at, // Time when JWT was issued.
            'exp' => $issued_at + 60
        ];

        JWT::$leeway = 60; // $leeway dalam detik
        // dd(env('JWT_SECRET'));
        return JWT::encode($payload, (string) $data['signature'],'HS256');
    }

    static function decodeJwtSignature($token,$secret) {
        try {
            return JWT::decode($token,new Key($secret, 'HS256'));
        } catch(\Throwable $e) {
            throw $e;
        }
    }

    static function base64url_encode($str)
    {
        return rtrim(strtr(base64_encode($str), '+/', '-_'), '=');
    }

    static function createVerificationToken($data = NULL) {
        $issued_at = time();
        $expired_at = $issued_at + 60*60*24; // Waktu kadaluarsa 1 hari
        $payload = [
            'iss' => "sicana-2022", // Issuer of the token
            'sub' => $data, // Subject of the token
            'iat' => $issued_at, // Time when JWT was issued.
            'exp' => $expired_at
        ];

        JWT::$leeway = 60; // $leeway dalam detik
        return [JWT::encode($payload, env('JWT_SECRET'),'HS256'), date("Y-m-d H:i:s", $expired_at)];
    }

    static function createResetPasswordToken($data = NULL) {
        $issued_at = time();
        $payload = [
            'iss' => "sicana-2022", // Issuer of the token
            'sub' => $data, // Subject of the token
            'iat' => $issued_at, // Time when JWT was issued.
            'exp' => $issued_at + 60*60*24 // Waktu kadaluarsa 1 hari
        ];

        JWT::$leeway = 60; // $leeway dalam detik
        return JWT::encode($payload, env('JWT_SECRET'),'HS256');
    }

    static function getUserJwt($request, $is_refresh_token = FALSE) {
        $token = $is_refresh_token? $request->refresh_token: $request->header('accesstoken');
        $decoded_data = JWT::decode($token,new Key(env('JWT_SECRET'), 'HS256'));
        return $decoded_data->sub;
    }

    static function getUserId($request) {
        $decoded_data = JWT::decode($request->header('accesstoken'), env('JWT_SECRET'), ['HS256']);
        return $decoded_data->sub;
    }

    static function getJwtData($token) {
        try {
            $decoded_data = JWT::decode($token,new Key(env('JWT_SECRET'), 'HS256'));
            return $decoded_data->sub;
        } catch(ExpiredException $e) {
            throw new \Exception("expired");
        } catch(\Throwable $e) {
            throw new \Exception("failed");
        }

    }

    static function decodeJwt($token) {
        try {
            return JWT::decode($token,new Key(env('JWT_SECRET'), 'HS256'));
        } catch(\Throwable $e) {
            throw $e;
        }

    }

    static function getContentType($fileName){
        $path_parts = pathinfo($fileName);
        $ext = strtolower($path_parts["extension"]);
        $mime = [
            'doc' => 'application/msword',
            'dot' => 'application/msword',
            'sfdt' => 'application/json',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'dotx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.template',
            'docm' => 'application/vnd.ms-word.document.macroEnabled.12',
            'dotm' => 'application/vnd.ms-word.template.macroEnabled.12',
            'xls' => 'application/vnd.ms-excel',
            'xlt' => 'application/vnd.ms-excel',
            'xla' => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'xltx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.template',
            'xlsm' => 'application/vnd.ms-excel.sheet.macroEnabled.12',
            'xltm' => 'application/vnd.ms-excel.template.macroEnabled.12',
            'xlam' => 'application/vnd.ms-excel.addin.macroEnabled.12',
            'xlsb' => 'application/vnd.ms-excel.sheet.binary.macroEnabled.12',
            'ppt' => 'application/vnd.ms-powerpoint',
            'pot' => 'application/vnd.ms-powerpoint',
            'pps' => 'application/vnd.ms-powerpoint',
            'ppa' => 'application/vnd.ms-powerpoint',
            'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'potx' => 'application/vnd.openxmlformats-officedocument.presentationml.template',
            'ppsx' => 'application/vnd.openxmlformats-officedocument.presentationml.slideshow',
            'ppam' => 'application/vnd.ms-powerpoint.addin.macroEnabled.12',
            'pptm' => 'application/vnd.ms-powerpoint.presentation.macroEnabled.12',
            'potm' => 'application/vnd.ms-powerpoint.presentation.macroEnabled.12',
            'ppsm' => 'application/vnd.ms-powerpoint.slideshow.macroEnabled.12',
            'pdf' => 'application/pdf',
            'png' => 'image/png',
            'jpeg' => 'image/jpeg',
            'jpg' => 'image/jpg'
        ];

        return isset($mime[$ext]) ? $mime[$ext] : 'application/octet-stream';
    }

    static function strToHex($string){
        $hex = '';
        for ($i=0; $i<strlen($string); $i++){
            $ord = ord($string[$i]);
            // $hexCode = dechex($ord);
            // $hex .= substr('0'.$hexCode, -2);
            $hex .= sprintf('%02x', $ord);
        }
        return strtolower($hex);
    }

    static function getDateNow() {
        return substr(Carbon::now()->format('Y-m-d\TH:i:s.u'),0,23).'+07:00';
    }

    static function getMessageForPatner($data)
    {

        $message = json_decode($data,true);
        return isset($message['responseMessage']) ? $message['responseMessage'] : $data;
    }

    static function getUserIP(){
        // Get real visitor IP behind CloudFlare network
        if (isset($_SERVER["HTTP_CF_CONNECTING_IP"])) {
                $_SERVER['REMOTE_ADDR'] = $_SERVER["HTTP_CF_CONNECTING_IP"];
                $_SERVER['HTTP_CLIENT_IP'] = $_SERVER["HTTP_CF_CONNECTING_IP"];
        }
        $client  = @$_SERVER['HTTP_CLIENT_IP'];
        $forward = @$_SERVER['HTTP_X_FORWARDED_FOR'];
        $remote  = $_SERVER['REMOTE_ADDR'];

        if(filter_var($client, FILTER_VALIDATE_IP))
        {
            $ip = $client;
        }
        elseif(filter_var($forward, FILTER_VALIDATE_IP))
        {
            $ip = $forward;
        }
        else
        {
            $ip = $remote;
        }

        return $ip;
    }

}
