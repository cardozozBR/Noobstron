<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class EmailVerificationController extends Controller
{
    public function verify(
        Request $request,
        int $id,
        string $hash,
    ): RedirectResponse {
        $user = User::query()
            ->withoutGlobalScopes()
            ->findOrFail($id);

        if (
            !hash_equals(
                (string) $hash,
                sha1(
                    $user->getEmailForVerification()
                )
            )
        ) {
            throw ValidationException::withMessages([
                'email' =>
                    'O link de verificação é inválido.',
            ]);
        }

        if (!$user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
        }

        return redirect()->to(
            $this->tenantLoginUrl($user)
        );
    }

    public function send(
        Request $request,
    ): RedirectResponse {
        $user = $request->user();

        if (!$user->hasVerifiedEmail()) {
            $user->sendEmailVerificationNotification();
        }

        return back()->with(
            'status',
            'verification-link-sent'
        );
    }

    private function tenantLoginUrl(
        User $user,
    ): string {
        $tenant = $user->tenant;

        $appUrl = (string) config('app.url');

        $scheme = parse_url(
            $appUrl,
            PHP_URL_SCHEME
        ) ?: 'http';

        $host = parse_url(
            $appUrl,
            PHP_URL_HOST
        );

        $port = parse_url(
            $appUrl,
            PHP_URL_PORT
        );

        if (!$host) {
            throw new \RuntimeException(
                'Application URL must contain a valid host.'
            );
        }

        return $scheme
            . '://'
            . $tenant->slug
            . '.'
            . $host
            . ($port ? ':' . $port : '')
            . '/login';
    }
}