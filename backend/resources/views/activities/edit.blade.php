@extends('layouts.app')

@section(
    'title',
    __('activities.edit_title')
)

@section('content')


<style>
    .activity-form-page {
        display: grid;
        gap: 24px;
    }

    .activity-form-page .page-header {
        display: flex;
        align-items: flex-end;
        justify-content: space-between;
        gap: 18px;
        margin: 0;
    }

    .activity-form-page .page-header h1 {
        margin: 2px 0 0;
        font-size: 30px;
        line-height: 1.15;
        letter-spacing: -0.025em;
    }

    .activity-form-page .page-header p {
        margin: 6px 0 0;
        color: #6b7280;
        font-size: 14px;
    }

    .activity-form-page .card {
        margin: 0;
        padding: 24px;
        border: 1px solid #e5e7eb;
        border-radius: 16px;
        background: #fff;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
    }

    .activity-form-page form {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 18px;
    }

    .activity-form-page .form-group {
        display: grid;
        gap: 7px;
    }

    .activity-form-page .form-label {
        color: #374151;
        font-size: 13px;
        font-weight: 700;
    }

    .activity-form-page .form-control {
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

    .activity-form-page textarea.form-control {
        min-height: 150px;
        resize: vertical;
    }

    .activity-form-page .form-control:focus {
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.10);
    }

    .activity-form-page .form-group:has(#description) {
        grid-column: 1 / -1;
    }

    .activity-form-page .form-help {
        color: #b91c1c;
        font-size: 12px;
        font-weight: 600;
    }

    .activity-form-page #activity_ai_rewrite {
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

    .activity-form-page .form-actions {
        grid-column: 1 / -1;
        display: flex;
        justify-content: flex-end;
        flex-wrap: wrap;
        gap: 8px;
        padding-top: 18px;
        border-top: 1px solid #e5e7eb;
    }

    .activity-form-page .btn {
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

    .activity-form-page .btn-secondary {
        border: 1px solid #d1d5db;
        background: #fff;
        color: #374151;
    }

    @media (max-width: 700px) {
        .activity-form-page .page-header {
            align-items: flex-start;
            flex-direction: column;
        }

        .activity-form-page form {
            grid-template-columns: 1fr;
        }

        .activity-form-page .form-group:has(#description),
        .activity-form-page .form-actions {
            grid-column: auto;
        }

        .activity-form-page .card {
            padding: 18px;
        }
    }
</style>

<div class="activity-form-page">
    <div class="page-header">
        <div>
            <span class="eyebrow">
                {{ __('activities.title') }}
            </span>

            <h1>
                {{ __('activities.edit') }}
            </h1>

            <p>{{ $activity->title }}</p>
        </div>

        <div class="actions">
            <a
                class="btn btn-secondary"
                href="{{ route('activities.index') }}"
            >
                {{ __('activities.actions.back') }}
            </a>
        </div>
    </div>

    <div class="card">
        <form
            method="POST"
            action="{{ route(
                'activities.update',
                $activity->id
            ) }}"
        >
            @csrf
            @method('PUT')

            @include('activities._form')

            <div class="form-actions">
                <button
                    class="btn btn-primary"
                    type="submit"
                >
                    {{ __('activities.actions.save') }}
                </button>

                <a
                    class="btn btn-secondary"
                    href="{{ route('activities.index') }}"
                >
                    {{ __('activities.actions.cancel') }}
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
