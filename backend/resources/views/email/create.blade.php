@extends('layouts.app')

@section('content')

<style>
.email-create-page{display:grid;gap:24px;max-width:960px;margin:0 auto}
.email-create-page .back-link{display:inline-flex;align-items:center;gap:6px;color:#4b5563;text-decoration:none;font-size:13px;font-weight:600}
.email-create-page .composer-card{border-radius:18px}
.email-create-page .composer-actions{display:flex;flex-wrap:wrap;justify-content:flex-end;gap:10px;padding-top:20px;border-top:1px solid #e5e7eb}
.email-create-page .save-button{border:1px solid #d1d5db;background:#fff;color:#374151}
.email-create-page .send-button{background:#111827;color:#fff}
</style>

<div class="email-create-page">
<div class="mx-auto max-w-4xl space-y-6">
    <div>
        <a
            href="{{ route('email.index') }}"
            class="back-link"
        >← {{ __('email.back') }}
        </a>

        <h1 class="mt-3 text-3xl font-bold tracking-tight text-gray-900">
            {{ __('email.new') }}
        </h1>

        <p class="mt-1 text-sm text-gray-600">
            {{ __('email.subtitle') }}
        </p>
    </div>

    <form
        method="POST"
        action="{{ route('email.store') }}"
        class="composer-card space-y-6 rounded-2xl border border-gray-200 bg-white p-6 shadow-sm sm:p-8"
    >
        @csrf

        @if ($templates->isNotEmpty())
            <div>
                <label
                    for="template_selector"
                    class="block text-sm font-semibold text-gray-700"
                >
                    {{ __('email.template') }}
                </label>

                <select
                    id="template_selector"
                    class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 shadow-sm outline-none transition focus:border-[var(--primary)] focus:ring-2 focus:ring-[color:var(--primary)]/15"
                >
                    <option value="">
                        {{ __('email.no_template') }}
                    </option>

                    @foreach ($templates as $template)
                        <option
                            value="{{ $template->id }}"
                            data-subject="{{ e($template->subject_template) }}"
                            data-body="{{ e($template->body_template) }}"
                        >
                            {{ $template->name }}
                        </option>
                    @endforeach
                </select>
            </div>
        @endif

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label
                    for="to_name"
                    class="block text-sm font-semibold text-gray-700"
                >
                    {{ __('email.recipient_name') }}
                </label>

                <input
                    id="to_name"
                    name="to_name"
                    type="text"
                    value="{{ old('to_name') }}"
                    class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 shadow-sm outline-none transition focus:border-[var(--primary)] focus:ring-2 focus:ring-[color:var(--primary)]/15"
                >
            </div>

            <div>
                <label
                    for="to_email"
                    class="block text-sm font-semibold text-gray-700"
                >
                    {{ __('email.recipient_email') }}
                </label>

                <input
                    id="to_email"
                    name="to_email"
                    type="email"
                    value="{{ old('to_email') }}"
                    required
                    class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 shadow-sm outline-none transition focus:border-[var(--primary)] focus:ring-2 focus:ring-[color:var(--primary)]/15"
                >

                @error('to_email')
                    <p class="mt-1 text-sm text-red-600">
                        {{ $message }}
                    </p>
                @enderror
            </div>
        </div>

        <div>
            <label
                for="subject"
                class="block text-sm font-semibold text-gray-700"
            >
                {{ __('email.subject') }}
            </label>

            <input
                id="subject"
                name="subject"
                type="text"
                value="{{ old('subject') }}"
                required
                class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 shadow-sm outline-none transition focus:border-[var(--primary)] focus:ring-2 focus:ring-[color:var(--primary)]/15"
            >

            @error('subject')
                <p class="mt-1 text-sm text-red-600">
                    {{ $message }}
                </p>
            @enderror
        </div>

        <div>
            <label
                for="body"
                class="block text-sm font-semibold text-gray-700"
            >
                {{ __('email.message') }}
            </label>

            <textarea
                id="body"
                name="body"
                rows="10"
                required
                class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 shadow-sm outline-none transition focus:border-[var(--primary)] focus:ring-2 focus:ring-[color:var(--primary)]/15"
            >{{ old('body') }}</textarea>

            @error('body')
                <p class="mt-1 text-sm text-red-600">
                    {{ $message }}
                </p>
            @enderror

            @if (
                app(\App\Support\TenantCapabilities::class)->enabled(
                    app(\App\Services\TenantContext::class)->get(),
                    \App\Enums\Feature::AI
                )
            )
                @if (
                    auth()->user()?->hasPermission(
                        \App\Enums\Permission::AI_USE
                    )
                )
                    <div class="mt-2 flex items-center gap-2">
                        <button
                            type="button"
                            id="email_ai_rewrite"
                            class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50"
                            data-url="{{ route('ai.rewrite') }}"
                        >
                            {{ __('email.ai_rewrite') }}
                        </button>

                        <span
                            id="email_ai_status"
                            class="text-sm text-gray-600"
                            aria-live="polite"
                        ></span>
                    </div>
                @endif
            @endif
        </div>

        <div class="composer-actions">
            <button
                type="submit"
                name="action"
                value="save"
                class="save-button rounded-lg px-4 py-2.5 text-sm font-semibold shadow-sm transition hover:bg-gray-50"
            >
                {{ __('email.save') }}
            </button>

            @if (auth()->user()?->hasPermission(\App\Enums\Permission::EMAIL_SEND))
                <button
                    type="submit"
                    name="action"
                    value="send"
                    class="send-button rounded-lg px-4 py-2.5 text-sm font-semibold shadow-sm transition hover:bg-gray-800"
                >
                    {{ __('email.send_now') }}
                </button>
            @endif
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const selector = document.getElementById('template_selector');
    const aiButton = document.getElementById('email_ai_rewrite');
    const body = document.getElementById('body');
    const aiStatus = document.getElementById('email_ai_status');

    if (selector) {
        selector.addEventListener('change', function () {
            const option = selector.options[selector.selectedIndex];

            if (!option || !option.value) {
                return;
            }

            document.getElementById('subject').value =
                option.dataset.subject || '';

            body.value =
                option.dataset.body || '';
        });
    }

    if (!aiButton || !body || !aiStatus) {
        return;
    }

    aiButton.addEventListener('click', async function () {
        const text = body.value.trim();

        if (!text) {
            aiStatus.textContent =
                @json(__('email.ai_rewrite_empty'));

            return;
        }

        aiButton.disabled = true;

        aiStatus.textContent =
            @json(__('email.ai_rewrite_loading'));

        try {
            const response = await fetch(
                aiButton.dataset.url,
                {
                    method: 'POST',

                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': @json(csrf_token())
                    },

                    body: JSON.stringify({
                        text: text,

                        instruction:
                            @json(__('email.ai_rewrite_instruction'))
                    })
                }
            );

            if (!response.ok) {
                throw new Error(
                    'AI rewrite request failed'
                );
            }

            const payload = await response.json();
            const content = payload?.data?.content;

            if (!content) {
                throw new Error(
                    'AI rewrite response is empty'
                );
            }

            body.value = content;

            aiStatus.textContent =
                @json(__('email.ai_rewrite_success'));
        } catch (error) {
            aiStatus.textContent =
                @json(__('email.ai_rewrite_error'));
        } finally {
            aiButton.disabled = false;
        }
    });
});
</script>
</div>
@endsection
