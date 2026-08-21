<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('ui.auth.forgot_title') }}</title>
    <style>
        * { box-sizing: border-box; }
        body { margin:0; min-height:100vh; font-family:Arial,Helvetica,sans-serif; background:#f5f7fb; display:flex; align-items:center; justify-content:center; color:#1f2937; }
        .card { width:min(440px,92%); background:#fff; padding:40px; border:1px solid #e5e7eb; border-radius:18px; box-shadow:0 16px 40px rgba(15,23,42,.08); }
        h1 { margin:0 0 8px; font-size:28px; }
        p { color:#6b7280; line-height:1.6; }
        label { display:block; margin:24px 0 8px; font-weight:600; }
        input { width:100%; padding:12px 14px; border:1px solid #d1d5db; border-radius:10px; font-size:16px; outline:none; } input:focus { border-color:#2563eb; box-shadow:0 0 0 3px rgba(37,99,235,.10); }
        button { width:100%; margin-top:18px; padding:13px; border:0; border-radius:8px; background:#2563eb; color:#fff; font-size:16px; font-weight:600; cursor:pointer; }
        .status { margin:18px 0; padding:12px; border-radius:8px; background:#dcfce7; color:#166534; }
        .error { margin:18px 0; padding:12px; border-radius:8px; background:#fee2e2; color:#991b1b; }
        .back { display:block; margin-top:20px; text-align:center; }
    </style>
</head>
<body>
<main class="card">
    <h1>{{ __('ui.auth.forgot_heading') }}</h1>
    <p>{{ __('ui.auth.forgot_description') }}</p>

    @if (session('status'))
        <div class="status">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
        <div class="error">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('password.email') }}">
        @csrf
        <label for="email">{{ __('ui.auth.email') }}</label>
        <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="email">
        <button type="submit">{{ __('ui.auth.send_reset_link') }}</button>
    </form>

    <a class="back" href="{{ route('login') }}">{{ __('ui.auth.back_to_login') }}</a>
</main>
</body>
</html>
