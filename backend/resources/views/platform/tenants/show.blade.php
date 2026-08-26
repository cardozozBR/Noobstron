@extends('platform.layout')

@section('title', $tenant->name)

@section('body')

<style>
.platform-tenant-show {
    --tenant-border: #e5e7eb;
    --tenant-muted: #64748b;
    --tenant-surface: #f8fafc;
}

.platform-tenant-show .tenant-summary {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    margin-bottom: 18px;
    overflow: hidden;
    background: #fff;
    border: 1px solid var(--tenant-border);
    border-radius: 16px;
}

.platform-tenant-show .tenant-summary__item {
    min-width: 0;
    padding: 16px 18px;
    border-right: 1px solid var(--tenant-border);
}

.platform-tenant-show .tenant-summary__item:last-child {
    border-right: 0;
}

.platform-tenant-show .tenant-summary__label {
    display: block;
    margin-bottom: 7px;
    color: var(--tenant-muted);
    font-size: 11px;
    font-weight: 700;
    line-height: 1.3;
    text-transform: uppercase;
    letter-spacing: .055em;
}

.platform-tenant-show .tenant-summary__item strong {
    display: block;
    min-width: 0;
    font-size: 15px;
    line-height: 1.4;
    overflow-wrap: anywhere;
}

@media (max-width: 800px) {
    .platform-tenant-show .tenant-summary {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .platform-tenant-show .tenant-summary__item {
        border-bottom: 1px solid var(--tenant-border);
    }

    .platform-tenant-show .tenant-summary__item:nth-child(2) {
        border-right: 0;
    }

    .platform-tenant-show .tenant-summary__item:nth-last-child(-n + 2) {
        border-bottom: 0;
    }
}

@media (max-width: 480px) {
    .platform-tenant-show .tenant-summary {
        grid-template-columns: 1fr;
    }

    .platform-tenant-show .tenant-summary__item {
        border-right: 0;
        border-bottom: 1px solid var(--tenant-border);
    }

    .platform-tenant-show .tenant-summary__item:nth-last-child(2) {
        border-bottom: 1px solid var(--tenant-border);
    }

    .platform-tenant-show .tenant-summary__item:last-child {
        border-bottom: 0;
    }
}

.platform-tenant-show .platform-toolbar {
    align-items: flex-end;
    gap: 18px;
    margin-bottom: 20px;
}

.platform-tenant-show .platform-toolbar h1 {
    margin: 4px 0 0;
    font-size: 32px;
    line-height: 1.1;
    letter-spacing: -.03em;
}

.platform-tenant-show .platform-toolbar .platform-muted {
    margin-bottom: 0;
}

.platform-tenant-show .detail-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 16px;
    align-items: start;
}

.platform-tenant-show .platform-card {
    min-width: 0;
    border-radius: 16px;
}

.platform-tenant-show .platform-card h2 {
    margin: 0 0 16px;
    font-size: 17px;
    line-height: 1.3;
    letter-spacing: -.01em;
}

.platform-tenant-show .detail-list {
    display: grid;
    gap: 0;
    margin: 0;
}

.platform-tenant-show .detail-list > div {
    display: grid;
    grid-template-columns: minmax(120px, .8fr) minmax(0, 1.2fr);
    gap: 16px;
    align-items: center;
    padding: 12px 0;
    border-bottom: 1px solid var(--tenant-border);
}

.platform-tenant-show .detail-list > div:first-child {
    padding-top: 0;
}

.platform-tenant-show .detail-list > div:last-child {
    padding-bottom: 0;
    border-bottom: 0;
}

.platform-tenant-show .detail-list dt {
    color: var(--tenant-muted);
    font-size: 11px;
    font-weight: 700;
    line-height: 1.4;
    text-transform: uppercase;
    letter-spacing: .055em;
}

.platform-tenant-show .detail-list dd {
    min-width: 0;
    margin: 0;
    font-weight: 600;
    overflow-wrap: anywhere;
}

.platform-tenant-show .platform-card form {
    padding-top: 16px;
    margin-top: 18px !important;
    border-top: 1px solid var(--tenant-border);
}

.platform-tenant-show .platform-card form label {
    display: block;
}

