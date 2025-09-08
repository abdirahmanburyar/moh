<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Inertia\Inertia;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
        ]);

        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->renderable(function (\Illuminate\Http\Exceptions\HttpExceptionInterface $e, $request) {
            if ($e->getStatusCode() === 403 && $request->is('login')) {
                return Inertia::render('Auth/Login', [
                    'error' => $e->getMessage(),
                    'canResetPassword' => Route::has('password.request'),
                    'status' => session('status'),
                ])->toResponse($request)->setStatusCode(403);
            }
        });

        // Handle authorization exceptions (403 Forbidden)
        $exceptions->renderable(function (\Illuminate\Auth\Access\AuthorizationException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'Access Denied',
                    'message' => $e->getMessage(),
                    'required_permission' => $e->getMessage(),
                ], 403);
            }

            // For web requests, render the permission denied page directly
            return Inertia::render('Errors/PermissionDenied', [
                'permission' => $e->getMessage()
            ])->toResponse($request)->setStatusCode(403);
        });
    })->create();
