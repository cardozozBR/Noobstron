@extends('layouts.app')

@section(
    'title',
    __('opportunities.edit_title')
)

@section('content')


<style>
    .opportunity-form-page {
        display: grid;
        gap: 24px;
    }

    .opportunity-form-page .page-header {
        display: flex;
        align-items: flex-end;
        justify-content: space-between;
        gap: 18px;
        margin: 0;
    }

    .opportunity-form-page .page-header h1 {
        margin: 2px 0 0;
        font-size: 30px;
        line-height: 1.15;
        letter-spacing: -0.025em;
    }

    .opportunity-form-page .page-header p {
        margin: 6px 0 0;
        color: #6b7280;
        font-size: 14px;
    }

    .opportunity-form-page .card {
        margin: 0;
        padding: 24px;
        border: 1px solid #e5e7eb;
        border-radius: 16px;
        background: #fff;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
    }

    .opportunity-form-page form {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 18px;
    }

    .opportunity-form-page .form-group {
        display: grid;
        gap: 7px;
    }

    .opportunity-form-page .form-label {
        color: #374151;
        font-size: 13px;
        font-weight: 700;
    }

    .opportunity-form-page .form-control {
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

    .opportunity-form-page textarea.form-control {
        min-height: 150px;
        resize: vertical;
    }

    .opportunity-form-page .form-control:focus {
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.10);
    }

    .opportunity-form-page .form-group:has(#notes) {
        grid-column: 1 / -1;
    }

    .opportunity-form-page .form-help {
        color: #6b7280;
        font-size: 12px;
    }

    .opportunity-form-page .form-group .form-help + .form-help,
    .opportunity-form-page .form-group div.form-help {
        color: #b91c1c;
        font-weight: 600;
    }

    .opportunity-form-page #opportunity_ai_rewrite {
        min-height: 38px;
        padding: 8px 12px;
        border: 1px solid #d1d5db;
        border-radius: 9px;
        background: #fff;
        color: #374151;
        font-size: 13px;
        font-weight: 700;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.035);
    }

    .opportunity-form-page .form-actions {
        grid-column: 1 / -1;
        display: flex;
        justify-content: flex-end;
        flex-wrap: wrap;
        gap: 8px;
        padding-top: 18px;
        border-top: 1px solid #e5e7eb;
    }

    .opportunity-form-page .btn {
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

    .opportunity-form-page .btn-secondary {
        border: 1px solid #d1d5db;
        background: #fff;
        color: #374151;
    }

    @media (max-width: 700px) {
        .opportunity-form-page .page-header {
            align-items: flex-start;
            flex-direction: column;
        }

        .opportunity-form-page form {
            grid-template-columns: 1fr;
        }

        .opportunity-form-page .form-group:has(#notes),
        .opportunity-form-page .form-actions {
            grid-column: auto;
        }

        .opportunity-form-page .card {
            padding: 18px;
        }
    }
</style>

<div class="opportunity-form-page">
    <div class="page-header">
        <div>
            <span class="eyebrow">
                {{ __('opportunities.title') }}
            </span>

            <h1>
                {{ __('opportunities.edit') }}
            </h1>

            <p>
                {{ $opportunity->name }}
            </p>
        </div>

        <div class="actions">
            <a
                class="btn btn-secondary"
                href="{{ route('opportunities.index') }}"
            >
                {{ __('opportunities.actions.back') }}
            </a>
        </div>
    </div>

    <div class="card">
        <form
            method="POST"
            action="{{ route(
                'opportunities.update',
                $opportunity->id
            ) }}"
        >
            @csrf
            @method('PUT')

            @include('opportunities._form')

            <div class="form-actions">
                <button
                    class="btn btn-primary"
                    type="submit"
                >
                    {{ __('opportunities.actions.save') }}
                </button>

                <a
                    class="btn btn-secondary"
                    href="{{ route('opportunities.index') }}"
                >
                    {{ __('opportunities.actions.cancel') }}
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
