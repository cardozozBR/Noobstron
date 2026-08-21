@php
    $editing = isset($opportunity);

    $currentPipelineId = old(
        'pipeline_id',
        $editing
            ? $opportunity->pipeline_id
            : (
                $pipelines->firstWhere('is_default', true)?->id
                ?? $pipelines->first()?->id
            )
    );

    $currentStageId = old(
        'pipeline_stage_id',
        $editing
            ? $opportunity->pipeline_stage_id
            : ''
    );
@endphp

<div class="form-group">
    <label
        class="form-label"
        for="name"
    >
        {{ __('opportunities.fields.name') }}
    </label>

    <input
        class="form-control"
        id="name"
        name="name"
        type="text"
        maxlength="255"
        required
        value="{{ old(
            'name',
            $editing ? $opportunity->name : ''
        ) }}"
    >

    @error('name')
        <div class="form-help">{{ $message }}</div>
    @enderror
</div>

<div class="form-group">
    <label
        class="form-label"
        for="customer_id"
    >
        {{ __('opportunities.fields.customer') }}
    </label>

    <select
        class="form-control"
        id="customer_id"
        name="customer_id"
        required
    >
        <option value="">
            {{ __('opportunities.select_customer') }}
        </option>

        @foreach ($customers as $customer)
            <option
                value="{{ $customer->id }}"
                @selected(
                    (string) old(
                        'customer_id',
                        $editing
                            ? $opportunity->customer_id
                            : ''
                    ) === (string) $customer->id
                )
            >
                {{ $customer->name }}
            </option>
        @endforeach
    </select>

    @error('customer_id')
        <div class="form-help">{{ $message }}</div>
    @enderror
</div>

<div class="form-group">
    <label
        class="form-label"
        for="pipeline_id"
    >
        {{ __('opportunities.fields.pipeline') }}
    </label>

    <select
        class="form-control"
        id="pipeline_id"
        name="pipeline_id"
        required
    >
        <option value="">
            {{ __('opportunities.select_pipeline') }}
        </option>

        @foreach ($pipelines as $pipeline)
            <option
                value="{{ $pipeline->id }}"
                @selected(
                    (string) $currentPipelineId
                    === (string) $pipeline->id
                )
            >
                {{ $pipeline->name }}
            </option>
        @endforeach
    </select>

    @error('pipeline_id')
        <div class="form-help">{{ $message }}</div>
    @enderror
</div>

<div class="form-group">
    <label
        class="form-label"
        for="pipeline_stage_id"
    >
        {{ __('opportunities.fields.stage') }}
    </label>

    <select
        class="form-control"
        id="pipeline_stage_id"
        name="pipeline_stage_id"
        required
    >
        <option value="">
            {{ __('opportunities.select_stage') }}
        </option>

        @foreach ($pipelines as $pipeline)
            <optgroup label="{{ $pipeline->name }}">
                @foreach ($pipeline->stages as $stage)
                    <option
                        value="{{ $stage->id }}"
                        @selected(
                            (string) $currentStageId
                            === (string) $stage->id
                        )
                    >
                        {{ $stage->name }}
                    </option>
                @endforeach
            </optgroup>
        @endforeach
    </select>

    <small class="form-help">
        {{ __('opportunities.stage_help') }}
    </small>

    @error('pipeline_stage_id')
        <div class="form-help">{{ $message }}</div>
    @enderror
</div>

<div class="form-group">
    <label
        class="form-label"
        for="responsible_user_id"
    >
        {{ __('opportunities.fields.responsible') }}
    </label>

    <select
        class="form-control"
        id="responsible_user_id"
        name="responsible_user_id"
    >
        <option value="">
            {{ __('opportunities.responsible_none') }}
        </option>

        @foreach ($responsibles as $responsible)
            <option
                value="{{ $responsible->id }}"
                @selected(
                    (string) old(
                        'responsible_user_id',
                        $editing
                            ? $opportunity->responsible_user_id
                            : ''
                    ) === (string) $responsible->id
                )
            >
                {{ $responsible->name }}
                ({{ $responsible->email }})
            </option>
        @endforeach
    </select>

    @error('responsible_user_id')
        <div class="form-help">{{ $message }}</div>
    @enderror
</div>

