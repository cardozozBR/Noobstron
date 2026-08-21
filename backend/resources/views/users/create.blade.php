@extends('layouts.app')

@section('title', __('ui.users.create_title'))

@section('content')

<style>
.user-form-page{display:grid;gap:24px;max-width:900px;margin:0 auto}
.user-form-page .page-header{display:flex;align-items:flex-end;justify-content:space-between;gap:18px;margin:0}
.user-form-page .page-header h1{margin:2px 0 0;font-size:30px;line-height:1.15;letter-spacing:-.025em}
.user-form-page .page-header p{margin:6px 0 0;color:#6b7280;font-size:14px}
.user-form-page .card{margin:0;padding:24px;border:1px solid #e5e7eb;border-radius:16px;background:#fff;box-shadow:0 1px 2px rgba(15,23,42,.04)}
.user-form-page form{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:18px}
.user-form-page .form-group{display:grid;gap:7px}
.user-form-page .form-label{color:#374151;font-size:13px;font-weight:700}
.user-form-page .form-control{width:100%;min-height:42px;padding:10px 12px;border:1px solid #d1d5db;border-radius:10px;background:#fff;color:#111827;font:inherit;font-size:14px;outline:none;box-shadow:0 1px 2px rgba(15,23,42,.035);transition:border-color 160ms ease,box-shadow 160ms ease}
.user-form-page .form-control:focus{border-color:var(--primary);box-shadow:0 0 0 3px rgba(37,99,235,.10)}
.user-form-page hr{grid-column:1/-1;margin:4px 0;border:0;border-top:1px solid #e5e7eb}
.user-form-page .section-header{grid-column:1/-1;margin:0}
.user-form-page .section-header h2{margin:2px 0 0;font-size:19px}
.user-form-page .section-header p{margin:6px 0 0;color:#6b7280;font-size:13px}
.user-form-page .form-help{color:#6b7280;font-size:12px}
.user-form-page .form-actions{grid-column:1/-1;display:flex;justify-content:flex-end;flex-wrap:wrap;gap:8px;padding-top:18px;border-top:1px solid #e5e7eb}
.user-form-page .btn{display:inline-flex;min-height:40px;align-items:center;justify-content:center;padding:9px 14px;border-radius:10px;font-size:13px;font-weight:700;text-decoration:none}
.user-form-page .btn-secondary{border:1px solid #d1d5db;background:#fff;color:#374151}
@media(max-width:700px){.user-form-page .page-header{align-items:flex-start;flex-direction:column}.user-form-page form{grid-template-columns:1fr}.user-form-page hr,.user-form-page .section-header,.user-form-page .form-actions{grid-column:auto}.user-form-page .card{padding:18px}}
</style>

<div class="user-form-page">
    <div class="page-header">
        <div>
            <span class="eyebrow">{{ __('ui.users.heading') }}</span>
            <h1>{{ __('ui.users.create_heading') }}</h1>

            <p>
                {{ __('ui.users.create_description', [
                    'tenant' => $tenant->name,
                ]) }}
            </p>
        </div>

        <div class="actions">
            <a href="{{ route('users.index') }}" class="btn btn-secondary">
                {{ __('ui.common.cancel') }}
            </a>
        </div>
    </div>

    <div class="card">
        <form method="POST" action="{{ route('users.store') }}">
            @csrf

            <div class="form-group">
                <label for="name" class="form-label">{{ __('ui.users.name') }}</label>
                <input id="name" type="text" name="name" value="{{ old('name') }}" required class="form-control">
            </div>

            <div class="form-group">
                <label for="email" class="form-label">{{ __('ui.users.email') }}</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required class="form-control">
            </div>

            <div class="form-group">
                <label for="role" class="form-label">{{ __('ui.users.role') }}</label>
                <select id="role" name="role" required class="form-control">
                    <option value="user" {{ old('role', 'user') === 'user' ? 'selected' : '' }}>
                        {{ __('ui.common.user') }}
                    </option>

                    @if(auth()->user()->isAdmin())
                        <option value="admin" {{ old('role') === 'admin' ? 'selected' : '' }}>
                            {{ __('ui.common.administrator') }}
                        </option>
                    @endif
                </select>
            </div>

            <div class="form-group">
                <label for="password" class="form-label">{{ __('ui.users.password') }}</label>
                <input id="password" type="password" name="password" required class="form-control">
            </div>

            <div class="form-group">
                <label for="password_confirmation" class="form-label">
                    {{ __('ui.users.confirm_password') }}
                </label>
                <input id="password_confirmation" type="password" name="password_confirmation" required class="form-control">
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    {{ __('ui.users.create_submit') }}
                </button>

                <a href="{{ route('users.index') }}" class="btn btn-secondary">
                    {{ __('ui.common.cancel') }}
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
