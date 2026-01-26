<?php
// app/Http/Middleware/EnsureEmailVerified.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Symfony\Component\HttpFoundation\Response;

class EnsureEmailVerified
{
    /**
     * Handle an incoming request.
     *
     * Middleware untuk memastikan email sudah diverifikasi
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->user() || !$request->user()->hasVerifiedEmail()) {
            return Redirect::route('verification.notice')
                ->with('error', 'Silakan verifikasi email Anda terlebih dahulu.');
        }

        return $next($request);
    }
}