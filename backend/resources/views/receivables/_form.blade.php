@if ($errors->any())
    <div class="alert alert-danger">
        <ul>
            @foreach ($errors->all() as $error)
                <li>
                    {{ $error }}
                </li>
            @endforeach
        </ul>
    </div>
@endif

<div class="form-grid">
    <div class="form-group">
        <label
            class="form-label"
            for="customer_id"
        >
            {{ __('receivables.fields.customer') }}
        </label>

        <select
            class="form-control"
            id="customer_id"
            name="customer_id"
            required
        >
            <option value="">
                {{ __('receivables.select_customer') }}
            </option>

            @foreach ($customers as $customer)
                <option
                    value="{{ $customer->id }}"
                    @selected(
                        (string) old(
                            'customer_id',
                            $receivable->customer_id ?? ''
                        )
                        ===
                        (string) $customer->id
                    )
                >
                    {{ $customer->name }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="form-group">
        <label
            class="form-label"
            for="sale_id"
        >
            {{ __('receivables.fields.sale') }}
        </label>

        <select
            class="form-control"
            id="sale_id"
            name="sale_id"
        >
            <option value="">
                {{ __('receivables.no_sale') }}
            </option>

            @foreach ($sales as $sale)
                <option
                    value="{{ $sale->id }}"
                    @selected(
                        (string) old(
                            'sale_id',
                            $receivable->sale_id ?? ''
                        )
                        ===
                        (string) $sale->id
                    )
                >
                    {{ $sale->number }}
                    —
                    {{ $sale->customer_name }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="form-group">
        <label
            class="form-label"
            for="title"
        >
            {{ __('receivables.fields.title') }}
        </label>

        <input
            class="form-control"
            id="title"
            name="title"
            maxlength="255"
            required
            value="{{ old(
                'title',
                $receivable->title ?? ''
            ) }}"
        >
    </div>

    <div class="form-group">
        <label
            class="form-label"
            for="amount_minor"
        >
            {{ __('receivables.fields.amount_minor') }}
        </label>

        <input
            class="form-control"
            id="amount_minor"
            name="amount_minor"
            type="number"
            min="0"
            step="1"
            required
            value="{{ old(
                'amount_minor',
                $receivable->amount_minor ?? ''
            ) }}"
        >
    </div>

    <div class="form-group">
        <label
            class="form-label"
            for="currency"
        >
            {{ __('receivables.fields.currency') }}
        </label>

        <input
            class="form-control"
            id="currency"
            name="currency"
            maxlength="3"
            value="{{ old(
                'currency',
                $receivable->currency
                    ?? app(
                        \App\Services\TenantContext::class
                    )->get()->currency
            ) }}"
        >
    </div>

    <div class="form-group">
        <label
            class="form-label"
            for="due_date"
        >
            {{ __('receivables.fields.due_date') }}
        </label>

        <input
            class="form-control"
            id="due_date"
            name="due_date"
            type="date"
            required
            value="{{ old(
                'due_date',
                isset($receivable)
                    ? $receivable->due_date?->toDateString()
                    : ''
            ) }}"
        >
    </div>
</div>
