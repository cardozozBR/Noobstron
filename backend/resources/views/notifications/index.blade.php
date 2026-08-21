@extends('layouts.app')

@section('title', __('notifications.title'))

@section('content')
<style>
.notifications-page{display:grid;gap:24px}
.notifications-page .page-header{display:flex;align-items:flex-end;justify-content:space-between;gap:18px;margin:0}
.notifications-page .page-header h1{margin:2px 0 0;font-size:30px;line-height:1.15;letter-spacing:-.025em}
.notifications-page .page-header p{margin:6px 0 0;color:#6b7280;font-size:14px}
.notifications-page .card{margin:0;padding:22px;border:1px solid #e5e7eb;border-radius:16px;background:#fff;box-shadow:0 1px 2px rgba(15,23,42,.04)}
.notifications-page .actions{display:flex;flex-wrap:wrap;gap:8px}
.notifications-page .btn{display:inline-flex;min-height:40px;align-items:center;justify-content:center;padding:9px 14px;border-radius:10px;font-size:13px;font-weight:700;text-decoration:none}
.notifications-page .btn-secondary{border:1px solid #d1d5db;background:#fff;color:#374151}
.notifications-page .table-wrapper{overflow-x:auto;border:1px solid #e5e7eb;border-radius:12px}
.notifications-page .data-table{width:100%;margin:0;border-collapse:collapse;background:#fff}
.notifications-page .data-table thead{background:#f9fafb}
.notifications-page .data-table th{padding:11px 12px;border-bottom:1px solid #e5e7eb;color:#6b7280;font-size:11px;font-weight:700;letter-spacing:.055em;text-align:left;text-transform:uppercase;white-space:nowrap}
.notifications-page .data-table td{padding:13px 12px;border-bottom:1px solid #f3f4f6;color:#4b5563;font-size:13px;vertical-align:middle}
.notifications-page .data-table tbody tr:hover{background:#f9fafb}
.notifications-page .data-table tbody tr:last-child td{border-bottom:0}
.notifications-page .data-table td:first-child strong{color:#111827}
.notifications-page .form-help{margin-top:4px;color:#6b7280;font-size:12px}
.notifications-page .status-badge{display:inline-flex;padding:4px 8px;border-radius:999px;background:#f3f4f6;color:#374151;font-size:11px;font-weight:700}
.notifications-page .status-badge.unread{background:#eff6ff;color:#1d4ed8;border:1px solid #dbeafe}
.notifications-page .data-table td a,
.notifications-page .data-table td button{display:inline-flex;min-height:32px;align-items:center;justify-content:center;padding:6px 9px;border:1px solid #d1d5db;border-radius:8px;background:#fff;color:#374151;font:inherit;font-size:11px;font-weight:700;text-decoration:none;cursor:pointer}
.notifications-page .data-table td form{display:inline-flex!important;margin-left:4px}
.notifications-page .empty-state{padding:44px 24px;border:1px dashed #d1d5db;border-radius:12px;background:#fafafa;color:#6b7280;text-align:center}
@media(max-width:700px){.notifications-page .page-header{align-items:flex-start;flex-direction:column}.notifications-page .card{padding:18px}}
</style>

<div class="notifications-page">
    <div class="page-header">
        <div>
            <span class="eyebrow">
                {{ __('notifications.navigation') }}
            </span>

            <h1>{{ __('notifications.heading') }}</h1>

            <p>{{ __('notifications.description') }}</p>
        </div>

        @if (auth()->user()->unreadNotifications()->exists())
            <div class="actions">
                <form
                    method="POST"
                    action="{{ route('notifications.read-all') }}"
                >
                    @csrf

                    <button
                        class="btn btn-secondary"
                        type="submit"
                    >
                        {{ __('notifications.mark_all_read') }}
                    </button>
                </form>
            </div>
        @endif
    </div>

    <div class="card">
        @if ($notifications->count() === 0)
            <div class="empty-state">
                {{ __('notifications.empty') }}
            </div>
        @else
            <div class="table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>{{ __('notifications.fields.notification') }}</th>
                            <th>{{ __('notifications.fields.due_at') }}</th>
                            <th>{{ __('notifications.fields.received_at') }}</th>
                            <th>{{ __('notifications.fields.status') }}</th>
                            <th>{{ __('notifications.fields.actions') }}</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($notifications as $notification)
                            @php
                                $data = $notification->data;
                                $activityId = $data['activity_id'] ?? null;
                                $title = $data['title'] ?? __('notifications.activity');
                                $dueAt = isset($data['due_at'])
                                    ? \Illuminate\Support\Carbon::parse($data['due_at'])
                                    : null;

                                $canOpenActivity =
                                    $activityId
                                    && auth()->user()->hasPermission(
                                        \App\Enums\Permission::ACTIVITIES_UPDATE
                                    )
                                    && app(
                                        \App\Support\TenantCapabilities::class
                                    )->enabled(
                                        app(
                                            \App\Services\TenantContext::class
                                        )->get(),
                                        \App\Enums\Feature::ACTIVITIES
                                    );
                            @endphp

                            <tr>
                                <td>
                                    <strong>{{ $title }}</strong>

                                    <div class="form-help">
                                        {{ __('notifications.activity_due_reminder') }}
                                    </div>
                                </td>

                                <td class="nowrap">
                                    {{
                                        $dueAt
                                            ? $dueAt->format('Y-m-d H:i')
                                            : '—'
                                    }}
                                </td>

                                <td class="nowrap">
                                    {{ $notification->created_at->format('Y-m-d H:i') }}
                                </td>

                                <td>
                                    @if ($notification->read_at)
                                        <span class="status-badge">
                                            {{ __('notifications.read') }}
                                        </span>
                                    @else
                                        <span class="status-badge unread">
                                            {{ __('notifications.unread') }}
                                        </span>
                                    @endif
                                </td>

                                <td class="nowrap">
                                    @if ($canOpenActivity)
                                        <a
                                            href="{{ route(
                                                'activities.edit',
                                                $activityId
                                            ) }}"
                                        >
                                            {{ __('notifications.open_activity') }}
                                        </a>
                                    @endif

                                    @if (! $notification->read_at)
                                        <form
                                            method="POST"
                                            action="{{ route(
                                                'notifications.read',
                                                $notification->id
                                            ) }}"
                                            style="display:inline"
                                        >
                                            @csrf

                                            <button type="submit">
                                                {{ __('notifications.mark_read') }}
                                            </button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{ $notifications->links() }}
        @endif
    </div>
</div>
@endsection
