@extends('layouts.app')

@section('title', __('billing.title'))

@section('content')
<div class="page-header">
    <div>
        <span class="eyebrow">{{ __('billing.account') }}</span>

        <h1>{{ __('billing.title') }}</h1>

        <p>
            {{ __('billing.intro') }}
        </p>
    </div>

    <div class="actions">
        <a
            href="{{ route('dashboard') }}"
            class="btn btn-secondary"
        >
            {{ __('billing.back_dashboard') }}
        </a>
    </div>
</div>

@if ($subscription === null)
    <div class="card">
        <div class="empty-state">
            {{ __('billing.no_subscription') }}
        </div>
    </div>
@else
    @php
        $plan = $subscription->plan;

        $price = $plan?->prices
            ?->firstWhere(
                'currency',
                $tenant->currency
            );

        $trialEndsAt = $tenant->trial_ends_at;

        $trialActive =
    ! $isPaid
    && $trialEndsAt !== null
    && now()->lessThan($trialEndsAt);

        $trialDaysRemaining = $trialActive
            ? max(
                0,
                now()->startOfDay()->diffInDays(
                    $trialEndsAt->startOfDay(),
                    false
                )
            )
            : 0;

        $amountFormatted = $price
            ? number_format(
                $price->amount_minor / 100,
                2,
                ',',
                '.'
            )
            : null;

           $subscriptionStatusLabel = match (
    $subscription->status->value
) {
    'active' => $subscription->cancel_at !== null
        ? __('billing.status.active_until', [
            'date' => $subscription->cancel_at->format('d/m/Y'),
        ])
        : __('billing.status.active'),
    'suspended' => __('billing.status.suspended'),
    'cancelled' => __('billing.status.cancelled'),
    'expired' => __('billing.status.expired'),
    default => ucfirst(
        $subscription->status->value
    ),
};

    @endphp

    <div class="billing-grid">
        <div class="card">
            <div class="section-header">
                <div>
                    <span class="eyebrow">
                        {{ __('billing.current_plan') }}
                    </span>

                    <h2>
                        {{ $plan?->name ?? __('billing.plan') }}
                    </h2>
                </div>
            </div>

            <div class="billing-details">
                <div>
                    <span class="billing-label">
                        {{ __('billing.monthly_amount') }}
                    </span>

                    <strong>
                        @if ($amountFormatted !== null)
                            {{ $tenant->currency }}
                            {{ $amountFormatted }}
                        @else
                            {{ __('billing.not_configured') }}
                        @endif
                    </strong>
                </div>

                <div>
                    <span class="billing-label">
                        {{ __('billing.subscription_status') }}
                    </span>

                   <strong>
    {{ $subscriptionStatusLabel }}
</strong>
                </div>

@if ($subscription->cancel_at !== null)
    <div>
        <span class="billing-label">
            {{ __('billing.cancellation') }}
        </span>

        <strong>
            {{ __('billing.scheduled_for') }}
            {{ $subscription->cancel_at->format('d/m/Y H:i') }}
        </strong>
    </div>
@endif

                <div>
                    <span class="billing-label">
                        {{ __('billing.payment') }}
                    </span>

                    <strong>
                        {{ $isPaid ? __('billing.paid') : __('billing.pending') }}
                    </strong>
                </div>

                @if ($isPaid)
                    <div>
                        <span class="billing-label">
                            {{ __('billing.provider') }}
                        </span>

                        <strong>
                            {{
    match ($subscription->payment_provider) {
        'stripe' => 'Stripe',
        'mercado_pago' => 'Mercado Pago',
        default => $subscription->payment_provider,
    }
}}
                        </strong>
                    </div>

                    <div>
                        <span class="billing-label">
                            {{ __('billing.payment_method') }}
                        </span>

                        <strong>
                            {{ $subscription->payment_method ?? __('billing.not_informed') }}
                        </strong>
                    </div>

                    <div>
                        <span class="billing-label">
                            {{ __('billing.paid_at') }}
                        </span>

                        <strong>
                            {{
                                $subscription->paid_at
                                    ?->format('d/m/Y H:i')
                            }}
                        </strong>
                    </div>
                @endif
            </div>
        </div>

        <div class="card">
            <div class="section-header">
                <div>
                    <span class="eyebrow">
                        {{ __('billing.trial_period') }}
                    </span>

                    <h2>
    @if ($isPaid)
        {{ __('billing.subscription_activated') }}
    @elseif ($trialActive)
        {{ __('billing.trial_active') }}
    @else
        {{ __('billing.trial_ended') }}
    @endif
</h2>
                </div>
            </div>

            @if ($isPaid)
    <p>
        {{ __('billing.trial_converted') }}
    </p>

    @if ($subscription->paid_at !== null)
        <p class="form-help">
            {{ __('billing.subscription_activated') }} em
            {{ $subscription->paid_at->format('d/m/Y H:i') }}.
        </p>
    @endif
