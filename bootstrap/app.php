<?php

use App\Http\Middleware\ForceJsonResponse;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\HandleCors;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        // Laravel 11+ no longer loads routes/api.php on its own. The prefix is
        // stated here because every published URL already carries it.
        api: __DIR__.'/../routes/api.php',
        apiPrefix: 'api',
        commands: __DIR__.'/../routes/console.php',
        // Answered without touching the database, and polled by
        // docker/deploy.sh before a release is called healthy.
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // The storefront is a separate origin, so CORS has to be settled
        // before anything else can reject the request. config/cors.php holds
        // the policy; this replaces fruitcake/laravel-cors, which was folded
        // into the framework in Laravel 9.
        $middleware->prepend(HandleCors::class);

        // Behind Apache on the VPS. Without this Laravel reads the proxy's
        // address as the client's and builds http:// URLs on an https:// site.
        $middleware->trustProxies(at: '*');

        // Stated in full rather than appended to: Laravel 11+ dropped
        // throttling from the default api group, and this API has always had
        // it. The limiter `api` names is defined in AppServiceProvider.
        $middleware->group('api', [
            ForceJsonResponse::class,
            ThrottleRequests::class.':api',
            SubstituteBindings::class,
        ]);

        $middleware->alias([
            'CheckAdmin' => \App\Http\Middleware\CheckAdmin::class,
            'CheckReferrer' => \App\Http\Middleware\CheckReferrer::class,
            'RestrictStaff' => \App\Http\Middleware\RestrictStaff::class,
        ]);

        // Passwords must never reach the log or a flashed-input bag.
        $middleware->trimStrings(except: [
            'current_password',
            'password',
            'password_confirmation',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->dontFlash([
            'current_password',
            'password',
            'password_confirmation',
        ]);

        // A URL that matches no route never reaches the api group, so
        // ForceJsonResponse cannot have run and the 404 would render as an
        // HTML page. Decided on the path instead. Returning null for anything
        // else leaves routes/web.php with its normal error pages.
        $exceptions->render(function (HttpExceptionInterface $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return response()->json([
                'message' => $e->getMessage() ?: 'Not found.',
            ], $e->getStatusCode());
        });
    })->create();
