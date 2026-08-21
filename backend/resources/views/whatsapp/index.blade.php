@extends('layouts.app')

@section('content')
<style>
.whatsapp-index-page{display:grid;gap:24px}
.whatsapp-index-page .page-header{display:flex;align-items:flex-end;justify-content:space-between;gap:18px;margin:0}
.whatsapp-index-page .page-header h1{margin:0;font-size:30px;line-height:1.15;letter-spacing:-.025em}
.whatsapp-index-page .header-actions{display:flex;flex-wrap:wrap;gap:8px}
.whatsapp-index-page .header-actions a{display:inline-flex;min-height:40px;align-items:center;justify-content:center;padding:9px 14px;border-radius:10px;font-size:13px;font-weight:700;text-decoration:none}
.whatsapp-index-page .header-actions .secondary{border:1px solid #d1d5db;background:#fff;color:#374151!important}
.whatsapp-index-page .header-actions .primary{background:var(--primary);color:#fff!important}
.whatsapp-index-page .card{margin:0;padding:22px;border:1px solid #e5e7eb;border-radius:16px;background:#fff;box-shadow:0 1px 2px rgba(15,23,42,.04)}
.whatsapp-index-page .table-responsive{overflow-x:auto;border:1px solid #e5e7eb;border-radius:12px}
.whatsapp-index-page table{width:100%;border-collapse:collapse;background:#fff}
.whatsapp-index-page thead{background:#f9fafb}
.whatsapp-index-page th{padding:11px 12px;border-bottom:1px solid #e5e7eb;color:#6b7280;font-size:11px;font-weight:700;letter-spacing:.055em;text-align:left;text-transform:uppercase;white-space:nowrap}
.whatsapp-index-page td{padding:13px 12px;border-bottom:1px solid #f3f4f6;color:#4b5563;font-size:13px;vertical-align:middle}
.whatsapp-index-page tbody tr:hover{background:#f9fafb}
.whatsapp-index-page tbody tr:last-child td{border-bottom:0}
.whatsapp-index-page td:first-child{color:#111827;font-weight:700;white-space:nowrap}
.whatsapp-index-page td:nth-child(2){max-width:520px}
.whatsapp-index-page .status-badge,.whatsapp-index-page .direction-badge{display:inline-flex;padding:4px 8px;border-radius:999px;background:#f3f4f6;color:#374151;font-size:11px;font-weight:700}
.whatsapp-index-page .direction-badge{background:#eff6ff;color:#1d4ed8;border:1px solid #dbeafe}
.whatsapp-index-page .empty-state{padding:44px 24px;border:1px dashed #d1d5db;border-radius:12px;background:#fafafa;color:#6b7280;text-align:center}
@media(max-width:700px){.whatsapp-index-page .page-header{align-items:flex-start;flex-direction:column}.whatsapp-index-page .card{padding:18px}}
</style>

<div class="whatsapp-index-page">
    <div class="page-header">
        <div>
            <h1>{{ __('whatsapp.history') }}</h1>
        </div>

        <div class="header-actions">
            @can('whatsapp.templates')
                <a
                    href="{{ route('whatsapp.templates.index') }}"
                    class="secondary"
                >
                    {{ __('whatsapp.templates') }}
                </a>
            @endcan

            @can('whatsapp.create')
                <a
                    href="{{ route('whatsapp.create') }}"
                    class="primary"
                >
                    {{ __('whatsapp.new_message') }}
                </a>
            @endcan
        </div>
    </div>

    <div class="card">
        @if ($messages->count() === 0)
            <div class="empty-state">
                {{ __('whatsapp.no_messages') }}
            </div>
        @else
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>{{ __('whatsapp.phone') }}</th>
                            <th>{{ __('whatsapp.message') }}</th>
                            <th>{{ __('whatsapp.status') }}</th>
                            <th>{{ __('whatsapp.direction') }}</th>
                            <th>{{ __('whatsapp.provider') }}</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($messages as $message)
                            <tr>
                                <td>{{ $message->phone }}</td>
                                <td>{{ $message->body }}</td>
                                <td>
                                    <span class="status-badge">
                                        {{ $message->status->value }}
                                    </span>
                                </td>
                                <td>
                                    <span class="direction-badge">
                                        {{ $message->direction }}
                                    </span>
                                </td>
                                <td>{{ $message->provider }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{ $messages->links() }}
        @endif
    </div>
</div>
@endsection
