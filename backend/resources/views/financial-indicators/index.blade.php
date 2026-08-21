@extends('layouts.app')

@section('title', __('financial_indicators.title'))

@section('content')


<style>
    .financial-indicators-page {
        display: grid;
        gap: 24px;
    }

    .financial-indicators-page .page-header {
        display: flex;
        align-items: flex-end;
        justify-content: space-between;
        gap: 18px;
        margin: 0;
    }

    .financial-indicators-page .page-header h1 {
        margin: 2px 0 0;
        font-size: 30px;
        line-height: 1.15;
        letter-spacing: -0.025em;
    }

    .financial-indicators-page .page-header p {
        margin: 6px 0 0;
        color: #6b7280;
        font-size: 14px;
    }

    .financial-indicators-page .card {
        margin: 0;
        padding: 22px;
        border: 1px solid #e5e7eb;
        border-radius: 16px;
        background: #fff;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
    }

    .financial-indicators-page .card > h2 {
        margin: 0 0 16px;
        font-size: 19px;
        color: #111827;
    }

    .financial-indicators-page .form-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 16px;
    }

    .financial-indicators-page .form-group {
        display: grid;
        gap: 7px;
    }

    .financial-indicators-page .form-label {
        color: #374151;
        font-size: 13px;
        font-weight: 700;
    }

    .financial-indicators-page .form-control {
        width: 100%;
        min-height: 42px;
        padding: 10px 12px;
        border: 1px solid #d1d5db;
        border-radius: 10px;
        background: #fff;
        color: #111827;
        font: inherit;
        font-size: 14px;
        outline: none;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.035);
        transition: border-color 160ms ease, box-shadow 160ms ease;
    }

    .financial-indicators-page .form-control:focus {
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.10);
    }

    .financial-indicators-page .actions {
        display: flex;
        justify-content: flex-end;
        margin-top: 16px;
    }

    .financial-indicators-page .btn {
        display: inline-flex;
        min-height: 40px;
        align-items: center;
        justify-content: center;
        padding: 9px 14px;
        border-radius: 10px;
        font-size: 13px;
        font-weight: 700;
        text-decoration: none;
    }

    .financial-indicators-page .dashboard-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 14px;
    }

    .financial-indicators-page .dashboard-grid .card {
        min-height: 128px;
        display: flex;
        flex-direction: column;
        justify-content: center;
        padding: 18px;
        transition: transform 160ms ease, box-shadow 160ms ease, border-color 160ms ease;
    }

    .financial-indicators-page .dashboard-grid .card:hover {
        transform: translateY(-1px);
        border-color: #d1d5db;
        box-shadow: 0 8px 22px rgba(15, 23, 42, 0.06);
    }

    .financial-indicators-page .dashboard-grid .eyebrow {
        margin-bottom: 10px;
        color: #6b7280;
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }

    .financial-indicators-page .dashboard-grid h2 {
        margin: 0;
        font-size: 28px;
        line-height: 1.05;
        letter-spacing: -0.03em;
        color: #111827;
    }

    .financial-indicators-page .table-responsive {
        overflow-x: auto;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
    }

    .financial-indicators-page table {
        width: 100%;
        border-collapse: collapse;
        background: #fff;
    }

    .financial-indicators-page thead {
        background: #f9fafb;
    }

    .financial-indicators-page th {
        padding: 11px 12px;
        border-bottom: 1px solid #e5e7eb;
        color: #6b7280;
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 0.055em;
        text-align: left;
        text-transform: uppercase;
    }

    .financial-indicators-page td {
        padding: 13px 12px;
        border-bottom: 1px solid #f3f4f6;
        color: #4b5563;
        font-size: 13px;
    }

    .financial-indicators-page tbody tr:hover {
        background: #f9fafb;
    }

    .financial-indicators-page tbody tr:last-child td {
        border-bottom: 0;
    }

    .financial-indicators-page td:last-child {
        color: #111827;
        font-weight: 700;
    }

    .financial-indicators-page .empty-state {
        padding: 36px 22px;
        border: 1px dashed #d1d5db;
        border-radius: 12px;
        background: #fafafa;
        color: #6b7280;
        text-align: center;
    }

    @media (max-width: 800px) {
        .financial-indicators-page .dashboard-grid,
        .financial-indicators-page .form-grid {
            grid-template-columns: 1fr;
        }

        .financial-indicators-page .card {
            padding: 18px;
        }
    }
