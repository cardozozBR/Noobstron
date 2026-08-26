@extends('platform.layout')

@section('title', __('platform.dashboard.title'))

@section('body')

<style>
.platform-dashboard-page .platform-toolbar {
    align-items: flex-end;
    gap: 18px;
}

.platform-dashboard-page .platform-toolbar h1 {
    margin: 4px 0 0;
    font-size: 32px;
    letter-spacing: -.03em;
}

.platform-dashboard-page .quick-actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.platform-dashboard-page .metric-grid {
    gap: 14px;
}

.platform-dashboard-page .metric-card {
    min-height: 126px;
    padding: 20px;
    border-radius: 16px;
    display: flex;
    flex-direction: column;
    justify-content: center;
}

.platform-dashboard-page .metric-label {
    color: #64748b;
    font-size: 12px;
    font-weight: 700;
    letter-spacing: .04em;
    text-transform: uppercase;
}

.platform-dashboard-page .metric-value {
    margin-top: 8px;
    font-size: 30px;
    line-height: 1;
    font-weight: 800;
    letter-spacing: -.035em;
}

.platform-dashboard-page .metric-meta {
    margin-top: 10px;
    font-size: 12px;
}

.platform-dashboard-page .metric-card--alert {
    border-color: #fecaca;
    background: #fef2f2;
}

.platform-dashboard-page .metric-card--alert .metric-label,
.platform-dashboard-page .metric-card--alert .metric-value {
    color: #991b1b;
}

.platform-dashboard-page .detail-grid {
    gap: 14px;
}

.platform-dashboard-page .platform-card {
    border-radius: 16px;
}

.platform-dashboard-page .platform-card h2 {
    margin-top: 0;
}

.platform-dashboard-page a.metric-card {
    text-decoration: none;
    color: inherit;
}

@media (max-width: 800px) {
    .platform-dashboard-page .platform-toolbar {
        align-items: flex-start;
        flex-direction: column;
    }
}
</style>

