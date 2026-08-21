@php
    $editing = isset($activity);
@endphp

<div class="form-group">
    <label
        class="form-label"
        for="type"
    >
        {{ __('activities.fields.type') }}
    </label>

    <select
        class="form-control"
        id="type"
        name="type"
        required
    >
        @foreach ($types as $type)
            <option
                value="{{ $type->value }}"
                @selected(
                    old(
                        'type',
                        $editing
                            ? $activity->type->value
                            : \App\Enums\ActivityType::TASK->value
                    ) === $type->value
                )
            >
                {{ __('activities.types.' . $type->value) }}
            </option>
        @endforeach
    </select>

    @error('type')
        <div class="form-help">{{ $message }}</div>
    @enderror
</div>

<div class="form-group">
    <label
        class="form-label"
        for="status"
    >
        {{ __('activities.fields.status') }}
    </label>

    <select
        class="form-control"
        id="status"
        name="status"
    >
        @foreach ($statuses as $status)
            <option
                value="{{ $status->value }}"
                @selected(
                    old(
                        'status',
                        $editing
                            ? $activity->status->value
                            : \App\Enums\ActivityStatus::PENDING->value
                    ) === $status->value
                )
            >
                {{ __('activities.statuses.' . $status->value) }}
            </option>
        @endforeach
    </select>

    @error('status')
        <div class="form-help">{{ $message }}</div>
    @enderror
</div>

<div class="form-group">
    <label
        class="form-label"
        for="title"
    >
        {{ __('activities.fields.title') }}
    </label>

    <input
        class="form-control"
        id="title"
        name="title"
        type="text"
        maxlength="255"
        required
        value="{{ old(
            'title',
            $editing ? $activity->title : ''
        ) }}"
    >

    @error('title')
        <div class="form-help">{{ $message }}</div>
    @enderror
</div>

<div class="form-group">
    <label
        class="form-label"
        for="customer_id"
    >
        {{ __('activities.fields.customer') }}
    </label>

    <select
        class="form-control"
        id="customer_id"
        name="customer_id"
    >
        <option value="">
            {{ __('activities.none') }}
        </option>

        @foreach ($customers as $customer)
            <option
                value="{{ $customer->id }}"
                @selected(
                    (string) old(
                        'customer_id',
                        $editing
                            ? $activity->customer_id
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
        for="opportunity_id"
    >
        {{ __('activities.fields.opportunity') }}
    </label>

    <select
        class="form-control"
        id="opportunity_id"
        name="opportunity_id"
    >
        <option value="">
            {{ __('activities.none') }}
        </option>

        @foreach ($opportunities as $opportunity)
            <option
                value="{{ $opportunity->id }}"
                @selected(
                    (string) old(
                        'opportunity_id',
                        $editing
                            ? $activity->opportunity_id
                            : ''
                    ) === (string) $opportunity->id
                )
            >
                {{ $opportunity->name }}
            </option>
        @endforeach
    </select>

    @error('opportunity_id')
        <div class="form-help">{{ $message }}</div>
    @enderror
</div>

<div class="form-group">
    <label
        class="form-label"
        for="responsible_user_id"
    >
        {{ __('activities.fields.responsible') }}
    </label>

    <select
        class="form-control"
        id="responsible_user_id"
        name="responsible_user_id"
    >
        <option value="">
            {{ __('activities.none') }}
        </option>

        @foreach ($responsibles as $responsible)
            <option
                value="{{ $responsible->id }}"
                @selected(
                    (string) old(
                        'responsible_user_id',
                        $editing
                            ? $activity->responsible_user_id
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
        for="due_at"
    >
        {{ __('activities.fields.due_at') }}
    </label>

    <input
        class="form-control"
        id="due_at"
        name="due_at"
        type="datetime-local"
        value="{{ old(
            'due_at',
            $editing && $activity->due_at
                ? $activity->due_at->format('Y-m-d\TH:i')
                : ''
        ) }}"
    >

    @error('due_at')
        <div class="form-help">{{ $message }}</div>
    @enderror
</div>

<div class="form-group">
    <label
        class="form-label"
        for="description"
    >
        {{ __('activities.fields.description') }}
    </label>

    <textarea
        class="form-control"
        id="description"
        name="description"
        rows="8"
        maxlength="10000"
    >{{ old(
        'description',
        $editing ? $activity->description : ''
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
                id="activity_ai_rewrite"
                class="rounded-lg border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700"
                data-url="{{ route('ai.rewrite') }}"
            >
                {{ __('activities.ai_rewrite') }}
            </button>

            <span
                id="activity_ai_status"
                class="text-sm text-gray-600"
                aria-live="polite"
            ></span>
        </div>
    @endif
@endif

    @error('description')
        <div class="form-help">{{ $message }}</div>
    @enderror
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const activityAiButton =
        document.getElementById('activity_ai_rewrite');

    const activityDescription =
        document.getElementById('description');

    const activityAiStatus =
        document.getElementById('activity_ai_status');

    if (
        !activityAiButton ||
        !activityDescription ||
        !activityAiStatus
    ) {
        return;
    }

    activityAiButton.addEventListener(
        'click',
        async function () {
            const text =
                activityDescription.value.trim();

            if (!text) {
                activityAiStatus.textContent =
                    @json(
                        __('activities.ai_rewrite_empty')
                    );

                return;
            }

            activityAiButton.disabled = true;

            activityAiStatus.textContent =
                @json(
                    __('activities.ai_rewrite_loading')
                );

            try {
                const response = await fetch(
                    activityAiButton.dataset.url,
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
                                        'activities.ai_rewrite_instruction'
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

                activityDescription.value =
                    content;

                activityAiStatus.textContent =
                    @json(
                        __('activities.ai_rewrite_success')
                    );
            } catch (error) {
                activityAiStatus.textContent =
                    @json(
                        __('activities.ai_rewrite_error')
                    );
            } finally {
                activityAiButton.disabled = false;
            }
        }
    );
});
</script>
