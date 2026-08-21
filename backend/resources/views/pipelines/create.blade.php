@extends('layouts.app')

@section('content')


<style>
    .pipeline-form-page {
        display: grid;
        gap: 24px;
        max-width: 900px;
        margin: 0 auto;
    }

    .pipeline-form-page h1 {
        margin: 0;
        font-size: 30px;
        line-height: 1.15;
        letter-spacing: -0.025em;
    }

    .pipeline-form-page .card {
        margin: 0;
        padding: 24px;
        border: 1px solid #e5e7eb;
        border-radius: 16px;
        background: #fff;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
    }

    .pipeline-form-page form {
        display: grid;
        gap: 18px;
    }

    .pipeline-form-page form > div {
        display: grid;
        gap: 7px;
    }

    .pipeline-form-page label {
        color: #374151;
        font-size: 13px;
        font-weight: 700;
    }

    .pipeline-form-page input:not([type="checkbox"]):not([type="hidden"]),
    .pipeline-form-page textarea,
    .pipeline-form-page select {
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

    .pipeline-form-page textarea {
        min-height: 130px;
        resize: vertical;
    }

    .pipeline-form-page input:not([type="checkbox"]):not([type="hidden"]):focus,
    .pipeline-form-page textarea:focus,
    .pipeline-form-page select:focus {
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.10);
    }

    .pipeline-form-page button {
        justify-self: start;
        min-height: 40px;
        padding: 9px 14px;
        border: 0;
        border-radius: 10px;
        background: var(--primary);
        color: #fff;
        font-size: 13px;
        font-weight: 700;
        cursor: pointer;
    }

    .pipeline-form-page label:has(input[type="checkbox"]) {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        font-weight: 600;
    }
</style>

<div class="pipeline-form-page">
<div class="card">
<div>
    <h1>
        {{ __('pipelines.new') }}
    </h1>

    <form
        method="POST"
        action="{{ route('pipelines.store') }}"
    >
        @csrf

        <div>
            <label for="name">
                {{ __('pipelines.name') }}
            </label>

            <input
                id="name"
                type="text"
                name="name"
                value="{{ old('name') }}"
                required
            >

            @error('name')
                <div>{{ $message }}</div>
            @enderror
        </div>

        <div>
            <label for="description">
                {{ __('pipelines.description') }}
            </label>

            <textarea
                id="description"
                name="description"
            >{{ old('description') }}</textarea>
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
                    @checked(old('is_default'))
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
                    @checked(old('is_active', true))
                >

                {{ __('pipelines.active') }}
            </label>
        </div>

        <button type="submit">
            {{ __('pipelines.create') }}
        </button>
    </form>
</div>
</div>
</div>
@endsection
