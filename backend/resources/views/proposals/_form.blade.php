<div class="form-grid">
    <div class="form-group">
        <label for="number">{{ __('proposals.number') }}</label>

        <input
            id="number"
            name="number"
            value="{{ old('number', $proposal->number ?? '') }}"
            required
        >
    </div>

    <div class="form-group">
        <label for="status">{{ __('proposals.status') }}</label>

        <select id="status" name="status" required>
            @foreach ($statuses as $status)
                <option
                    value="{{ $status->value }}"
                    @selected(
                        old(
                            'status',
                            isset($proposal)
                                ? $proposal->status->value
                                : 'draft'
                        ) === $status->value
                    )
                >
                    {{ __('proposals.' . $status->value) }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="form-group">
        <label for="customer_id">{{ __('proposals.customer') }}</label>

        <select id="customer_id" name="customer_id">
            <option value="">—</option>

            @foreach ($customers as $customer)
                <option
                    value="{{ $customer->id }}"
                    @selected(
                        (string) old(
                            'customer_id',
                            $proposal->customer_id ?? ''
                        ) === (string) $customer->id
                    )
                >
                    {{ $customer->name }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="form-group">
        <label for="opportunity_id">{{ __('proposals.opportunity') }}</label>

        <select id="opportunity_id" name="opportunity_id">
            <option value="">—</option>

            @foreach ($opportunities as $opportunity)
                <option
                    value="{{ $opportunity->id }}"
                    @selected(
                        (string) old(
                            'opportunity_id',
                            $proposal->opportunity_id ?? ''
                        ) === (string) $opportunity->id
                    )
                >
                    {{ $opportunity->name }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="form-group">
        <label for="currency">{{ __('proposals.currency') }}</label>

        <input
            id="currency"
            name="currency"
            maxlength="3"
            value="{{
                old(
                    'currency',
                    $proposal->currency
                        ?? app(\App\Services\TenantContext::class)->get()->currency
                )
            }}"
        >
    </div>

    <div class="form-group">
        <label for="valid_until">{{ __('proposals.valid_until') }}</label>

        <input
            id="valid_until"
            name="valid_until"
            type="date"
            value="{{
                old(
                    'valid_until',
                    isset($proposal) && $proposal->valid_until
                        ? $proposal->valid_until->format('Y-m-d')
                        : ''
                )
            }}"
        >
    </div>
</div>

<div class="form-group">
    <label for="notes">{{ __('proposals.notes') }}</label>

    <textarea id="notes" name="notes">{{ old('notes', $proposal->notes ?? '') }}</textarea>
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
                id="proposal_ai_rewrite"
                class="rounded-lg border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700"
                data-url="{{ route('ai.rewrite') }}"
            >
                {{ __('proposals.ai_rewrite') }}
            </button>

            <span
                id="proposal_ai_status"
                class="text-sm text-gray-600"
                aria-live="polite"
            ></span>
        </div>
    @endif
@endif
</div>

