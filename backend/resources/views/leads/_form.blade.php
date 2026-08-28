@php
    $editing = isset($lead);

    $currentTags = old(
        'tags',
        $editing
            ? ($lead->tags ?? [])
            : []
    );

    if (!is_array($currentTags)) {
        $currentTags = [];
    }

    $tagInputs = max(
        3,
        count($currentTags) + 1
    );
@endphp

<div class="grid gap-5 md:grid-cols-2">
    <div>
        <label for="name" class="block text-sm font-semibold text-gray-700">
            {{ __('leads.fields.name') }}
        </label>

        <input
            id="name"
            name="name"
            type="text"
            value="{{ old('name', $editing ? $lead->name : '') }}"
            required
            maxlength="255"
            class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 shadow-sm outline-none transition focus:border-[var(--primary)] focus:ring-2 focus:ring-[color:var(--primary)]/15"
        >

        @error('name')
            <p class="mt-1 text-sm font-medium text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="email" class="block text-sm font-semibold text-gray-700">
            {{ __('leads.fields.email') }}
        </label>

        <input
            id="email"
            name="email"
            type="email"
            value="{{ old('email', $editing ? $lead->email : '') }}"
            maxlength="255"
            class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 shadow-sm outline-none transition focus:border-[var(--primary)] focus:ring-2 focus:ring-[color:var(--primary)]/15"
        >

        @error('email')
            <p class="mt-1 text-sm font-medium text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="phone" class="block text-sm font-semibold text-gray-700">
            {{ __('leads.fields.phone') }}
        </label>

        <input
            id="phone"
            name="phone"
            type="text"
            value="{{ old('phone', $editing ? $lead->phone : '') }}"
            maxlength="50"
            class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 shadow-sm outline-none transition focus:border-[var(--primary)] focus:ring-2 focus:ring-[color:var(--primary)]/15"
        >

        @error('phone')
            <p class="mt-1 text-sm font-medium text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="responsible_user_id" class="block text-sm font-semibold text-gray-700">
            {{ __('leads.fields.responsible') }}
        </label>

        <select
            id="responsible_user_id"
            name="responsible_user_id"
            class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 shadow-sm outline-none focus:border-[var(--primary)] focus:ring-2 focus:ring-[color:var(--primary)]/15"
        >
            <option value="">
                {{ __('leads.responsible_none') }}
            </option>

            @foreach ($responsibles as $responsible)
                <option
                    value="{{ $responsible->id }}"
                    @selected(
                        (string) old(
                            'responsible_user_id',
                            $editing ? $lead->responsible_user_id : ''
                        ) === (string) $responsible->id
                    )
                >
                    {{ $responsible->name }} ({{ $responsible->email }})
                </option>
            @endforeach
        </select>

        @error('responsible_user_id')
            <p class="mt-1 text-sm font-medium text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="status" class="block text-sm font-semibold text-gray-700">
            {{ __('leads.fields.status') }}
        </label>

        <select
            id="status"
            name="status"
            required
            class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 shadow-sm outline-none focus:border-[var(--primary)] focus:ring-2 focus:ring-[color:var(--primary)]/15"
        >
            @foreach ($statuses as $status)
                <option
                    value="{{ $status->value }}"
                    @selected(
                        old(
                            'status',
                            $editing
                                ? $lead->status->value
                                : \App\Enums\LeadStatus::NEW->value
                        ) === $status->value
                    )
                >
                    {{ $status->label() }}
                </option>
            @endforeach
        </select>

        @error('status')
            <p class="mt-1 text-sm font-medium text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="source" class="block text-sm font-semibold text-gray-700">
            {{ __('leads.fields.source') }}
        </label>

        <select
            id="source"
            name="source"
            required
            class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 shadow-sm outline-none focus:border-[var(--primary)] focus:ring-2 focus:ring-[color:var(--primary)]/15"
        >
            @foreach ($sources as $source)
                <option
                    value="{{ $source->value }}"
                    @selected(
                        old(
                            'source',
                            $editing
                                ? $lead->source->value
                                : \App\Enums\LeadSource::MANUAL->value
                        ) === $source->value
                    )
                >
                    {{ $source->label() }}
                </option>
            @endforeach
        </select>

        @error('source')
            <p class="mt-1 text-sm font-medium text-red-600">{{ $message }}</p>
        @enderror
    </div>
