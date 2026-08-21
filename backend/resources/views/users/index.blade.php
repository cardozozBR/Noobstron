@extends('layouts.app')

@section('title', __('ui.users.title'))

@section('content')
<style>
.users-index-page{display:grid;gap:24px}
.users-index-page .page-header{display:flex;align-items:flex-end;justify-content:space-between;gap:18px;margin:0}
.users-index-page .page-header h1{margin:0 0 6px;font-size:30px;line-height:1.15;letter-spacing:-.025em}
.users-index-page .page-header p{margin:0;color:#6b7280;font-size:14px}
.users-index-page .actions{display:flex;flex-wrap:wrap;gap:8px}
.users-index-page .btn{display:inline-flex;min-height:40px;align-items:center;justify-content:center;padding:9px 14px;border-radius:10px;font-size:13px;font-weight:700;text-decoration:none}
.users-index-page .btn-secondary{border:1px solid #d1d5db;background:#fff;color:#374151}
.users-index-page .card{margin:0;padding:22px;border:1px solid #e5e7eb;border-radius:16px;background:#fff;box-shadow:0 1px 2px rgba(15,23,42,.04)}
.users-index-page .table-wrapper{overflow-x:auto;border:1px solid #e5e7eb;border-radius:12px}
.users-index-page .table{width:100%;margin:0;border-collapse:collapse;background:#fff}
.users-index-page .table thead{background:#f9fafb}
.users-index-page .table th{padding:11px 12px;border-bottom:1px solid #e5e7eb;color:#6b7280;font-size:11px;font-weight:700;letter-spacing:.055em;text-align:left;text-transform:uppercase;white-space:nowrap}
.users-index-page .table td{padding:13px 12px;border-bottom:1px solid #f3f4f6;color:#4b5563;font-size:13px;vertical-align:middle}
.users-index-page .table tbody tr:hover{background:#f9fafb}
.users-index-page .table tbody tr:last-child td{border-bottom:0}
.users-index-page .table td:first-child{color:#111827;font-weight:700}
.users-index-page .table td a,
.users-index-page .table td button{display:inline-flex;min-height:32px;align-items:center;justify-content:center;padding:6px 9px;border:1px solid #d1d5db;border-radius:8px;background:#fff;color:#374151;font:inherit;font-size:11px;font-weight:700;text-decoration:none;cursor:pointer}
.users-index-page .table td form{display:inline-flex!important;margin-left:6px}
.users-index-page .empty-state{padding:44px 24px;border:1px dashed #d1d5db;border-radius:12px;background:#fafafa;color:#6b7280;text-align:center}
.users-index-page .pagination-wrap{margin-top:18px}
@media(max-width:700px){.users-index-page .page-header{align-items:flex-start;flex-direction:column}.users-index-page .card{padding:18px}}
</style>

<div class="users-index-page">
    <div class="page-header">
        <div>
            <h1>{{ __('ui.users.heading') }}</h1>

            <p>
                {{ __('ui.users.tenant_users', ['tenant' => $tenant->name]) }}
            </p>
        </div>

        <div class="actions">
            @if(auth()->user()->hasPermission(\App\Enums\Permission::USERS_CREATE))
                <a href="{{ route('users.create') }}" class="btn btn-primary">
                    {{ __('ui.users.new_user') }}
                </a>
            @endif

            <a href="{{ route('dashboard') }}" class="btn btn-secondary">
                {{ __('ui.users.back_dashboard') }}
            </a>
        </div>
    </div>

    <div class="card">
        @if ($users->isEmpty())
            <div class="empty-state">
                {{ __('ui.users.empty') }}
            </div>
        @else
            <div class="table-wrapper">
                <table class="table">
                    <thead>
                        <tr>
                            <th>{{ __('ui.users.name') }}</th>
                            <th>{{ __('ui.users.email') }}</th>
                            <th>{{ __('ui.users.created_at') }}</th>
                            <th>{{ __('ui.users.actions') }}</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($users as $user)
                            <tr>
                                <td>{{ $user->name }}</td>
                                <td>{{ $user->email }}</td>
                                <td>
                                    {{ $user->created_at ? app(\App\Support\TenantDateTime::class)->formatForTenant($user->created_at, 'd/m/Y H:i') : '' }}
                                </td>
                                <td>
                                    @if(auth()->user()->hasPermission(\App\Enums\Permission::USERS_UPDATE))
                                        <a href="{{ route('users.edit', $user->id) }}">
                                            {{ __('ui.users.edit') }}
                                        </a>
                                    @endif

                                    @if(auth()->user()->hasPermission(\App\Enums\Permission::USERS_PERMISSIONS))
                                        <a
                                            href="{{ route('users.permissions.edit', $user->id) }}"
                                            style="margin-left: 6px;"
                                        >
                                            {{ __('ui.users.permissions') }}
                                        </a>
                                    @endif

                                    @if(auth()->user()->hasPermission(\App\Enums\Permission::USERS_DELETE))
                                        <form
                                            method="POST"
                                            action="{{ route('users.destroy', $user->id) }}"
                                            style="display:inline; margin-left: 6px;"
                                            onsubmit="return confirm(@js(__('ui.users.delete_confirm')));"
                                        >
                                            @csrf
                                            @method('DELETE')

                                            <button type="submit">
                                                {{ __('ui.users.delete') }}
                                            </button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="pagination-wrap">
                {{ $users->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
