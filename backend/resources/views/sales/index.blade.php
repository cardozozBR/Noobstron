@extends('layouts.app')

@section(
    'title',
    __('sales.title')
)

@section('content')


<style>
    .sales-index-page {
        display: grid;
        gap: 24px;
    }

    .sales-index-page .page-header {
        display: flex;
        align-items: flex-end;
        justify-content: space-between;
        gap: 18px;
        margin: 0;
    }

    .sales-index-page .page-header h1 {
        margin: 2px 0 0;
        font-size: 30px;
        line-height: 1.15;
        letter-spacing: -0.025em;
    }

    .sales-index-page .page-header p {
        margin: 6px 0 0;
        color: #6b7280;
        font-size: 14px;
    }

    .sales-index-page .card {
        margin: 0;
        padding: 22px;
        border: 1px solid #e5e7eb;
        border-radius: 16px;
        background: #fff;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
    }

    .sales-index-page .alert-success {
        padding: 12px 14px;
        border: 1px solid #bbf7d0;
        border-radius: 10px;
        background: #f0fdf4;
        color: #166534;
        font-size: 13px;
        font-weight: 600;
    }

    .sales-index-page .table-responsive {
        overflow-x: auto;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
    }

    .sales-index-page table {
        width: 100%;
        border-collapse: collapse;
        background: #fff;
    }

    .sales-index-page thead {
        background: #f9fafb;
    }

    .sales-index-page th {
        padding: 11px 12px;
        border-bottom: 1px solid #e5e7eb;
        color: #6b7280;
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 0.055em;
        text-align: left;
        text-transform: uppercase;
        white-space: nowrap;
    }

    .sales-index-page td {
        padding: 13px 12px;
        border-bottom: 1px solid #f3f4f6;
        color: #4b5563;
        font-size: 13px;
        vertical-align: middle;
    }

    .sales-index-page tbody tr:hover {
        background: #f9fafb;
    }

    .sales-index-page tbody tr:last-child td {
        border-bottom: 0;
    }

    .sales-index-page td:first-child strong {
        color: #111827;
    }

    .sales-index-page code {
        display: inline-flex;
        padding: 4px 8px;
        border: 1px solid #dbeafe;
        border-radius: 999px;
        background: #eff6ff;
        color: #1d4ed8;
        font-size: 11px;
        font-weight: 700;
    }

    .sales-index-page .section-header {
        margin-bottom: 16px;
    }

    .sales-index-page .section-header h2 {
        margin: 2px 0 0;
        font-size: 19px;
    }

    .sales-index-page .empty-state {
        padding: 44px 24px;
        border: 1px dashed #d1d5db;
        border-radius: 12px;
        background: #fafafa;
        color: #6b7280;
        text-align: center;
    }

    @media (max-width: 700px) {
        .sales-index-page .card {
            padding: 18px;
        }
    }
</style>

<div class="sales-index-page">
    <div class="page-header">
        <div>
            <span class="eyebrow">
                {{ __('sales.navigation') }}
            </span>

            <h1>
                {{ __('sales.title') }}
            </h1>

            <p>
                {{ __('sales.index_description') }}
            </p>
        </div>
    </div>

    @if (session('status'))
        <div class="alert alert-success">
            {{ session('status') }}
        </div>
    @endif

    <div class="card">
        @if ($sales->isEmpty())
            <div class="empty-state">
                {{ __('sales.empty') }}
            </div>
        @else
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>
                                {{ __('sales.fields.number') }}
                            </th>

                            <th>
                                {{ __('sales.fields.customer') }}
                            </th>

                            <th>
                                {{ __('sales.fields.opportunity') }}
                            </th>

                            <th>
                                {{ __('sales.fields.proposal') }}
                            </th>

                            <th>
                                {{ __('sales.fields.total') }}
                            </th>

                            <th>
                                {{ __('sales.fields.closed_at') }}
                            </th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($sales as $sale)
                            <tr>
                                <td>
                                    <strong>
                                        {{ $sale->number }}
                                    </strong>
                                </td>

                                <td>
                                    {{ $sale->customer_name }}
                                </td>

                                <td>
                                    {{ $sale->opportunity_title }}
                                </td>

                                <td>
                                    {{ $sale->proposal_number ?? '—' }}
                                </td>

                                <td>
                                    {{ $sale->currency }}
                                    {{ number_format(
                                        $sale->total_minor / 100,
                                        2,
                                        ',',
                                        '.'
                                    ) }}
                                </td>

                                <td>
                                    {{ $sale->closed_at?->format(
                                        'd/m/Y H:i'
                                    ) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{ $sales->links() }}
        @endif
    </div>

    <div class="card">
        <div class="section-header">
            <div>
                <span class="eyebrow">
                    {{ __('sales.history.eyebrow') }}
                </span>

                <h2>
                    {{ __('sales.history.title') }}
                </h2>
            </div>
        </div>

        @if ($history->isEmpty())
            <div class="empty-state">
                {{ __('sales.history.empty') }}
            </div>
        @else
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>
                                {{ __('sales.history.date') }}
                            </th>

                            <th>
                                {{ __('sales.history.user') }}
                            </th>

                            <th>
                                {{ __('sales.history.action') }}
                            </th>

                            <th>
                                {{ __('sales.history.description') }}
                            </th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($history as $log)
                            <tr>
                                <td class="nowrap">
                                    {{
                                        $log->created_at
                                            ?->timezone(
                                                $tenant->timezone
                                            )
                                            ?->format(
                                                'd/m/Y H:i'
                                            )
                                    }}
                                </td>

                                <td>
                                    {{
                                        $log->user?->name
                                        ?? __('sales.history.system')
                                    }}
                                </td>

                                <td>
                                    <code>
                                        {{ $log->action }}
                                    </code>
                                </td>

                                <td>
                                    {{ $log->description }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection
