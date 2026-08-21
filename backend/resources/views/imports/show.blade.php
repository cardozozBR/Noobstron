@extends('layouts.app')

@section('content')
<style>
.import-show-page{display:grid;gap:24px}
.import-show-page .card{margin:0;padding:22px;border:1px solid #e5e7eb;border-radius:16px;background:#fff;box-shadow:0 1px 2px rgba(15,23,42,.04)}
.import-show-page h1{margin:0;font-size:30px;line-height:1.15;letter-spacing:-.025em}
.import-show-page .summary-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px}
.import-show-page .summary-item{padding:14px;border:1px solid #e5e7eb;border-radius:12px;background:#f9fafb}
.import-show-page .summary-label{display:block;margin-bottom:5px;color:#6b7280;font-size:11px;font-weight:700;letter-spacing:.05em;text-transform:uppercase}
.import-show-page .summary-value{color:#111827;font-size:14px;font-weight:700}
.import-show-page .table-responsive{overflow-x:auto;border:1px solid #e5e7eb;border-radius:12px}
.import-show-page table{width:100%;border-collapse:collapse;background:#fff}
.import-show-page thead{background:#f9fafb}
.import-show-page th{padding:11px 12px;border-bottom:1px solid #e5e7eb;color:#6b7280;font-size:11px;font-weight:700;letter-spacing:.055em;text-align:left;text-transform:uppercase}
.import-show-page td{padding:13px 12px;border-bottom:1px solid #f3f4f6;color:#4b5563;font-size:13px;vertical-align:top}
.import-show-page tbody tr:last-child td{border-bottom:0}
.import-show-page pre{margin:0;max-width:520px;white-space:pre-wrap;word-break:break-word;padding:10px;border-radius:8px;background:#111827;color:#f9fafb;font-size:12px;line-height:1.45}
@media(max-width:800px){.import-show-page .summary-grid{grid-template-columns:1fr}.import-show-page .card{padding:18px}}
</style>

<div class="import-show-page">
    <div>
        <h1>{{ __('imports.title') }}</h1>
    </div>

    <div class="card">
        <div class="summary-grid">
            <div class="summary-item">
                <span class="summary-label">{{ __('imports.original_name') }}</span>
                <span class="summary-value">{{ $import->original_name }}</span>
            </div>

            <div class="summary-item">
                <span class="summary-label">{{ __('imports.target') }}</span>
                <span class="summary-value">{{ $import->target?->value }}</span>
            </div>

            <div class="summary-item">
                <span class="summary-label">{{ __('imports.status') }}</span>
                <span class="summary-value">
                    {{
                        __('imports.status_'
                            . $import->status->value)
                    }}
                </span>
            </div>

            <div class="summary-item">
                <span class="summary-label">{{ __('imports.rows') }}</span>
                <span class="summary-value">{{ $import->row_count ?? 0 }}</span>
            </div>

            <div class="summary-item">
                <span class="summary-label">{{ __('imports.processed') }}</span>
                <span class="summary-value">{{ $import->processed_count }}</span>
            </div>

            <div class="summary-item">
                <span class="summary-label">{{ __('imports.success') }}</span>
                <span class="summary-value">{{ $import->success_count }}</span>
            </div>

            <div class="summary-item">
                <span class="summary-label">{{ __('imports.failed') }}</span>
                <span class="summary-value">{{ $import->failure_count }}</span>
            </div>
        </div>
    </div>

    @if ($import->rows->count() > 0)
        <div class="card">
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>{{ __('imports.line') }}</th>
                            <th>{{ __('imports.status') }}</th>
                            <th>{{ __('imports.data') }}</th>
                            <th>{{ __('imports.errors') }}</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($import->rows as $row)
                            <tr>
                                <td>{{ $row->line }}</td>
                                <td>{{ $row->status->value }}</td>

                                <td>
                                    <pre>{{ json_encode(
                                        $row->data,
                                        JSON_UNESCAPED_UNICODE
                                        | JSON_PRETTY_PRINT
                                    ) }}</pre>
                                </td>

                                <td>
                                    @if ($row->errors)
                                        <pre>{{ json_encode(
                                            $row->errors,
                                            JSON_UNESCAPED_UNICODE
                                            | JSON_PRETTY_PRINT
                                        ) }}</pre>
                                    @else
                                        {{ __('imports.no_errors') }}
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
@endsection