@elseif ($trialActive)
    <p>
        {{ __('billing.trial_ends_at') }}
        <strong>
            {{ $trialEndsAt->format('d/m/Y') }}
        </strong>.
    </p>

    <p class="form-help">
        {{ __('billing.approximately') }}
        {{ $trialDaysRemaining }}
        {{ __('billing.days_remaining') }}
    </p>
@else
    <p>
        {{ __('billing.trial_already_ended') }}
    </p>
@endif
        </div>
    </div>

@if (
    $isPaid
    && $subscription->payment_provider === 'stripe'
)
    <form
        method="POST"
        action="{{ route('billing.portal') }}"
        class="billing-portal-form"
    >
        @csrf

        <button
    type="submit"
    class="btn btn-secondary"
>
    @if ($subscription->cancel_at !== null)
        {{ __('billing.manage_billing') }}
    @else
        {{ __('billing.manage_subscription') }}
    @endif
</button>
    </form>
@endif


    <div class="card billing-payment-card">
        <div class="section-header">
            <div>
                <span class="eyebrow">
                    {{ __('billing.payment') }}
                </span>

                <h2>
                    {{ $isPaid ? __('billing.subscription_paid') : __('billing.subscribe_now') }}
                </h2>

                <p>
    @if ($subscription->cancel_at !== null)
        {{ __('billing.subscription_active_until') }}
        <strong>
            {{ $subscription->cancel_at->format('d/m/Y') }}
        </strong>.
        {{ __('billing.ends_after_date') }}
    @elseif ($isPaid)
        {{ __('billing.payment_registered') }}
    @else
        {{ __('billing.continue_secure_payment') }}
    @endif
</p>
            </div>
        </div>

        @if (! $isPaid)
            @if ($price === null)
                <div class="alert alert-error">
                    {{ __('billing.no_price_for_currency') }}
                    {{ $tenant->currency }}.
                </div>
            @else
                <form
    method="POST"
    action="{{ route('billing.checkout') }}"
>
    @csrf

    <div class="billing-provider-actions">
        <button
            type="submit"
            name="provider"
            value="stripe"
            class="btn btn-primary"
        >
            {{ __('billing.pay_with_stripe') }}
        </button>

        <button
            type="submit"
            name="provider"
            value="mercado_pago"
            class="btn btn-secondary"
        >
            {{ __('billing.pay_with_mercado_pago') }}
        </button>
    </div>
</form>

<p class="form-help billing-help">
    {{ __('billing.provider_help') }}
</p>
            @endif
        @endif
    </div>
@endif

@if (
    $isPaid
    && $subscription->payment_provider === 'stripe'
    && isset($availablePlans)
    && $availablePlans->isNotEmpty()
)
    <div class="card billing-plans-card">
        <div class="section-header">
            <div>
                <span class="eyebrow">
                    {{ __('billing.plans') }}
                </span>

                <h2>
                    {{ __('billing.change_plan') }}
                </h2>

                <p>
                    {{ __('billing.change_plan_description') }}
                </p>
            </div>
        </div>

        <div class="billing-plan-grid">
            @foreach ($availablePlans as $availablePlan)
                @php
                    $availablePrice = $availablePlan
                        ->prices
                        ->firstWhere(
                            'currency',
                            $tenant->currency
                        );

                    $availableAmount = $availablePrice
                        ? number_format(
                            $availablePrice->amount_minor / 100,
                            2,
                            ',',
                            '.'
                        )
                        : null;

                    $isCurrentPlan =
                        $subscription->plan_id
                        === $availablePlan->id;
                @endphp

                <div class="billing-plan-option">
                    <div>
                        <strong class="billing-plan-name">
                            {{ $availablePlan->name }}
                        </strong>

                        <div class="billing-plan-price">
                            @if ($availableAmount !== null)
                                {{ $tenant->currency }}
                                {{ $availableAmount }}
                                / {{ __('billing.month') }}
                            @endif
                        </div>
                    </div>

                    @if ($isCurrentPlan)
                        <button
                            type="button"
                            class="btn btn-secondary"
                            disabled
                        >
                            {{ __('billing.current_plan') }}
                        </button>
                    @else
                        <form
                            method="POST"
                            action="{{ route('billing.change-plan') }}"
                        >
                            @csrf

                            <input
                                type="hidden"
                                name="plan_id"
                                value="{{ $availablePlan->id }}"
                            >

                            <button
                                type="submit"
                                class="btn btn-primary"
                            >
                                {{ __('billing.change_to', ['plan' => $availablePlan->name]) }}
                            </button>
                        </form>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
@endif

