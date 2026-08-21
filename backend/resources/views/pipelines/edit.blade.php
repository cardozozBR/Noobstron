@extends('layouts.app')

@section('content')


<style>
    .pipeline-edit-page {
        display: grid;
        gap: 24px;
    }

    .pipeline-edit-page h1 {
        margin: 0;
        font-size: 30px;
        line-height: 1.15;
        letter-spacing: -0.025em;
    }

    .pipeline-edit-page h2,
    .pipeline-edit-page h3 {
        color: #111827;
    }

    .pipeline-edit-page .pipeline-card {
        padding: 22px;
        border: 1px solid #e5e7eb;
        border-radius: 16px;
        background: #fff;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
    }

    .pipeline-edit-page form {
        display: grid;
        gap: 14px;
        margin-top: 12px;
        padding: 16px;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        background: #f9fafb;
    }

    .pipeline-edit-page form[action*="destroy"] {
        display: flex;
        justify-content: flex-end;
        padding: 0;
        border: 0;
        background: transparent;
    }

    .pipeline-edit-page label {
        display: grid;
        gap: 7px;
        color: #374151;
        font-size: 13px;
        font-weight: 700;
    }

    .pipeline-edit-page input:not([type="checkbox"]):not([type="hidden"]),
    .pipeline-edit-page textarea,
    .pipeline-edit-page select {
        width: 100%;
        min-height: 40px;
        padding: 9px 11px;
        border: 1px solid #d1d5db;
        border-radius: 9px;
        background: #fff;
        color: #111827;
        font: inherit;
        font-size: 13px;
        outline: none;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.03);
    }

    .pipeline-edit-page textarea {
        min-height: 120px;
        resize: vertical;
    }

    .pipeline-edit-page input:not([type="checkbox"]):not([type="hidden"]):focus,
    .pipeline-edit-page textarea:focus,
    .pipeline-edit-page select:focus {
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.10);
    }

    .pipeline-edit-page label:has(input[type="checkbox"]) {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        font-weight: 600;
    }

    .pipeline-edit-page button {
        justify-self: start;
        min-height: 38px;
        padding: 8px 12px;
        border: 1px solid #d1d5db;
        border-radius: 9px;
        background: #111827;
        color: #fff;
        font-size: 12px;
        font-weight: 700;
        cursor: pointer;
    }

    .pipeline-edit-page form[action*="destroy"] button {
        border-color: #fecaca;
        background: #fef2f2;
        color: #b91c1c;
    }

    .pipeline-edit-page hr {
        margin: 24px 0;
        border: 0;
        border-top: 1px solid #e5e7eb;
    }

    .pipeline-edit-page .stage-block {
        margin-top: 14px;
        padding: 18px;
        border: 1px solid #e5e7eb;
        border-radius: 14px;
        background: #fff;
    }

    .pipeline-edit-page .stage-block + .stage-block {
        margin-top: 12px;
    }
</style>