<div class="platform-dashboard-page">
    @include('platform.partials.navigation')

    <main class="platform-main">
        @php
            $breadcrumbs = [
                [
                    'label' => __('platform.nav.dashboard'),
                    'url' => null,
                ],
            ];
        @endphp

        @include('platform.partials.breadcrumbs')
        <div class="platform-toolbar">
            <div>
                <div class="platform-muted">
                    {{ __('platform.dashboard.eyebrow') }}
                </div>

                <h1>
                    {{ __('platform.dashboard.title') }}
                </h1>

                <p class="platform-muted">
                    {{ __('platform.dashboard.description') }}
                </p>
            </div>

            <div class="quick-actions">
                <a
                    class="button"
                    href="{{ route('platform.tenants.index') }}"
                >
                    {{ __('platform.dashboard.tenants') }}
                </a>

                <a
                    class="button"
                    href="{{ route('platform.contacts.index') }}"
                >
                    {{ __('platform.dashboard.contacts') }}
                </a>

                <a
                    class="button"
                    href="{{ route('platform.health') }}"
                >
                    {{ __('platform.dashboard.health') }}
                </a>

                <a
                    class="button"
                    href="{{ route('platform.webhooks') }}"
                >
                    Webhooks
                </a>
            </div>
        </div>

        <section
            class="platform-card"
            style="margin-top:24px;"
        >
            <h2>
                {{ __('platform.dashboard.commercial_indicators') }}
            </h2>

            <p class="platform-muted">
                {{ __('platform.dashboard.commercial_indicators_description') }}
            </p>

            <div class="metric-grid">
                <div class="metric-card">
                    <div class="metric-label">
                        {{ __('platform.dashboard.new_subscriptions') }}
                    </div>

                    <div class="metric-value">
                        {{ $newSubscriptions }}
                    </div>
                </div>

                <div class="metric-card">
                    <div class="metric-label">
                        {{ __('platform.dashboard.cancellations') }}
                    </div>

                    <div class="metric-value">
                        {{ $cancellations }}
                    </div>
                </div>

                <div class="metric-card">
                    <div class="metric-label">
                        {{ __('platform.dashboard.churn') }}
                    </div>

                    <div class="metric-value">
                        {{ number_format($churnRate, 2, ',', '.') }}%
                    </div>
                </div>

                <div class="metric-card">
                    <div class="metric-label">
                        {{ __('platform.dashboard.trial_conversions') }}
                    </div>

                    <div class="metric-value">
                        {{ $trialConversions }}
                    </div>
                </div>

                <div class="metric-card">
                    <div class="metric-label">
                        {{ __('platform.dashboard.expired_trials_without_conversion') }}
                    </div>

                    <div class="metric-value">
                        {{ $expiredTrialsWithoutConversion }}
                    </div>
                </div>

                <div class="metric-card">
                    <div class="metric-label">
                        {{ __('platform.dashboard.active_subscriptions') }}
                    </div>

                    <div class="metric-value">
                        {{ (int) ($subscriptionCounts['active'] ?? 0) }}
                    </div>
                </div>

                <div class="metric-card">
                    <div class="metric-label">
                        {{ __('platform.dashboard.active_trials') }}
                    </div>

                    <div class="metric-value">
                        {{ $trialActive }}
                    </div>
                </div>

                <div class="metric-card">
                    <div class="metric-label">
                        {{ __('platform.dashboard.trials_expiring') }}
                    </div>

                    <div class="metric-value">
                        {{ $trialExpiring }}
                    </div>
                </div>
            </div>
        </section>

        <div
            class="metric-grid"
            style="margin-top:24px;"
        >
            <div class="metric-card">
                <div class="metric-label">
                    {{ __('platform.dashboard.tenants') }}
                </div>

                <div class="metric-value">
                    {{ $tenantCount }}
                </div>
            </div>

            <div class="metric-card">
                <div class="metric-label">
                    {{ __('platform.dashboard.suspended') }}
                </div>

                <div class="metric-value">
                    {{ (int) ($subscriptionCounts['suspended'] ?? 0) }}
                </div>
            </div>

            <div class="metric-card">
                <div class="metric-label">
                    {{ __('platform.dashboard.global_users') }}
                </div>

                <div class="metric-value">
                    {{ $usage['users'] }}
                </div>
            </div>
            <a
                class="metric-card{{ (int) ($webhookCounts['failed'] ?? 0) > 0 ? ' metric-card--alert' : '' }}"
                href="{{ route(
                    'platform.webhooks',
                    ['status' => 'failed']
                ) }}"
            >
                <div class="metric-label">
                    Webhooks falhos
                </div>

                <div class="metric-value">
                    {{ (int) ($webhookCounts['failed'] ?? 0) }}
                </div>

                <div class="platform-muted metric-meta">
                    @if ($latestFailedWebhook !== null)
                        Última falha:
                        {{ $latestFailedWebhook->updated_at?->format('d/m/Y H:i') }}
                    @else
                        Nenhuma falha pendente
                    @endif
                </div>
            </a>

            <a
                class="metric-card"
                href="{{ route(
                    'platform.webhooks',
                    ['status' => 'processing']
                ) }}"
            >
                <div class="metric-label">
                    Webhooks em processamento
                </div>

                <div class="metric-value">
                    {{ (int) ($webhookCounts['processing'] ?? 0) }}
                </div>
            </a>

            <a
                class="metric-card"
                href="{{ route('platform.jobs') }}"
            >
                <div class="metric-label">
                    Jobs pendentes
                </div>

                <div class="metric-value">
                    {{ (int) ($queueCounts['pending'] ?? 0) }}
                </div>

                <div class="platform-muted metric-meta">
                    Fila aguardando processamento
                </div>
            </a>

            <a
                class="metric-card{{ (int) ($queueCounts['failed'] ?? 0) > 0 ? ' metric-card--alert' : '' }}"
                href="{{ route('platform.jobs') }}"
            >
                <div class="metric-label">
                    Jobs falhos
                </div>

                <div class="metric-value">
                    {{ (int) ($queueCounts['failed'] ?? 0) }}
                </div>

                <div class="platform-muted metric-meta">
                    @if ((int) ($queueCounts['failed'] ?? 0) > 0)
                        Falhas na fila precisam de atenção
                    @else
                        Nenhuma falha na fila
                    @endif
                </div>
            </a>

            <a
                class="metric-card{{ (int) ($messageFailureCounts['email'] ?? 0) > 0 ? ' metric-card--alert' : '' }}"
                href="{{ route('platform.email-failures') }}"
            >
                <div class="metric-label">
                    E-mails falhos
                </div>

                <div class="metric-value">
                    {{ (int) ($messageFailureCounts['email'] ?? 0) }}
                </div>

                <div class="platform-muted metric-meta">
                    @if ((int) ($messageFailureCounts['email'] ?? 0) > 0)
                        Falhas de envio de e-mail precisam de atenção
                    @else
                        Nenhuma falha de e-mail
                    @endif
                </div>
            </a>

            <a
                class="metric-card{{ (int) ($messageFailureCounts['whatsapp'] ?? 0) > 0 ? ' metric-card--alert' : '' }}"
                href="{{ route('platform.whatsapp-failures') }}"
            >
                <div class="metric-label">
                    WhatsApps falhos
                </div>

                <div class="metric-value">
                    {{ (int) ($messageFailureCounts['whatsapp'] ?? 0) }}
                </div>

                <div class="platform-muted metric-meta">
                    @if ((int) ($messageFailureCounts['whatsapp'] ?? 0) > 0)
                        Falhas de envio do WhatsApp precisam de atenção
                    @else
                        Nenhuma falha de WhatsApp
                    @endif
                </div>
            </a>
        </div>

        <div
            class="detail-grid"
            style="margin-top:24px;"
        >
            <section class="platform-card">
                <h2>
                    {{ __('platform.dashboard.mrr') }}
                </h2>

                @forelse ($mrr as $currency => $minor)
                    <p>
                        <strong>
                            {{ $currency }} {{ number_format(((int) $minor) / 100, 2, ',', '.') }}
                        </strong>
                    </p>

                    <p class="platform-muted">
                        {{ __('platform.dashboard.arr') }}:
                        {{ $currency }} {{ number_format((((int) $minor) * 12) / 100, 2, ',', '.') }}
                    </p>
                @empty
                    <p class="platform-muted">
                        {{ __('platform.dashboard.no_mrr') }}
                    </p>
                @endforelse
            </section>

            <section class="platform-card">
                <h2>
                    {{ __('platform.dashboard.global_usage') }}
                </h2>

                <p>
                    <strong>
                        {{ number_format($usage['messages'], 0, ',', '.') }}
                    </strong>
                    {{ __('platform.dashboard.messages_sent') }}
                </p>

                <p>
                    <strong>
                        {{ number_format($usage['ai_tokens'], 0, ',', '.') }}
                    </strong>
                    {{ __('platform.dashboard.ai_tokens') }}
                </p>
            </section>

            <section class="platform-card">
                <h2>
                    {{ __('platform.dashboard.current_subscriptions') }}
                </h2>

                <p>
                    {{ __('platform.dashboard.active') }}:
                    <strong>
                        {{ (int) ($subscriptionCounts['active'] ?? 0) }}
                    </strong>
                </p>

                <p>
                    {{ __('platform.dashboard.suspended') }}:
                    <strong>
                        {{ (int) ($subscriptionCounts['suspended'] ?? 0) }}
                    </strong>
                </p>

                <p>
                    {{ __('platform.dashboard.cancelled') }}:
                    <strong>
                        {{ (int) ($subscriptionCounts['cancelled'] ?? 0) }}
                    </strong>
                </p>

                <p>
                    {{ __('platform.dashboard.expired') }}:
                    <strong>
                        {{ (int) ($subscriptionCounts['expired'] ?? 0) }}
                    </strong>
                </p>
            </section>
        </div>
    </main>
</div>

@endsection
