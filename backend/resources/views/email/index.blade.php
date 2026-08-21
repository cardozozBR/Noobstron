@extends('layouts.app')

@section('content')

<style>
.email-index-page{display:grid;gap:24px}
.email-index-page .email-header{display:flex;align-items:flex-end;justify-content:space-between;gap:18px;flex-wrap:wrap}
.email-index-page .email-card{border-radius:16px}
.email-index-page .email-card:hover{transform:translateY(-1px)}
.email-index-page .email-actions{display:flex;gap:8px;flex-wrap:wrap}
.email-index-page .email-status{display:inline-flex;padding:4px 8px;border-radius:999px;background:#f3f4f6;color:#374151;font-size:11px;font-weight:700}
.email-index-page .pagination-wrap{padding-top:4px}
</style>

<div class="email-index-page">
<div class="space-y-6">
    <div class="email-header">
        <div>
            <span class="text-xs font-semibold uppercase tracking-[0.16em] text-gray-500">{{ __('email.title') }}</span>
            <h1 class="mt-1 text-3xl font-bold tracking-tight text-gray-900">{{ __('email.title') }}</h1>
            <p class="mt-1 max-w-2xl text-sm text-gray-600">{{ __('email.subtitle') }}</p>
        </div>

        <div class="flex flex-wrap gap-2">
            @if (auth()->user()?->hasPermission(\App\Enums\Permission::EMAIL_TEMPLATES))
                <a href="{{ route('email.templates.index') }}" class="inline-flex min-h-10 items-center justify-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50" style="color:#374151;">
                    <span aria-hidden="true">▤</span> {{ __('email.templates') }}
                </a>
            @endif
            @if (auth()->user()?->hasPermission(\App\Enums\Permission::EMAIL_CREATE))
                <a href="{{ route('email.create') }}" class="inline-flex min-h-10 items-center justify-center gap-2 rounded-lg bg-gray-900 px-4 py-2 text-sm font-semibold shadow-sm transition hover:bg-gray-800" style="color:white;">
                    <span aria-hidden="true">+</span> {{ __('email.new') }}
                </a>
            @endif
        </div>
    </div>

    @if (session('success'))
        <div class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-800">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-800">{{ session('error') }}</div>
    @endif

    @if ($messages->isEmpty())
        <div class="rounded-2xl border border-dashed border-gray-300 bg-white px-6 py-10 text-center shadow-sm">
            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-blue-50 text-[var(--primary)] ring-1 ring-inset ring-blue-100">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-7 w-7" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75A2.25 2.25 0 0 1 6 4.5h12a2.25 2.25 0 0 1 2.25 2.25v10.5A2.25 2.25 0 0 1 18 19.5H6a2.25 2.25 0 0 1-2.25-2.25V6.75Z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 7.5 6.22 4.15a2.25 2.25 0 0 0 2.56 0L19.5 7.5"/>
                </svg>
            </div>
            <h2 class="mt-4 text-lg font-semibold text-gray-900">{{ __('email.empty_title') }}</h2>
            <p class="mx-auto mt-2 max-w-lg text-sm leading-6 text-gray-600">{{ __('email.empty_text') }}</p>
            @if (auth()->user()?->hasPermission(\App\Enums\Permission::EMAIL_CREATE))
                <a href="{{ route('email.create') }}" class="mt-5 inline-flex min-h-10 items-center justify-center gap-2 rounded-lg bg-gray-900 px-4 py-2 text-sm font-semibold shadow-sm transition hover:bg-gray-800" style="color:white;">
                    <span aria-hidden="true">+</span> {{ __('email.new') }}
                </a>
            @endif
        </div>
    @else
        <div class="space-y-3">
            @foreach ($messages as $message)
                <article class="email-card rounded-2xl border border-gray-200 bg-white p-5 shadow-sm transition hover:border-gray-300 hover:shadow-md">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <h2 class="truncate text-base font-semibold text-gray-900">{{ $message->subject }}</h2>
                                <span class="email-status">{{ __('email.status.' . $message->status->value) }}</span>
                            </div>
                            <div class="mt-3 flex items-center gap-3">
                                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-blue-50 text-sm font-bold text-[var(--primary)]">
                                    {{ mb_strtoupper(mb_substr($message->to_name ?: $message->to_email, 0, 1)) }}
                                </div>
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-medium text-gray-800">{{ $message->to_name ?: $message->to_email }}</p>
                                    @if ($message->to_name)<p class="truncate text-xs text-gray-500">{{ $message->to_email }}</p>@endif
                                </div>
                            </div>
                            <p class="mt-3 line-clamp-2 text-sm leading-6 text-gray-600">{{ $message->body }}</p>
                            <p class="mt-3 text-xs text-gray-500">{{ __('email.status_help.' . $message->status->value) }}</p>
                            @if ($message->status->value === 'failed' && $message->failure_reason)
                                <p class="mt-2 rounded-lg bg-red-50 px-3 py-2 text-xs text-red-700">{{ $message->failure_reason }}</p>
                            @endif
                        </div>
                        <div class="flex shrink-0 gap-2">
                            @if ($message->status->value === 'pending' && auth()->user()?->hasPermission(\App\Enums\Permission::EMAIL_SEND))
                                <form method="POST" action="{{ route('email.send', $message->id) }}">@csrf
                                    <button type="submit" class="rounded-lg bg-gray-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-gray-800">{{ __('email.send') }}</button>
                                </form>
                            @endif
                            @if ($message->status->value === 'failed' && auth()->user()?->hasPermission(\App\Enums\Permission::EMAIL_SEND))
                                <form method="POST" action="{{ route('email.retry', $message->id) }}">@csrf
                                    <button type="submit" class="rounded-lg bg-gray-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-gray-800">{{ __('email.retry') }}</button>
                                </form>
                            @endif
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
        <div class="pagination-wrap">{{ $messages->links() }}</div>
    @endif
</div>
</div>
@endsection
