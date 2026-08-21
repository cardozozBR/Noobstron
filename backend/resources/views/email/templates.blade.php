@extends('layouts.app')

@section('content')

<style>
.email-templates-page{display:grid;gap:24px;max-width:1000px;margin:0 auto}
.email-templates-page .back-link{display:inline-flex;align-items:center;gap:6px;color:#4b5563;text-decoration:none;font-size:13px;font-weight:600}
.email-templates-page .template-create-card,
.email-templates-page .template-card{border-radius:18px}
.email-templates-page .template-list{display:grid;gap:16px}
.email-templates-page .template-card{transition:border-color 160ms ease,box-shadow 160ms ease}
.email-templates-page .template-card:hover{border-color:#d1d5db;box-shadow:0 8px 22px rgba(15,23,42,.05)}
.email-templates-page .template-actions{display:flex;flex-wrap:wrap;gap:8px}
</style>

<div class="email-templates-page">
<div class="space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <a
                href="{{ route('email.index') }}"
                class="back-link"
            >← {{ __('email.back') }}
            </a>

            <h1 class="mt-3 text-3xl font-bold tracking-tight text-gray-900">
                {{ __('email.template_title') }}
            </h1>

            <p class="mt-1 text-sm text-gray-600">
                {{ __('email.template_subtitle') }}
            </p>
        </div>
    </div>

    @if (session('success'))
        <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
            {{ session('success') }}
        </div>
    @endif

    <div class="template-create-card rounded-2xl border border-gray-200 bg-white p-6 shadow-sm sm:p-8">
        <h2 class="font-semibold text-gray-900">
            {{ __('email.new_template') }}
        </h2>

        <form
            method="POST"
            action="{{ route('email.templates.store') }}"
            class="mt-4 space-y-4"
        >
            @csrf

            <input
                name="name"
                type="text"
                required
                placeholder="{{ __('email.template_name') }}"
                class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 shadow-sm outline-none transition placeholder:text-gray-400 focus:border-[var(--primary)] focus:ring-2 focus:ring-[color:var(--primary)]/15"
            >

            <input
                name="subject_template"
                type="text"
                required
                placeholder="{{ __('email.subject_template') }}"
                class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 shadow-sm outline-none transition placeholder:text-gray-400 focus:border-[var(--primary)] focus:ring-2 focus:ring-[color:var(--primary)]/15"
            >

            <textarea
                id="email_template_body_new"
                name="body_template"
                rows="6"
                required
                placeholder="{{ __('email.body_template') }}"
                class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 shadow-sm outline-none transition placeholder:text-gray-400 focus:border-[var(--primary)] focus:ring-2 focus:ring-[color:var(--primary)]/15"
            ></textarea>

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
                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            id="email_template_ai_rewrite_new"
                            class="email-template-ai-rewrite rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50"
                            data-target="email_template_body_new"

                            data-status="email_template_ai_status_new"
                            data-url="{{ route('ai.rewrite') }}"
                        >
                            {{ __('email.ai_rewrite') }}
                        </button>

                        <span
                            id="email_template_ai_status_new"
                            class="text-sm text-gray-600"
                            aria-live="polite"
                        ></span>
                    </div>
                @endif
            @endif

            <div class="rounded-xl border border-blue-100 bg-blue-50/60 p-4 text-sm text-gray-600">
                <strong>
                    {{ __('email.placeholders_title') }}
                </strong>

                <p class="mt-1">
                    {{ __('email.placeholders_help') }}
                </p>
            </div>

            <button
                type="submit"
                class="rounded-lg bg-gray-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-gray-800"
            >
                {{ __('email.create_template') }}
            </button>
        </form>
    </div>

    <div class="template-list">
        @foreach ($templates as $template)
            <article class="template-card rounded-2xl border border-gray-200 bg-white p-6 shadow-sm sm:p-8">
                <form
                    method="POST"
                    action="{{ route('email.templates.update', $template->id) }}"
                    class="space-y-4"
                >
                    @csrf
                    @method('PUT')

                    <input
                        name="name"
                        type="text"
                        value="{{ $template->name }}"
                        required
                        class="block w-full rounded-lg border-gray-300 font-medium"
                    >

                    <input
                        name="subject_template"
                        type="text"
                        value="{{ $template->subject_template }}"
                        required
                        class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 shadow-sm outline-none transition placeholder:text-gray-400 focus:border-[var(--primary)] focus:ring-2 focus:ring-[color:var(--primary)]/15"
                    >

                    <textarea
                        id="email_template_body_{{ $template->id }}"
                        name="body_template"
                        rows="5"
                        required
                        class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 shadow-sm outline-none transition placeholder:text-gray-400 focus:border-[var(--primary)] focus:ring-2 focus:ring-[color:var(--primary)]/15"
                    >{{ $template->body_template }}</textarea>

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
                            <div class="flex items-center gap-2">
                                <button
                                    type="button"
                                    class="email-template-ai-rewrite rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50"
                                    data-target="email_template_body_{{ $template->id }}"
                                    data-status="email_template_ai_status_{{ $template->id }}"
                                    data-url="{{ route('ai.rewrite') }}"
                                >
                                    {{ __('email.ai_rewrite') }}
                                </button>

                                <span
                                    id="email_template_ai_status_{{ $template->id }}"
                                    class="text-sm text-gray-600"
                                    aria-live="polite"
                                ></span>
                            </div>
                        @endif
                    @endif

                    <div class="template-actions">
                        <button
                            type="submit"
                            class="rounded-lg bg-gray-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-gray-800"
                        >
                            {{ __('email.save_template') }}
                        </button>
                    </div>
                </form>

                <form
                    method="POST"
                    action="{{ route('email.templates.destroy', $template->id) }}"
                    class="mt-2"
                >
                    @csrf
                    @method('DELETE')

                    <button
                        type="submit"
                        class="inline-flex rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm font-semibold text-red-700 transition hover:bg-red-100"
                    >
                        {{ __('email.delete') }}
                    </button>
                </form>
            </article>
        @endforeach
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const buttons =
        document.querySelectorAll(
            '.email-template-ai-rewrite'
        );

    buttons.forEach(function (button) {
        button.addEventListener(
            'click',
            async function () {
                const target =
                    document.getElementById(
                        button.dataset.target
                    );

                const status =
                    document.getElementById(
                        button.dataset.status
                    );

                if (!target || !status) {
                    return;
                }

                const text =
                    target.value.trim();

                if (!text) {
                    status.textContent =
                        @json(
                            __('email.ai_rewrite_empty')
                        );

                    return;
                }

                button.disabled = true;

                status.textContent =
                    @json(
                        __('email.ai_rewrite_loading')
                    );

                try {
                    const response =
                        await fetch(
                            button.dataset.url,
                            {
                                method: 'POST',

                                headers: {
                                    'Accept':
                                        'application/json',
                                    'Content-Type':
                                        'application/json',
                                    'X-CSRF-TOKEN':
                                        @json(
                                            csrf_token()
                                        )
                                },

                                body: JSON.stringify({
                                    text: text,

                                    instruction:
                                        @json(
                                            __(
                                                'email.ai_rewrite_instruction'
                                            )
                                        )
                                })
                            }
                        );

                    if (!response.ok) {
                        throw new Error(
                            'AI rewrite request failed'
                        );
                    }

                    const payload =
                        await response.json();

                    const content =
                        payload?.data?.content;

                    if (!content) {
                        throw new Error(
                            'AI rewrite response is empty'
                        );
                    }

                    target.value =
                        content;

                    status.textContent =
                        @json(
                            __('email.ai_rewrite_success')
                        );
                } catch (error) {
                    status.textContent =
                        @json(
                            __('email.ai_rewrite_error')
                        );
                } finally {
                    button.disabled =
                        false;
                }
            }
        );
    });
});
</script>
</div>
@endsection
