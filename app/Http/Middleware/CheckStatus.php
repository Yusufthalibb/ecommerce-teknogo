<?php
// app/Http/Middleware/CheckStatus.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckStatus
{
    /**
     * Handle an incoming request.
     *
     * Middleware ini mengecek apakah user masih aktif (is_active = true)
     * Jika tidak aktif, logout dan redirect ke login
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Cek apakah user login
        if (Auth::check()) {
            $user = Auth::user();
            
            // Jika user tidak aktif
            if (!$user->is_active) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                
                return redirect()->route('login')
                    ->with('error', 'Akun Anda telah dinonaktifkan. Hubungi administrator.');
            }
        }

        return $next($request);
    }
}