@extends('layouts.app')

@section('content')

<style>
.inbox-index-page{display:grid;gap:24px}
.inbox-index-page .inbox-hero{display:flex;align-items:flex-end;justify-content:space-between;gap:18px}
.inbox-index-page .inbox-hero h1{margin:2px 0 0;font-size:30px;line-height:1.15;letter-spacing:-.025em}
.inbox-index-page .inbox-hero p{margin:6px 0 0;color:#6b7280;font-size:14px}
.inbox-index-page .filter-panel{border-radius:16px}
.inbox-index-page .conversation-card{border-radius:16px}
.inbox-index-page .conversation-card:hover{transform:translateY(-1px)}
.inbox-index-page .conversation-meta{display:flex;flex-wrap:wrap;gap:8px;align-items:center}
.inbox-index-page .preview-panel{border-radius:16px}
@media(max-width:800px){.inbox-index-page .inbox-hero{align-items:flex-start;flex-direction:column}}
</style>

<div class="inbox-index-page">
<div class="space-y-6">
    <div class="inbox-hero">
        <span class="text-xs font-semibold uppercase tracking-[0.16em] text-gray-500">
            {{ __('inbox.title') }}
        </span>

        <h1 class="mt-1 text-3xl font-bold tracking-tight text-gray-900">
            {{ __('inbox.title') }}
        </h1>

        <p class="mt-1 max-w-2xl text-sm text-gray-600">
            {{ __('inbox.subtitle') }}
        </p>
    </div>

    <form
        method="GET"
        action="{{ route('inbox.index') }}"
        class="filter-panel rounded-2xl border border-gray-200 bg-white p-5 shadow-sm"
    >
        <div class="grid gap-4 lg:grid-cols-12">
            <div class="lg:col-span-6">
                <label
                    for="search"
                    class="block text-sm font-semibold text-gray-700"
                >
                    {{ __('inbox.filters.search') }}
                </label>

                <div class="relative mt-1">
                    <input
                        id="search"
                        name="search"
                        type="search"
                        value="{{ request('search') }}"
                        placeholder="{{ __('inbox.filters.search_placeholder') }}"
                        class="block w-full rounded-lg border border-gray-300 bg-white py-2.5 pl-3 pr-10 text-sm text-gray-900 shadow-sm outline-none transition placeholder:text-gray-400 focus:border-[var(--primary)] focus:ring-2 focus:ring-[color:var(--primary)]/15"
                    >

                    <span
                        class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-gray-400"
                        aria-hidden="true"
                    >
                        ⌕
                    </span>
                </div>
            </div>

            <div class="lg:col-span-3">
                <label
                    for="channel"
                    class="block text-sm font-semibold text-gray-700"
                >
                    {{ __('inbox.fields.channel') }}
                </label>

                <select
                    id="channel"
                    name="channel"
                    class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 shadow-sm outline-none transition focus:border-[var(--primary)] focus:ring-2 focus:ring-[color:var(--primary)]/15"
                >
                    <option value="">
                        {{ __('inbox.filters.all_channels') }}
                    </option>

                    @foreach ($channels as $channel)
                        <option
                            value="{{ $channel->value }}"
                            @selected(request('channel') === $channel->value)
                        >
                            {{ __('inbox.channels.' . $channel->value) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="lg:col-span-3">
                <label
                    for="status"
                    class="block text-sm font-semibold text-gray-700"
                >
                    {{ __('inbox.fields.status') }}
                </label>

                <select
                    id="status"
                    name="status"
                    class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 shadow-sm outline-none transition focus:border-[var(--primary)] focus:ring-2 focus:ring-[color:var(--primary)]/15"
                >
                    <option value="">
                        {{ __('inbox.filters.all_statuses') }}
                    </option>

                    @foreach ($statuses as $status)
                        <option
                            value="{{ $status->value }}"
                            @selected(request('status') === $status->value)
                        >
                            {{ __('inbox.statuses.' . $status->value) }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="mt-4 flex flex-wrap items-center gap-2">
            <button
                type="submit"
                class="inline-flex min-h-10 items-center justify-center gap-2 rounded-lg bg-gray-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-gray-800"
            >
                <span aria-hidden="true">⌄</span>
                {{ __('inbox.filters.filter') }}
            </button>

            <a
                href="{{ route('inbox.index') }}"
                class="inline-flex min-h-10 items-center justify-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50"
            >
                <span aria-hidden="true">↻</span>
                {{ __('inbox.filters.clear') }}
            </a>
        </div>
    </form>

    @if ($conversations->isEmpty())
        <div class="rounded-2xl border border-dashed border-gray-300 bg-white px-6 py-10 text-center shadow-sm">
            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-blue-50 text-[var(--primary)] ring-1 ring-inset ring-blue-100">
                <svg
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1.8"
                    class="h-7 w-7"
                    aria-hidden="true"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M7.5 9.75h9m-9 3h5.25M6 18.75l-3 1.5.75-3.75A8.25 8.25 0 1 1 12 20.25c-1.56 0-3.02-.43-4.27-1.18L6 18.75Z"
                    />
                </svg>
            </div>

            <h2 class="mt-4 text-lg font-semibold text-gray-900">
                {{ __('inbox.empty_title') }}
            </h2>

            <p class="mx-auto mt-2 max-w-lg text-sm leading-6 text-gray-600">
                {{ __('inbox.empty_text') }}
            </p>
        </div>
    @else
        <div class="grid gap-5 lg:grid-cols-[minmax(0,0.85fr)_minmax(0,1.15fr)]">
            <div class="space-y-3">
                @foreach ($conversations as $conversation)
                    <a
                        href="{{ route('inbox.show', $conversation->id) }}"
                        class="conversation-card group block rounded-2xl border border-gray-200 bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:border-gray-300 hover:shadow-md"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h2 class="truncate font-semibold text-gray-900">
                                        {{ $conversation->display_name ?: $conversation->external_address }}
                                    </h2>

                                    <span class="inline-flex rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-700">
                                        {{ __('inbox.channels.' . $conversation->channel->value) }}
                                    </span>

                                    <span class="inline-flex rounded-full bg-blue-50 px-2.5 py-1 text-xs font-semibold text-blue-700 ring-1 ring-inset ring-blue-100">
                                        {{ __('inbox.statuses.' . $conversation->status->value) }}
                                    </span>
                                </div>

                                <p class="mt-2 truncate text-sm text-gray-600">
                                    {{ $conversation->external_address }}
                                </p>

                                @if ($conversation->responsible)
                                    <p class="mt-2 text-xs text-gray-500">
                                        {{ __('inbox.responsible') }}:
                                        {{ $conversation->responsible->name }}
                                    </p>
                                @endif
                            </div>

                            @if ($conversation->last_message_at)
                                <span class="shrink-0 text-xs text-gray-400">
                                    {{ $conversation->last_message_at->format('d/m H:i') }}
                                </span>
                            @endif
                        </div>
                    </a>
                @endforeach

                <div class="pt-1">
                    {{ $conversations->withQueryString()->links() }}
                </div>
            </div>

            <div class="preview-panel hidden rounded-2xl border border-dashed border-gray-300 bg-white p-8 text-center text-sm text-gray-500 shadow-sm lg:flex lg:min-h-[360px] lg:items-center lg:justify-center">
                <div class="max-w-sm">
                    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 text-gray-500">
                        <svg
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                            class="h-6 w-6"
                            aria-hidden="true"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M8.25 10.5h7.5m-7.5 3h4.5M5.25 18 3 19.5l.75-3A8.25 8.25 0 1 1 12 20.25c-1.7 0-3.28-.51-4.59-1.38L5.25 18Z"
                            />
                        </svg>
                    </div>

                    <p class="mt-4 font-semibold text-gray-900">
                        {{ __('inbox.empty_title') }}
                    </p>

                    <p class="mt-1 leading-6 text-gray-500">
                        {{ __('inbox.empty_text') }}
                    </p>
                </div>
            </div>
        </div>
    @endif
</div>
</div>
@endsection
