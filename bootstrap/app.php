<?php

use App\Http\Middleware\EnsureUserCanManageMicroPearls;
use App\Http\Middleware\EnsureUserIsAdmin;
use App\Http\Middleware\EnsureUserIsFaculty;
use App\Http\Middleware\EnsureUserIsStudent;
use App\Http\Middleware\PreventAuthenticatedPageCaching;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(
            at: '*',
            headers: Request::HEADER_X_FORWARDED_FOR
                | Request::HEADER_X_FORWARDED_HOST
                | Request::HEADER_X_FORWARDED_PORT
                | Request::HEADER_X_FORWARDED_PROTO
                | Request::HEADER_X_FORWARDED_PREFIX,
        );

        $middleware->web(append: [
            PreventAuthenticatedPageCaching::class,
        ]);

        $middleware->alias([
            'admin' => EnsureUserIsAdmin::class,
            'faculty' => EnsureUserIsFaculty::class,
            'student' => EnsureUserIsStudent::class,
            'pearl-editor' => EnsureUserCanManageMicroPearls::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