<div class="pipeline-edit-page">
<div class="pipeline-card">
<div>
    <h1>
        {{ __('pipelines.edit') }}:
        {{ $pipeline->name }}
    </h1>

    @if (session('success'))
        <div>
            {{ session('success') }}
        </div>
    @endif

    <form
        method="POST"
        action="{{ route(
            'pipelines.update',
            $pipeline->id
        ) }}"
    >
        @csrf
        @method('PUT')

        <div>
            <label for="name">
                {{ __('pipelines.name') }}
            </label>

            <input
                id="name"
                type="text"
                name="name"
                value="{{ old(
                    'name',
                    $pipeline->name
                ) }}"
                required
            >
        </div>

        <div>
            <label for="description">
                {{ __('pipelines.description') }}
            </label>

            <textarea
                id="description"
                name="description"
            >{{ old(
                'description',
                $pipeline->description
            ) }}</textarea>
        </div>

        <div>
            <input
                type="hidden"
                name="is_default"
                value="0"
            >

            <label>
                <input
                    type="checkbox"
                    name="is_default"
                    value="1"
                    @checked(
                        old(
                            'is_default',
                            $pipeline->is_default
                        )
                    )
                >

                {{ __('pipelines.default') }}
            </label>
        </div>

        <div>
            <input
                type="hidden"
                name="is_active"
                value="0"
            >

            <label>
                <input
                    type="checkbox"
                    name="is_active"
                    value="1"
                    @checked(
                        old(
                            'is_active',
                            $pipeline->is_active
                        )
                    )
                >

                {{ __('pipelines.active') }}
            </label>
        </div>

        <button type="submit">
            {{ __('pipelines.save') }}
        </button>
    </form>

    <hr>

    <h2>
        {{ __('pipelines.stages') }}
    </h2>

    @if ($pipeline->stages->isEmpty())
        <p>
            {{ __('pipelines.no_stages') }}
        </p>
    @else
        @foreach ($pipeline->stages as $stage)
            <form
                method="POST"
                action="{{ route(
                    'pipelines.stages.update',
                    [
                        $pipeline->id,
                        $stage->id,
                    ]
                ) }}"
            >
                @csrf
                @method('PUT')

                <label>
                    {{ __('pipelines.name') }}

                    <input
                        type="text"
                        name="name"
                        value="{{ $stage->name }}"
                        required
                    >
                </label>

                <label>
                    {{ __('pipelines.position') }}

                    <input
                        type="number"
                        name="position"
                        min="1"
                        value="{{ $stage->position }}"
                    >
                </label>

                <input
                    type="hidden"
                    name="is_active"
                    value="0"
                >

                <label>
                    <input
                        type="checkbox"
                        name="is_active"
                        value="1"
                        @checked($stage->is_active)
                    >

                    {{ __('pipelines.active') }}
                </label>

                <button type="submit">
                    {{ __('pipelines.update') }}
                </button>
            </form>

            <form
                method="POST"
                action="{{ route(
                    'pipelines.stages.destroy',
                    [
                        $pipeline->id,
                        $stage->id,
                    ]
                ) }}"
            >
                @csrf
                @method('DELETE')

                <button type="submit">
                    {{ __('pipelines.delete') }}
                </button>
            </form>

            <hr>
        @endforeach
    @endif

    <h3>
        {{ __('pipelines.new_stage') }}
    </h3>

    <form
        method="POST"
        action="{{ route(
            'pipelines.stages.store',
            $pipeline->id
        ) }}"
    >
        @csrf

        <div>
            <label>
                {{ __('pipelines.name') }}

                <input
                    type="text"
                    name="name"
                    required
                >
            </label>
        </div>

        <div>
            <label>
                {{ __('pipelines.position') }}

                <input
                    type="number"
                    name="position"
                    min="1"
                >
            </label>
        </div>

        <input
            type="hidden"
            name="is_active"
            value="1"
        >

        <button type="submit">
            {{ __('pipelines.new_stage') }}
        </button>
    </form>

    @if ($pipeline->stages->count() > 1)
        <hr>

        <h3>
            {{ __('pipelines.reorder') }}
        </h3>

        <p>
            {{ __('pipelines.reorder_help') }}
        </p>

        <form
            method="POST"
            action="{{ route(
                'pipelines.stages.reorder',
                $pipeline->id
            ) }}"
        >
            @csrf

            @foreach ($pipeline->stages as $position => $currentStage)
                <div>
                    <label>
                        {{ __('pipelines.position') }}
                        {{ $position + 1 }}

                        <select
                            name="stage_ids[]"
                            required
                        >
                            @foreach ($pipeline->stages as $stage)
                                <option
                                    value="{{ $stage->id }}"
                                    @selected(
                                        $stage->id
                                        === $currentStage->id
                                    )
                                >
                                    {{ $stage->name }}
                                </option>
                            @endforeach
                        </select>
                    </label>
                </div>
            @endforeach

            <button type="submit">
                {{ __('pipelines.reorder') }}
            </button>
        </form>
    @endif
</div>
</div>
</div>
@endsection
