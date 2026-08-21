<!DOCTYPE html>

<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">


<title>{{ __('ui.auth.title') }}</title>

@php
    $brandingTenant = request()->attributes->get('tenant');
    $tenantPrimaryColor = $brandingTenant
        ? $brandingTenant->effectiveBrandPrimaryColor()
        : '#2563EB';
@endphp

<style>
    :root {
        --tenant-primary-color: {{ $tenantPrimaryColor }};
    }
    * {
        box-sizing: border-box;
    }

    body {
        margin: 0;
        min-height: 100vh;
        font-family: Arial, Helvetica, sans-serif;
        background: #f5f7fb;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #1f2937;
    }

    .card {
        width: min(440px, 92%);
        background: #fff;
        padding: 40px;
        border: 1px solid #e5e7eb;
        border-radius: 18px;
        box-shadow: 0 16px 40px rgba(15, 23, 42, 0.08);
    }

    h1 {
        margin: 0 0 8px;
        font-size: 28px;
    }

    .subtitle {
        margin: 0 0 28px;
        color: #6b7280;
    }

    label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
    }

    input {
        width: 100%;
        padding: 12px 14px;
        margin-bottom: 18px;
        border: 1px solid #d1d5db;
        border-radius: 10px;
        font-size: 16px;
        outline: none;
    }

    input:focus {
        border-color: var(--tenant-primary-color);
        box-shadow: 0 0 0 3px color-mix(in srgb, var(--tenant-primary-color) 15%, transparent);
    }

    button {
        width: 100%;
        padding: 13px;
        border: 0;
        border-radius: 8px;
        background: var(--tenant-primary-color);
        color: white;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
    }

    .error {
        margin-bottom: 18px;
        padding: 12px;
        border-radius: 10px;
        background: #fee2e2;
        color: #991b1b;
    }

    .auth-footer {
        margin: 18px 0 0;
        text-align: center;
        font-size: 14px;
    }
</style>


</head>

<body>
    <main class="card">
        <h1>{{ __('ui.auth.heading') }}</h1>


    <p class="subtitle">
        {{ __('ui.auth.subtitle') }}
    </p>

    @if ($errors->any())
        <div class="error">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <label for="email">{{ __('ui.auth.email') }}</label>

        <input
            id="email"
            type="email"
            name="email"
            value="{{ old('email') }}"
            required
            autofocus
        >

        <label for="password">{{ __('ui.auth.password') }}</label>

        <input
            id="password"
            type="password"
            name="password"
            required
        >

        <button type="submit">
            {{ __('ui.auth.submit') }}
        </button>
    </form>

    <p class="auth-footer">
        <a href="{{ route('password.request') }}">
            {{ __('ui.auth.forgot_password') }}
        </a>
    </p>
</main>


</body>
</html>
