<?php

namespace App\Http\Controllers;

use App\Services\AuditService;
use App\Services\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(
        Request $request,
        AuditService $audits
    ) {
        $credentials = $request->validate([
            'email' => [
                'required',
                'email',
            ],

            'password' => [
                'required',
                'string',
            ],
        ]);

        $tenant = app(TenantContext::class)->get();

        $throttleKey = implode('|', [
            'login',
            $tenant->id,
            Str::lower($credentials['email']),
            $request->ip(),
        ]);

        if (
            RateLimiter::tooManyAttempts(
                $throttleKey,
                5
            )
        ) {
            throw ValidationException::withMessages([
                'email' => __('validation.auth.invalid_credentials'),
            ]);
        }

        if (!Auth::attempt([
            'email' => $credentials['email'],
            'password' => $credentials['password'],
            'tenant_id' => $tenant->id,
        ])) {
            RateLimiter::hit(
                $throttleKey,
                60
            );

            $audits->log(
                'login.failed',
                'Tentativa de login inválida para: '
                    . $credentials['email']
                    . '.'
            );

            throw ValidationException::withMessages([
                'email' => __('validation.auth.invalid_credentials'),
            ]);
        }

        RateLimiter::clear(
            $throttleKey
        );

        $request->session()->regenerate();

        $audits->log(
            'login.success',
            'Login realizado com sucesso.'
        );

        return redirect()->route('dashboard');
    }

    public function logout(
        Request $request,
        AuditService $audits
    ) {
        $audits->log(
            'logout',
            'Logout realizado com sucesso.'
        );

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }
}