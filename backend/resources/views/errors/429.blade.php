<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        {{ __('errors.usage_limit.title') }}
    </title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            font-family: Arial, Helvetica, sans-serif;
            background: #f5f7fb;
            color: #111827;
        }

        .error-card {
            width: min(100%, 600px);
            padding: 32px;
            text-align: center;
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            box-shadow: 0 8px 24px rgba(0,0,0,.05);
        }

        .error-code {
            display: block;
            margin-bottom: 8px;
            color: #6b7280;
            font-size: 13px;
            font-weight: 700;
            letter-spacing: .08em;
        }

        h1 {
            margin: 0 0 12px;
        }

        p {
            margin: 0 0 16px;
            color: #6b7280;
            line-height: 1.6;
        }

        .upgrade {
            margin-top: 20px;
            padding: 16px;
            border-radius: 10px;
            background: #f3f4f6;
            color: #374151;
            font-weight: 600;
        }

        a {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 16px;
            border-radius: 8px;
            background: #111827;
            color: #fff;
            text-decoration: none;
            font-weight: 600;
        }
    </style>
</head>

<body>
    <main class="error-card">
        <span class="error-code">
            {{ __('errors.usage_limit.code') }}
        </span>

        <h1>
            {{ __('errors.usage_limit.title') }}
        </h1>

        <p>
            {{ __('errors.usage_limit.message') }}
        </p>

        @if (
            isset($exception)
            && $exception->upgradeSuggested
        )
            <div class="upgrade">
                {{ __('errors.usage_limit.upgrade_suggestion') }}
            </div>
        @endif

        <a href="/">
            {{ __('errors.back_home') }}
        </a>
    </main>
</body>
</html>