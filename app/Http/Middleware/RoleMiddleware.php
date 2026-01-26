<?php
// bootstrap/app.php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // ==================== GLOBAL MIDDLEWARE ====================
        // Middleware yang jalan di semua request
        
        // Web middleware group sudah include:
        // - EncryptCookies
        // - AddQueuedCookiesToResponse
        // - StartSession
        // - ValidateCsrfToken
        // - ShareErrorsFromSession
        // - SubstituteBindings
        
        // ==================== MIDDLEWARE ALIAS ====================
        // Daftarkan alias untuk middleware custom
        $middleware->alias([
            'role' => \App\Http\Middleware\RoleMiddleware::class,
            'check.status' => \App\Http\Middleware\CheckStatus::class,
            'verified' => \App\Http\Middleware\EnsureEmailVerified::class,
        ]);

        // ==================== MIDDLEWARE GROUPS ====================
        // Tambah middleware ke group tertentu jika perlu
        $middleware->web(append: [
            \App\Http\Middleware\CheckStatus::class,
        ]);

        // ==================== MIDDLEWARE PRIORITY ====================
        // Atur urutan eksekusi middleware jika perlu
        $middleware->priority([
            \Illuminate\Foundation\Http\Middleware\HandlePrecognitiveRequests::class,
            \Illuminate\Cookie\Middleware\EncryptCookies::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \Illuminate\Contracts\Auth\Middleware\AuthenticatesRequests::class,
            \Illuminate\Routing\Middleware\ThrottleRequests::class,
            \Illuminate\Routing\Middleware\ThrottleRequestsWithRedis::class,
            \Illuminate\Session\Middleware\AuthenticateSession::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            \Illuminate\Auth\Middleware\Authorize::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();