.platform-tenant-show .platform-card form input,
.platform-tenant-show .platform-card form select {
    box-sizing: border-box;
    width: 100%;
    max-width: 100%;
}

.platform-tenant-show .table-wrap {
    margin-top: 4px;
}

.platform-tenant-show .platform-table {
    font-size: 13px;
}

.platform-tenant-show .platform-table th {
    white-space: nowrap;
}

.platform-tenant-show .platform-table td {
    vertical-align: middle;
}

.platform-tenant-show .platform-card:has(.platform-table) {
    grid-column: 1 / -1;
}

.platform-tenant-show .platform-card:has(.table-wrap) h2 {
    margin-bottom: 14px;
}

.platform-tenant-show .platform-card > .platform-muted:first-child {
    font-size: 12px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .045em;
}

@media (max-width: 980px) {
    .platform-tenant-show .detail-grid {
        grid-template-columns: 1fr;
    }

    .platform-tenant-show .platform-card:has(.platform-table) {
        grid-column: auto;
    }
}

@media (max-width: 800px) {
    .platform-tenant-show .platform-toolbar {
        align-items: flex-start;
        flex-direction: column;
    }

    .platform-tenant-show .platform-toolbar h1 {
        font-size: 27px;
    }

    .platform-tenant-show .detail-list > div {
        grid-template-columns: 1fr;
        gap: 4px;
    }
}

@media (max-width: 560px) {
    .platform-tenant-show .platform-toolbar .button {
        width: 100%;
    }

    .platform-tenant-show .platform-card {
        border-radius: 14px;
    }
}

/* Tenant detail final polish */
.platform-tenant-show .detail-grid {
    grid-auto-flow: row dense;
}

.platform-tenant-show .detail-list + form {
    margin-top: 14px !important;
}

.platform-tenant-show .platform-card form {
    padding-top: 14px;
    margin-top: 14px !important;
}

.platform-tenant-show .platform-card form > p:first-child,
.platform-tenant-show .platform-card form > label:first-child {
    margin-top: 0;
}

.platform-tenant-show .platform-card form label {
    margin-bottom: 10px;
    color: #475569;
    font-size: 13px;
    font-weight: 600;
}

.platform-tenant-show .platform-card form input,
.platform-tenant-show .platform-card form select {
    min-height: 42px;
}

.platform-tenant-show .platform-card form .button {
    min-height: 40px;
}

.platform-tenant-show .platform-card form p.platform-muted {
    margin: 8px 0 0;
    font-size: 12px;
    line-height: 1.5;
}

/* Forms inside informational cards read as secondary admin actions. */
.platform-tenant-show .detail-list ~ form {
    padding: 14px;
    margin-right: -2px;
    margin-left: -2px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
}

/* Avoid a second separator when the action already has its own panel. */
.platform-tenant-show .detail-list ~ form + form {
    margin-top: 10px !important;
}

/* Keep destructive controls visually distinct without dominating the page. */
.platform-tenant-show form .button-danger,
.platform-tenant-show form .button--danger {
    font-weight: 700;
}

/* Long administrative cards should remain comfortable but compact. */
.platform-tenant-show .platform-card > form:last-child {
    margin-bottom: 0;
}

/* Historical/operational tables remain visually separate from editing UI. */
.platform-tenant-show .platform-card:has(.table-wrap) {
    padding-top: 20px;
    padding-bottom: 20px;
}

.platform-tenant-show .platform-card:has(.table-wrap) form {
    background: transparent;
}

</style>

