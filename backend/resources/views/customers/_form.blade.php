<style>
    .customer-form-shell {
        display: grid;
        gap: 18px;
    }

    .customer-form-shell > div,
    .customer-form-shell > fieldset {
        display: grid;
        gap: 7px;
    }

    .customer-form-shell > div > label,
    .customer-form-shell > fieldset > legend {
        font-size: 0.875rem;
        font-weight: 600;
        color: #374151;
    }

    .customer-form-shell input:not([type="checkbox"]),
    .customer-form-shell select,
    .customer-form-shell textarea {
        width: 100%;
        min-height: 42px;
        padding: 10px 12px;
        border: 1px solid #d1d5db;
        border-radius: 10px;
        background: #fff;
        color: #111827;
        font: inherit;
        font-size: 0.875rem;
        line-height: 1.3;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
        outline: none;
        transition: border-color 160ms ease, box-shadow 160ms ease;
    }

    .customer-form-shell input:not([type="checkbox"]):focus,
    .customer-form-shell select:focus,
    .customer-form-shell textarea:focus {
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
    }

    .customer-form-shell textarea {
        min-height: 150px;
        resize: vertical;
    }

    .customer-form-shell fieldset {
        padding: 16px;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        background: #f9fafb;
    }

    .customer-form-shell fieldset > input {
        margin-top: 6px;
    }

    .customer-form-shell > div > div,
    .customer-form-shell > fieldset > div {
        color: #b91c1c;
        font-size: 0.8125rem;
        font-weight: 600;
    }

    .customer-form-shell #customer_ai_rewrite {
        width: auto;
        min-height: 38px;
        padding: 8px 12px;
        border: 1px solid #d1d5db;
        border-radius: 10px;
        background: #fff;
        color: #374151;
        font-size: 0.875rem;
        font-weight: 600;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
    }

    .customer-form-shell #customer_ai_rewrite:hover {
        background: #f9fafb;
    }

    @media (min-width: 768px) {
        .customer-form-shell {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .customer-form-shell > fieldset,
        .customer-form-shell > div:has(#notes),
        .customer-form-shell > div:first-child {
            grid-column: 1 / -1;
        }
    }
</style>

<div class="customer-form-shell">
@php
    $editing = isset($customer);

    $currentTags = old(
        'tags',
        $editing
            ? ($customer->tags ?? [])
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

@if ($errors->has('limit'))
    <div>
        {{ $errors->first('limit') }}
    </div>
@endif

<div>
    <label for="type">{{ __('customers.type') }}</label>

    <select
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
                            ? $customer->type->value
                            : \App\Enums\CustomerType::INDIVIDUAL->value
                    ) === $type->value
                )
            >
                {{ $type->label() }}
            </option>
        @endforeach
    </select>

    @error('type')
        <div>{{ $message }}</div>
    @enderror
</div>

<div>
    <label for="name">
        {{ __('customers.name') }}
    </label>

    <input
        id="name"
        name="name"
        type="text"
        maxlength="255"
        required
        value="{{ old(
            'name',
            $editing ? $customer->name : ''
        ) }}"
    >

    @error('name')
        <div>{{ $message }}</div>
    @enderror
</div>

<div>
    <label for="legal_name">
        {{ __('customers.legal_name') }}
    </label>

    <input
        id="legal_name"
        name="legal_name"
        type="text"
        maxlength="255"
        value="{{ old(
            'legal_name',
            $editing ? $customer->legal_name : ''
        ) }}"
    >

    @error('legal_name')
        <div>{{ $message }}</div>
    @enderror
</div>

<div>
    <label for="tax_country_code">
        {{ __('customers.tax_country') }}
    </label>

    <select
        id="tax_country_code"
        name="tax_country_code"
    >
        <option value="">
            {{ __('customers.no_document') }}
        </option>

        @foreach ($countries as $countryCode)
            <option
                value="{{ $countryCode }}"
                @selected(
                    old(
                        'tax_country_code',
                        $editing
                            ? $customer->tax_country_code
                            : ''
                    ) === $countryCode
                )
            >
                {{ $countryCode }}
            </option>
        @endforeach
    </select>

    @error('tax_country_code')
        <div>{{ $message }}</div>
    @enderror
