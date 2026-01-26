<?php
// app/Http/Controllers/Auth/LoginController.php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    /**
     * Show the login form
     */
    public function showLoginForm()
    {
        return view('auth.login');
    }

    /**
     * Handle login request
     */
    public function login(Request $request)
    {
        // Validasi input
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        // Cek apakah "remember me" dicentang
        $remember = $request->filled('remember');

        // Attempt login
        if (Auth::attempt($credentials, $remember)) {
            // Regenerate session untuk keamanan
            $request->session()->regenerate();

            // Redirect berdasarkan role
            return $this->redirectBasedOnRole();
        }

        // Jika gagal, throw validation exception
        throw ValidationException::withMessages([
            'email' => 'Email atau password salah.',
        ]);
    }

    /**
     * Redirect user based on their role
     */
    protected function redirectBasedOnRole()
    {
        $user = Auth::user();

        return match($user->role) {
            'super_admin' => redirect()->intended(route('super-admin.dashboard'))
                ->with('success', 'Selamat datang, ' . $user->name),
            'admin' => redirect()->intended(route('admin.dashboard'))
                ->with('success', 'Selamat datang, ' . $user->name),
            'customer' => redirect()->intended(route('home'))
                ->with('success', 'Selamat datang kembali, ' . $user->name),
            default => redirect()->route('home'),
        };
    }

    /**
     * Logout user
     */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')
            ->with('success', 'Anda telah logout.');
    }
}