<div class="platform-tenant-show">
    @include('platform.partials.navigation')

    <main class="platform-main">
        @php
            $breadcrumbs = [
                [
                    'label' => __('platform.nav.dashboard'),
                    'url' => route('platform.dashboard'),
                ],
                [
                    'label' => __('platform.nav.tenants'),
                    'url' => route('platform.tenants.index'),
                ],
                [
                    'label' => $tenant->name,
                    'url' => null,
                ],
            ];
        @endphp

        @include('platform.partials.breadcrumbs')

        <x-platform.error-state :errors="$errors" />
        <div class="platform-toolbar">
            <div>
                <div class="platform-muted">
                    {{ __('platform.tenants.tenant') }}
                </div>

                <h1>{{ $tenant->name }}</h1>

                <p class="platform-muted">
                    {{ $tenant->slug }}
                </p>
            </div>

            <a
                class="button button-secondary"
                href="{{ route('platform.tenants.index') }}"
            >
                {{ __('platform.tenants.back') }}
            </a>
        </div>

        <div class="tenant-summary">
    <div class="tenant-summary__item">
        <span class="tenant-summary__label">
            {{ __('platform.tenants.status') }}
        </span>

        <strong>
            <x-platform.badge
                :variant="match ($tenant->status) {
                    'active' => 'success',
                    'blocked' => 'danger',
                    'inactive' => 'neutral',
                    default => 'neutral',
                }"
            >
                {{ $tenant->status }}
            </x-platform.badge>
        </strong>
    </div>

    <div class="tenant-summary__item">
        <span class="tenant-summary__label">
            {{ __('platform.tenants.plan') }}
        </span>

        <strong>
            {{ $subscription?->plan?->name ?? '—' }}
        </strong>
    </div>

    <div class="tenant-summary__item">
        <span class="tenant-summary__label">
            {{ __('platform.tenants.trial') }}
        </span>

        <strong>
            {{ $tenant->trial_ends_at?->format('d/m/Y') ?? '—' }}
        </strong>
    </div>

    <div class="tenant-summary__item">
        <span class="tenant-summary__label">
            {{ __('platform.tenants.users') }}
        </span>

        <strong>{{ number_format($userCount) }}</strong>
    </div>
</div>

        <div class="detail-grid">
@if (
    $tenant->trial_started_at
    && $tenant->trial_ends_at
)
    <form
        method="POST"
        action="{{ route(
            'platform.tenants.trial.extend',
            $tenant
        ) }}"
        style="margin-top: 18px"
    >
        @csrf

        <div style="display:grid; gap:10px">
            <label>
                <strong>Prorrogar trial</strong>

                <input
                    type="number"
                    name="days"
                    min="1"
                    max="90"
                    value="7"
                    required
                    style="display:block; margin-top:6px"
                >
            </label>

            <label>
                <span class="platform-muted">Motivo</span>

                <input
                    type="text"
                    name="reason"
                    maxlength="500"
                    placeholder="Motivo administrativo"
                    style="display:block; margin-top:6px; width:100%"
                >
            </label>

            <button
                class="button button-secondary"
                type="submit"
                onclick="return confirm(
                    'Confirma a prorrogação do trial deste tenant?'
                )"
            >
                Prorrogar
            </button>
        </div>
    </form>
@endif
            <section class="platform-card">
                <h2>{{ __('platform.tenants.basic_data') }}</h2>

                <dl class="detail-list">
                    <div>
                        <dt>{{ __('platform.tenants.status') }}</dt>
                        <dd>
                            <x-platform.badge
                                :variant="match ($tenant->status) {
                                    'active' => 'success',
                                    'blocked' => 'danger',
                                    'inactive' => 'neutral',
                                    default => 'neutral',
                                }"
                            >
                                {{ $tenant->status }}
                            </x-platform.badge>
                        </dd>
                    </div>

                    <div>
                        <dt>{{ __('platform.tenants.country') }}</dt>
                        <dd>{{ $tenant->country_code }}</dd>
                    </div>

                    <div>
                        <dt>{{ __('platform.tenants.language') }}</dt>
                        <dd>{{ $tenant->locale }}</dd>
                    </div>

                    <div>
                        <dt>{{ __('platform.tenants.segment') }}</dt>
                        <dd>{{ $tenant->segment ?? '—' }}</dd>
                    </div>

                    <div>
                        <dt>{{ __('platform.tenants.users') }}</dt>
                        <dd>{{ $userCount }}</dd>
                    </div>
                </dl>
