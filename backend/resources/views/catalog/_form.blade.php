<div class="grid gap-5 md:grid-cols-2">
    <div>
        <label for="type" class="block text-sm font-semibold text-gray-700">
            {{ __('catalog.type') }}
        </label>

        <select
            id="type"
            name="type"
            required
            class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 shadow-sm outline-none transition focus:border-[var(--primary)] focus:ring-2 focus:ring-[color:var(--primary)]/15"
        >
            @foreach ($types as $type)
                <option
                    value="{{ $type->value }}"
                    @selected(
                        old(
                            'type',
                            isset($item)
                                ? $item->type->value
                                : 'product'
                        ) === $type->value
                    )
                >
                    {{ __('catalog.' . $type->value) }}
                </option>
            @endforeach
        </select>
    </div>

    <div>
        <label for="name" class="block text-sm font-semibold text-gray-700">
            {{ __('catalog.name') }}
        </label>

        <input
            id="name"
            name="name"
            value="{{ old('name', $item->name ?? '') }}"
            required
            class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 shadow-sm outline-none transition focus:border-[var(--primary)] focus:ring-2 focus:ring-[color:var(--primary)]/15"
        >
    </div>

    <div>
        <label for="code" class="block text-sm font-semibold text-gray-700">
            {{ __('catalog.code') }}
        </label>

        <input
            id="code"
            name="code"
            value="{{ old('code', $item->code ?? '') }}"
            class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 shadow-sm outline-none transition focus:border-[var(--primary)] focus:ring-2 focus:ring-[color:var(--primary)]/15"
        >
    </div>

    <div>
        <label for="price_minor" class="block text-sm font-semibold text-gray-700">
            {{ __('catalog.price') }}
        </label>

        <input
            id="price_minor"
            name="price_minor"
            type="number"
            min="0"
            step="1"
            value="{{ old('price_minor', $item->price_minor ?? 0) }}"
            required
            class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 shadow-sm outline-none transition focus:border-[var(--primary)] focus:ring-2 focus:ring-[color:var(--primary)]/15"
        >
    </div>

    <div>
        <label for="currency" class="block text-sm font-semibold text-gray-700">
            {{ __('catalog.currency') }}
        </label>

        <input
            id="currency"
            name="currency"
            maxlength="3"
            value="{{
                old(
                    'currency',
                    $item->currency
                        ?? app(\App\Services\TenantContext::class)->get()->currency
                )
            }}"
            class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm uppercase text-gray-900 shadow-sm outline-none transition focus:border-[var(--primary)] focus:ring-2 focus:ring-[color:var(--primary)]/15"
        >
    </div>

    <div>
        <label for="is_active" class="block text-sm font-semibold text-gray-700">
            {{ __('catalog.status') }}
        </label>

        <select
            id="is_active"
            name="is_active"
            class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 shadow-sm outline-none transition focus:border-[var(--primary)] focus:ring-2 focus:ring-[color:var(--primary)]/15"
        >
            <option
                value="1"
                @selected(
                    (string) old(
                        'is_active',
                        isset($item) ? (int) $item->is_active : 1
                    ) === '1'
                )
            >
                {{ __('catalog.active') }}
            </option>

            <option
                value="0"
                @selected(
                    (string) old(
                        'is_active',
                        isset($item) ? (int) $item->is_active : 1
                    ) === '0'
                )
            >
                {{ __('catalog.inactive') }}
            </option>
        </select>
    </div>
</div>
