@extends('platform.layout')

@section('title', $tenant->name)

@section('body')

<style>
.platform-tenant-show .platform-toolbar{align-items:flex-end;gap:18px}
.platform-tenant-show .platform-toolbar h1{margin:4px 0 0;font-size:32px;letter-spacing:-.03em}
.platform-tenant-show .detail-grid{gap:14px}
.platform-tenant-show .platform-card{border-radius:16px}
.platform-tenant-show .platform-card h2{margin-top:0}
.platform-tenant-show .detail-list{display:grid;gap:0}
.platform-tenant-show .detail-list>div{padding:12px 0;border-bottom:1px solid #e5e7eb}
.platform-tenant-show .detail-list>div:last-child{border-bottom:0}
.platform-tenant-show .detail-list dt{color:#64748b;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.04em}
.platform-tenant-show .detail-list dd{margin:5px 0 0;font-weight:600}
@media(max-width:800px){.platform-tenant-show .platform-toolbar{align-items:flex-start;flex-direction:column}}
</style>

<div class="platform-tenant-show">
    <header class="platform-header">
        <div class="platform-header__inner">
            <a
                class="platform-brand"
                href="{{ route('platform.dashboard') }}"
            >
                {{ __('platform.brand') }}
            </a>

            <form
                method="POST"
                action="{{ route('platform.logout') }}"
            >
                @csrf

                <button
                    class="logout-button"
                    type="submit"
                >
                    {{ __('platform.logout') }}
                </button>
            </form>
        </div>
    </header>

    <main class="platform-main">
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
                        <dd>{{ $tenant->status }}</dd>
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
                                {{ $subscription->status->value }}
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
                                    <th>{{ __('platform.tenants.feature') }}</th>
                                    <th>{{ __('platform.tenants.enabled') }}</th>
                                    <th>{{ __('platform.tenants.limit') }}</th>
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
                                    <th>{{ __('platform.tenants.metric') }}</th>
                                    <th>{{ __('platform.tenants.limit') }}</th>
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
                        Nenhuma assinatura encontrada.
                    </p>
                @else
                    <div class="table-wrap">
                        <table class="platform-table">
                            <thead>
                                <tr>
                                    <th>Plano</th>
                                    <th>Status</th>
                                    <th>Provider</th>
                                    <th>Referência</th>
                                    <th>Período</th>
                                </tr>
                            </thead>

                            <tbody>
                                @foreach ($subscriptionHistory as $item)
                                    <tr>
                                        <td>{{ $item->plan?->name ?? '—' }}</td>
                                        <td>{{ $item->status->value }}</td>
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
                        Nenhuma fatura encontrada.
                    </p>
                @else
                    <div class="table-wrap">
                        <table class="platform-table">
                            <thead>
                                <tr>
                                    <th>Provider</th>
                                    <th>Fatura</th>
                                    <th>Status</th>
                                    <th>Valor</th>
                                    <th>Pago em</th>
                                </tr>
                            </thead>

                            <tbody>
                                @foreach ($invoices as $invoice)
                                    <tr>
                                        <td>{{ strtoupper($invoice->provider) }}</td>
                                        <td>{{ $invoice->external_invoice_id }}</td>
                                        <td>{{ strtoupper($invoice->status) }}</td>
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

            <section class="platform-card">
                <h2>Falhas de e-mail</h2>

                @if ($emailFailures->isEmpty())
                    <p class="platform-muted">
                        Nenhuma falha de e-mail.
                    </p>
                @else
                    <div class="table-wrap">
                        <table class="platform-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Destinatário</th>
                                    <th>Assunto</th>
                                    <th>Status</th>
                                    <th>Erro</th>
                                </tr>
                            </thead>

                            <tbody>
                                @foreach ($emailFailures as $message)
                                    <tr>
                                        <td>#{{ $message->id }}</td>
                                        <td>{{ $message->to_email }}</td>
                                        <td>{{ $message->subject }}</td>
                                        <td>{{ strtoupper($message->status instanceof \BackedEnum ? $message->status->value : (string) $message->status) }}</td>
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
                        Nenhuma falha de WhatsApp.
                    </p>
                @else
                    <div class="table-wrap">
                        <table class="platform-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Destinatário</th>
                                    <th>Mensagem</th>
                                    <th>Status</th>
                                    <th>Erro</th>
                                </tr>
                            </thead>

                            <tbody>
                                @foreach ($whatsAppFailures as $message)
                                    <tr>
                                        <td>#{{ $message->id }}</td>
                                        <td>{{ $message->phone }}</td>
                                        <td>{{ $message->body }}</td>
                                        <td>{{ strtoupper($message->status instanceof \BackedEnum ? $message->status->value : (string) $message->status) }}</td>
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
                        Nenhum webhook relacionado.
                    </p>
                @else
                    <div class="table-wrap">
                        <table class="platform-table">
                            <thead>
                                <tr>
                                    <th>Provider</th>
                                    <th>Evento</th>
                                    <th>Status</th>
                                    <th>Tentativas</th>
                                </tr>
                            </thead>

                            <tbody>
                                @foreach ($webhooks as $webhook)
                                    <tr>
                                        <td>{{ strtoupper($webhook->provider) }}</td>
                                        <td>{{ $webhook->event_type }}</td>
                                        <td>{{ strtoupper($webhook->status) }}</td>
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
