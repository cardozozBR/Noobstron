@extends('layouts.app')

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

<div>
    <div>
        <h1>{{ __('leads.new') }}</h1>

        <a href="{{ route('leads.index') }}">
            {{ __('leads.actions.back') }}
        </a>
    </div>

    @if ($tenantWriteAllowed)
    <form
        method="POST"
        action="{{ route('leads.store') }}"
    >
        @csrf

        @include('leads._form')

        <button type="submit">
            {{ __('leads.actions.create') }}
        </button>

        <a href="{{ route('leads.index') }}">
            {{ __('leads.actions.cancel') }}
        </a>
    </form>
    @endif
</div>
@endsection
