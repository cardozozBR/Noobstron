@extends('layouts.app')

@section('title', __('charges.create_title'))

@section('content')


<style>
.charge-create-page{display:grid;gap:24px;max-width:1000px;margin:0 auto}
.charge-create-page .page-header{display:flex;align-items:flex-end;justify-content:space-between;gap:18px;margin:0}
.charge-create-page .page-header h1{margin:2px 0 0;font-size:30px;line-height:1.15;letter-spacing:-.025em}
.charge-create-page .page-header p{margin:6px 0 0;color:#6b7280;font-size:14px}
.charge-create-page .card{margin:0;padding:22px;border:1px solid #e5e7eb;border-radius:16px;background:#fff;box-shadow:0 1px 2px rgba(15,23,42,.04)}
.charge-create-page .form-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px}
.charge-create-page .form-group{display:grid;gap:7px}
.charge-create-page .form-label{color:#374151;font-size:13px;font-weight:700}
.charge-create-page .form-control{width:100%;min-height:42px;padding:10px 12px;border:1px solid #d1d5db;border-radius:10px;background:#fff;color:#111827;font:inherit;font-size:14px;outline:none;box-shadow:0 1px 2px rgba(15,23,42,.035);transition:border-color 160ms ease,box-shadow 160ms ease}
.charge-create-page .form-control:focus{border-color:var(--primary);box-shadow:0 0 0 3px rgba(37,99,235,.10)}
.charge-create-page .actions{display:flex;justify-content:flex-end;flex-wrap:wrap;gap:8px;padding-top:18px;border-top:1px solid #e5e7eb}
.charge-create-page .btn{display:inline-flex;min-height:40px;align-items:center;justify-content:center;padding:9px 14px;border-radius:10px;font-size:13px;font-weight:700;text-decoration:none}
.charge-create-page .btn-secondary{border:1px solid #d1d5db;background:#fff;color:#374151}
.charge-create-page .alert-danger{padding:12px 14px;border:1px solid #fecaca;border-radius:10px;background:#fef2f2;color:#b91c1c;font-size:13px}
@media(max-width:700px){.charge-create-page .page-header{align-items:flex-start;flex-direction:column}.charge-create-page .form-grid{grid-template-columns:1fr}.charge-create-page .card{padding:18px}}
</style>

<div class="charge-create-page">
    <div class="page-header">
        <div>
            <span class="eyebrow">
                {{ __('charges.navigation') }}
            </span>

            <h1>
                {{ __('charges.create_title') }}
            </h1>

            <p>
                {{ __('charges.create_description') }}
            </p>
        </div>

        <div class="actions">
            <a
                class="btn btn-secondary"
                href="{{ route('charges.index') }}"
            >
                {{ __('charges.back') }}
            </a>
        </div>
    </div>

    <div class="card">
        @if ($errors->any())
            <div class="alert alert-danger">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form
            method="POST"
            action="{{ route('charges.store') }}"
        >
            @csrf

            <div class="form-grid">
                <div class="form-group">
                    <label
                        class="form-label"
                        for="receivable_id"
                    >
                        {{ __('charges.fields.receivable') }}
                    </label>

                    <select
                        class="form-control"
                        id="receivable_id"
                        name="receivable_id"
                        required
                    >
                        <option value="">
                            {{ __('charges.select_receivable') }}
                        </option>

                        @foreach ($receivables as $receivable)
                            <option
                                value="{{ $receivable->id }}"
                                @selected(
                                    (string) old(
                                        'receivable_id'
                                    )
                                    ===
                                    (string) $receivable->id
                                )
                            >
                                {{ $receivable->title }}
                                —
                                {{ $receivable->customer?->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label
                        class="form-label"
                        for="scheduled_at"
                    >
                        {{ __('charges.fields.scheduled_at') }}
                    </label>

                    <input
                        class="form-control"
                        id="scheduled_at"
                        name="scheduled_at"
                        type="datetime-local"
                        value="{{ old('scheduled_at') }}"
                    >
                </div>

                <div class="form-group">
                    <label
                        class="form-label"
                        for="channel"
                    >
                        {{ __('charges.fields.channel') }}
                    </label>

                    <input
                        class="form-control"
                        id="channel"
                        name="channel"
                        maxlength="50"
                        value="{{ old('channel') }}"
                    >
                </div>

                <div class="form-group">
                    <label
                        class="form-label"
                        for="recipient"
                    >
                        {{ __('charges.fields.recipient') }}
                    </label>

                    <input
                        class="form-control"
                        id="recipient"
                        name="recipient"
                        maxlength="255"
                        value="{{ old('recipient') }}"
                    >
                </div>

                <div class="form-group">
                    <label
                        class="form-label"
                        for="external_reference"
                    >
                        {{ __('charges.fields.external_reference') }}
                    </label>

                    <input
                        class="form-control"
                        id="external_reference"
                        name="external_reference"
                        maxlength="255"
                        value="{{ old('external_reference') }}"
                    >
                </div>
            </div>

            <div class="actions">
                <button
                    class="btn btn-primary"
                    type="submit"
                >
                    {{ __('charges.create_action') }}
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
