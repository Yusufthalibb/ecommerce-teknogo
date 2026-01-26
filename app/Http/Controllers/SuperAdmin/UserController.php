<?php
// app/Http/Controllers/SuperAdmin/UserController.php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    /**
     * Display users
     */
    public function index(Request $request)
    {
        $query = User::query();

        // Filter by role
        if ($request->has('role') && $request->role) {
            $query->where('role', $request->role);
        }

        // Search
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->latest()->paginate(20);

        return view('super-admin.users.index', compact('users'));
    }

    /**
     * Show create form
     */
    public function create()
    {
        return view('super-admin.users.create');
    }

    /**
     * Store new user
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'role' => 'required|in:super_admin,admin,customer',
            'password' => ['required', 'confirmed', Password::min(8)],
            'is_active' => 'boolean',
        ]);

        $validated['password'] = Hash::make($validated['password']);

        User::create($validated);

        return redirect()->route('super-admin.users.index')
            ->with('success', 'User berhasil ditambahkan');
    }

    /**
     * Show edit form
     */
    public function edit(User $user)
    {
        return view('super-admin.users.edit', compact('user'));
    }

    /**
     * Update user
     */
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'role' => 'required|in:super_admin,admin,customer',
            'is_active' => 'boolean',
        ]);

        // Prevent changing own role
        if ($user->id === Auth::id() && $validated['role'] !== Auth::user()->role) {
            return back()->with('error', 'Anda tidak dapat mengubah role Anda sendiri');
        }

        $user->update($validated);

        return redirect()->route('super-admin.users.index')
            ->with('success', 'User berhasil diperbarui');
    }

    /**
     * Delete user
     */
    public function destroy(User $user)
    {
        // Prevent delete own account
        if ($user->id === Auth::id()) {
            return back()->with('error', 'Anda tidak dapat menghapus akun Anda sendiri');
        }

        // Check if user has orders
        if ($user->orders()->count() > 0) {
            return back()->with('error', 'User tidak dapat dihapus karena memiliki riwayat transaksi');
        }

        $user->delete();

        return redirect()->route('super-admin.users.index')
            ->with('success', 'User berhasil dihapus');
    }

    /**
     * Toggle user status
     */
    public function toggleStatus(User $user)
    {
        // Prevent deactivate own account
        if ($user->id === Auth::id()) {
            return back()->with('error', 'Anda tidak dapat menonaktifkan akun Anda sendiri');
        }

        $user->update([
            'is_active' => !$user->is_active
        ]);

        $status = $user->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return back()->with('success', "User berhasil {$status}");
    }

    /**
     * Change user role
     */
    public function changeRole(Request $request, User $user)
    {
        $validated = $request->validate([
            'role' => 'required|in:super_admin,admin,customer',
        ]);

        // Prevent changing own role
        if ($user->id === Auth::id()) {
            return back()->with('error', 'Anda tidak dapat mengubah role Anda sendiri');
        }

        $user->update(['role' => $validated['role']]);

        return back()->with('success', 'Role user berhasil diubah');
    }
}