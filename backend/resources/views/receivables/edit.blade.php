@extends('layouts.app')

@section(
    'title',
    __('receivables.edit_title')
)

@section('content')


<style>
.receivable-form-page{display:grid;gap:24px;max-width:1000px;margin:0 auto}
.receivable-form-page .page-header{display:flex;align-items:flex-end;justify-content:space-between;gap:18px;margin:0}
.receivable-form-page .page-header h1{margin:2px 0 0;font-size:30px;line-height:1.15;letter-spacing:-.025em}
.receivable-form-page .page-header p{margin:6px 0 0;color:#6b7280;font-size:14px}
.receivable-form-page .card{margin:0;padding:22px;border:1px solid #e5e7eb;border-radius:16px;background:#fff;box-shadow:0 1px 2px rgba(15,23,42,.04)}
.receivable-form-page form{display:grid;gap:18px}
.receivable-form-page .form-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px}
.receivable-form-page .form-group{display:grid;gap:7px}
.receivable-form-page .form-label{color:#374151;font-size:13px;font-weight:700}
.receivable-form-page .form-control{width:100%;min-height:42px;padding:10px 12px;border:1px solid #d1d5db;border-radius:10px;background:#fff;color:#111827;font:inherit;font-size:14px;outline:none;box-shadow:0 1px 2px rgba(15,23,42,.035);transition:border-color 160ms ease,box-shadow 160ms ease}
.receivable-form-page .form-control:focus{border-color:var(--primary);box-shadow:0 0 0 3px rgba(37,99,235,.10)}
.receivable-form-page .actions{display:flex;justify-content:flex-end;flex-wrap:wrap;gap:8px;padding-top:18px;border-top:1px solid #e5e7eb}
.receivable-form-page .btn{display:inline-flex;min-height:40px;align-items:center;justify-content:center;padding:9px 14px;border-radius:10px;font-size:13px;font-weight:700;text-decoration:none}
.receivable-form-page .btn-secondary{border:1px solid #d1d5db;background:#fff;color:#374151}
.receivable-form-page .alert-danger{padding:12px 14px;border:1px solid #fecaca;border-radius:10px;background:#fef2f2;color:#b91c1c;font-size:13px}
.receivable-form-page .summary-card .form-grid{grid-template-columns:repeat(2,minmax(0,1fr))}
.receivable-form-page .summary-card strong{font-size:16px;color:#111827}
@media(max-width:700px){.receivable-form-page .page-header{align-items:flex-start;flex-direction:column}.receivable-form-page .form-grid,.receivable-form-page .summary-card .form-grid{grid-template-columns:1fr}.receivable-form-page .card{padding:18px}}
</style>

<div class="receivable-form-page">
    <div class="page-header">
        <div>
            <span class="eyebrow">
                {{ __('receivables.navigation') }}
            </span>

            <h1>
                {{ __('receivables.edit_title') }}
            </h1>

            <p>
                {{ __('receivables.edit_description') }}
            </p>
        </div>

        <div class="actions">
            <a
                class="btn btn-secondary"
                href="{{ route('receivables.index') }}"
            >
                {{ __('receivables.back') }}
            </a>
        </div>
    </div>

    <div class="card summary-card">
        <div class="form-grid">
            <div class="form-group">
                <span class="form-label">
                    {{ __('receivables.fields.status') }}
                </span>

                <strong>
                    {{
                        __(
                            'receivables.statuses.'
                            . $receivable->status->value
                        )
                    }}
                </strong>
            </div>

            @if ($receivable->paid_at)
                <div class="form-group">
                    <span class="form-label">
                        {{ __('receivables.fields.paid_at') }}
                    </span>

                    <strong>
                        {{
                            $receivable->paid_at->format(
                                'd/m/Y H:i'
                            )
                        }}
                    </strong>
                </div>
            @endif
        </div>
    </div>

    @if (
        $receivable->status
        ===
        \App\Enums\ReceivableStatus::PENDING
    )
        <div class="card">
            <form
                method="POST"
                action="{{ route(
                    'receivables.update',
                    $receivable->id
                ) }}"
            >
                @csrf
                @method('PUT')

                @include('receivables._form')

                <div class="actions">
                    <button
                        class="btn btn-primary"
                        type="submit"
                    >
                        {{ __('receivables.update_action') }}
                    </button>
                </div>
            </form>
        </div>
    @else
        <div class="card">
            <p>
                {{ __('receivables.closed_edit_notice') }}
            </p>
        </div>
    @endif
</div>
@endsection
