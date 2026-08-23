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

<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <span class="text-xs font-semibold uppercase tracking-[0.16em] text-gray-500">
                {{ __('customers.eyebrow') }}
            </span>

            <h1 class="mt-1 text-3xl font-bold tracking-tight text-gray-900">
                {{ __('customers.title') }}
            </h1>

            <p class="mt-1 max-w-2xl text-sm text-gray-600">
                {{ __('customers.index_description') }}
            </p>
        </div>

        @if (
            auth()->user()->hasPermission(
                \App\Enums\Permission::CUSTOMERS_CREATE
            )
            && $tenantWriteAllowed
        )
            <a
                href="{{ route('customers.create') }}"
                class="inline-flex min-h-10 items-center justify-center gap-2 rounded-lg bg-[var(--primary)] px-4 py-2.5 text-sm font-semibold shadow-sm transition hover:opacity-90"
                style="color:white;"
            >
                <span aria-hidden="true">＋</span>
                {{ __('customers.new') }}
            </a>
        @endif
    </div>

    @if (session('success'))
        <div class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-800">
            {{ session('success') }}
        </div>
    @endif

    <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
        <form
            method="GET"
            action="{{ route('customers.index') }}"
        >
            <div class="grid gap-4 md:grid-cols-3">
                <div>
                    <label for="customer-search" class="block text-sm font-semibold text-gray-700">
                        {{ __('customers.search') }}
                    </label>

                    <input
                        id="customer-search"
                        name="search"
                        type="search"
                        value="{{ request('search') }}"
                        placeholder="{{ __('customers.search_placeholder') }}"
                        class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 shadow-sm outline-none transition placeholder:text-gray-400 focus:border-[var(--primary)] focus:ring-2 focus:ring-[color:var(--primary)]/15"
                    >
                </div>

                <div>
                    <label for="customer-type" class="block text-sm font-semibold text-gray-700">
                        {{ __('customers.type') }}
                    </label>

                    <select
                        id="customer-type"
                        name="type"
                        class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 shadow-sm outline-none transition focus:border-[var(--primary)] focus:ring-2 focus:ring-[color:var(--primary)]/15"
                    >
                        <option value="">
                            {{ __('customers.all_types') }}
                        </option>

                        @foreach ($types as $type)
                            <option
                                value="{{ $type->value }}"
                                @selected(
                                    request('type')
                                        === $type->value
                                )
                            >
                                {{ $type->label() }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="customer-responsible" class="block text-sm font-semibold text-gray-700">
                        {{ __('customers.responsible') }}
                    </label>

                    <select
                        id="customer-responsible"
                        name="responsible_user_id"
                        class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 shadow-sm outline-none transition focus:border-[var(--primary)] focus:ring-2 focus:ring-[color:var(--primary)]/15"
                    >
                        <option value="">
                            {{ __('customers.all_responsibles') }}
                        </option>

                        @foreach ($responsibles as $responsible)
                            <option
                                value="{{ $responsible->id }}"
                                @selected(
                                    (string) request(
                                        'responsible_user_id'
                                    ) === (string) $responsible->id
                                )
                            >
                                {{ $responsible->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="mt-4 flex flex-wrap gap-2">
                <button
                    type="submit"
                    class="inline-flex min-h-10 items-center justify-center rounded-lg bg-gray-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-gray-800"
                >
                    {{ __('customers.filter') }}
                </button>

                <a
                    href="{{ route('customers.index') }}"
                    class="inline-flex min-h-10 items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50"
                    style="color:#374151;"
                >
                    {{ __('customers.clear') }}
                </a>
            </div>
        </form>
    </div>

    @if ($customers->isEmpty())
        <div class="rounded-2xl border border-dashed border-gray-300 bg-white px-6 py-12 text-center shadow-sm">
            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-blue-50 text-[var(--primary)] ring-1 ring-inset ring-blue-100">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-7 w-7" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 20.25v-1.5A3.75 3.75 0 0 0 12.25 15h-4.5A3.75 3.75 0 0 0 4 18.75v1.5"/>
                    <circle cx="10" cy="7.5" r="3.25"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 8v6M14 11h6"/>
                </svg>
            </div>

            <h2 class="mt-4 text-lg font-semibold text-gray-900">
                {{ __('customers.empty') }}
            </h2>

            @if (
                auth()->user()->hasPermission(
                    \App\Enums\Permission::CUSTOMERS_CREATE
                )
            )
                <a
                    href="{{ route('customers.create') }}"
                    class="mt-5 inline-flex min-h-10 items-center justify-center gap-2 rounded-lg bg-[var(--primary)] px-4 py-2.5 text-sm font-semibold shadow-sm transition hover:opacity-90"
                    style="color:white;"
                >
                    <span aria-hidden="true">＋</span>
                    {{ __('customers.new') }}
                </a>
            @endif
        </div>
    @else
        <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                {{ __('customers.name') }}
                            </th>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                {{ __('customers.type') }}
                            </th>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                {{ __('customers.document') }}
                            </th>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                {{ __('customers.responsible') }}
                            </th>
                            <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">
                                {{ __('customers.actions') }}
                            </th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-100 bg-white">
                        @foreach ($customers as $customer)
                            <tr class="transition hover:bg-gray-50/80">
                                <td class="px-5 py-4">
                                    <a
                                        href="{{ route(
                                            'customers.show',
                                            $customer->id
                                        ) }}"
                                        class="font-semibold text-[var(--primary)] hover:underline"
                                    >
                                        {{ $customer->name }}
                                    </a>
                                </td>

                                <td class="px-5 py-4">
                                    <span class="inline-flex rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-700">
                                        {{ $customer->type->label() }}
                                    </span>
                                </td>

                                <td class="px-5 py-4 text-sm text-gray-600">
                                    @if ($customer->tax_identifier)
                                        {{ $customer->tax_identifier_type }}
                                        {{ $customer->tax_identifier }}
                                    @else
                                        —
                                    @endif
                                </td>

                                <td class="px-5 py-4 text-sm text-gray-700">
                                    {{ $customer->responsible?->name ?? '—' }}
                                </td>

                                <td class="px-5 py-4 text-right">
                                    <div class="inline-flex flex-wrap justify-end gap-2">
                                        @if (
                                            auth()->user()->hasPermission(
                                                \App\Enums\Permission::CUSTOMERS_UPDATE
                                            )
                                            && $tenantWriteAllowed
                                        )
                                            <a
                                                href="{{ route(
                                                    'customers.edit',
                                                    $customer->id
                                                ) }}"
                                                class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs font-semibold text-gray-700 transition hover:bg-gray-50"
                                                style="color:#374151;"
                                            >
                                                {{ __('customers.edit') }}
                                            </a>
                                        @endif

                                        @if (
                                            auth()->user()->hasPermission(
                                                \App\Enums\Permission::CUSTOMERS_DELETE
                                            )
                                            && $tenantWriteAllowed
                                        )
                                            <form
                                                method="POST"
                                                action="{{ route(
                                                    'customers.destroy',
                                                    $customer->id
                                                ) }}"
                                                style="display:inline"
                                                onsubmit="return confirm('{{ __('customers.delete') }}?')"
                                            >
                                                @csrf
                                                @method('DELETE')

                                                <button
                                                    type="submit"
                                                    class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs font-semibold text-red-700 transition hover:bg-red-100"
                                                >
                                                    {{ __('customers.delete') }}
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($customers->hasPages())
                <div class="border-t border-gray-200 bg-white px-5 py-4">
                    {{ $customers->withQueryString()->links() }}
                </div>
            @endif
        </div>
    @endif
</div>
@endsection