</style>

<div class="financial-indicators-page">
    @php
        $money = static fn (int $minor): string =>
            $tenant->currency
            . ' '
            . number_format(
                $minor / 100,
                2,
                ',',
                '.'
            );
    @endphp

    <div class="page-header">
        <div>
            <span class="eyebrow">
                {{ __('financial_indicators.navigation') }}
            </span>

            <h1>
                {{ __('financial_indicators.title') }}
            </h1>

            <p>
                {{ __('financial_indicators.description') }}
            </p>
        </div>
    </div>

    <div class="card">
        <h2>
            {{ __('financial_indicators.filters') }}
        </h2>

        <form
            method="GET"
            action="{{ route('financial-indicators.index') }}"
        >
            <div class="form-grid">
                <div class="form-group">
                    <label
                        class="form-label"
                        for="from"
                    >
                        {{ __('financial_indicators.from') }}
                    </label>

                    <input
                        class="form-control"
                        id="from"
                        name="from"
                        type="date"
                        value="{{ $from->format('Y-m-d') }}"
                    >
                </div>

                <div class="form-group">
                    <label
                        class="form-label"
                        for="until"
                    >
                        {{ __('financial_indicators.until') }}
                    </label>

                    <input
                        class="form-control"
                        id="until"
                        name="until"
                        type="date"
                        value="{{ $until->format('Y-m-d') }}"
                    >
                </div>
            </div>

            <div class="actions">
                <button
                    class="btn btn-primary"
                    type="submit"
                >
                    {{ __('financial_indicators.apply') }}
                </button>
            </div>
        </form>
    </div>

    <div class="dashboard-grid">
        <div class="card">
            <span class="eyebrow">
                {{ __('financial_indicators.received') }}
            </span>

            <h2>
                {{ $money($summary['received_minor']) }}
            </h2>
        </div>

        <div class="card">
            <span class="eyebrow">
                {{ __('financial_indicators.outstanding') }}
            </span>

            <h2>
                {{ $money($summary['outstanding_minor']) }}
            </h2>
        </div>

        <div class="card">
            <span class="eyebrow">
                {{ __('financial_indicators.overdue') }}
            </span>

            <h2>
                {{ $money($summary['overdue_minor']) }}
            </h2>
        </div>
    </div>

    <div class="card">
        <h2>
            {{ __('financial_indicators.revenue_by_period') }}
        </h2>

        @if ($revenueByPeriod->isEmpty())
            <div class="empty-state">
                {{ __('financial_indicators.empty_period') }}
            </div>
        @else
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>
                                {{ __('financial_indicators.date') }}
                            </th>

                            <th>
                                {{ __('financial_indicators.amount') }}
                            </th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($revenueByPeriod as $row)
                            <tr>
                                <td>
                                    {{ $row['date'] }}
                                </td>

                                <td>
                                    {{ $money($row['amount_minor']) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <div class="card">
        <h2>
            {{ __('financial_indicators.revenue_by_customer') }}
        </h2>

        @if ($revenueByCustomer->isEmpty())
            <div class="empty-state">
                {{ __('financial_indicators.empty_customer') }}
            </div>
        @else
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>
                                {{ __('financial_indicators.customer') }}
                            </th>

                            <th>
                                {{ __('financial_indicators.amount') }}
                            </th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($revenueByCustomer as $row)
                            <tr>
                                <td>
                                    {{ $row['customer_name'] }}
                                </td>

                                <td>
                                    {{ $money($row['amount_minor']) }}
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
