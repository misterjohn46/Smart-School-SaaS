<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Enums\RoleName;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): \Symfony\Component\HttpFoundation\Response
    {
        $request->authenticate();

        $request->session()->regenerate();

        $user = $request->user();

        // Bersihkan url.intended jika mengarah ke /central namun user bukan super_admin
        // agar tidak memicu 403 Unauthorized setelah login.
        $intended = $request->session()->get('url.intended');
        if ($intended && str_contains($intended, '/central') && ! ($user && $user->hasRole(RoleName::SuperAdmin->value))) {
            $request->session()->forget('url.intended');
        }

        // 1. Super Admin Platform -> Panel Manajemen SaaS Central di Vue 3 SPA
        if ($user && $user->hasRole(RoleName::SuperAdmin->value)) {
            return redirect()->intended(route('central.tenants.index', absolute: false));
        }

        // 2. Siswa -> Portal Siswa Mobile-First
        if ($user && $user->hasRole(RoleName::Student->value)) {
            return redirect()->intended(route('portal.student', absolute: false));
        }

        // 3. Orang Tua / Wali -> Portal Orang Tua
        if ($user && $user->hasRole(RoleName::Parent->value)) {
            return redirect()->intended(route('portal.parent', absolute: false));
        }

        // 4. Admin Sekolah, Kepala Sekolah, Guru, Bendahara, Staf TU -> Vue 3 SPA Command Center
        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
