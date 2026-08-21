@extends('layouts.app')

@section('title', __('charges.title'))

@section('content')


<style>
.charges-index-page{display:grid;gap:24px}
.charges-index-page .page-header{display:flex;align-items:flex-end;justify-content:space-between;gap:18px;margin:0}
.charges-index-page .page-header h1{margin:2px 0 0;font-size:30px;line-height:1.15;letter-spacing:-.025em}
.charges-index-page .page-header p{margin:6px 0 0;color:#6b7280;font-size:14px}
.charges-index-page .card{margin:0;padding:22px;border:1px solid #e5e7eb;border-radius:16px;background:#fff;box-shadow:0 1px 2px rgba(15,23,42,.04)}
.charges-index-page .alert-success,.charges-index-page .alert-danger{padding:12px 14px;border-radius:10px;font-size:13px;font-weight:600}
.charges-index-page .alert-success{border:1px solid #bbf7d0;background:#f0fdf4;color:#166534}
.charges-index-page .alert-danger{border:1px solid #fecaca;background:#fef2f2;color:#b91c1c}
.charges-index-page .table-responsive{overflow-x:auto;border:1px solid #e5e7eb;border-radius:12px}
.charges-index-page table{width:100%;border-collapse:collapse;background:#fff}
.charges-index-page thead{background:#f9fafb}
.charges-index-page th{padding:11px 12px;border-bottom:1px solid #e5e7eb;color:#6b7280;font-size:11px;font-weight:700;letter-spacing:.055em;text-align:left;text-transform:uppercase;white-space:nowrap}
.charges-index-page td{padding:13px 12px;border-bottom:1px solid #f3f4f6;color:#4b5563;font-size:13px;vertical-align:middle}
.charges-index-page tbody tr:hover{background:#f9fafb}
.charges-index-page tbody tr:last-child td{border-bottom:0}
.charges-index-page td:first-child{color:#111827;font-weight:700}
.charges-index-page .actions{display:flex;flex-wrap:wrap;gap:8px}
.charges-index-page td form{display:inline-flex;align-items:center;gap:6px;margin:2px 0}
.charges-index-page td form input{min-height:34px;padding:7px 9px;border:1px solid #d1d5db;border-radius:8px;background:#fff;font-size:12px;outline:none}
.charges-index-page td form input:focus{border-color:var(--primary);box-shadow:0 0 0 3px rgba(37,99,235,.08)}
.charges-index-page .btn{display:inline-flex;min-height:36px;align-items:center;justify-content:center;padding:7px 10px;border-radius:9px;font-size:12px;font-weight:700;text-decoration:none}
.charges-index-page .btn-secondary{border:1px solid #d1d5db;background:#fff;color:#374151}
.charges-index-page .status-badge{display:inline-flex;padding:4px 8px;border-radius:999px;background:#f3f4f6;color:#374151;font-size:11px;font-weight:700}
.charges-index-page .details-row td{background:#fafafa;color:#6b7280;font-size:12px}
.charges-index-page .details-row strong{color:#374151}
.charges-index-page .empty-state{padding:44px 24px;border:1px dashed #d1d5db;border-radius:12px;background:#fafafa;color:#6b7280;text-align:center}
@media(max-width:800px){.charges-index-page .page-header{align-items:flex-start;flex-direction:column}.charges-index-page .card{padding:18px}}
</style>

<div class="charges-index-page">
    <div class="page-header">
        <div>
            <span class="eyebrow">
                {{ __('charges.navigation') }}
            </span>

            <h1>
                {{ __('charges.title') }}
            </h1>

            <p>
                {{ __('charges.description') }}
            </p>
        </div>

        @if (
            auth()->user()->hasPermission(
                \App\Enums\Permission::CHARGES_CREATE
            )
        )
            <div class="actions">
                <a
                    class="btn btn-primary"
                    href="{{ route('charges.create') }}"
                >
                    {{ __('charges.create_action') }}
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
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card">
        @if ($charges->isEmpty())
            <div class="empty-state">
                {{ __('charges.empty') }}
            </div>
        @else
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>{{ __('charges.fields.receivable') }}</th>
                            <th>{{ __('charges.fields.customer') }}</th>
                            <th>{{ __('charges.fields.attempt') }}</th>
                            <th>{{ __('charges.fields.status') }}</th>
                            <th>{{ __('charges.fields.scheduled_at') }}</th>
                            <th>{{ __('charges.fields.channel') }}</th>
                            <th>{{ __('charges.fields.recipient') }}</th>
                            <th>{{ __('charges.actions') }}</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($charges as $charge)
                            <tr>
                                <td>
                                    {{ $charge->receivable?->title }}
                                </td>

                                <td>
                                    {{ $charge->receivable?->customer?->name }}
                                </td>

                                <td>
                                    {{ $charge->attempt }}
                                </td>

                                <td>
                                    <span class="status-badge">
                                        {{
                                            __(
                                                'charges.statuses.'
                                                . $charge->status->value
                                            )
                                        }}
                                    </span>
                                </td>

                                <td>
                                    {{
                                        $charge->scheduled_at
                                            ?->format('d/m/Y H:i')
                                        ?? '—'
                                    }}
                                </td>

                                <td>
                                    {{ $charge->channel ?? '—' }}
                                </td>

                                <td>
                                    {{ $charge->recipient ?? '—' }}
                                </td>

                                <td>
                                    @if (
                                        auth()->user()->hasPermission(
                                            \App\Enums\Permission::CHARGES_UPDATE
                                        )
                                    )
                                        <div class="actions">
                                            @if (
                                                $charge->status
                                                ===
                                                \App\Enums\ChargeStatus::PENDING
                                            )
                                                <form
                                                    method="POST"
                                                    action="{{ route(
                                                        'charges.sent',
                                                        $charge->id
                                                    ) }}"
                                                >
                                                    @csrf

                                                    <input
                                                        name="external_reference"
                                                        placeholder="{{
                                                            __('charges.external_reference_placeholder')
                                                        }}"
                                                    >

                                                    <button
                                                        class="btn btn-primary"
                                                        type="submit"
                                                    >
                                                        {{ __('charges.sent_action') }}
                                                    </button>
                                                </form>
                                            @endif

                                            @if (
                                                in_array(
                                                    $charge->status,
                                                    [
                                                        \App\Enums\ChargeStatus::PENDING,
                                                        \App\Enums\ChargeStatus::SENT,
                                                    ],
                                                    true
                                                )
                                            )
                                                <form
                                                    method="POST"
                                                    action="{{ route(
                                                        'charges.failed',
                                                        $charge->id
                                                    ) }}"
                                                >
                                                    @csrf

                                                    <input
                                                        name="failure_reason"
                                                        required
                                                        placeholder="{{
                                                            __('charges.failure_reason_placeholder')
                                                        }}"
                                                    >

                                                    <button
                                                        class="btn btn-secondary"
                                                        type="submit"
                                                    >
                                                        {{ __('charges.failed_action') }}
                                                    </button>
                                                </form>
                                            @endif

                                            @if (
                                                in_array(
                                                    $charge->status,
                                                    [
                                                        \App\Enums\ChargeStatus::PENDING,
                                                        \App\Enums\ChargeStatus::SENT,
                                                        \App\Enums\ChargeStatus::FAILED,
                                                    ],
                                                    true
                                                )
                                            )
                                                <form
                                                    method="POST"
                                                    action="{{ route(
                                                        'charges.cancel',
                                                        $charge->id
                                                    ) }}"
                                                >
                                                    @csrf

                                                    <button
                                                        class="btn btn-secondary"
                                                        type="submit"
                                                    >
                                                        {{ __('charges.cancel_action') }}
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    @endif
                                </td>
                            </tr>

                            @if (
                                $charge->failure_reason
                                ||
                                $charge->external_reference
                            )
                                <tr class="details-row">
                                    <td colspan="8">
                                        @if ($charge->external_reference)
                                            <strong>
                                                {{ __('charges.fields.external_reference') }}:
                                            </strong>

                                            {{ $charge->external_reference }}
                                        @endif

                                        @if ($charge->failure_reason)
                                            <strong>
                                                {{ __('charges.fields.failure_reason') }}:
                                            </strong>

                                            {{ $charge->failure_reason }}
                                        @endif
                                    </td>
                                </tr>
                            @endif
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{ $charges->links() }}
        @endif
    </div>
</div>
@endsection
