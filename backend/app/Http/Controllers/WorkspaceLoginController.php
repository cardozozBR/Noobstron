<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class WorkspaceLoginController extends Controller
{
    public function show(): View
    {
        return view('auth.workspace-login');
    }

    public function redirect(
        Request $request
    ): RedirectResponse {
        $data = $request->validate([
            'workspace' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-zA-Z0-9_-]+$/',
            ],
        ]);

        $slug = Str::lower(
            trim($data['workspace'])
        );

        $tenant = Tenant::query()
            ->where('slug', $slug)
            ->where('status', 'active')
            ->first();

        if (!$tenant) {
            throw ValidationException::withMessages([
                'workspace' =>
                    'Workspace não encontrado ou indisponível.',
            ]);
        }

        $scheme = $request->getScheme();
        $host = $request->getHost();
        $port = $request->getPort();

        if ($host === '') {
            throw new \RuntimeException(
                'Request host must be available.'
            );
        }

        $defaultPort = match ($scheme) {
            'https' => 443,
            default => 80,
        };

        $url = $scheme
            . '://'
            . $tenant->slug
            . '.'
            . $host
            . (
                $port !== $defaultPort
                    ? ':' . $port
                    : ''
            )
            . '/login';

        return redirect()->away($url);
    }
}