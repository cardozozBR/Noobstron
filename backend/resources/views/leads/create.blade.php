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

        <div class="mt-6 flex items-center gap-3">
            <button
                type="submit"
                class="inline-flex items-center justify-center rounded-xl bg-gray-900 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-gray-900 focus:ring-offset-2"
            >
                {{ __('leads.actions.create') }}
            </button>

            <a
                href="{{ route('leads.index') }}"
                class="inline-flex items-center justify-center rounded-xl border border-gray-300 bg-white px-5 py-3 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-900 focus:ring-offset-2"
            >
                {{ __('leads.actions.cancel') }}
            </a>
        </div>
    </form>
    @endif
</div>
@endsection