<div class="form-group">
    <label
        class="form-label"
        for="value_minor"
    >
        {{ __('opportunities.fields.value_minor') }}
    </label>

    <input
        class="form-control"
        id="value_minor"
        name="value_minor"
        type="number"
        min="0"
        step="1"
        required
        value="{{ old(
            'value_minor',
            $editing ? $opportunity->value_minor : 0
        ) }}"
    >

    <small class="form-help">
        {{ __('opportunities.value_minor_help') }}
    </small>

    @error('value_minor')
        <div class="form-help">{{ $message }}</div>
    @enderror
</div>

<div class="form-group">
    <label
        class="form-label"
        for="currency"
    >
        {{ __('opportunities.fields.currency') }}
    </label>

    <input
        class="form-control"
        id="currency"
        name="currency"
        type="text"
        maxlength="3"
        minlength="3"
        required
        value="{{ old(
            'currency',
            $editing
                ? $opportunity->currency
                : (
                    request()->attributes
                        ->get('tenant')
                        ?->currency
                    ?? 'BRL'
                )
        ) }}"
    >

    @error('currency')
        <div class="form-help">{{ $message }}</div>
    @enderror
</div>

<div class="form-group">
    <label
        class="form-label"
        for="probability"
    >
        {{ __('opportunities.fields.probability') }}
    </label>

    <input
        class="form-control"
        id="probability"
        name="probability"
        type="number"
        min="0"
        max="100"
        step="1"
        required
        value="{{ old(
            'probability',
            $editing ? $opportunity->probability : 0
        ) }}"
    >

    @error('probability')
        <div class="form-help">{{ $message }}</div>
    @enderror
</div>

<div class="form-group">
    <label
        class="form-label"
        for="expected_close_date"
    >
        {{ __('opportunities.fields.expected_close_date') }}
    </label>

    <input
        class="form-control"
        id="expected_close_date"
        name="expected_close_date"
        type="date"
        value="{{ old(
            'expected_close_date',
            $editing && $opportunity->expected_close_date
                ? $opportunity->expected_close_date->format('Y-m-d')
                : ''
        ) }}"
    >

    @error('expected_close_date')
        <div class="form-help">{{ $message }}</div>
    @enderror
</div>

<div class="form-group">
    <label
        class="form-label"
        for="notes"
    >
        {{ __('opportunities.fields.notes') }}
    </label>

    <textarea
        class="form-control"
        id="notes"
        name="notes"
        rows="8"
        maxlength="10000"
    >{{ old(
        'notes',
        $editing ? $opportunity->notes : ''
    ) }}</textarea>
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
                id="opportunity_ai_rewrite"
                class="rounded-lg border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700"
                data-url="{{ route('ai.rewrite') }}"
            >
                {{ __('opportunities.ai_rewrite') }}
            </button>

            <span
                id="opportunity_ai_status"
                class="text-sm text-gray-600"
                aria-live="polite"
            ></span>
        </div>
    @endif
@endif

    @error('notes')
        <div class="form-help">{{ $message }}</div>
    @enderror
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const opportunityAiButton =
        document.getElementById('opportunity_ai_rewrite');

    const opportunityNotes =
        document.getElementById('notes');

    const opportunityAiStatus =
        document.getElementById('opportunity_ai_status');

    if (
        !opportunityAiButton ||
        !opportunityNotes ||
        !opportunityAiStatus
    ) {
        return;
    }

    opportunityAiButton.addEventListener(
        'click',
        async function () {
            const text =
                opportunityNotes.value.trim();

            if (!text) {
                opportunityAiStatus.textContent =
                    @json(
                        __('opportunities.ai_rewrite_empty')
                    );

                return;
            }

            opportunityAiButton.disabled = true;

            opportunityAiStatus.textContent =
                @json(
                    __('opportunities.ai_rewrite_loading')
                );

            try {
                const response = await fetch(
                    opportunityAiButton.dataset.url,
                    {
                        method: 'POST',

                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN':
                                @json(csrf_token())
                        },

                        body: JSON.stringify({
                            text: text,

                            instruction:
                                @json(
                                    __(
                                        'opportunities.ai_rewrite_instruction'
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

                opportunityNotes.value =
                    content;

                opportunityAiStatus.textContent =
                    @json(
                        __('opportunities.ai_rewrite_success')
                    );
            } catch (error) {
                opportunityAiStatus.textContent =
                    @json(
                        __('opportunities.ai_rewrite_error')
                    );
            } finally {
                opportunityAiButton.disabled =
                    false;
            }
        }
    );
});
</script>