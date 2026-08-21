@extends('layouts.app')

@section('content')


<style>
    .pipelines-index-page {
        display: grid;
        gap: 24px;
    }

    .pipelines-index-page .page-header {
        display: flex;
        align-items: flex-end;
        justify-content: space-between;
        gap: 18px;
        margin: 0;
    }

    .pipelines-index-page .page-header h1 {
        margin: 0;
        font-size: 30px;
        line-height: 1.15;
        letter-spacing: -0.025em;
    }

    .pipelines-index-page .card {
        margin: 0;
        padding: 22px;
        border: 1px solid #e5e7eb;
        border-radius: 16px;
        background: #fff;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
    }

    .pipelines-index-page .success-message {
        padding: 12px 14px;
        border: 1px solid #bbf7d0;
        border-radius: 10px;
        background: #f0fdf4;
        color: #166534;
        font-size: 13px;
        font-weight: 600;
    }

    .pipelines-index-page table {
        width: 100%;
        border-collapse: collapse;
        overflow: hidden;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        background: #fff;
    }

    .pipelines-index-page thead {
        background: #f9fafb;
    }

    .pipelines-index-page th {
        padding: 11px 12px;
        border-bottom: 1px solid #e5e7eb;
        color: #6b7280;
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 0.055em;
        text-align: left;
        text-transform: uppercase;
    }

    .pipelines-index-page td {
        padding: 13px 12px;
        border-bottom: 1px solid #f3f4f6;
        color: #4b5563;
        font-size: 13px;
        vertical-align: middle;
    }

    .pipelines-index-page tbody tr:hover {
        background: #f9fafb;
    }

    .pipelines-index-page tbody tr:last-child td {
        border-bottom: 0;
    }

    .pipelines-index-page td:first-child {
        color: #111827;
        font-weight: 700;
    }

    .pipelines-index-page td form {
        display: inline-flex !important;
        margin: 2px 3px 2px 0;
    }

    .pipelines-index-page td a,
    .pipelines-index-page td button {
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

    .pipelines-index-page .create-link {
        display: inline-flex;
        min-height: 40px;
        align-items: center;
        justify-content: center;
        padding: 9px 14px;
        border-radius: 10px;
        background: var(--primary);
        color: #fff !important;
        font-size: 13px;
        font-weight: 700;
        text-decoration: none;
    }

    .pipelines-index-page .default-badge {
        display: inline-flex;
        min-width: 28px;
        min-height: 28px;
        align-items: center;
        justify-content: center;
        border: 1px solid #dbeafe;
        border-radius: 999px;
        background: #eff6ff;
        color: #1d4ed8;
        font-size: 13px;
        font-weight: 800;
    }

    .pipelines-index-page .status-badge {
        display: inline-flex;
        padding: 4px 8px;
        border-radius: 999px;
        background: #f3f4f6;
        color: #374151;
        font-size: 11px;
        font-weight: 700;
    }

    .pipelines-index-page .empty-state {
        padding: 44px 24px;
        border: 1px dashed #d1d5db;
        border-radius: 12px;
        background: #fafafa;
        color: #6b7280;
        text-align: center;
    }

    @media (max-width: 700px) {
        .pipelines-index-page .page-header {
            align-items: flex-start;
            flex-direction: column;
        }

        .pipelines-index-page .card {
            padding: 18px;
        }
    }
</style>

<div class="pipelines-index-page">
<div class="page-header">
    <div>
        <h1>{{ __('pipelines.title') }}</h1>
    </div>

    @if (
        auth()->user()->hasPermission(
            \App\Enums\Permission::PIPELINES_CREATE
        )
    )
        <div>
            <a class="create-link" href="{{ route('pipelines.create') }}">
                {{ __('pipelines.new') }}
            </a>
        </div>
    @endif
</div>

    @if (session('success'))
        <div class="success-message">
            {{ session('success') }}
        </div>
    @endif

    @if ($pipelines->isEmpty())
        <div class="card"><div class="empty-state">{{ __('pipelines.no_pipelines') }}</div></div>
    @else
        <table>
            <thead>
                <tr>
                    <th>
                        {{ __('pipelines.name') }}
                    </th>

                    <th>
                        {{ __('pipelines.stage_count') }}
                    </th>

                    <th>
                        {{ __('pipelines.default') }}
                    </th>

                    <th>
                        {{ __('pipelines.active') }}
                    </th>

                    <th>
                        {{ __('pipelines.actions') }}
                    </th>
                </tr>
            </thead>

            <tbody>
                @foreach ($pipelines as $pipeline)
                    <tr>
                        <td>
                            {{ $pipeline->name }}
                        </td>

                        <td>
                            {{ $pipeline->stages_count }}
                        </td>

                        <td>
                            @if ($pipeline->is_default)<span class="default-badge">✓</span>@endif
                        </td>

                        <td>
                            <span class="status-badge">
                                {{
                                    $pipeline->is_active
                                        ? __('pipelines.active')
                                        : __('pipelines.inactive')
                                }}
                            </span>
                        </td>

                        <td>
                            @if (
                                auth()->user()->hasPermission(
                                    \App\Enums\Permission::PIPELINES_UPDATE
                                )
                            )
                                <a
                                    href="{{ route(
                                        'pipelines.edit',
                                        $pipeline->id
                                    ) }}"
                                >
                                    {{ __('pipelines.edit_action') }}
                                </a>

                                @if (! $pipeline->is_default)
                                    <form
                                        method="POST"
                                        action="{{ route(
                                            'pipelines.default',
                                            $pipeline->id
                                        ) }}"
                                        style="display:inline"
                                    >
                                        @csrf

                                        <button type="submit">
                                            {{ __('pipelines.set_default') }}
                                        </button>
                                    </form>
                                @endif
                            @endif

                            @if (
                                auth()->user()->hasPermission(
                                    \App\Enums\Permission::PIPELINES_DELETE
                                )
                            )
                                <form
                                    method="POST"
                                    action="{{ route(
                                        'pipelines.destroy',
                                        $pipeline->id
                                    ) }}"
                                    style="display:inline"
                                >
                                    @csrf
                                    @method('DELETE')

                                    <button type="submit">
                                        {{ __('pipelines.delete') }}
                                    </button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>
</div>
@endsection
