@extends('layouts.app')

@section('content')
<style>
.imports-index-page{display:grid;gap:24px}
.imports-index-page .page-header{display:flex;align-items:flex-end;justify-content:space-between;gap:18px;margin:0}
.imports-index-page .page-header h1{margin:0;font-size:30px;line-height:1.15;letter-spacing:-.025em}
.imports-index-page .card{margin:0;padding:22px;border:1px solid #e5e7eb;border-radius:16px;background:#fff;box-shadow:0 1px 2px rgba(15,23,42,.04)}
.imports-index-page .create-link{display:inline-flex;min-height:40px;align-items:center;justify-content:center;padding:9px 14px;border-radius:10px;background:var(--primary);color:#fff!important;font-size:13px;font-weight:700;text-decoration:none}
.imports-index-page .table-responsive{overflow-x:auto;border:1px solid #e5e7eb;border-radius:12px}
.imports-index-page table{width:100%;border-collapse:collapse;background:#fff}
.imports-index-page thead{background:#f9fafb}
.imports-index-page th{padding:11px 12px;border-bottom:1px solid #e5e7eb;color:#6b7280;font-size:11px;font-weight:700;letter-spacing:.055em;text-align:left;text-transform:uppercase;white-space:nowrap}
.imports-index-page td{padding:13px 12px;border-bottom:1px solid #f3f4f6;color:#4b5563;font-size:13px;vertical-align:middle}
.imports-index-page tbody tr:hover{background:#f9fafb}
.imports-index-page tbody tr:last-child td{border-bottom:0}
.imports-index-page td:first-child{color:#111827;font-weight:700}
.imports-index-page .status-badge{display:inline-flex;padding:4px 8px;border-radius:999px;background:#f3f4f6;color:#374151;font-size:11px;font-weight:700}
.imports-index-page .view-link{display:inline-flex;min-height:32px;align-items:center;justify-content:center;padding:6px 9px;border:1px solid #d1d5db;border-radius:8px;background:#fff;color:#374151!important;font-size:11px;font-weight:700;text-decoration:none}
.imports-index-page .empty-state{padding:44px 24px;border:1px dashed #d1d5db;border-radius:12px;background:#fafafa;color:#6b7280;text-align:center}
@media(max-width:700px){.imports-index-page .page-header{align-items:flex-start;flex-direction:column}.imports-index-page .card{padding:18px}}
</style>

<div class="imports-index-page">
    <div class="page-header">
        <div>
            <h1>{{ __('imports.title') }}</h1>
        </div>

        @if (
            auth()->user()->hasPermission(
                \App\Enums\Permission::IMPORTS_CREATE
            )
        )
            <a class="create-link" href="{{ route('imports.create') }}">
                {{ __('imports.new') }}
            </a>
        @endif
    </div>

    <div class="card">
        @if ($imports->count() === 0)
            <div class="empty-state">
                {{ __('imports.no_imports') }}
            </div>
        @else
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>{{ __('imports.original_name') }}</th>
                            <th>{{ __('imports.target') }}</th>
                            <th>{{ __('imports.status') }}</th>
                            <th>{{ __('imports.processed') }}</th>
                            <th>{{ __('imports.success') }}</th>
                            <th>{{ __('imports.failed') }}</th>
                            <th>{{ __('imports.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($imports as $import)
                            <tr>
                                <td>{{ $import->original_name }}</td>
                                <td>{{ $import->target?->value }}</td>
                                <td>
                                    <span class="status-badge">
                                        {{
                                            __('imports.status_'
                                                . $import->status->value)
                                        }}
                                    </span>
                                </td>
                                <td>{{ $import->processed_count }}</td>
                                <td>{{ $import->success_count }}</td>
                                <td>{{ $import->failure_count }}</td>
                                <td>
                                    <a
                                        class="view-link"
                                        href="{{ route(
                                            'imports.show',
                                            $import->id
                                        ) }}"
                                    >
                                        {{ __('imports.view') }}
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{ $imports->links() }}
        @endif
    </div>
</div>
@endsection
