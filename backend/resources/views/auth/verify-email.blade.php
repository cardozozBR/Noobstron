<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>{{ __('verification.title') }}</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 24px;
            background: #f5f7fb;
            color: #111827;
            font-family: Arial, Helvetica, sans-serif;
        }

        .verification-card {
            width: min(100%, 560px);
            padding: 32px;
            border: 1px solid #e5e7eb;
            border-radius: 18px;
            background: #ffffff;
            box-shadow: 0 16px 40px rgba(15, 23, 42, 0.08);
        }

        h1 {
            margin: 0 0 12px;
            font-size: clamp(28px, 5vw, 38px);
            line-height: 1.1;
        }

        p {
            margin: 0 0 16px;
            color: #64748b;
            line-height: 1.6;
        }

        .email-box {
            margin: 20px 0;
            padding: 14px 16px;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            background: #f8fafc;
        }

        .email-box span,
        .email-box strong {
            display: block;
        }

        .email-box span {
            margin-bottom: 4px;
            color: #64748b;
            font-size: 13px;
        }

        .status {
            margin: 20px 0;
            padding: 12px 14px;
            border-radius: 10px;
            background: #dcfce7;
            color: #166534;
        }

        .actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 24px;
        }

        button {
            border: 0;
            border-radius: 9px;
            padding: 11px 16px;
            background: #111827;
            color: #ffffff;
            font-weight: 700;
            cursor: pointer;
        }

        .secondary {
            background: #ffffff;
            color: #111827;
            border: 1px solid #cbd5e1;
        }

        form {
            margin: 0;
        }

        .help {
            margin-top: 18px;
            font-size: 14px;
        }
    </style>
</head>

<body>
    <main class="verification-card">
        <h1>{{ __('verification.title') }}</h1>

        <p>
            {{ __('verification.description') }}
        </p>

        <p>
            {{ __('verification.details') }}
        </p>

        <div class="email-box">
            <span>{{ __('verification.email_label') }}</span>
            <strong>{{ auth()->user()->email }}</strong>
        </div>

        @if (session('status') === 'verification-link-sent')
            <div class="status" role="status">
                {{ __('verification.sent') }}
            </div>
        @endif

        <div class="actions">
            <form
                method="POST"
                action="{{ route('verification.send') }}"
            >
                @csrf

                <button type="submit">
                    {{ __('verification.resend') }}
                </button>
            </form>

            <form
                method="POST"
                action="{{ route('logout') }}"
            >
                @csrf

                <button
                    type="submit"
                    class="secondary"
                >
                    {{ __('verification.logout') }}
                </button>
            </form>
        </div>

        <p class="help">
            {{ __('verification.help') }}
        </p>
    </main>
</body>
</html>
