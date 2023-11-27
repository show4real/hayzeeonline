<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class Cors
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    // public function handle(Request $request, Closure $next)
    // {
    //     return $next($request);
    // }

    public function handle($request, Closure $next) {
        return $next($request)
        ->header('Access-Control-Allow-Origin', "*")
        ->header('Access-Controll-Allow-Methods', "GET, POST")
        ->header('Access-Controll-Allow-Headers', "Accept,Authorization,Content-Type");
    }
}
