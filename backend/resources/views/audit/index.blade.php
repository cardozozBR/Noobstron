@extends('layouts.app')

@section('title', __('audit.title'))

@section('content')
<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <span class="text-xs font-semibold uppercase tracking-[0.16em] text-gray-500">
                {{ __('audit.eyebrow') }}
            </span>

            <h1 class="mt-1 text-3xl font-bold tracking-tight text-gray-900">
                {{ __('audit.heading') }}
            </h1>

            <p class="mt-1 max-w-3xl text-sm text-gray-600">
                {{ __('audit.description') }}
                <strong class="font-semibold text-gray-900">{{ $tenant->name }}</strong>
            </p>
        </div>

        <a
            href="{{ route('dashboard') }}"
            class="inline-flex min-h-10 items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50"
            style="color:#374151;"
        >
            {{ __('audit.back_dashboard') }}
        </a>
    </div>

    <div class="grid gap-4 md:grid-cols-3">
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
            <div class="text-xs font-semibold uppercase tracking-[0.12em] text-gray-500">
                {{ __('audit.stats.total_events') }}
            </div>
            <div class="mt-2 text-3xl font-bold tracking-tight text-gray-900">
                {{ $totalLogs }}
            </div>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
            <div class="text-xs font-semibold uppercase tracking-[0.12em] text-gray-500">
                {{ __('audit.stats.users_involved') }}
            </div>
            <div class="mt-2 text-3xl font-bold tracking-tight text-gray-900">
                {{ $totalUsers }}
            </div>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
            <div class="text-xs font-semibold uppercase tracking-[0.12em] text-gray-500">
                {{ __('audit.stats.different_actions') }}
            </div>
            <div class="mt-2 text-3xl font-bold tracking-tight text-gray-900">
                {{ $totalActions }}
            </div>
        </div>
    </div>

    <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm sm:p-6">
        <div>
            <span class="text-xs font-semibold uppercase tracking-[0.14em] text-gray-500">
                {{ __('audit.filters.eyebrow') }}
            </span>

            <h2 class="mt-1 text-lg font-semibold text-gray-900">
                {{ __('audit.filters.heading') }}
            </h2>
        </div>

        <form
            method="GET"
            action="{{ route('audit.index') }}"
            class="mt-5"
        >
            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                <div>
                    <label for="search" class="block text-sm font-semibold text-gray-700">
                        {{ __('audit.filters.search') }}
                    </label>

                    <input
                        type="text"
                        id="search"
                        name="search"
                        value="{{ $search }}"
                        placeholder="{{ __('audit.filters.search_placeholder') }}"
                        class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 shadow-sm outline-none transition placeholder:text-gray-400 focus:border-[var(--primary)] focus:ring-2 focus:ring-[color:var(--primary)]/15"
                    >
                </div>

                <div>
                    <label for="user_id" class="block text-sm font-semibold text-gray-700">
                        {{ __('audit.filters.user') }}
                    </label>

                    <select
                        id="user_id"
                        name="user_id"
                        class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 shadow-sm outline-none transition focus:border-[var(--primary)] focus:ring-2 focus:ring-[color:var(--primary)]/15"
                    >
                        <option value="">{{ __('audit.filters.all_users') }}</option>

                        @foreach ($users as $user)
                            <option
                                value="{{ $user->id }}"
                                @selected((string) $selectedUser === (string) $user->id)
                            >
                                {{ $user->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="action" class="block text-sm font-semibold text-gray-700">
                        {{ __('audit.filters.action') }}
                    </label>

                    <select
                        id="action"
                        name="action"
                        class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 shadow-sm outline-none transition focus:border-[var(--primary)] focus:ring-2 focus:ring-[color:var(--primary)]/15"
                    >
                        <option value="">{{ __('audit.filters.all_actions') }}</option>

                        @foreach ($actions as $action)
                            <option
                                value="{{ $action }}"
                                @selected($selectedAction === $action)
                            >
                                {{ $action }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="origin" class="block text-sm font-semibold text-gray-700">
                        {{ __('audit.filters.origin') }}
                    </label>

                    <select
                        id="origin"
                        name="origin"
                        class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 shadow-sm outline-none transition focus:border-[var(--primary)] focus:ring-2 focus:ring-[color:var(--primary)]/15"
                    >
                        <option value="all" @selected($origin === 'all')>{{ __('audit.filters.all') }}</option>
                        <option value="user" @selected($origin === 'user')>{{ __('audit.filters.user_origin') }}</option>
                        <option value="system" @selected($origin === 'system')>{{ __('audit.filters.system') }}</option>
                    </select>
                </div>

                <div>
                    <label for="date_from" class="block text-sm font-semibold text-gray-700">
                        {{ __('audit.filters.from') }}
                    </label>

                    <input
                        type="date"
                        id="date_from"
                        name="date_from"
                        value="{{ $dateFrom }}"
                        class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 shadow-sm outline-none transition focus:border-[var(--primary)] focus:ring-2 focus:ring-[color:var(--primary)]/15"
                    >
                </div>

                <div>
                    <label for="date_to" class="block text-sm font-semibold text-gray-700">
                        {{ __('audit.filters.to') }}
                    </label>

                    <input
                        type="date"
                        id="date_to"
                        name="date_to"
                        value="{{ $dateTo }}"
                        class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 shadow-sm outline-none transition focus:border-[var(--primary)] focus:ring-2 focus:ring-[color:var(--primary)]/15"
                    >
                </div>
            </div>

            <div class="mt-5 flex flex-wrap gap-2">
                <button
                    type="submit"
                    class="inline-flex min-h-10 items-center justify-center rounded-lg bg-gray-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-gray-800"
                >
                    {{ __('audit.filters.submit') }}
                </button>

                @if ($search || $selectedAction || $selectedUser || $dateFrom || $dateTo || $origin !== 'all')
                    <a
                        href="{{ route('audit.index') }}"
                        class="inline-flex min-h-10 items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50"
                        style="color:#374151;"
                    >
                        {{ __('audit.filters.clear') }}
                    </a>
                @endif
            </div>
        </form>
    </div>

    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
        <div class="border-b border-gray-200 px-5 py-4 sm:px-6">
            <span class="text-xs font-semibold uppercase tracking-[0.14em] text-gray-500">
                {{ __('audit.history.eyebrow') }}
            </span>

            <h2 class="mt-1 text-lg font-semibold text-gray-900">
                {{ __('audit.history.heading') }}
            </h2>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                            {{ __('audit.history.date') }}
                        </th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                            {{ __('audit.history.user') }}
                        </th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                            {{ __('audit.history.action') }}
                        </th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                            {{ __('audit.history.description') }}
                        </th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-100 bg-white">
                    @forelse ($logs as $log)
                        @php
                            $action = (string) $log->action;

                            if (str_contains($action, 'failed')) {
                                $actionClass = 'bg-red-50 text-red-700 ring-red-100';
                            } elseif (
                                str_contains($action, 'success')
                                || str_contains($action, 'created')
                            ) {
                                $actionClass = 'bg-green-50 text-green-700 ring-green-100';
                            } elseif (
                                str_contains($action, 'deleted')
                                || str_contains($action, 'logout')
                            ) {
                                $actionClass = 'bg-gray-100 text-gray-700 ring-gray-200';
                            } else {
                                $actionClass = 'bg-blue-50 text-blue-700 ring-blue-100';
                            }
                        @endphp

                        <tr class="transition hover:bg-gray-50/80">
                            <td class="whitespace-nowrap px-5 py-4 text-sm text-gray-600">
                                {{ app(\App\Support\TenantDateTime::class)->formatForTenant($log->created_at) }}
                            </td>

                            <td class="px-5 py-4 text-sm font-medium text-gray-800">
                                {{ $log->user?->name ?? __('audit.history.system') }}
                            </td>

                            <td class="px-5 py-4">
                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset {{ $actionClass }}">
                                    {{ $log->action }}
                                </span>
                            </td>

                            <td class="max-w-2xl px-5 py-4 text-sm leading-6 text-gray-600">
                                {{ $log->description }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-14 text-center">
                                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 text-gray-500">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-6 w-6" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5.5h6M8 3.5h8v3H8v-3ZM6.5 5.5h11A1.5 1.5 0 0 1 19 7v13H5V7a1.5 1.5 0 0 1 1.5-1.5Z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.5 10h7M8.5 13.5h7M8.5 17h4"/>
                                    </svg>
                                </div>

                                <p class="mt-3 text-sm font-semibold text-gray-900">
                                    {{ __('audit.history.empty') }}
                                </p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($logs->hasPages())
            <div class="border-t border-gray-200 bg-white px-5 py-4 sm:px-6">
                {{ $logs->withQueryString()->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
