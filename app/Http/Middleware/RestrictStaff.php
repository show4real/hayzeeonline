<?php

namespace App\Http\Middleware;

use Closure;

class RestrictStaff
{
    /**
     * Block staff users (admin == 2) from accessing the guarded resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (auth()->user()->admin == 2) {
            return response()->json(['error' => 'You don\'t have sufficient permission to access this resource'], 403);
        }

        return $next($request);
    }
}
