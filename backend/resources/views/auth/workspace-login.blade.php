@extends('layouts.marketing')

@section('title', __('public.gateway.title') . ' — Noobstron')

@section('content')

<style>
.workspace-login-page{padding:24px 0 48px}
.workspace-login-page .workspace-card{max-width:620px;margin:0 auto;border-radius:18px}
.workspace-login-page .workspace-form{margin-top:24px;display:grid;gap:18px}
.workspace-login-page .workspace-input{display:block;width:100%;margin-top:8px;padding:12px;border:1px solid #cbd5e1;border-radius:10px;outline:none}
.workspace-login-page .workspace-input:focus{border-color:#2563eb;box-shadow:0 0 0 3px rgba(37,99,235,.10)}
.workspace-login-page .workspace-help{margin:8px 0 0;color:#64748b;font-size:14px}
.workspace-login-page .workspace-error{margin:8px 0 0;color:#b91c1c;font-size:14px}
.workspace-login-page .workspace-actions{margin-top:6px}
</style>

<div class="workspace-login-page">
<main>
    <section class="section">
        <div
            class="container workspace-card"
        >
            <div class="card">
                <h1>{{ __('public.gateway.title') }}</h1>

                <p>
                    {{ __('public.gateway.description') }}
                </p>

                <form
                    method="POST"
                    action="{{ route('workspace.login.redirect') }}"
                    class="workspace-form"
                >
                    @csrf

                    <label for="workspace">
                        {{ __('public.gateway.workspace') }}
                    </label>

                    <input
                        id="workspace"
                        name="workspace"
                        type="text"
                        value="{{ old('workspace') }}"
                        placeholder="tenant-a"
                        required
                        autofocus
                        aria-describedby="workspace-help"
                        class="workspace-input"
                    >

                    <p
                        id="workspace-help"
                        class="workspace-help"
                    >
                        {{ __('public.gateway.help') }}
                    </p>

                    @error('workspace')
                        <p class="workspace-error">
                            {{ $message }}
                        </p>
                    @enderror

                    <div
                        class="actions workspace-actions"
                    >
                        <button
                            type="submit"
                            class="button"
                        >
                            {{ __('public.gateway.continue') }}
                        </button>

                        <a
                            href="{{ route('marketing.home') }}"
                            class="button button-secondary"
                        >
                            {{ __('public.gateway.back') }}
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </section>
</main>
</div>
@endsection
