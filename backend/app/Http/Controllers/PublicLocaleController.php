<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PublicLocaleController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $locale = trim((string) $request->query('locale', ''));
        $supported = array_keys(config('global.locales', []));

        if (!in_array($locale, $supported, true)) {
            abort(404);
        }

        $return = trim((string) $request->query('return', '/'));

        if (!str_starts_with($return, '/') || str_starts_with($return, '//')) {
            $return = '/';
        }

        return redirect($return)->withCookie(
            cookie(
                'public_locale',
                $locale,
                60 * 24 * 365,
                '/',
                null,
                $request->isSecure(),
                true,
                false,
                'lax'
            )
        );
    }
}