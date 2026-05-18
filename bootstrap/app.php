<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Middleware\HandleCors;
use Symfony\Component\HttpFoundation\Response;
return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $statefulApiEnabled = filter_var(env('SANCTUM_STATEFUL_API', false), FILTER_VALIDATE_BOOL);

        $middleware
            ->redirectGuestsTo(fn () => null)
            ->append(HandleCors::class);

        // The API primarily uses Bearer tokens. Keep Sanctum's cookie/session
        // middleware opt-in so parallel frontend requests do not pay the
        // overhead of stateful SPA authentication unless explicitly needed.
        if ($statefulApiEnabled) {
            $middleware->statefulApi();
        }
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (AuthenticationException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'message' => $e->getMessage(),
                ], Response::HTTP_UNAUTHORIZED);
            }
        });
    })->create();
