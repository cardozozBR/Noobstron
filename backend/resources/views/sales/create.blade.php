@extends('layouts.app')

@section(
    'title',
    __('sales.close_title')
)

@section('content')


<style>
    .sale-create-page {
        display: grid;
        gap: 24px;
        max-width: 1000px;
        margin: 0 auto;
    }

    .sale-create-page .page-header {
        display: flex;
        align-items: flex-end;
        justify-content: space-between;
        gap: 18px;
        margin: 0;
    }

    .sale-create-page .page-header h1 {
        margin: 2px 0 0;
        font-size: 30px;
        line-height: 1.15;
        letter-spacing: -0.025em;
    }

    .sale-create-page .page-header p {
        margin: 6px 0 0;
        color: #6b7280;
        font-size: 14px;
    }

    .sale-create-page .card {
        margin: 0;
        padding: 22px;
        border: 1px solid #e5e7eb;
        border-radius: 16px;
        background: #fff;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
    }

    .sale-create-page .form-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 16px;
    }

    .sale-create-page .form-group {
        display: grid;
        gap: 7px;
    }

    .sale-create-page .form-label {
        color: #374151;
        font-size: 13px;
        font-weight: 700;
    }

    .sale-create-page .form-control {
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

    .sale-create-page .form-control:focus {
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.10);
    }

    .sale-create-page .alert-danger {
        padding: 12px 14px;
        border: 1px solid #fecaca;
        border-radius: 10px;
        background: #fef2f2;
        color: #b91c1c;
        font-size: 13px;
    }

    .sale-create-page .actions {
        display: flex;
        justify-content: flex-end;
        margin-top: 18px;
        padding-top: 18px;
        border-top: 1px solid #e5e7eb;
    }

    .sale-create-page .btn {
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

    .sale-create-page .btn-secondary {
        border: 1px solid #d1d5db;
        background: #fff;
        color: #374151;
    }

    @media (max-width: 700px) {
        .sale-create-page .page-header {
            align-items: flex-start;
            flex-direction: column;
        }

        .sale-create-page .form-grid {
            grid-template-columns: 1fr;
        }

        .sale-create-page .card {
            padding: 18px;
        }
    }
</style>

<div class="sale-create-page">
    <div class="page-header">
        <div>
            <span class="eyebrow">
                {{ __('sales.navigation') }}
            </span>

            <h1>
                {{ __('sales.close_title') }}
            </h1>

            <p>
                {{ __('sales.close_description') }}
            </p>
        </div>

        <div class="actions">
            <a
                class="btn btn-secondary"
                href="{{ route('sales.index') }}"
            >
                {{ __('sales.back') }}
            </a>
        </div>
    </div>

    <div class="card">
        <div class="form-grid">
            <div class="form-group">
                <span class="form-label">
                    {{ __('sales.fields.opportunity') }}
                </span>

                <strong>
                    {{ $opportunity->name }}
                </strong>
            </div>

            <div class="form-group">
                <span class="form-label">
                    {{ __('sales.fields.customer') }}
                </span>

                <strong>
                    {{ $opportunity->customer?->name }}
                </strong>
            </div>
        </div>
    </div>

    <div class="card">
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

        <form
            method="POST"
            action="{{ route(
                'sales.store',
                $opportunity->id
            ) }}"
        >
            @csrf

            <div class="form-grid">
                <div class="form-group">
                    <label
                        class="form-label"
                        for="proposal_id"
                    >
                        {{ __('sales.accepted_proposal') }}
                    </label>

                    <select
                        class="form-control"
                        id="proposal_id"
                        name="proposal_id"
                    >
                        <option value="">
                            {{ __('sales.direct_close') }}
                        </option>

                        @foreach ($proposals as $proposal)
                            <option
                                value="{{ $proposal->id }}"
                                @selected(
                                    (string) old(
                                        'proposal_id'
                                    )
                                    ===
                                    (string) $proposal->id
                                )
                            >
                                {{ $proposal->number }}
                                —
                                {{ $proposal->currency }}
                                {{ number_format(
                                    $proposal->total_minor / 100,
                                    2,
                                    ',',
                                    '.'
                                ) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label
                        class="form-label"
                        for="number"
                    >
                        {{ __('sales.fields.number') }}
                    </label>

                    <input
                        class="form-control"
                        id="number"
                        name="number"
                        value="{{ old('number') }}"
                    >
                </div>

                <div class="form-group">
                    <label
                        class="form-label"
                        for="total_minor"
                    >
                        {{ __('sales.fields.total_minor') }}
                    </label>

                    <input
                        class="form-control"
                        id="total_minor"
                        name="total_minor"
                        type="number"
                        min="0"
                        value="{{ old(
                            'total_minor',
                            $opportunity->value_minor
                        ) }}"
                    >
                </div>

                <div class="form-group">
                    <label
                        class="form-label"
                        for="currency"
                    >
                        {{ __('sales.fields.currency') }}
                    </label>

                    <input
                        class="form-control"
                        id="currency"
                        name="currency"
                        maxlength="3"
                        value="{{ old(
                            'currency',
                            $opportunity->currency
                        ) }}"
                    >
                </div>

                <div class="form-group">
                    <label
                        class="form-label"
                        for="closed_at"
                    >
                        {{ __('sales.fields.close_date') }}
                    </label>

                    <input
                        class="form-control"
                        id="closed_at"
                        name="closed_at"
                        type="datetime-local"
                        value="{{ old('closed_at') }}"
                    >
                </div>
            </div>

            <div class="actions">
                <button
                    class="btn btn-primary"
                    type="submit"
                >
                    {{ __('sales.register') }}
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
