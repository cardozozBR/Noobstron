@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <span class="text-xs font-semibold uppercase tracking-[0.16em] text-gray-500">
                {{ __('leads.title') }}
            </span>

            <h1 class="mt-1 text-3xl font-bold tracking-tight text-gray-900">
                {{ __('leads.title') }}
            </h1>
        </div>

        <a
            href="{{ route('leads.create') }}"
            class="inline-flex items-center justify-center rounded-lg bg-[var(--primary)] px-4 py-2.5 text-sm font-semibold shadow-sm transition hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-[var(--primary)] focus:ring-offset-2" style="color: white;"
        >
            {{ __('leads.new') }}
        </a>
    </div>

    @if (session('success'))
        <div class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-800">
            {{ session('success') }}
        </div>
    @endif

    <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
        <form
            method="GET"
            action="{{ route('leads.index') }}"
            class="grid gap-4 md:grid-cols-2 xl:grid-cols-4"
        >
            <div class="xl:col-span-2">
                <label
                    for="search"
                    class="block text-sm font-semibold text-gray-700"
                >
                    {{ __('leads.filters.search') }}
                </label>

                <input
                    id="search"
                    name="search"
                    type="search"
                    value="{{ request('search') }}"
                    placeholder="{{ __('leads.filters.search_placeholder') }}"
                    class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 shadow-sm outline-none transition placeholder:text-gray-400 focus:border-[var(--primary)] focus:ring-2 focus:ring-[color:var(--primary)]/15"
                >
            </div>

            <div>
                <label
                    for="status"
                    class="block text-sm font-semibold text-gray-700"
                >
                    {{ __('leads.fields.status') }}
                </label>

                <select
                    id="status"
                    name="status"
                    class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 shadow-sm outline-none focus:border-[var(--primary)] focus:ring-2 focus:ring-[color:var(--primary)]/15"
                >
                    <option value="">
                        {{ __('leads.filters.all_statuses') }}
                    </option>

                    @foreach ($statuses as $status)
                        <option
                            value="{{ $status->value }}"
                            @selected(request('status') === $status->value)
                        >
                            {{ $status->label() }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label
                    for="source"
                    class="block text-sm font-semibold text-gray-700"
                >
                    {{ __('leads.fields.source') }}
                </label>

                <select
                    id="source"
                    name="source"
                    class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 shadow-sm outline-none focus:border-[var(--primary)] focus:ring-2 focus:ring-[color:var(--primary)]/15"
                >
                    <option value="">
                        {{ __('leads.filters.all_sources') }}
                    </option>

                    @foreach ($sources as $source)
                        <option
                            value="{{ $source->value }}"
                            @selected(request('source') === $source->value)
                        >
                            {{ $source->label() }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="md:col-span-2 xl:col-span-2">
                <label
                    for="responsible_user_id"
                    class="block text-sm font-semibold text-gray-700"
                >
                    {{ __('leads.fields.responsible') }}
                </label>

                <select
                    id="responsible_user_id"
                    name="responsible_user_id"
                    class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 shadow-sm outline-none focus:border-[var(--primary)] focus:ring-2 focus:ring-[color:var(--primary)]/15"
                >
                    <option value="">
                        {{ __('leads.filters.all_responsibles') }}
                    </option>

                    @foreach ($responsibles as $responsible)
                        <option
                            value="{{ $responsible->id }}"
                            @selected(
                                (string) request('responsible_user_id')
                                === (string) $responsible->id
                            )
                        >
                            {{ $responsible->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="flex flex-wrap items-end gap-2 md:col-span-2 xl:col-span-2 xl:justify-end">
                <button
                    type="submit"
                    class="inline-flex min-h-10 items-center justify-center rounded-lg bg-gray-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-gray-800"
                >
                    {{ __('leads.filters.filter') }}
                </button>

                <a
                    href="{{ route('leads.index') }}"
                    class="inline-flex min-h-10 items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50"
                >
                    {{ __('leads.filters.clear') }}
                </a>
            </div>
        </form>
    </div>

    @if ($leads->count() === 0)
        <div class="rounded-2xl border border-dashed border-gray-300 bg-white px-6 py-14 text-center shadow-sm">
            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 text-xl text-gray-500">
                +
            </div>

            <h2 class="mt-4 text-lg font-semibold text-gray-900">
                {{ __('leads.empty') }}
            </h2>

            <a
                href="{{ route('leads.create') }}"
                class="mt-5 inline-flex items-center justify-center rounded-lg bg-[var(--primary)] px-4 py-2.5 text-sm font-semibold shadow-sm transition hover:opacity-90" style="color: white;"
            >
                {{ __('leads.new') }}
            </a>
        </div>
    @else
        <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                {{ __('leads.fields.name') }}
                            </th>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                {{ __('leads.fields.status') }}
                            </th>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                {{ __('leads.fields.source') }}
                            </th>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                {{ __('leads.fields.responsible') }}
                            </th>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                {{ __('leads.contact') }}
                            </th>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                {{ __('leads.fields.tags') }}
                            </th>
                            <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">
                                {{ __('leads.actions_column') }}
                            </th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-100 bg-white">
                        @foreach ($leads as $lead)
                            <tr class="transition hover:bg-gray-50/80">
                                <td class="whitespace-nowrap px-5 py-4">
                                    <div class="font-semibold text-gray-900">
                                        {{ $lead->name }}
                                    </div>
                                </td>

                                <td class="whitespace-nowrap px-5 py-4">
                                    <span class="inline-flex rounded-full bg-blue-50 px-2.5 py-1 text-xs font-semibold text-blue-700 ring-1 ring-inset ring-blue-200">
                                        {{ $lead->status->label() }}
                                    </span>
                                </td>

                                <td class="whitespace-nowrap px-5 py-4">
                                    <span class="inline-flex rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-700">
                                        {{ $lead->source->label() }}
                                    </span>
                                </td>

                                <td class="whitespace-nowrap px-5 py-4 text-sm text-gray-700">
                                    {{ $lead->responsible?->name ?? '—' }}
                                </td>

                                <td class="px-5 py-4 text-sm text-gray-600">
                                    @if ($lead->email)
                                        <div class="whitespace-nowrap">{{ $lead->email }}</div>
                                    @endif

                                    @if ($lead->phone)
                                        <div class="mt-1 whitespace-nowrap text-gray-500">
                                            {{ $lead->phone }}
                                        </div>
                                    @endif

                                    @if (!$lead->email && !$lead->phone)
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>

                                <td class="px-5 py-4">
                                    <div class="flex max-w-xs flex-wrap gap-1.5">
                                        @forelse ($lead->tags ?? [] as $tag)
                                            <span class="inline-flex rounded-full bg-violet-50 px-2.5 py-1 text-xs font-medium text-violet-700 ring-1 ring-inset ring-violet-200">
                                                {{ $tag }}
                                            </span>
                                        @empty
                                            <span class="text-sm text-gray-400">—</span>
                                        @endforelse
                                    </div>
                                </td>

                                <td class="whitespace-nowrap px-5 py-4 text-right">
                                    <div class="inline-flex items-center gap-2">
                                        <a
                                            href="{{ route('leads.edit', $lead->id) }}"
                                            class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs font-semibold text-gray-700 transition hover:bg-gray-50"
                                        >
                                            {{ __('leads.actions.edit') }}
                                        </a>

                                        <form
                                            method="POST"
                                            action="{{ route('leads.destroy', $lead->id) }}"
                                            class="inline"
                                        >
                                            @csrf
                                            @method('DELETE')

                                            <button
                                                type="submit"
                                                class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs font-semibold text-red-700 transition hover:bg-red-100"
                                            >
                                                {{ __('leads.actions.delete') }}
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="border-t border-gray-200 bg-white px-5 py-4">
                {{ $leads->withQueryString()->links() }}
            </div>
        </div>
    @endif
</div>
@endsection
