@extends('layouts.app')

@section('content')

<style>
.inbox-show-page{display:grid;gap:24px}
.inbox-show-page .back-link{display:inline-flex;align-items:center;gap:6px;color:#4b5563;text-decoration:none;font-size:13px;font-weight:600}
.inbox-show-page .conversation-head{display:flex;align-items:flex-start;justify-content:space-between;gap:18px;flex-wrap:wrap}
.inbox-show-page .conversation-head h1{margin:0;font-size:28px;letter-spacing:-.025em}
.inbox-show-page .summary-card,
.inbox-show-page .action-card,
.inbox-show-page .message-card{border-radius:16px}
.inbox-show-page .action-card{box-shadow:0 1px 2px rgba(15,23,42,.03)}
.inbox-show-page .message-card{transition:border-color 160ms ease,box-shadow 160ms ease}
.inbox-show-page .message-card:hover{border-color:#d1d5db;box-shadow:0 6px 18px rgba(15,23,42,.04)}
.inbox-show-page .history-heading{display:flex;align-items:center;justify-content:space-between}
</style>

<div class="inbox-show-page">
<div class="space-y-6">
    <div>
        <a
            href="{{ route('inbox.index') }}"
            class="back-link"
        >
            ← {{ __('inbox.back') }}
        </a>

        <div class="conversation-head mt-4">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900">
                    {{
                        $conversation->display_name
                        ?: $conversation->external_address
                    }}
                </h1>

                <p class="mt-1 text-sm text-gray-600">
                    {{ $conversation->external_address }}
                </p>
            </div>

            <div class="flex flex-wrap gap-2">
                <span class="rounded-full bg-gray-100 px-3 py-1 text-sm text-gray-700">
                    {{
                        __(
                            'inbox.channels.'
                            . $conversation->channel->value
                        )
                    }}
                </span>

                <span class="rounded-full bg-gray-100 px-3 py-1 text-sm text-gray-700">
                    {{
                        __(
                            'inbox.statuses.'
                            . $conversation->status->value
                        )
                    }}
                </span>
            </div>
        </div>
    </div>

    <div class="summary-card rounded-xl border border-gray-200 bg-white p-5">
        <dl class="grid gap-4 sm:grid-cols-2">
            <div>
                <dt class="text-xs font-medium uppercase text-gray-500">
                    {{ __('inbox.responsible') }}
                </dt>

                <dd class="mt-1 text-sm text-gray-900">
                    {{
                        $conversation->responsible?->name
                        ?? __('inbox.unassigned')
                    }}
                </dd>
            </div>

            @if ($conversation->lead)
                <div>
                    <dt class="text-xs font-medium uppercase text-gray-500">
                        {{ __('inbox.lead') }}
                    </dt>

                    <dd class="mt-1 text-sm text-gray-900">
                        {{ $conversation->lead->name }}
                    </dd>
                </div>
            @endif

            @if ($conversation->customer)
                <div>
                    <dt class="text-xs font-medium uppercase text-gray-500">
                        {{ __('inbox.customer') }}
                    </dt>

                    <dd class="mt-1 text-sm text-gray-900">
                        {{ $conversation->customer->name }}
                    </dd>
                </div>
            @endif
        </dl>
    </div>

    <section class="grid gap-4 lg:grid-cols-2">
        @if (
            auth()->user()?->hasPermission(
                \App\Enums\Permission::INBOX_MANAGE
            )
        )
            <form
                method="POST"
                action="{{ route('inbox.status', $conversation->id) }}"
                class="action-card rounded-xl border border-gray-200 bg-white p-5"
            >
                @csrf
                @method('PUT')

                <label
                    for="status"
                    class="block text-sm font-medium text-gray-700"
                >
                    {{ __('inbox.fields.status') }}
                </label>

                <select
                    id="status"
                    name="status"
                    class="mt-2 w-full rounded-lg border-gray-300"
                >
                    @foreach ($statuses as $status)
                        <option
                            value="{{ $status->value }}"
                            @selected(
                                $conversation->status ===
                                $status
                            )
                        >
                            {{
                                __(
                                    'inbox.statuses.'
                                    . $status->value
                                )
                            }}
                        </option>
                    @endforeach
                </select>

                <button
                    type="submit"
                    class="mt-3 rounded-lg bg-gray-900 px-4 py-2 text-sm font-medium text-white"
                >
                    {{ __('inbox.actions.update_status') }}
                </button>
            </form>
        @endif

        @if (
            auth()->user()?->hasPermission(
                \App\Enums\Permission::INBOX_ASSIGN
            )
        )
            <form
                method="POST"
                action="{{ route('inbox.assign', $conversation->id) }}"
                class="action-card rounded-xl border border-gray-200 bg-white p-5"
            >
                @csrf
                @method('PUT')

                <label
                    for="responsible_user_id"
                    class="block text-sm font-medium text-gray-700"
                >
                    {{ __('inbox.responsible') }}
                </label>

                <input
                    id="responsible_user_id"
                    name="responsible_user_id"
                    type="number"
                    value="{{ $conversation->responsible_user_id }}"
                    class="mt-2 w-full rounded-lg border-gray-300"
                >

                <p class="mt-2 text-xs text-gray-500">
                    {{ __('inbox.assign_help') }}
                </p>

                <button
                    type="submit"
                    class="mt-3 rounded-lg bg-gray-900 px-4 py-2 text-sm font-medium text-white"
                >
                    {{ __('inbox.actions.assign') }}
                </button>
            </form>
        @endif
    </section>
    <section class="space-y-3">
        <div class="history-heading"><h2 class="text-lg font-semibold text-gray-900">
            {{ __('inbox.history') }}
        </h2></div>

        @forelse ($history as $message)
            <article class="action-card rounded-xl border border-gray-200 bg-white p-5">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <span class="text-xs font-medium uppercase text-gray-500">
                        {{
                            __(
                                'inbox.channels.'
                                . $message['channel']
                            )
                        }}
                    </span>

                    @if ($message['created_at'])
                        <span class="text-xs text-gray-500">
                            {{
                                $message['created_at']
                                    ->format('d/m/Y H:i')
                            }}
                        </span>
                    @endif
                </div>

                <p class="mt-3 whitespace-pre-wrap text-sm text-gray-800">{{
                    $message['body']
                }}</p>
            </article>
        @empty
            <div class="rounded-xl border border-dashed border-gray-300 bg-white p-8 text-center">
                <p class="text-sm text-gray-600">
                    {{ __('inbox.no_history') }}
                </p>
            </div>
        @endforelse
    </section>
</div>
</div>
@endsection
