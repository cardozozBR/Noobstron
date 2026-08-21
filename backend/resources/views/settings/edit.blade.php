@extends('layouts.app')

@section('title', __('ui.settings.title'))

@section('content')
<div class="page-header">
    <div>
        <span class="eyebrow">
            {{ __('ui.settings.eyebrow') }}
        </span>

        <h1>{{ __('ui.settings.heading') }}</h1>

        <p>
            {{ __('ui.settings.description') }}
        </p>
    </div>
</div>

<form
    method="POST"
    action="{{ route('settings.update') }}"
    class="card"
    enctype="multipart/form-data"
>
    @csrf
    @method('PUT')

    <div class="form-group">
        <label for="name">
            {{ __('ui.settings.commercial_name') }}
        </label>

        <input
            id="name"
            name="name"
            type="text"
            value="{{ old('name', $tenant->name) }}"
            required
        >

        @error('name')
            <div class="field-error">{{ $message }}</div>
        @enderror
    </div>

    <div class="form-group">
        <label for="brand_primary_color">
            {{ __('ui.settings.primary_color') }}
        </label>

        <input
            id="brand_primary_color"
            name="brand_primary_color"
            type="text"
            value="{{ old('brand_primary_color', $tenant->brand_primary_color) }}"
            placeholder="#2563EB"
            maxlength="7"
        >

        <small class="form-help">
            {{ __('ui.settings.primary_color_help') }}
        </small>

        @error('brand_primary_color')
            <div class="field-error">{{ $message }}</div>
        @enderror
    </div>

    <div class="form-group">
        <label for="logo">
            {{ __('ui.settings.logo') }}
        </label>

        <input
            id="logo"
            name="logo"
            type="file"
            accept="image/png,image/jpeg,image/webp"
        >

        <small class="form-help">
            {{ __('ui.settings.logo_help') }}
        </small>

        @error('logo')
            <div class="field-error">{{ $message }}</div>
        @enderror
    </div>

    @if ($tenant->hasLogo())
        <div class="form-group">
            <div class="form-help">
                {{ __('ui.settings.current_logo') }}
            </div>

            <div style="margin-top: 8px;">
                <img
                    src="{{ asset('storage/' . $tenant->logo_path) }}"
                    alt="{{ $tenant->name }}"
                    style="max-height: 80px; max-width: 240px;"
                >
            </div>

            <label style="display: inline-flex; gap: 8px; align-items: center; margin-top: 12px;">
                <input
                    type="checkbox"
                    name="remove_logo"
                    value="1"
                >

                <span>
                    {{ __('ui.settings.remove_logo') }}
                </span>
            </label>
        </div>
    @endif

    <div class="form-actions">
        <button type="submit" class="btn btn-primary">
            {{ __('ui.settings.save') }}
        </button>
    </div>
</form>
@endsection