<?php

namespace App\Exceptions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Illuminate\Support\Str;
use App\ApiHelper as ResponseInterface;
use App\Constants\ErrorCode;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<Throwable>>
     */
    protected $dontReport = [
        AuthorizationException::class,
        HttpException::class,
        ModelNotFoundException::class,
        ValidationException::class,
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];


    public function error_code() { return strtoupper(Str::random(10)); }

    /**
     * Report or log an exception.
     *
     * This is a great spot to send exceptions to Sentry, Bugsnag, etc.
     *
     * @param  \Throwable  $exception
     * @return void
     *
     * @throws \Exception
     */
    public static function generateReport($exception,$code)
    {
        if ($exception){
            Log::error(
                'Error ID '.$code."\n".
                'Message : '.$exception->getMessage()."\n".
                'File : '.$exception->getFile()."\n".
                'Line : '.$exception->getLine()."\n".
                'Trace : '. "\n" . $exception->getTraceAsString()."\n"
            );
        }
    }

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function render($request, Throwable $exception)
    {
        if ($request->wantsJson()) {   //add Accept: application/json in request
            return $this->handleApiException($request, $exception);
        } else {
            $retval = parent::render($request, $exception);
        }

        return $retval;
    }

    private function handleApiException($request, \Exception $exception)
    {

        // dd($exception);
        $headers = [
            'Access-Control-Allow-Origin'      => '*',
            'Access-Control-Allow-Methods'     => 'HEAD, POST, GET, OPTIONS, PUT, DELETE',
            'Access-Control-Allow-Credentials' => 'true',
            'Access-Control-Max-Age'           => '86400',
            'Access-Control-Allow-Headers'     => 'X-Requested-With, Content-Type, Accept, Origin, Authorization, APIKey, Timestamp, AccessToken'
        ];

        $code = $this->error_code();
        $statusCode = $exception->getCode() ?? 500;
        self::generateReport($exception,$code);
        return ResponseInterface::createErrorResponse(
            $exception->getMessage()
            ,$statusCode,
            $headers
        );
    }

}