<div class="card">
    <div class="section-header">
        <h2>{{ __('proposals.items') }}</h2>

        <button
            class="btn btn-secondary"
            type="button"
            id="proposal-add-item"
        >
            {{ __('proposals.add_item') }}
        </button>
    </div>

    @php
        $oldItems = old(
            'items',
            isset($proposal)
                ? $proposal->items->map(
                    fn ($item) => [
                        'catalog_item_id' => $item->catalog_item_id,
                        'item_type' => $item->item_type,
                        'name' => $item->name,
                        'code' => $item->code,
                        'quantity' => $item->quantity,
                        'unit_price_minor' => $item->unit_price_minor,
                        'discount_minor' => $item->discount_minor,
                        'taxes' => $item->taxes ?? [],
                    ]
                )->toArray()
                : [
                    [
                        'catalog_item_id' => null,
                        'item_type' => 'service',
                        'name' => '',
                        'code' => '',
                        'quantity' => 1,
                        'unit_price_minor' => 0,
                        'discount_minor' => 0,
                        'taxes' => [],
                    ],
                ]
        );
    @endphp

    <div id="proposal-items">
        @foreach ($oldItems as $index => $oldItem)
            <div
                class="proposal-item-row card"
                data-item-index="{{ $index }}"
            >
                <div class="form-grid">
                    <div class="form-group">
                        <label>
                            {{ __('proposals.catalog_item') }}
                        </label>

                        <select
                            name="items[{{ $index }}][catalog_item_id]"
                            class="proposal-catalog-select"
                        >
                            <option value="">
                                {{ __('proposals.manual_item') }}
                            </option>

                            @foreach ($catalogItems as $catalogItem)
                                <option
                                    value="{{ $catalogItem->id }}"
                                    data-name="{{ $catalogItem->name }}"
                                    data-code="{{ $catalogItem->code }}"
                                    data-price="{{ $catalogItem->price_minor }}"
                                    data-type="{{ $catalogItem->type->value }}"
                                    @selected(
                                        (string) ($oldItem['catalog_item_id'] ?? '')
                                        === (string) $catalogItem->id
                                    )
                                >
                                    {{ $catalogItem->name }}
                                    @if ($catalogItem->code)
                                        — {{ $catalogItem->code }}
                                    @endif
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label>{{ __('proposals.item_name') }}</label>

                        <input
                            name="items[{{ $index }}][name]"
                            class="proposal-item-name"
                            value="{{ $oldItem['name'] ?? '' }}"
                        >
                    </div>

                    <div class="form-group">
                        <label>{{ __('proposals.item_code') }}</label>

                        <input
                            name="items[{{ $index }}][code]"
                            class="proposal-item-code"
                            value="{{ $oldItem['code'] ?? '' }}"
                        >
                    </div>

                    <div class="form-group">
                        <label>{{ __('proposals.quantity') }}</label>

                        <input
                            name="items[{{ $index }}][quantity]"
                            class="proposal-item-quantity"
                            type="number"
                            min="0.0001"
                            step="0.0001"
                            value="{{ $oldItem['quantity'] ?? 1 }}"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label>{{ __('proposals.unit_price') }}</label>

                        <input
                            name="items[{{ $index }}][unit_price_minor]"
                            class="proposal-item-price"
                            type="number"
                            min="0"
                            step="1"
                            value="{{ $oldItem['unit_price_minor'] ?? 0 }}"
                        >
                    </div>

                    <div class="form-group">
                        <label>{{ __('proposals.discount') }}</label>

                        <input
                            name="items[{{ $index }}][discount_minor]"
                            class="proposal-item-discount"
                            type="number"
                            min="0"
                            step="1"
                            value="{{ $oldItem['discount_minor'] ?? 0 }}"
                        >
                    </div>

                    <div class="form-group">
                        <label>{{ __('proposals.tax_code') }}</label>

                        <input
                            name="items[{{ $index }}][taxes][0][code]"
                            value="{{ $oldItem['taxes'][0]['code'] ?? '' }}"
                        >
                    </div>

                    <div class="form-group">
                        <label>{{ __('proposals.tax_amount') }}</label>

                        <input
                            name="items[{{ $index }}][taxes][0][amount_minor]"
                            class="proposal-item-tax"
                            type="number"
                            min="0"
                            step="1"
                            value="{{ $oldItem['taxes'][0]['amount_minor'] ?? 0 }}"
                        >
                    </div>
                </div>

                <input
                    type="hidden"
                    name="items[{{ $index }}][item_type]"
                    class="proposal-item-type"
                    value="{{ $oldItem['item_type'] ?? 'service' }}"
                >

                <div class="actions">
                    <button
                        class="btn btn-danger proposal-remove-item"
                        type="button"
                    >
                        {{ __('proposals.remove_item') }}
                    </button>
                </div>
            </div>
        @endforeach
    </div>

    <div class="dashboard-grid">
        <div class="stat-card">
            <span class="stat-label">
                {{ __('proposals.subtotal') }}
            </span>

            <strong id="proposal-preview-subtotal">0</strong>
        </div>

        <div class="stat-card">
            <span class="stat-label">
                {{ __('proposals.discount') }}
            </span>

            <strong id="proposal-preview-discount">0</strong>
        </div>

        <div class="stat-card">
            <span class="stat-label">
                {{ __('proposals.taxes') }}
            </span>

            <strong id="proposal-preview-tax">0</strong>
        </div>

        <div class="stat-card">
            <span class="stat-label">
                {{ __('proposals.preview_total') }}
            </span>

            <strong id="proposal-preview-total">0</strong>
        </div>
    </div>