</div>

<div>
    <label for="tax_identifier_type">
        {{ __('customers.tax_identifier_type') }}
    </label>

    <input
        id="tax_identifier_type"
        name="tax_identifier_type"
        type="text"
        maxlength="50"
        placeholder="CPF, CNPJ, EIN..."
        value="{{ old(
            'tax_identifier_type',
            $editing
                ? $customer->tax_identifier_type
                : ''
        ) }}"
    >

    @error('tax_identifier_type')
        <div>{{ $message }}</div>
    @enderror
</div>

<div>
    <label for="tax_identifier">
        {{ __('customers.tax_identifier') }}
    </label>

    <input
        id="tax_identifier"
        name="tax_identifier"
        type="text"
        maxlength="100"
        value="{{ old(
            'tax_identifier',
            $editing
                ? $customer->tax_identifier
                : ''
        ) }}"
    >

    @error('tax_identifier')
        <div>{{ $message }}</div>
    @enderror
</div>

<div>
    <label for="responsible_user_id">
        {{ __('customers.responsible') }}
    </label>

    <select
        id="responsible_user_id"
        name="responsible_user_id"
    >
        <option value="">
            {{ __('customers.no_responsible') }}
        </option>

        @foreach ($responsibles as $responsible)
            <option
                value="{{ $responsible->id }}"
                @selected(
                    (string) old(
                        'responsible_user_id',
                        $editing
                            ? $customer->responsible_user_id
                            : ''
                    ) === (string) $responsible->id
                )
            >
                {{ $responsible->name }}
            </option>
        @endforeach
    </select>

    @error('responsible_user_id')
        <div>{{ $message }}</div>
    @enderror
</div>

<fieldset>
    <legend>{{ __('customers.tags') }}</legend>

    @for ($i = 0; $i < $tagInputs; $i++)
        <input
            name="tags[]"
            type="text"
            maxlength="50"
            value="{{ $currentTags[$i] ?? '' }}"
        >
    @endfor

    @error('tags')
        <div>{{ $message }}</div>
    @enderror

    @error('tags.*')
        <div>{{ $message }}</div>
    @enderror
</fieldset>

<div>
    <label for="notes">
        {{ __('customers.notes') }}
    </label>

    <textarea
        id="notes"
        name="notes"
        rows="8"
        maxlength="10000"
    >{{ old(
        'notes',
        $editing ? $customer->notes : ''
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
                id="customer_ai_rewrite"
                class="rounded-lg border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700"
                data-url="{{ route('ai.rewrite') }}"
            >
                {{ __('customers.ai_rewrite') }}
            </button>

            <span
                id="customer_ai_status"
                class="text-sm text-gray-600"
                aria-live="polite"
            ></span>
        </div>
    @endif
@endif

    @error('notes')
        <div>{{ $message }}</div>
    @enderror
</div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const customerAiButton =
        document.getElementById('customer_ai_rewrite');

    const customerNotes =
        document.getElementById('notes');

    const customerAiStatus =
        document.getElementById('customer_ai_status');

    if (
        !customerAiButton ||
        !customerNotes ||
        !customerAiStatus
    ) {
        return;
    }

    customerAiButton.addEventListener(
        'click',
        async function () {
            const text =
                customerNotes.value.trim();

            if (!text) {
                customerAiStatus.textContent =
                    @json(
                        __('customers.ai_rewrite_empty')
                    );

                return;
            }

            customerAiButton.disabled = true;

            customerAiStatus.textContent =
                @json(
                    __('customers.ai_rewrite_loading')
                );

            try {
                const response = await fetch(
                    customerAiButton.dataset.url,
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
                                        'customers.ai_rewrite_instruction'
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

                customerNotes.value =
                    content;

                customerAiStatus.textContent =
                    @json(
                        __('customers.ai_rewrite_success')
                    );
            } catch (error) {
                customerAiStatus.textContent =
                    @json(
                        __('customers.ai_rewrite_error')
                    );
            } finally {
                customerAiButton.disabled =
                    false;
            }
        }
    );
});
</script>