@if ($tenant->status === 'active')
    <form
        method="POST"
        action="{{ route(
            'platform.tenants.suspend',
            $tenant
        ) }}"
        style="margin-top:18px"
    >
        @csrf

        <label>
            <span class="platform-muted">
                Motivo da suspensão
            </span>

            <input
                type="text"
                name="reason"
                maxlength="500"
                required
                placeholder="Informe o motivo administrativo"
                style="display:block; margin-top:6px; width:100%"
            >
        </label>

        <button
            class="button button-secondary"
            type="submit"
            style="margin-top:10px"
            onclick="return confirm(
                'Confirma a suspensão deste tenant? O workspace ficará indisponível.'
            )"
        >
            Suspender tenant
        </button>
    </form>
@elseif ($tenant->status === 'blocked')
    <form
        method="POST"
        action="{{ route(
            'platform.tenants.reactivate',
            $tenant
        ) }}"
        style="margin-top:18px"
    >
        @csrf

        <label>
            <span class="platform-muted">
                Motivo da reativação
            </span>

            <input
                type="text"
                name="reason"
                maxlength="500"
                required
                placeholder="Informe o motivo administrativo"
                style="display:block; margin-top:6px; width:100%"
            >
        </label>

        <button
            class="button button-secondary"
            type="submit"
            style="margin-top:10px"
            onclick="return confirm(
                'Confirma a reativação deste tenant?'
            )"
        >
            Reativar tenant
        </button>
    </form>
@endif
            </section>

            <section class="platform-card">
                <h2>{{ __('platform.tenants.trial') }}</h2>

                <dl class="detail-list">
                    <div>
                        <dt>{{ __('platform.tenants.start') }}</dt>
                        <dd>
                            {{ $tenant->trial_started_at?->format('d/m/Y H:i') ?? __('platform.tenants.not_started') }}
                        </dd>
                    </div>

                    <div>
                        <dt>{{ __('platform.tenants.end') }}</dt>
                        <dd>
                            {{ $tenant->trial_ends_at?->format('d/m/Y H:i') ?? '—' }}
                        </dd>
                    </div>
                </dl>
            </section>

            <section class="platform-card">
                <h2>{{ __('platform.tenants.subscription') }}</h2>

                @if ($subscription)
                    <dl class="detail-list">
                        <div>
                            <dt>{{ __('platform.tenants.plan') }}</dt>
                            <dd>
                                {{ $subscription->plan?->name ?? '—' }}
                            </dd>
                        </div>

                        <div>
                            <dt>{{ __('platform.tenants.status') }}</dt>
                            <dd>
                                <x-platform.badge
                                    :variant="match ($subscription->status->value) {
                                        'active' => 'success',
                                        'suspended' => 'warning',
                                        'cancelled',
                                        'expired' => 'neutral',
                                        default => 'neutral',
                                    }"
                                >
                                    {{ $subscription->status->value }}
                                </x-platform.badge>
                            </dd>
                        </div>

                        <div>
                            <dt>{{ __('platform.tenants.current_period') }}</dt>
                            <dd>
                                {{ $subscription->current_period_start?->format('d/m/Y') }}
                                —
                                {{ $subscription->current_period_end?->format('d/m/Y') }}
                            </dd>
                        </div>
                    </dl>

@if (
    $subscription->status->value === 'active'
    && $subscription->payment_provider === 'stripe'
    && $subscription->cancel_at === null
    && $availablePlans->count() > 1
)
    <form
        method="POST"
        action="{{ route(
            'platform.tenants.subscription.correct-plan',
            $tenant
        ) }}"
        style="margin-top:18px"
    >
        @csrf

        <label>
            <span class="platform-muted">
                Corrigir plano da assinatura
            </span>

            <select
                name="plan_id"
                required
                style="display:block; margin-top:6px; width:100%"
            >
                <option value="">
                    Selecione o plano de destino
                </option>

                @foreach ($availablePlans as $plan)
                    @if ($plan->id !== $subscription->plan_id)
                        <option value="{{ $plan->id }}">
                            {{ $plan->name }}
                        </option>
                    @endif
                @endforeach
            </select>
        </label>

        <label style="display:block; margin-top:10px">
            <span class="platform-muted">
                Motivo da correção
            </span>

            <input
                type="text"
                name="reason"
                maxlength="500"
                required
                placeholder="Informe o motivo administrativo"
                style="display:block; margin-top:6px; width:100%"
            >
        </label>

        <button
            class="button button-secondary"
            type="submit"
            style="margin-top:10px"
            onclick="return confirm(
                'Confirma a correção do plano desta assinatura? A Stripe poderá gerar prorrata.'
            )"
        >
            Corrigir plano
        </button>
    </form>