@if (
    $subscription !== null
    && $subscription->relationLoaded('invoices')
)
    <div class="card billing-invoices-card">
        <div class="section-header">
            <div>
                <span class="eyebrow">
                    {{ __('billing.charges') }}
                </span>

                <h2>
                    {{ __('billing.invoice_history') }}
                </h2>

                <p>
                    {{ __('billing.invoice_history_description') }}
                </p>
            </div>
        </div>

        @if ($subscription->invoices->isEmpty())
            <div class="empty-state">
                {{ __('billing.no_invoices') }}
            </div>
        @else
            <div class="billing-invoice-list">
                @foreach ($subscription->invoices as $invoice)
                    @php
                        $invoiceAmount = number_format(
                            $invoice->amount_due / 100,
                            2,
                            ',',
                            '.'
                        );

                        $invoiceStatus = match (
                            strtolower(
                                (string) $invoice->status
                            )
                        ) {
                            'paid' => __('billing.invoice_status.paid'),
                            'open' => __('billing.invoice_status.open'),
                            'draft' => __('billing.invoice_status.draft'),
                            'void' => __('billing.invoice_status.void'),
                            'uncollectible' =>
                                __('billing.invoice_status.uncollectible'),
                            default =>
                                $invoice->status
                                    ?? __('billing.not_informed'),
                        };
                    @endphp

                    <div class="billing-invoice-item">
                        <div>
                            <strong>
                                {{ $invoice->currency }}
                                {{ $invoiceAmount }}
                            </strong>

                            <div class="form-help">
                                @if ($invoice->period_start)
                                    {{ __('billing.period') }}:
                                    {{ $invoice->period_start->format('d/m/Y') }}
                                @endif

                                @if ($invoice->period_end)
                                    {{ __('billing.until') }}
                                    {{ $invoice->period_end->format('d/m/Y') }}
                                @endif
                            </div>
                        </div>

                        <div class="billing-invoice-status">
                            <strong>
                                {{ $invoiceStatus }}
                            </strong>

                            @if ($invoice->paid_at)
                                <span class="form-help">
                                    {{ __('billing.paid_at') }}
                                    {{ $invoice->paid_at->format('d/m/Y H:i') }}
                                </span>
                            @endif
                        </div>

                        <div class="billing-invoice-actions">
                            @if ($invoice->hosted_invoice_url)
                                <a
                                    href="{{ $invoice->hosted_invoice_url }}"
                                    class="btn btn-secondary"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                >
                                    {{ __('billing.view_invoice') }}
                                </a>
                            @endif

                            @if ($invoice->invoice_pdf)
                                <a
                                    href="{{ $invoice->invoice_pdf }}"
                                    class="btn btn-secondary"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                >
                                    PDF
                                </a>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
@endif

<style>

    .billing-invoices-card {
    margin-top: 20px;
}

.billing-invoice-list {
    display: grid;
    gap: 14px;
}

.billing-invoice-item {
    display: grid;
    grid-template-columns:
        minmax(0, 1fr)
        minmax(140px, auto)
        auto;
    align-items: center;
    gap: 20px;
    padding: 16px 0;
    border-bottom: 1px solid #e5e7eb;
}

.billing-invoice-item:last-child {
    border-bottom: 0;
}

.billing-invoice-status {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.billing-invoice-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    justify-content: flex-end;
}

@media (max-width: 800px) {
    .billing-invoice-item {
        grid-template-columns: 1fr;
        gap: 10px;
    }

    .billing-invoice-actions {
        justify-content: flex-start;
    }
}

    .billing-plans-card {
    margin-top: 20px;
}

.billing-plan-grid {
    display: grid;
    grid-template-columns: repeat(
        3,
        minmax(0, 1fr)
    );
    gap: 16px;
    margin-top: 20px;
}

.billing-plan-option {
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    gap: 18px;
    padding: 18px;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
}

.billing-plan-name {
    display: block;
    font-size: 18px;
}

.billing-plan-price {
    margin-top: 6px;
    color: #6b7280;
}

@media (max-width: 800px) {
    .billing-plan-grid {
        grid-template-columns: 1fr;
    }
}

.billing-grid {
    display: grid;
    grid-template-columns: repeat(
        2,
        minmax(0, 1fr)
    );
    gap: 20px;
    margin-bottom: 20px;
}

.billing-portal-form {
    margin-top: 20px;
}

.billing-details {
    display: grid;
    gap: 18px;
}

.billing-provider-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
}

.billing-details > div {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
    padding-bottom: 14px;
    border-bottom: 1px solid #e5e7eb;
}

.billing-details > div:last-child {
    padding-bottom: 0;
    border-bottom: 0;
}

.billing-label {
    color: #6b7280;
    font-size: 14px;
}

.billing-payment-card {
    margin-top: 20px;
}

.billing-help {
    margin-top: 12px;
}

@media (max-width: 800px) {
    .billing-grid {
        grid-template-columns: 1fr;
    }

    .billing-details > div {
        align-items: flex-start;
        flex-direction: column;
        gap: 5px;
    }
}
</style>
@endsection
