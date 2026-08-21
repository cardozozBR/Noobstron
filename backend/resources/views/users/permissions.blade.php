@extends('layouts.app')

@section('title', __('ui.permissions.title'))

@section('content')
<style>
.user-permissions-page{display:grid;gap:24px;max-width:1000px;margin:0 auto}
.user-permissions-page .page-header{display:flex;align-items:flex-end;justify-content:space-between;gap:18px;margin:0}
.user-permissions-page .page-header h1{margin:2px 0 0;font-size:30px;line-height:1.15;letter-spacing:-.025em}
.user-permissions-page .page-header p{margin:6px 0 0;color:#6b7280;font-size:14px}
.user-permissions-page .card{margin:0;padding:22px;border:1px solid #e5e7eb;border-radius:16px;background:#fff;box-shadow:0 1px 2px rgba(15,23,42,.04)}
.user-permissions-page .section-header{margin-bottom:18px}
.user-permissions-page .section-header h2{margin:0;font-size:20px;color:#111827}
.user-permissions-page .section-header p{margin:6px 0 0;color:#6b7280;font-size:13px}
.user-permissions-page .checkbox-list{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}
.user-permissions-page .checkbox-item{display:flex;align-items:flex-start;gap:10px;padding:14px;border:1px solid #e5e7eb;border-radius:12px;background:#fff;cursor:pointer;transition:border-color 160ms ease,background-color 160ms ease,box-shadow 160ms ease}
.user-permissions-page .checkbox-item:hover{border-color:#cbd5e1;background:#f9fafb;box-shadow:0 1px 2px rgba(15,23,42,.035)}
.user-permissions-page .checkbox-item input{margin-top:3px}
.user-permissions-page .permission-copy{display:block}
.user-permissions-page .permission-copy strong{display:block;color:#111827;font-size:13px}
.user-permissions-page .permission-copy span{display:block;margin-top:3px;color:#6b7280;font-size:12px;line-height:1.45}
.user-permissions-page .form-actions{display:flex;justify-content:flex-end;flex-wrap:wrap;gap:8px;margin-top:20px;padding-top:18px;border-top:1px solid #e5e7eb}
.user-permissions-page .btn{display:inline-flex;min-height:40px;align-items:center;justify-content:center;padding:9px 14px;border-radius:10px;font-size:13px;font-weight:700;text-decoration:none}
.user-permissions-page .btn-secondary{border:1px solid #d1d5db;background:#fff;color:#374151}
@media(max-width:750px){.user-permissions-page .page-header{align-items:flex-start;flex-direction:column}.user-permissions-page .checkbox-list{grid-template-columns:1fr}.user-permissions-page .card{padding:18px}}
</style>

<div class="user-permissions-page">
    <div class="page-header">
        <div>
            <span class="eyebrow">{{ __('ui.permissions.eyebrow') }}</span>
            <h1>{{ __('ui.permissions.heading') }}</h1>

            <p>
                {{ __('ui.permissions.description') }}
            </p>
        </div>

        <div class="actions">
            <a href="{{ route('users.edit', $user->id) }}" class="btn btn-secondary">
                {{ __('ui.common.back') }}
            </a>
        </div>
    </div>

    <div class="card">
        <div class="section-header">
            <div>
                <h2>{{ $user->name }}</h2>

                <p>
                    {{ $user->email }}
                    ·
                    {{ __('ui.permissions.role_label') }}
                    <strong>{{ $user->role->value }}</strong>
                </p>
            </div>
        </div>

        <form method="POST" action="{{ route('users.permissions.update', $user->id) }}">
            @csrf
            @method('PUT')

            <div class="checkbox-list">
                @foreach ($permissions as $permission)
                    <label class="checkbox-item">
                        <input
                            type="checkbox"
                            name="permissions[]"
                            value="{{ $permission->id }}"
                            {{ in_array($permission->id, $userPermissionIds) ? 'checked' : '' }}
                        >

                        <span class="permission-copy">
                            <strong>{{ $permission->name }}</strong>
                            <span>— {{ $permission->label }}</span>
                        </span>
                    </label>
                @endforeach
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    {{ __('ui.permissions.save') }}
                </button>

                <a
                    href="{{ route('users.edit', $user->id) }}"
                    class="btn btn-secondary"
                >
                    {{ __('ui.common.back') }}
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