@endif

@if (
    $subscription->status->value === 'active'
    && $subscription->payment_provider === 'stripe'
    && $subscription->cancel_at === null
)
    <form
        method="POST"
        action="{{ route(
            'platform.tenants.subscription.cancel',
            $tenant
        ) }}"
        style="margin-top:18px"
    >
        @csrf

        <label>
            <span class="platform-muted">
                Motivo do cancelamento
            </span>

            <input
                type="text"
                name="reason"
                maxlength="500"
                required
                placeholder="Informe o motivo administrativo"
                style="display:block; margin-top:6px; width:100%"
            >
        </label>

        <button
            class="button button-secondary"
            type="submit"
            style="margin-top:10px"
            onclick="return confirm(
                'Confirma o cancelamento da assinatura ao fim do período atual? O acesso permanecerá ativo até essa data.'
            )"
        >
            Cancelar assinatura ao fim do período
        </button>
    </form>
@elseif (
    $subscription->status->value === 'active'
    && $subscription->payment_provider === 'stripe'
    && $subscription->cancel_at !== null
)
    <p
        class="platform-muted"
        style="margin-top:14px"
    >
        Cancelamento agendado para
        {{ $subscription->cancel_at->format('d/m/Y H:i') }}.
    </p>
@endif

                @else
                    <p class="platform-muted">
                        {{ __('platform.tenants.no_subscription') }}.
                    </p>
                @endif
            </section>

            <section class="platform-card">
                <h2>{{ __('platform.tenants.features') }}</h2>

                @if ($features->isEmpty())
                    <p class="platform-muted">
                        {{ __('platform.tenants.no_features') }}
                    </p>
                @else
                    <div class="table-wrap">
                        <table class="platform-table">
                            <thead>
                                <tr>
                                    <th scope="col">{{ __('platform.tenants.feature') }}</th>
                                    <th scope="col">{{ __('platform.tenants.enabled') }}</th>
                                    <th scope="col">{{ __('platform.tenants.limit') }}</th>
                                </tr>
                            </thead>

                            <tbody>
                                @foreach ($features as $feature)
                                    <tr>
                                        <td>
                                            {{ $feature->feature->value }}
                                        </td>
                                        <td>
                                            {{ $feature->enabled ? __('platform.tenants.yes') : __('platform.tenants.no') }}
                                        </td>
                                        <td>
                                            {{ $feature->limit_value ?? __('platform.tenants.unlimited') }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>

            <section class="platform-card">
                <h2>{{ __('platform.tenants.plan_limits') }}</h2>

                @if ($usageLimits->isEmpty())
                    <p class="platform-muted">
                        {{ __('platform.tenants.no_limits') }}
                    </p>
                @else
                    <div class="table-wrap">
                        <table class="platform-table">
                            <thead>
                                <tr>
                                    <th scope="col">{{ __('platform.tenants.metric') }}</th>
                                    <th scope="col">{{ __('platform.tenants.limit') }}</th>
                                </tr>
                            </thead>

                            <tbody>
                                @foreach ($usageLimits as $limit)
                                    <tr>
                                        <td>
                                            {{ $limit->metric->value }}
                                        </td>
                                        <td>
                                            {{ $limit->limit_value ?? __('platform.tenants.unlimited') }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>

            <section class="platform-card">
                <h2>Uso</h2>

                <dl class="detail-list">
                    <div>
                        <dt>Usuários</dt>
                        <dd>{{ number_format($usage['users']) }}</dd>
                    </div>

                    <div>
                        <dt>E-mails</dt>
                        <dd>{{ number_format($usage['email_messages']) }}</dd>
                    </div>

                    <div>
                        <dt>WhatsApps enviados</dt>
                        <dd>{{ number_format($usage['whatsapp_messages']) }}</dd>
                    </div>

                    <div>
                        <dt>Tokens de IA</dt>
                        <dd>{{ number_format($usage['ai_tokens']) }}</dd>
                    </div>
                </dl>
            </section>

            <section class="platform-card">
                <h2>Histórico de assinaturas</h2>

                @if ($subscriptionHistory->isEmpty())
                    <p class="platform-muted">
                        {{ __('platform.empty_states.tenant_no_subscription') }}
                    </p>
                @else
                    <div class="table-wrap">
                        <table class="platform-table">
                            <thead>
                                <tr>
                                    <th scope="col">Plano</th>
                                    <th scope="col">Status</th>
                                    <th scope="col">Provider</th>
                                    <th scope="col">Referência</th>
                                    <th scope="col">Período</th>
                                </tr>
                            </thead>

                            <tbody>
                                @foreach ($subscriptionHistory as $item)
                                    <tr>
                                        <td>{{ $item->plan?->name ?? '—' }}</td>
                                        <td>
                                            <x-platform.badge
                                                :variant="match ($item->status->value) {
                                                    'active' => 'success',
                                                    'suspended' => 'warning',
                                                    'cancelled',
                                                    'expired' => 'neutral',
                                                    default => 'neutral',
                                                }"
                                            >
                                                {{ $item->status->value }}
                                            </x-platform.badge>
                                        </td>
                                        <td>{{ strtoupper($item->payment_provider ?? '—') }}</td>
                                        <td>{{ $item->external_reference ?? '—' }}</td>
                                        <td>
                                            {{ $item->current_period_start?->format('d/m/Y') ?? '—' }}
                                            —
                                            {{ $item->current_period_end?->format('d/m/Y') ?? '—' }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>

            <section class="platform-card">
                <h2>Cobrança</h2>

                @if ($invoices->isEmpty())
                    <p class="platform-muted">
                        {{ __('platform.empty_states.tenant_no_invoices') }}
                    </p>
                @else
                    <div class="table-wrap">
                        <table class="platform-table">
                            <thead>
                                <tr>
                                    <th scope="col">Provider</th>
                                    <th scope="col">Fatura</th>
                                    <th scope="col">Status</th>
                                    <th scope="col">Valor</th>
                                    <th scope="col">Pago em</th>
                                </tr>
                            </thead>

                            <tbody>
                                @foreach ($invoices as $invoice)
                                    <tr>
                                        <td>{{ strtoupper($invoice->provider) }}</td>
                                        <td>{{ $invoice->external_invoice_id }}</td>
                                        <td>
                                            <x-platform.badge
                                                :variant="match ($invoice->status) {
                                                    'paid' => 'success',
                                                    'pending',
                                                    'processing' => 'warning',
                                                    'failed' => 'danger',
                                                    'cancelled' => 'neutral',
                                                    default => 'neutral',
                                                }"
                                            >
                                                {{ strtoupper($invoice->status) }}
                                            </x-platform.badge>
                                        </td>
                                        <td>
                                            {{ $invoice->currency }}
                                            {{ number_format(
                                                $invoice->amount_due / 100,
                                                2,
                                                ',',
                                                '.'
                                            ) }}
                                        </td>
                                        <td>
                                            {{ $invoice->paid_at?->format('d/m/Y H:i') ?? '—' }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>

            <section class="platform-card" style="margin-top:18px">
    <div class="platform-muted">
        Atalhos operacionais
    </div>

    <div
        style="
            display:flex;
            flex-wrap:wrap;
            gap:10px;
            margin-top:12px;
        "
    >
        <a
            class="button button-secondary"
            href="{{ route(
                'platform.email-failures',
                ['tenant_id' => $tenant->id]
            ) }}"
        >
            E-mails falhos
        </a>

        <a
            class="button button-secondary"
            href="{{ route(
                'platform.whatsapp-failures',
                ['tenant_id' => $tenant->id]
            ) }}"
        >
            WhatsApps falhos
        </a>

        <a
            class="button button-secondary"
            href="{{ route(
                'platform.webhooks',
                ['tenant_id' => $tenant->id]
            ) }}"
        >
            Webhooks
        </a>
    </div>