</div>

<template id="proposal-item-template">
    <div class="proposal-item-row card" data-item-index="__INDEX__">
        <div class="form-grid">
            <div class="form-group">
                <label>{{ __('proposals.catalog_item') }}</label>

                <select
                    name="items[__INDEX__][catalog_item_id]"
                    class="proposal-catalog-select"
                >
                    <option value="">
                        {{ __('proposals.manual_item') }}
                    </option>

                    @foreach ($catalogItems as $catalogItem)
                        <option
                            value="{{ $catalogItem->id }}"
                            data-name="{{ $catalogItem->name }}"
                            data-code="{{ $catalogItem->code }}"
                            data-price="{{ $catalogItem->price_minor }}"
                            data-type="{{ $catalogItem->type->value }}"
                        >
                            {{ $catalogItem->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label>{{ __('proposals.item_name') }}</label>
                <input name="items[__INDEX__][name]" class="proposal-item-name">
            </div>

            <div class="form-group">
                <label>{{ __('proposals.item_code') }}</label>
                <input name="items[__INDEX__][code]" class="proposal-item-code">
            </div>

            <div class="form-group">
                <label>{{ __('proposals.quantity') }}</label>
                <input
                    name="items[__INDEX__][quantity]"
                    class="proposal-item-quantity"
                    type="number"
                    min="0.0001"
                    step="0.0001"
                    value="1"
                    required
                >
            </div>

            <div class="form-group">
                <label>{{ __('proposals.unit_price') }}</label>
                <input
                    name="items[__INDEX__][unit_price_minor]"
                    class="proposal-item-price"
                    type="number"
                    min="0"
                    step="1"
                    value="0"
                >
            </div>

            <div class="form-group">
                <label>{{ __('proposals.discount') }}</label>
                <input
                    name="items[__INDEX__][discount_minor]"
                    class="proposal-item-discount"
                    type="number"
                    min="0"
                    step="1"
                    value="0"
                >
            </div>

            <div class="form-group">
                <label>{{ __('proposals.tax_code') }}</label>
                <input name="items[__INDEX__][taxes][0][code]">
            </div>

            <div class="form-group">
                <label>{{ __('proposals.tax_amount') }}</label>
                <input
                    name="items[__INDEX__][taxes][0][amount_minor]"
                    class="proposal-item-tax"
                    type="number"
                    min="0"
                    step="1"
                    value="0"
                >
            </div>
        </div>

        <input
            type="hidden"
            name="items[__INDEX__][item_type]"
            class="proposal-item-type"
            value="service"
        >

        <div class="actions">
            <button
                class="btn btn-danger proposal-remove-item"
                type="button"
            >
                {{ __('proposals.remove_item') }}
            </button>
        </div>
    </div>
</template>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const container = document.getElementById('proposal-items');
    const template = document.getElementById('proposal-item-template');
    const addButton = document.getElementById('proposal-add-item');

    let nextIndex = container.querySelectorAll('.proposal-item-row').length;

    function numberValue(element) {
        const value = Number(element?.value ?? 0);

        return Number.isFinite(value)
            ? value
            : 0;
    }

    function refreshTotals() {
        let subtotal = 0;
        let discount = 0;
        let tax = 0;

        container.querySelectorAll('.proposal-item-row').forEach(function (row) {
            const quantity = numberValue(
                row.querySelector('.proposal-item-quantity')
            );

            const price = numberValue(
                row.querySelector('.proposal-item-price')
            );

            const rowDiscount = numberValue(
                row.querySelector('.proposal-item-discount')
            );

            const rowTax = numberValue(
                row.querySelector('.proposal-item-tax')
            );

            subtotal += Math.round(price * quantity);
            discount += rowDiscount;
            tax += rowTax;
        });

        const total = subtotal - discount + tax;

        document.getElementById('proposal-preview-subtotal').textContent =
            Math.round(subtotal);

        document.getElementById('proposal-preview-discount').textContent =
            Math.round(discount);

        document.getElementById('proposal-preview-tax').textContent =
            Math.round(tax);

        document.getElementById('proposal-preview-total').textContent =
            Math.round(total);
    }

    function bindRow(row) {
        const catalog = row.querySelector('.proposal-catalog-select');

        catalog.addEventListener('change', function () {
            const option = catalog.options[catalog.selectedIndex];

            if (! option.value) {
                return;
            }

            row.querySelector('.proposal-item-name').value =
                option.dataset.name ?? '';

            row.querySelector('.proposal-item-code').value =
                option.dataset.code ?? '';

            row.querySelector('.proposal-item-price').value =
                option.dataset.price ?? 0;

            row.querySelector('.proposal-item-type').value =
                option.dataset.type ?? 'service';

            refreshTotals();
        });

        row.querySelectorAll(
            '.proposal-item-quantity, ' +
            '.proposal-item-price, ' +
            '.proposal-item-discount, ' +
            '.proposal-item-tax'
        ).forEach(function (input) {
            input.addEventListener('input', refreshTotals);
        });

        row.querySelector('.proposal-remove-item')
            .addEventListener('click', function () {
                const rows = container.querySelectorAll('.proposal-item-row');

                if (rows.length <= 1) {
                    return;
                }

                row.remove();
                refreshTotals();
            });
    }

    container.querySelectorAll('.proposal-item-row').forEach(bindRow);

    addButton.addEventListener('click', function () {
        const html = template.innerHTML.replaceAll(
            '__INDEX__',
            String(nextIndex)
        );

        nextIndex += 1;

        const wrapper = document.createElement('div');
        wrapper.innerHTML = html.trim();

        const row = wrapper.firstElementChild;

        container.appendChild(row);
        bindRow(row);
        refreshTotals();
    });

    refreshTotals();
});

    const proposalAiButton =
        document.getElementById('proposal_ai_rewrite');

    const proposalNotes =
        document.getElementById('notes');

    const proposalAiStatus =
        document.getElementById('proposal_ai_status');

    if (
        proposalAiButton &&
        proposalNotes &&
        proposalAiStatus
    ) {
        proposalAiButton.addEventListener(
            'click',
            async function () {
                const text =
                    proposalNotes.value.trim();

                if (!text) {
                    proposalAiStatus.textContent =
                        @json(__('proposals.ai_rewrite_empty'));

                    return;
                }

                proposalAiButton.disabled = true;

                proposalAiStatus.textContent =
                    @json(__('proposals.ai_rewrite_loading'));

                try {
                    const response = await fetch(
                        proposalAiButton.dataset.url,
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
                                        __('proposals.ai_rewrite_instruction')
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

                    proposalNotes.value =
                        content;

                    proposalAiStatus.textContent =
                        @json(
                            __('proposals.ai_rewrite_success')
                        );
                } catch (error) {
                    proposalAiStatus.textContent =
                        @json(
                            __('proposals.ai_rewrite_error')
                        );
                } finally {
                    proposalAiButton.disabled =
                        false;
                }
            }
        );
    }</script>
