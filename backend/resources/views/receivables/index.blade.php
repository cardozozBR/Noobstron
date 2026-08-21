@extends('layouts.app')

@section(
    'title',
    __('receivables.title')
)

@section('content')


<style>
.receivables-index-page{display:grid;gap:24px}
.receivables-index-page .page-header{display:flex;align-items:flex-end;justify-content:space-between;gap:18px;margin:0}
.receivables-index-page .page-header h1{margin:2px 0 0;font-size:30px;line-height:1.15;letter-spacing:-.025em}
.receivables-index-page .page-header p{margin:6px 0 0;color:#6b7280;font-size:14px}
.receivables-index-page .card{margin:0;padding:22px;border:1px solid #e5e7eb;border-radius:16px;background:#fff;box-shadow:0 1px 2px rgba(15,23,42,.04)}
.receivables-index-page .alert-success,.receivables-index-page .alert-danger{padding:12px 14px;border-radius:10px;font-size:13px;font-weight:600}
.receivables-index-page .alert-success{border:1px solid #bbf7d0;background:#f0fdf4;color:#166534}
.receivables-index-page .alert-danger{border:1px solid #fecaca;background:#fef2f2;color:#b91c1c}
.receivables-index-page .table-responsive{overflow-x:auto;border:1px solid #e5e7eb;border-radius:12px}
.receivables-index-page table{width:100%;border-collapse:collapse;background:#fff}
.receivables-index-page thead{background:#f9fafb}
.receivables-index-page th{padding:11px 12px;border-bottom:1px solid #e5e7eb;color:#6b7280;font-size:11px;font-weight:700;letter-spacing:.055em;text-align:left;text-transform:uppercase;white-space:nowrap}
.receivables-index-page td{padding:13px 12px;border-bottom:1px solid #f3f4f6;color:#4b5563;font-size:13px;vertical-align:middle}
.receivables-index-page tbody tr:hover{background:#f9fafb}
.receivables-index-page tbody tr:last-child td{border-bottom:0}
.receivables-index-page td:first-child strong{color:#111827}
.receivables-index-page .actions{display:flex;flex-wrap:wrap;gap:8px}
.receivables-index-page .btn{display:inline-flex;min-height:36px;align-items:center;justify-content:center;padding:7px 10px;border-radius:9px;font-size:12px;font-weight:700;text-decoration:none}
.receivables-index-page .btn-secondary{border:1px solid #d1d5db;background:#fff;color:#374151}
.receivables-index-page td form{display:inline-flex;gap:6px;align-items:center;margin:2px 0}
.receivables-index-page td form input{min-height:34px;padding:7px 9px;border:1px solid #d1d5db;border-radius:8px;font-size:12px}
.receivables-index-page .status-badge{display:inline-flex;padding:4px 8px;border-radius:999px;background:#f3f4f6;color:#374151;font-size:11px;font-weight:700}
.receivables-index-page .empty-state{padding:44px 24px;border:1px dashed #d1d5db;border-radius:12px;background:#fafafa;color:#6b7280;text-align:center}
@media(max-width:800px){.receivables-index-page .page-header{align-items:flex-start;flex-direction:column}.receivables-index-page .card{padding:18px}}
</style>

<div class="receivables-index-page">
    <div class="page-header">
        <div>
            <span class="eyebrow">
                {{ __('receivables.navigation') }}
            </span>

            <h1>
                {{ __('receivables.title') }}
            </h1>

            <p>
                {{ __('receivables.index_description') }}
            </p>
        </div>

        @if (
            auth()->user()->hasPermission(
                \App\Enums\Permission::RECEIVABLES_CREATE
            )
        )
            <div class="actions">
                <a
                    class="btn btn-primary"
                    href="{{ route('receivables.create') }}"
                >
                    {{ __('receivables.create_action') }}
                </a>
            </div>
        @endif
    </div>

    @if (session('status'))
        <div class="alert alert-success">
            {{ session('status') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>
                        {{ $error }}
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card">
        @if ($receivables->isEmpty())
            <div class="empty-state">
                {{ __('receivables.empty') }}
            </div>
        @else
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>
                                {{ __('receivables.fields.title') }}
                            </th>

                            <th>
                                {{ __('receivables.fields.customer') }}
                            </th>

                            <th>
                                {{ __('receivables.fields.sale') }}
                            </th>

                            <th>
                                {{ __('receivables.fields.amount') }}
                            </th>

                            <th>
                                {{ __('receivables.fields.due_date') }}
                            </th>

                            <th>
                                {{ __('receivables.fields.status') }}
                            </th>

                            <th>
                                {{ __('receivables.fields.payment') }}
                            </th>

                            <th>
                                {{ __('receivables.actions') }}
                            </th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($receivables as $receivable)
                            <tr>
                                <td>
                                    <strong>
                                        {{ $receivable->title }}
                                    </strong>
                                </td>

                                <td>
                                    {{ $receivable->customer?->name }}
                                </td>

                                <td>
                                    {{ $receivable->sale?->number ?? '—' }}
                                </td>

                                <td>
                                    {{ $receivable->currency }}
                                    {{
                                        number_format(
                                            $receivable->amount_minor / 100,
                                            2,
                                            ',',
                                            '.'
                                        )
                                    }}
                                </td>

                                <td>
                                    {{
                                        $receivable->due_date
                                            ?->format('d/m/Y')
                                    }}
                                </td>

                                <td>
                                    <span class="status-badge">
                                        {{
                                            __(
                                                'receivables.statuses.'
                                                . $receivable->status->value
                                            )
                                        }}
                                    </span>
                                </td>

                                <td>
                                    @if (
                                        $receivable->status
                                        ===
                                        \App\Enums\ReceivableStatus::PAID
                                    )
                                        <div>
                                            {{
                                                $receivable->paid_at
                                                    ?->format(
                                                        'd/m/Y H:i'
                                                    )
                                            }}
                                        </div>

                                        @if (
                                            $receivable->payment_reference
                                        )
                                            <small>
                                                {{
                                                    $receivable
                                                        ->payment_reference
                                                }}
                                            </small>
                                        @endif
                                    @else
                                        —
                                    @endif
                                </td>

                                <td>
                                    <div class="actions">
                                        @if (
                                            auth()->user()->hasPermission(
                                                \App\Enums\Permission::RECEIVABLES_UPDATE
                                            )
                                        )
                                            <a
                                                class="btn btn-secondary"
                                                href="{{ route(
                                                    'receivables.edit',
                                                    $receivable->id
                                                ) }}"
                                            >
                                                {{ __('receivables.edit_action') }}
                                            </a>

                                            @if (
                                                $receivable->status
                                                ===
                                                \App\Enums\ReceivableStatus::PENDING
                                            )
                                                <form
                                                    method="POST"
                                                    action="{{ route(
                                                        'receivables.pay',
                                                        $receivable->id
                                                    ) }}"
                                                >
                                                    @csrf

                                                    <input
                                                        name="payment_reference"
                                                        placeholder="{{
                                                            __('receivables.payment_reference_placeholder')
                                                        }}"
                                                    >

                                                    <button
                                                        class="btn btn-primary"
                                                        type="submit"
                                                    >
                                                        {{ __('receivables.pay_action') }}
                                                    </button>
                                                </form>

                                                <form
                                                    method="POST"
                                                    action="{{ route(
                                                        'receivables.cancel',
                                                        $receivable->id
                                                    ) }}"
                                                >
                                                    @csrf

                                                    <button
                                                        class="btn btn-secondary"
                                                        type="submit"
                                                    >
                                                        {{ __('receivables.cancel_action') }}
                                                    </button>
                                                </form>
                                            @endif
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{ $receivables->links() }}
        @endif
    </div>
</div>
@endsection
