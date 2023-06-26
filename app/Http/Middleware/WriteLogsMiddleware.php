<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\ApiHelper as Helper;
use Carbon\Carbon;
use Jenssegers\Agent\Facades\Agent;
use Stevebauman\Location\Facades\Location;

class WriteLogsMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $location = Location::get((string) Helper::getUserIP());
        Log::info([
            'timestamps' => Carbon::now()->format('Y-m-d H:i:s'),
            'access-log' => [
                'ip-address' => Helper::getUserIP(),
                'method' => $request->getMethod(),
                'url' => url()->current(),
                'request_body' => json_encode($request->all()),
                'device' => Agent::device(),
                'platform' => Agent::platform(),
                'browser' => Agent::browser(),
                'platform_version' => Agent::version(Agent::platform()),
                'browser_version' => Agent::version(Agent::browser()),
                'region' => $location->regionName ?? null,
                'city' => $location->cityName ?? null,
                'country' => $location->countryName ?? null,
            ]
        ]);
        return $next($request);
    }
}
