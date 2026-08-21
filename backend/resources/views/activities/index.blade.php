@extends('layouts.app')

@section(
    'title',
    __('activities.title')
)

@section('content')


<style>
    .activities-index-page {
        display: grid;
        gap: 24px;
    }

    .activities-index-page .page-header {
        display: flex;
        align-items: flex-end;
        justify-content: space-between;
        gap: 18px;
        margin: 0;
    }

    .activities-index-page .page-header h1 {
        margin: 2px 0 0;
        font-size: 30px;
        line-height: 1.15;
        letter-spacing: -0.025em;
    }

    .activities-index-page .page-header p {
        margin: 6px 0 0;
        color: #6b7280;
        font-size: 14px;
    }

    .activities-index-page .card {
        margin: 0;
        padding: 22px;
        border: 1px solid #e5e7eb;
        border-radius: 16px;
        background: #fff;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
    }

    .activities-index-page .filter-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 16px;
    }

    .activities-index-page .form-group {
        display: grid;
        gap: 7px;
    }

    .activities-index-page .form-label {
        color: #374151;
        font-size: 13px;
        font-weight: 700;
    }

    .activities-index-page .form-control {
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

    .activities-index-page .form-control:focus {
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.10);
    }

    .activities-index-page .form-actions,
    .activities-index-page .actions {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-top: 16px;
    }

    .activities-index-page .btn {
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

    .activities-index-page .btn-secondary {
        border: 1px solid #d1d5db;
        background: #fff;
        color: #374151;
    }

    .activities-index-page .table-wrapper {
        overflow-x: auto;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
    }

    .activities-index-page .data-table {
        width: 100%;
        margin: 0;
        border-collapse: collapse;
    }

    .activities-index-page .data-table thead {
        background: #f9fafb;
    }

    .activities-index-page .data-table th {
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

    .activities-index-page .data-table td {
        padding: 13px 12px;
        border-bottom: 1px solid #f3f4f6;
        color: #4b5563;
        font-size: 13px;
        vertical-align: middle;
    }

    .activities-index-page .data-table tbody tr:hover {
        background: #f9fafb;
    }

    .activities-index-page .data-table tbody tr:last-child td {
        border-bottom: 0;
    }

    .activities-index-page .data-table td:first-child {
        color: #111827;
        font-weight: 700;
    }

    .activities-index-page .data-table td form {
        display: inline-flex !important;
        margin: 2px 3px 2px 0;
    }

    .activities-index-page .data-table td a,
    .activities-index-page .data-table td button {
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

    .activities-index-page .data-table td button:hover,
    .activities-index-page .data-table td a:hover {
        background: #f9fafb;
    }

    .activities-index-page .empty-state {
        padding: 44px 24px;
        border: 1px dashed #d1d5db;
        border-radius: 12px;
        background: #fafafa;
        color: #6b7280;
        text-align: center;
    }

    @media (max-width: 1000px) {
        .activities-index-page .filter-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 700px) {
        .activities-index-page .page-header {
            align-items: flex-start;
            flex-direction: column;
        }

        .activities-index-page .filter-grid {
            grid-template-columns: 1fr;
        }

        .activities-index-page .card {
            padding: 18px;
        }
    }
</style>

<div class="activities-index-page">
    <div class="page-header">
        <div>
            <span class="eyebrow">
                {{ __('activities.navigation') }}
            </span>

            <h1>{{ __('activities.title') }}</h1>

            <p>
                {{ __('activities.index_description') }}
            </p>
        </div>

        @if (
            auth()->user()->hasPermission(
                \App\Enums\Permission::ACTIVITIES_CREATE
            )
        )
            <div class="actions">
                <a
                    class="btn btn-primary"
                    href="{{ route('activities.create') }}"
                >
                    {{ __('activities.new') }}
                </a>
            </div>
        @endif
    </div>

    <div class="card">
        <form
            method="GET"
            action="{{ route('activities.index') }}"
        >
            <div class="filter-grid">
                <div class="form-group">
                    <label
                        class="form-label"
                        for="search"
                    >
                        {{ __('activities.filters.search') }}
                    </label>

                    <input
                        class="form-control"
                        id="search"
                        name="search"
                        type="search"
                        value="{{ request('search') }}"
                        placeholder="{{ __('activities.filters.search_placeholder') }}"
                    >
                </div>

                <div class="form-group">
                    <label
                        class="form-label"
                        for="type"
                    >
                        {{ __('activities.fields.type') }}
                    </label>

                    <select
                        class="form-control"
                        id="type"
                        name="type"
                    >
                        <option value="">
                            {{ __('activities.filters.all_types') }}
                        </option>

                        @foreach ($types as $type)
                            <option
                                value="{{ $type->value }}"
                                @selected(
                                    request('type')
                                    === $type->value
                                )
                            >
                                {{ __('activities.types.' . $type->value) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label
                        class="form-label"
                        for="status"
                    >
                        {{ __('activities.fields.status') }}
                    </label>

                    <select
                        class="form-control"
                        id="status"
                        name="status"
                    >
                        <option value="">
                            {{ __('activities.filters.all_statuses') }}
                        </option>

                        @foreach ($statuses as $status)
                            <option
                                value="{{ $status->value }}"
                                @selected(
                                    request('status')
                                    === $status->value
                                )
                            >
                                {{ __('activities.statuses.' . $status->value) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label
                        class="form-label"
                        for="customer_id"
                    >
                        {{ __('activities.fields.customer') }}
                    </label>

                    <select
                        class="form-control"
                        id="customer_id"
                        name="customer_id"
                    >
                        <option value="">
                            {{ __('activities.filters.all_customers') }}
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
                        for="opportunity_id"
                    >
                        {{ __('activities.fields.opportunity') }}
                    </label>

                    <select
                        class="form-control"
                        id="opportunity_id"
                        name="opportunity_id"
                    >
                        <option value="">
                            {{ __('activities.filters.all_opportunities') }}
                        </option>

                        @foreach ($opportunities as $opportunity)
                            <option
                                value="{{ $opportunity->id }}"
                                @selected(
                                    (string) request('opportunity_id')
                                    === (string) $opportunity->id
                                )
                            >
                                {{ $opportunity->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label
                        class="form-label"
                        for="responsible_user_id"
                    >
                        {{ __('activities.fields.responsible') }}
                    </label>

                    <select
                        class="form-control"
                        id="responsible_user_id"
                        name="responsible_user_id"
                    >
                        <option value="">
                            {{ __('activities.filters.all_responsibles') }}
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
                    {{ __('activities.filters.filter') }}
                </button>

                <a
                    class="btn btn-secondary"
                    href="{{ route('activities.index') }}"
                >
                    {{ __('activities.filters.clear') }}
                </a>
            </div>
        </form>
    </div>

    <div class="card">
        @if ($activities->count() === 0)
            <div class="empty-state">
                {{ __('activities.empty') }}
            </div>
        @else
            <div class="table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>{{ __('activities.fields.title') }}</th>
                            <th>{{ __('activities.fields.type') }}</th>
                            <th>{{ __('activities.fields.status') }}</th>
                            <th>{{ __('activities.fields.customer') }}</th>
                            <th>{{ __('activities.fields.opportunity') }}</th>
                            <th>{{ __('activities.fields.responsible') }}</th>
                            <th>{{ __('activities.fields.due_at') }}</th>
                            <th>{{ __('activities.actions_column') }}</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($activities as $activity)
                            <tr>
                                <td>{{ $activity->title }}</td>

                                <td>
                                    {{ __('activities.types.' . $activity->type->value) }}
                                </td>

                                <td>
                                    {{ __('activities.statuses.' . $activity->status->value) }}
                                </td>

                                <td>
                                    {{ $activity->customer?->name ?? '—' }}
                                </td>

                                <td>
                                    {{ $activity->opportunity?->name ?? '—' }}
                                </td>

                                <td>
                                    {{ $activity->responsible?->name ?? '—' }}
                                </td>

                                <td class="nowrap">
                                    {{
                                        $activity->due_at
                                            ? $activity->due_at->format('Y-m-d H:i')
                                            : '—'
                                    }}
                                </td>

                                <td class="nowrap">
                                    @if (
                                        auth()->user()->hasPermission(
                                            \App\Enums\Permission::ACTIVITIES_UPDATE
                                        )
                                    )
                                        <a
                                            href="{{ route(
                                                'activities.edit',
                                                $activity->id
                                            ) }}"
                                        >
                                            {{ __('activities.actions.edit') }}
                                        </a>

                                        @if (
                                            $activity->status
                                            === \App\Enums\ActivityStatus::PENDING
                                        )
                                            <form
                                                method="POST"
                                                action="{{ route(
                                                    'activities.complete',
                                                    $activity->id
                                                ) }}"
                                                style="display:inline"
                                            >
                                                @csrf

                                                <button type="submit">
                                                    {{ __('activities.actions.complete') }}
                                                </button>
                                            </form>

                                            <form
                                                method="POST"
                                                action="{{ route(
                                                    'activities.cancel',
                                                    $activity->id
                                                ) }}"
                                                style="display:inline"
                                            >
                                                @csrf

                                                <button type="submit">
                                                    {{ __('activities.actions.cancel_activity') }}
                                                </button>
                                            </form>
                                        @else
                                            <form
                                                method="POST"
                                                action="{{ route(
                                                    'activities.reopen',
                                                    $activity->id
                                                ) }}"
                                                style="display:inline"
                                            >
                                                @csrf

                                                <button type="submit">
                                                    {{ __('activities.actions.reopen') }}
                                                </button>
                                            </form>
                                        @endif
                                    @endif

                                    @if (
                                        auth()->user()->hasPermission(
                                            \App\Enums\Permission::ACTIVITIES_DELETE
                                        )
                                    )
                                        <form
                                            method="POST"
                                            action="{{ route(
                                                'activities.destroy',
                                                $activity->id
                                            ) }}"
                                            style="display:inline"
                                        >
                                            @csrf
                                            @method('DELETE')

                                            <button
                                                type="submit"
                                                onclick="return confirm(
                                                    '{{ __('activities.confirm_delete') }}'
                                                )"
                                            >
                                                {{ __('activities.actions.delete') }}
                                            </button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{ $activities->links() }}
        @endif
    </div>
</div>
@endsection
