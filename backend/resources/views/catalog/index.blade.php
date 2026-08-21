@extends('layouts.app')

@php
    use App\Enums\Permission;
    use App\Support\TenantMoneyFormatter;
@endphp

@section('title', __('catalog.title'))

@section('content')
<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <span class="text-xs font-semibold uppercase tracking-[0.16em] text-gray-500">
                {{ __('catalog.title') }}
            </span>

            <h1 class="mt-1 text-3xl font-bold tracking-tight text-gray-900">
                {{ __('catalog.heading') }}
            </h1>

            <p class="mt-1 max-w-2xl text-sm text-gray-600">
                {{ __('catalog.description') }}
            </p>
        </div>

        @if (auth()->user()->hasPermission(Permission::CATALOG_CREATE))
            <a
                href="{{ route('catalog.create') }}"
                class="inline-flex min-h-10 items-center justify-center gap-2 rounded-lg bg-[var(--primary)] px-4 py-2.5 text-sm font-semibold shadow-sm transition hover:opacity-90"
                style="color:white;"
            >
                <span aria-hidden="true">+</span>
                {{ __('catalog.new_item') }}
            </a>
        @endif
    </div>

    <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
        <form method="GET" action="{{ route('catalog.index') }}">
            <div class="grid gap-4 md:grid-cols-3">
                <div>
                    <label for="search" class="block text-sm font-semibold text-gray-700">
                        {{ __('catalog.search') }}
                    </label>

                    <input
                        id="search"
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="{{ __('catalog.search_placeholder') }}"
                        class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 shadow-sm outline-none transition placeholder:text-gray-400 focus:border-[var(--primary)] focus:ring-2 focus:ring-[color:var(--primary)]/15"
                    >
                </div>

                <div>
                    <label for="type" class="block text-sm font-semibold text-gray-700">
                        {{ __('catalog.filter_type') }}
                    </label>

                    <select
                        id="type"
                        name="type"
                        class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 shadow-sm outline-none transition focus:border-[var(--primary)] focus:ring-2 focus:ring-[color:var(--primary)]/15"
                    >
                        <option value="">{{ __('catalog.all_types') }}</option>

                        @foreach ($types as $type)
                            <option
                                value="{{ $type->value }}"
                                @selected(request('type') === $type->value)
                            >
                                {{ __('catalog.' . $type->value) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="status" class="block text-sm font-semibold text-gray-700">
                        {{ __('catalog.filter_status') }}
                    </label>

                    <select
                        id="status"
                        name="status"
                        class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 shadow-sm outline-none transition focus:border-[var(--primary)] focus:ring-2 focus:ring-[color:var(--primary)]/15"
                    >
                        <option value="">{{ __('catalog.all_statuses') }}</option>
                        <option value="active" @selected(request('status') === 'active')>
                            {{ __('catalog.active') }}
                        </option>
                        <option value="inactive" @selected(request('status') === 'inactive')>
                            {{ __('catalog.inactive') }}
                        </option>
                    </select>
                </div>
            </div>

            <div class="mt-4 flex flex-wrap gap-2">
                <button
                    type="submit"
                    class="inline-flex min-h-10 items-center justify-center rounded-lg bg-gray-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-gray-800"
                >
                    {{ __('catalog.search') }}
                </button>

                <a
                    href="{{ route('catalog.index') }}"
                    class="inline-flex min-h-10 items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50"
                    style="color:#374151;"
                >
                    {{ __('catalog.all_statuses') }}
                </a>
            </div>
        </form>
    </div>

    @if ($items->isEmpty())
        <div class="rounded-2xl border border-dashed border-gray-300 bg-white px-6 py-12 text-center shadow-sm">
            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-blue-50 text-[var(--primary)] ring-1 ring-inset ring-blue-100">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-7 w-7" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 7.5 12 3l7.5 4.5L12 12 4.5 7.5Z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 7.5V16.5L12 21l7.5-4.5V7.5"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 12v9"/>
                </svg>
            </div>

            <h2 class="mt-4 text-lg font-semibold text-gray-900">
                {{ __('catalog.empty') }}
            </h2>

            @if (auth()->user()->hasPermission(Permission::CATALOG_CREATE))
                <a
                    href="{{ route('catalog.create') }}"
                    class="mt-5 inline-flex min-h-10 items-center justify-center gap-2 rounded-lg bg-[var(--primary)] px-4 py-2.5 text-sm font-semibold shadow-sm transition hover:opacity-90"
                    style="color:white;"
                >
                    <span aria-hidden="true">+</span>
                    {{ __('catalog.new_item') }}
                </a>
            @endif
        </div>
    @else
        <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('catalog.name') }}</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('catalog.code') }}</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('catalog.type') }}</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('catalog.price') }}</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('catalog.status') }}</th>
                            <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('catalog.actions') }}</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-100 bg-white">
                        @foreach ($items as $item)
                            <tr class="transition hover:bg-gray-50/80">
                                <td class="px-5 py-4">
                                    <div class="font-semibold text-gray-900">{{ $item->name }}</div>
                                </td>

                                <td class="px-5 py-4 text-sm text-gray-600">
                                    {{ $item->code ?? '—' }}
                                </td>

                                <td class="px-5 py-4">
                                    <span class="inline-flex rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-700">
                                        {{ __('catalog.' . $item->type->value) }}
                                    </span>
                                </td>

                                <td class="px-5 py-4 text-sm font-semibold text-gray-900">
                                    {{
                                        app(TenantMoneyFormatter::class)
                                            ->formatMinor(
                                                $item->price_minor,
                                                $item->currency
                                            )
                                    }}
                                </td>

                                <td class="px-5 py-4">
                                    @if ($item->is_active)
                                        <span class="inline-flex rounded-full bg-green-50 px-2.5 py-1 text-xs font-semibold text-green-700 ring-1 ring-inset ring-green-200">
                                            {{ __('catalog.active') }}
                                        </span>
                                    @else
                                        <span class="inline-flex rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-600">
                                            {{ __('catalog.inactive') }}
                                        </span>
                                    @endif
                                </td>

                                <td class="px-5 py-4 text-right">
                                    <div class="inline-flex flex-wrap justify-end gap-2">
                                        @if (auth()->user()->hasPermission(Permission::CATALOG_UPDATE))
                                            <a
                                                href="{{ route('catalog.edit', $item->id) }}"
                                                class="inline-flex min-h-9 items-center justify-center rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50"
                                                style="color:#374151;"
                                            >
                                                {{ __('catalog.edit') }}
                                            </a>
                                        @endif

                                        @if (auth()->user()->hasPermission(Permission::CATALOG_DELETE))
                                            <form
                                                method="POST"
                                                action="{{ route('catalog.destroy', $item->id) }}"
                                                onsubmit="return confirm('{{ __('catalog.delete_confirm') }}')"
                                            >
                                                @csrf
                                                @method('DELETE')

                                                <button
                                                    type="submit"
                                                    class="inline-flex min-h-9 items-center justify-center rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs font-semibold text-red-700 transition hover:bg-red-100"
                                                >
                                                    {{ __('catalog.delete') }}
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

            <div class="border-t border-gray-200 bg-white px-5 py-4">
                {{ $items->withQueryString()->links() }}
            </div>
        </div>
    @endif
</div>
@endsection
