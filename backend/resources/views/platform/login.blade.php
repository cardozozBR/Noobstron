@extends('platform.layout')

@section('title', __('platform.login.title'))

@section('body')

<style>
.platform-login-page{min-height:100vh;display:grid;place-items:center;padding:32px 18px}
.platform-login-page .login-card{width:min(100%,460px);margin:0;padding:30px;border-radius:18px}
.platform-login-page .login-card h1{margin:0 0 10px;font-size:30px;letter-spacing:-.03em}
.platform-login-page .login-card>p{margin:0 0 24px;color:#64748b;line-height:1.6}
.platform-login-page form{display:grid;gap:18px}
.platform-login-page .form-group{display:grid;gap:7px}
.platform-login-page .form-control{min-height:44px}
.platform-login-page .button{width:100%;min-height:44px;justify-content:center}
.platform-login-page .error{margin-top:6px;color:#b91c1c;font-size:12px;font-weight:600}
</style>

<div class="platform-login-page">
    <main class="platform-main">
        <div
            class="platform-card login-card"
        >
            <h1>{{ __('platform.login.title') }}</h1>

            <p>
                {{ __('platform.login.description') }}
            </p>

            <form
                method="POST"
                action="{{ route('platform.login.store') }}"
            >
                @csrf

                <div class="form-group">
                    <label for="platform-email">
                        {{ __('platform.email') }}
                    </label>

                    <input
                        id="platform-email"
                        class="form-control"
                        type="email"
                        name="email"
                        value="{{ old('email') }}"
                        required
                        autofocus
                    >

                    @error('email')
                        <div class="error">
                            {{ $message }}
                        </div>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="platform-password">
                        {{ __('platform.password') }}
                    </label>

                    <input
                        id="platform-password"
                        class="form-control"
                        type="password"
                        name="password"
                        required
                    >
                </div>

                <button
                    class="button"
                    type="submit"
                >
                    {{ __('platform.login.submit') }}
                </button>
            </form>
        </div>
    </main>
</div>
@endsection