</section>

            <section class="platform-card">
                <h2>Falhas de e-mail</h2>

                @if ($emailFailures->isEmpty())
                    <p class="platform-muted">
                        {{ __('platform.empty_states.tenant_no_email_failures') }}
                    </p>
                @else
                    <div class="table-wrap">
                        <table class="platform-table">
                            <thead>
                                <tr>
                                    <th scope="col">ID</th>
                                    <th scope="col">Destinatário</th>
                                    <th scope="col">Assunto</th>
                                    <th scope="col">Status</th>
                                    <th scope="col">Erro</th>
                                </tr>
                            </thead>

                            <tbody>
                                @foreach ($emailFailures as $message)
                                    <tr>
                                        <td>#{{ $message->id }}</td>
                                        <td>{{ $message->to_email }}</td>
                                        <td>{{ $message->subject }}</td>
                                        <td>
                                            @php
                                                $messageStatus = $message->status instanceof \BackedEnum
                                                    ? $message->status->value
                                                    : (string) $message->status;
                                            @endphp

                                            <x-platform.badge
                                                :variant="match ($messageStatus) {
                                                    'sent',
                                                    'delivered',
                                                    'read',
                                                    'received' => 'success',
                                                    'pending',
                                                    'processing' => 'warning',
                                                    'failed' => 'danger',
                                                    default => 'neutral',
                                                }"
                                            >
                                                {{ strtoupper($messageStatus) }}
                                            </x-platform.badge>
                                        </td>
                                        <td>{{ $message->failure_reason ?? '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>

            <section class="platform-card">
                <h2>Falhas de WhatsApp</h2>

                @if ($whatsAppFailures->isEmpty())
                    <p class="platform-muted">
                        {{ __('platform.empty_states.tenant_no_whatsapp_failures') }}
                    </p>
                @else
                    <div class="table-wrap">
                        <table class="platform-table">
                            <thead>
                                <tr>
                                    <th scope="col">ID</th>
                                    <th scope="col">Destinatário</th>
                                    <th scope="col">Mensagem</th>
                                    <th scope="col">Status</th>
                                    <th scope="col">Erro</th>
                                </tr>
                            </thead>

                            <tbody>
                                @foreach ($whatsAppFailures as $message)
                                    <tr>
                                        <td>#{{ $message->id }}</td>
                                        <td>{{ $message->phone }}</td>
                                        <td>{{ $message->body }}</td>
                                        <td>
                                            @php
                                                $messageStatus = $message->status instanceof \BackedEnum
                                                    ? $message->status->value
                                                    : (string) $message->status;
                                            @endphp

                                            <x-platform.badge
                                                :variant="match ($messageStatus) {
                                                    'sent',
                                                    'delivered',
                                                    'read',
                                                    'received' => 'success',
                                                    'pending',
                                                    'processing' => 'warning',
                                                    'failed' => 'danger',
                                                    default => 'neutral',
                                                }"
                                            >
                                                {{ strtoupper($messageStatus) }}
                                            </x-platform.badge>
                                        </td>
                                        <td>{{ $message->failure_reason ?? '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>

            <section class="platform-card">
                <h2>Webhooks relacionados</h2>

                @if ($webhooks->isEmpty())
                    <p class="platform-muted">
                        {{ __('platform.empty_states.tenant_no_webhooks') }}
                    </p>
                @else
                    <div class="table-wrap">
                        <table class="platform-table">
                            <thead>
                                <tr>
                                    <th scope="col">Provider</th>
                                    <th scope="col">Evento</th>
                                    <th scope="col">Status</th>
                                    <th scope="col">Tentativas</th>
                                </tr>
                            </thead>

                            <tbody>
                                @foreach ($webhooks as $webhook)
                                    <tr>
                                        <td>{{ strtoupper($webhook->provider) }}</td>
                                        <td>{{ $webhook->event_type }}</td>
                                        <td>
                                            <x-platform.badge
                                                :variant="match ($webhook->status) {
                                                    'processed' => 'success',
                                                    'processing' => 'warning',
                                                    'failed' => 'danger',
                                                    default => 'neutral',
                                                }"
                                            >
                                                {{ strtoupper($webhook->status) }}
                                            </x-platform.badge>
                                        </td>
                                        <td>{{ $webhook->attempts }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>
        </div>
    </main>
</div>
@endsection
