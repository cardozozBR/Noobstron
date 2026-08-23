@extends('layouts.app')

@section(
    'title',
    __('opportunities.title')
)

@section('content')
@php
    $tenantWriteAllowed = app(
        \App\Services\TenantWriteAccessService::class
    )->allowed(
        app(\App\Services\TenantContext::class)->get()
    );
@endphp

@if (! $tenantWriteAllowed)
    @include('components.subscription-read-only-notice')
@endif



<style>
    .opportunities-index-page {
        display: grid;
        gap: 24px;
    }

    .opportunities-index-page .page-header {
        display: flex;
        align-items: flex-end;
        justify-content: space-between;
        gap: 18px;
        margin: 0;
    }

    .opportunities-index-page .page-header h1 {
        margin: 2px 0 0;
        font-size: 30px;
        line-height: 1.15;
        letter-spacing: -0.025em;
    }

    .opportunities-index-page .page-header p {
        margin: 6px 0 0;
        color: #6b7280;
        font-size: 14px;
    }

    .opportunities-index-page .card {
        margin: 0;
        padding: 22px;
        border: 1px solid #e5e7eb;
        border-radius: 16px;
        background: #fff;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
    }

    .opportunities-index-page .filter-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 16px;
    }

    .opportunities-index-page .form-group {
        display: grid;
        gap: 7px;
    }

    .opportunities-index-page .form-label {
        color: #374151;
        font-size: 13px;
        font-weight: 700;
    }

    .opportunities-index-page .form-control {
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

    .opportunities-index-page .form-control:focus {
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.10);
    }

    .opportunities-index-page .form-actions,
    .opportunities-index-page .actions {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-top: 16px;
    }

    .opportunities-index-page .btn {
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

    .opportunities-index-page .btn-secondary {
        border: 1px solid #d1d5db;
        background: #fff;
        color: #374151;
    }

    .opportunities-index-page .table-wrapper {
        overflow-x: auto;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
    }

    .opportunities-index-page .data-table {
        width: 100%;
        margin: 0;
        border-collapse: collapse;
    }

    .opportunities-index-page .data-table thead {
        background: #f9fafb;
    }

    .opportunities-index-page .data-table th {
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

    .opportunities-index-page .data-table td {
        padding: 13px 12px;
        border-bottom: 1px solid #f3f4f6;
        color: #4b5563;
        font-size: 13px;
        vertical-align: middle;
    }

    .opportunities-index-page .data-table tbody tr:hover {
        background: #f9fafb;
    }

    .opportunities-index-page .data-table tbody tr:last-child td {
        border-bottom: 0;
    }

    .opportunities-index-page .data-table td:first-child {
        color: #111827;
        font-weight: 700;
    }

    .opportunities-index-page .data-table td form {
        display: inline-flex !important;
        margin: 2px 3px 2px 0;
    }

    .opportunities-index-page .data-table td a,
    .opportunities-index-page .data-table td button {
        display: inline-flex;
        min-height: 32px;
        align-items: center;
        justify-content: center;
        padding: 6px 9px;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        background: #fff;
        color: #374151;
        font: inherit;
        font-size: 11px;
        font-weight: 700;
        text-decoration: none;
        cursor: pointer;
    }

    .opportunities-index-page .data-table td a.btn-primary {
        border-color: transparent;
        background: var(--primary);
        color: #fff !important;
    }

    .opportunities-index-page .badge {
        display: inline-flex;
        align-items: center;
        padding: 5px 8px;
        border: 1px solid #dbeafe;
        border-radius: 999px;
        background: #eff6ff;
        color: #1d4ed8;
        font-size: 11px;
        font-weight: 700;
    }

    .opportunities-index-page .empty-state {
        padding: 44px 24px;
        border: 1px dashed #d1d5db;
        border-radius: 12px;
        background: #fafafa;
        color: #6b7280;
        text-align: center;
    }

    @media (max-width: 1100px) {
        .opportunities-index-page .filter-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 700px) {
        .opportunities-index-page .page-header {
            align-items: flex-start;
            flex-direction: column;
        }

        .opportunities-index-page .filter-grid {
            grid-template-columns: 1fr;
        }

        .opportunities-index-page .card {
            padding: 18px;
        }
    }
</style>

<div class="opportunities-index-page">
    <div class="page-header">
        <div>
            <span class="eyebrow">
                {{ __('opportunities.navigation') }}
            </span>

            <h1>
                {{ __('opportunities.title') }}
            </h1>

            <p>
                {{ __('opportunities.index_description') }}
            </p>
        </div>

        @if (
            auth()->user()->hasPermission(
                \App\Enums\Permission::OPPORTUNITIES_CREATE
            )
            && $tenantWriteAllowed
        )
            <div class="actions">
                <a
                    class="btn btn-primary"
                    href="{{ route('opportunities.create') }}"
                >
                    {{ __('opportunities.new') }}
                </a>
            </div>
        @endif
    </div>

    <div class="card">
        <form
            method="GET"
            action="{{ route('opportunities.index') }}"
        >
            <div class="filter-grid">
                <div class="form-group">
                    <label
                        class="form-label"
                        for="search"
                    >
                        {{ __('opportunities.filters.search') }}
                    </label>

                    <input
                        class="form-control"
                        id="search"
                        name="search"
                        type="search"
                        value="{{ request('search') }}"
                        placeholder="{{ __('opportunities.filters.search_placeholder') }}"
                    >
                </div>

                <div class="form-group">
                    <label
                        class="form-label"
                        for="customer_id"
                    >
                        {{ __('opportunities.fields.customer') }}
                    </label>

                    <select
                        class="form-control"
                        id="customer_id"
                        name="customer_id"
                    >
                        <option value="">
                            {{ __('opportunities.filters.all_customers') }}
                        </option>

                        @foreach ($customers as $customer)
                            <option
                                value="{{ $customer->id }}"
                                @selected(
                                    (string) request('customer_id')
                                    === (string) $customer->id
                                )
                            >
                                {{ $customer->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label
                        class="form-label"
                        for="pipeline_id"
                    >
                        {{ __('opportunities.fields.pipeline') }}
                    </label>

                    <select
                        class="form-control"
                        id="pipeline_id"
                        name="pipeline_id"
                    >
                        <option value="">
                            {{ __('opportunities.filters.all_pipelines') }}
                        </option>

                        @foreach ($pipelines as $pipeline)
                            <option
                                value="{{ $pipeline->id }}"
                                @selected(
                                    (string) request('pipeline_id')
                                    === (string) $pipeline->id
                                )
                            >
                                {{ $pipeline->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label
                        class="form-label"
                        for="pipeline_stage_id"
                    >
                        {{ __('opportunities.fields.stage') }}
                    </label>

                    <select
                        class="form-control"
                        id="pipeline_stage_id"
                        name="pipeline_stage_id"
                    >
                        <option value="">
                            {{ __('opportunities.filters.all_stages') }}
                        </option>

                        @foreach ($pipelines as $pipeline)
                            <optgroup label="{{ $pipeline->name }}">
                                @foreach ($pipeline->stages as $stage)
                                    <option
                                        value="{{ $stage->id }}"
                                        @selected(
                                            (string) request(
                                                'pipeline_stage_id'
                                            ) === (string) $stage->id
                                        )
                                    >
                                        {{ $stage->name }}
                                    </option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label
                        class="form-label"
                        for="responsible_user_id"
                    >
                        {{ __('opportunities.fields.responsible') }}
                    </label>

                    <select
                        class="form-control"
                        id="responsible_user_id"
                        name="responsible_user_id"
                    >
                        <option value="">
                            {{ __('opportunities.filters.all_responsibles') }}
                        </option>

                        @foreach ($responsibles as $responsible)
                            <option
                                value="{{ $responsible->id }}"
                                @selected(
                                    (string) request(
                                        'responsible_user_id'
                                    ) === (string) $responsible->id
                                )
                            >
                                {{ $responsible->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="form-actions">
                <button
                    class="btn btn-primary"
                    type="submit"
                >
                    {{ __('opportunities.filters.filter') }}
                </button>

                <a
                    class="btn btn-secondary"
                    href="{{ route('opportunities.index') }}"
                >
                    {{ __('opportunities.filters.clear') }}
                </a>
            </div>
        </form>
    </div>

    <div class="card">
        @if ($opportunities->count() === 0)
            <div class="empty-state">
                {{ __('opportunities.empty') }}
            </div>
        @else
            <div class="table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>{{ __('opportunities.fields.name') }}</th>
                            <th>{{ __('opportunities.fields.customer') }}</th>
                            <th>{{ __('opportunities.fields.pipeline') }}</th>
                            <th>{{ __('opportunities.fields.stage') }}</th>
                            <th>{{ __('opportunities.fields.responsible') }}</th>
                            <th>{{ __('opportunities.fields.value_minor') }}</th>
                            <th>{{ __('opportunities.fields.probability') }}</th>
                            <th>{{ __('opportunities.fields.expected_close_date') }}</th>
                            <th>{{ __('opportunities.actions_column') }}</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($opportunities as $opportunity)
                            <tr>
                                <td>
                                    {{ $opportunity->name }}
                                </td>

                                <td>
                                    {{ $opportunity->customer->name }}
                                </td>

                                <td>
                                    {{ $opportunity->pipeline->name }}
                                </td>

                                <td>
                                    @if (
                                        $tenantWriteAllowed
                                        && auth()->user()->hasPermission(
                                            \App\Enums\Permission::OPPORTUNITIES_UPDATE
                                        )
                                    )
                                        <form
                                            method="POST"
                                            action="{{ route(
                                                'opportunities.stage',
                                                $opportunity->id
                                            ) }}"
                                        >
                                            @csrf

                                            <select
                                                class="form-control"
                                                name="pipeline_stage_id"
                                                onchange="this.form.submit()"
                                            >
                                                @foreach ($pipelines as $pipeline)
                                                    <optgroup
                                                        label="{{ $pipeline->name }}"
                                                    >
                                                        @foreach ($pipeline->stages as $stage)
                                                            <option
                                                                value="{{ $stage->id }}"
                                                                @selected(
                                                                    $stage->id
                                                                    === $opportunity->pipeline_stage_id
                                                                )
                                                            >
                                                                {{ $stage->name }}
                                                            </option>
                                                        @endforeach
                                                    </optgroup>
                                                @endforeach
                                            </select>
                                        </form>
                                    @else
                                        {{ $opportunity->stage->name }}
                                    @endif
                                </td>

                                <td>
                                    {{
                                        $opportunity->responsible?->name
                                        ?? __('opportunities.responsible_none')
                                    }}
                                </td>

                                <td class="nowrap">
                                    {{ $opportunity->value_minor }}
                                    {{ $opportunity->currency }}
                                </td>

                                <td>
                                    {{ $opportunity->probability }}%
                                </td>

                                <td class="nowrap">
                                    {{
                                        $opportunity->expected_close_date
                                            ? $opportunity
                                                ->expected_close_date
                                                ->format('Y-m-d')
                                            : '—'
                                    }}
                                </td>

                                <td class="nowrap">
                                    @php
                                        $sale = $opportunity
                                            ->sales
                                            ->first();

                                        $salesEnabled = app(
                                            \App\Support\TenantCapabilities::class
                                        )->enabled(
                                            app(
                                                \App\Services\TenantContext::class
                                            )->get(),
                                            \App\Enums\Feature::SALES
                                        );

                                        $canCloseSale =
                                            $tenantWriteAllowed
                                            && $salesEnabled
                                            && auth()
                                                ->user()
                                                ->hasPermission(
                                                    \App\Enums\Permission::SALES_CREATE
                                                );
                                    @endphp

                                    @if ($sale)
                                        <span class="badge">
                                            {{ __('sales.closed_badge') }}
                                            —
                                            {{ $sale->number }}
                                        </span>
                                    @elseif ($canCloseSale)
                                        <a
                                            class="btn btn-primary"
                                            href="{{ route(
                                                'sales.create',
                                                $opportunity->id
                                            ) }}"
                                        >
                                            {{ __('sales.close_action') }}
                                        </a>
                                    @endif

                                    @if (
                                        $tenantWriteAllowed
                                        && auth()->user()->hasPermission(
                                            \App\Enums\Permission::OPPORTUNITIES_UPDATE
                                        )
                                    )
                                        <a
                                            href="{{ route(
                                                'opportunities.edit',
                                                $opportunity->id
                                            ) }}"
                                        >
                                            {{ __('opportunities.actions.edit') }}
                                        </a>
                                    @endif

                                    @if (
                                        $tenantWriteAllowed
                                        && auth()->user()->hasPermission(
                                            \App\Enums\Permission::OPPORTUNITIES_DELETE
                                        )
                                    )
                                        <form
                                            method="POST"
                                            action="{{ route(
                                                'opportunities.destroy',
                                                $opportunity->id
                                            ) }}"
                                            style="display:inline"
                                        >
                                            @csrf
                                            @method('DELETE')

                                            <button
                                                type="submit"
                                                onclick="return confirm(
                                                    '{{ __('opportunities.confirm_delete') }}'
                                                )"
                                            >
                                                {{ __('opportunities.actions.delete') }}
                                            </button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{ $opportunities->links() }}
        @endif
    </div>
</div>
@endsection