</div>

<fieldset class="mt-6 rounded-xl border border-gray-200 bg-gray-50/70 p-4">
    <legend class="px-2 text-sm font-semibold text-gray-700">
        {{ __('leads.fields.tags') }}
    </legend>

    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
        @for ($i = 0; $i < $tagInputs; $i++)
            <input
                name="tags[]"
                type="text"
                value="{{ $currentTags[$i] ?? '' }}"
                maxlength="50"
                placeholder="{{ __('leads.tag_placeholder') }}"
                class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 shadow-sm outline-none transition focus:border-[var(--primary)] focus:ring-2 focus:ring-[color:var(--primary)]/15"
            >
        @endfor
    </div>

    @error('tags')
        <p class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p>
    @enderror

    @error('tags.*')
        <p class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p>
    @enderror
</fieldset>

<div class="mt-6">
    <label for="notes" class="block text-sm font-semibold text-gray-700">
        {{ __('leads.fields.notes') }}
    </label>

    <textarea
        id="notes"
        name="notes"
        maxlength="10000"
        rows="8"
        class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 shadow-sm outline-none transition focus:border-[var(--primary)] focus:ring-2 focus:ring-[color:var(--primary)]/15"
    >{{ old('notes', $editing ? $lead->notes : '') }}</textarea>

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
            <div class="mt-3 flex flex-wrap items-center gap-2">
                <button
                    type="button"
                    id="lead_ai_rewrite"
                    class="rounded-lg border border-[#111827] bg-[#111827] px-3 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-[#1f2937]"
                    data-url="{{ route('ai.rewrite') }}"
                >
                    {{ __('leads.ai_rewrite') }}
                </button>

                <span
                    id="lead_ai_status"
                    class="text-sm text-gray-600"
                    aria-live="polite"
                ></span>
            </div>
        @endif
    @endif

    @error('notes')
        <p class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p>
    @enderror
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const leadAiButton =
        document.getElementById('lead_ai_rewrite');

    const leadNotes =
        document.getElementById('notes');

    const leadAiStatus =
        document.getElementById('lead_ai_status');

    if (
        !leadAiButton ||
        !leadNotes ||
        !leadAiStatus
    ) {
        return;
    }

    leadAiButton.addEventListener(
        'click',
        async function () {
            const text =
                leadNotes.value.trim();

            if (!text) {
                leadAiStatus.textContent =
                    @json(
                        __('leads.ai_rewrite_empty')
                    );

                return;
            }

            leadAiButton.disabled = true;

            leadAiStatus.textContent =
                @json(
                    __('leads.ai_rewrite_loading')
                );

            try {
                const response = await fetch(
                    leadAiButton.dataset.url,
                    {
                        method: 'POST',

                        headers: {
                            'Accept': 'application/json',
                            'Content-Type':
                                'application/json',
                            'X-CSRF-TOKEN':
                                @json(csrf_token())
                        },

                        body: JSON.stringify({
                            text: text,

                            instruction:
                                @json(
                                    __(
                                        'leads.ai_rewrite_instruction'
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

                leadNotes.value =
                    content;

                leadAiStatus.textContent =
                    @json(
                        __('leads.ai_rewrite_success')
                    );
            } catch (error) {
                leadAiStatus.textContent =
                    @json(
                        __('leads.ai_rewrite_error')
                    );
            } finally {
                leadAiButton.disabled =
                    false;
            }
        }
    );
});
</script>
