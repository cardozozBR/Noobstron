@extends('layouts.app')

@php
    use App\Enums\Permission;
    use App\Support\TenantMoneyFormatter;
@endphp

@section('title', __('proposals.title'))

@section('content')
<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <span class="text-xs font-semibold uppercase tracking-[0.16em] text-gray-500">
                {{ __('proposals.title') }}
            </span>

            <h1 class="mt-1 text-3xl font-bold tracking-tight text-gray-900">
                {{ __('proposals.heading') }}
            </h1>

            <p class="mt-1 max-w-2xl text-sm text-gray-600">
                {{ __('proposals.description') }}
            </p>
        </div>

        @if (auth()->user()->hasPermission(Permission::PROPOSALS_CREATE))
            <a
                href="{{ route('proposals.create') }}"
                class="inline-flex min-h-10 items-center justify-center gap-2 rounded-lg bg-[var(--primary)] px-4 py-2.5 text-sm font-semibold shadow-sm transition hover:opacity-90"
                style="color:white;"
            >
                <span aria-hidden="true">＋</span>
                {{ __('proposals.new') }}
            </a>
        @endif
    </div>

    <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
        <form method="GET" action="{{ route('proposals.index') }}">
            <div class="grid gap-4 md:grid-cols-[minmax(0,2fr)_minmax(220px,1fr)]">
                <div>
                    <label for="search" class="block text-sm font-semibold text-gray-700">
                        {{ __('proposals.search') }}
                    </label>
                    <input
                        id="search"
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="{{ __('proposals.search_placeholder') }}"
                        class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 shadow-sm outline-none transition placeholder:text-gray-400 focus:border-[var(--primary)] focus:ring-2 focus:ring-[color:var(--primary)]/15"
                    >
                </div>

                <div>
                    <label for="status" class="block text-sm font-semibold text-gray-700">
                        {{ __('proposals.status') }}
                    </label>
                    <select
                        id="status"
                        name="status"
                        class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 shadow-sm outline-none transition focus:border-[var(--primary)] focus:ring-2 focus:ring-[color:var(--primary)]/15"
                    >
                        <option value="">{{ __('proposals.all_statuses') }}</option>
                        @foreach ($statuses as $status)
                            <option value="{{ $status->value }}" @selected(request('status') === $status->value)>
                                {{ __('proposals.' . $status->value) }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="mt-4 flex flex-wrap gap-2">
                <button type="submit" class="inline-flex min-h-10 items-center justify-center rounded-lg bg-gray-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-gray-800">
                    {{ __('proposals.search') }}
                </button>
                <a href="{{ route('proposals.index') }}" class="inline-flex min-h-10 items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50" style="color:#374151;">
                    {{ __('proposals.all_statuses') }}
                </a>
            </div>
        </form>
    </div>

    @if ($proposals->isEmpty())
        <div class="rounded-2xl border border-dashed border-gray-300 bg-white px-6 py-12 text-center shadow-sm">
            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-blue-50 text-[var(--primary)] ring-1 ring-inset ring-blue-100">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-7 w-7" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M7 3.75h7l4 4v12.5H7a2 2 0 0 1-2-2V5.75a2 2 0 0 1 2-2Z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M14 3.75v4h4M8.5 12h6M8.5 15.5h6"/>
                </svg>
            </div>
            <h2 class="mt-4 text-lg font-semibold text-gray-900">{{ __('proposals.empty') }}</h2>
            @if (auth()->user()->hasPermission(Permission::PROPOSALS_CREATE))
                <a href="{{ route('proposals.create') }}" class="mt-5 inline-flex min-h-10 items-center justify-center gap-2 rounded-lg bg-[var(--primary)] px-4 py-2.5 text-sm font-semibold shadow-sm transition hover:opacity-90" style="color:white;">
                    <span aria-hidden="true">＋</span>
                    {{ __('proposals.new') }}
                </a>
            @endif
        </div>
    @else
        <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('proposals.number') }}</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('proposals.customer') }}</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('proposals.status') }}</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('proposals.valid_until') }}</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('proposals.total') }}</th>
                            <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('proposals.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        @foreach ($proposals as $proposal)
                            <tr class="transition hover:bg-gray-50/80">
                                <td class="px-5 py-4 font-semibold text-gray-900">{{ $proposal->number }}</td>
                                <td class="px-5 py-4 text-sm text-gray-700">{{ $proposal->customer?->name ?? '—' }}</td>
                                <td class="px-5 py-4">
                                    <span class="inline-flex rounded-full bg-blue-50 px-2.5 py-1 text-xs font-semibold text-blue-700 ring-1 ring-inset ring-blue-100">
                                        {{ __('proposals.' . $proposal->status->value) }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-sm text-gray-600">{{ $proposal->valid_until ? $proposal->valid_until->format('d/m/Y') : '—' }}</td>
                                <td class="px-5 py-4 text-sm font-semibold text-gray-900">
                                    {{ app(TenantMoneyFormatter::class)->formatMinor($proposal->total_minor, $proposal->currency) }}
                                </td>
                                <td class="px-5 py-4 text-right">
                                    <div class="inline-flex flex-wrap justify-end gap-2">
                                        <a href="{{ route('proposals.pdf', $proposal->id) }}" class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs font-semibold text-gray-700 transition hover:bg-gray-50" style="color:#374151;">{{ __('proposals.pdf') }}</a>
                                        @if (auth()->user()->hasPermission(Permission::PROPOSALS_UPDATE))
                                            <a href="{{ route('proposals.edit', $proposal->id) }}" class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs font-semibold text-gray-700 transition hover:bg-gray-50" style="color:#374151;">{{ __('proposals.edit') }}</a>
                                        @endif
                                        @if (auth()->user()->hasPermission(Permission::PROPOSALS_DELETE))
                                            <form method="POST" action="{{ route('proposals.destroy', $proposal->id) }}" onsubmit="return confirm('{{ __('proposals.delete_confirm') }}')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs font-semibold text-red-700 transition hover:bg-red-100">{{ __('proposals.delete') }}</button>
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
                {{ $proposals->withQueryString()->links() }}
            </div>
        </div>
    @endif
</div>
@endsection
