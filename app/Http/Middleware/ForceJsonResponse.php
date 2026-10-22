<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Makes every API request an explicitly JSON one.
 *
 * Laravel decides how to render an error from $request->expectsJson(). A
 * client that omits `Accept: application/json` — curl, a webhook, a mobile
 * client, a browser address bar — therefore gets HTML back, and an
 * unauthenticated call gets something worse: Authenticate throws with a
 * redirect target, the handler reaches for route('login'), and because this
 * application has no such route the 401 surfaces as
 * "Route [login] not defined" with status 500.
 *
 * Setting the header on the way in fixes that for authentication, validation,
 * model-not-found and 404s in one place, and does so without relying on the
 * exception handler's shouldRenderJsonWhen hook — which a decorating handler
 * (Collision, installed in dev) bypasses entirely.
 *
 * Applied to the api group only, so routes/web.php keeps its HTML pages.
 */
class ForceJsonResponse
{
    public function handle(Request $request, Closure $next): Response
    {
        $request->headers->set('Accept', 'application/json');

        return $next($request);
    }
}
