@extends('platform.layout')

@section('title', __('platform.health.title'))

@section('body')

<style>
.platform-health-page {
    --health-border: #e5e7eb;
    --health-muted: #64748b;
}

.platform-health-page .platform-toolbar {
    align-items: flex-end;
    gap: 18px;
    margin-bottom: 8px;
}

.platform-health-page .platform-toolbar h1 {
    margin: 4px 0 0;
    font-size: 32px;
    line-height: 1.1;
    letter-spacing: -.03em;
}

.platform-health-page .checked-at {
    display: flex;
    justify-content: flex-end;
    align-items: center;
    gap: 6px;
    margin: 0 0 18px;
    font-size: 12px;
}

.platform-health-page .detail-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 14px;
    align-items: stretch;
}

.platform-health-page .platform-card {
    position: relative;
    min-width: 0;
    min-height: 142px;
    padding: 20px;
    overflow: hidden;
    border-radius: 16px;
}

.platform-health-page .platform-card h2 {
    margin: 0;
    padding-right: 90px;
    font-size: 16px;
    line-height: 1.3;
    letter-spacing: -.01em;
}

.platform-health-page .platform-card p {
    margin: 14px 0 0;
    color: #475569;
    font-size: 13px;
    line-height: 1.55;
}

.platform-health-page .health-status {
    position: absolute;
    top: 17px;
    right: 17px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 46px;
    padding: 5px 9px;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 800;
    line-height: 1.2;
    letter-spacing: .02em;
}

.platform-health-page .health-status::before {
    width: 7px;
    height: 7px;
    margin-right: 6px;
    background: currentColor;
    border-radius: 50%;
    content: "";
}

.platform-health-page .health-status--ok {
    background: #f0fdf4;
    color: #166534;
}

.platform-health-page .health-status--warning {
    background: #fffbeb;
    color: #92400e;
}

.platform-health-page .health-status--critical {
    background: #fef2f2;
    color: #991b1b;
}

.platform-health-page .platform-card:has(.health-status--warning) {
    border-color: #fde68a;
    background: linear-gradient(
        180deg,
        #fffdf7 0%,
        #ffffff 72%
    );
}

.platform-health-page .platform-card:has(.health-status--critical) {
    border-color: #fecaca;
    background: linear-gradient(
        180deg,
        #fff8f8 0%,
        #ffffff 72%
    );
}

.platform-health-page .platform-card:has(.health-status--critical)::before {
    position: absolute;
    top: 0;
    right: 0;
    left: 0;
    height: 3px;
    background: #dc2626;
    content: "";
}

@media (max-width: 1050px) {
    .platform-health-page .detail-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 800px) {
    .platform-health-page .platform-toolbar {
        align-items: flex-start;
        flex-direction: column;
    }

    .platform-health-page .platform-toolbar h1 {
        font-size: 27px;
    }

    .platform-health-page .checked-at {
        justify-content: flex-start;
        text-align: left;
    }
}

@media (max-width: 620px) {
    .platform-health-page .detail-grid {
        grid-template-columns: 1fr;
    }

    .platform-health-page .platform-card {
        min-height: 120px;
    }
}
</style>

<div class="platform-health-page">
    @include('platform.partials.navigation')

    <main class="platform-main">
        @php
            $breadcrumbs = [
                [
                    'label' => __('platform.nav.dashboard'),
                    'url' => route('platform.dashboard'),
                ],
                [
                    'label' => __('platform.nav.health'),
                    'url' => null,
                ],
            ];
        @endphp

        @include('platform.partials.breadcrumbs')
        <div class="platform-toolbar">
            <div>
                <div class="platform-muted">
                    {{ __('platform.health.eyebrow') }}
                </div>

                <h1>
                    {{ __('platform.health.title') }}
                </h1>

                <p class="platform-muted">
                    {{ __('platform.health.description') }}
                </p>
            </div>

            <a
                class="button"
                href="{{ route('platform.dashboard') }}"
            >
                {{ __('platform.back_dashboard') }}
            </a>
        </div>

        <p class="platform-muted checked-at">
            Verificado em
            {{ $checks['checked_at']->format('d/m/Y H:i:s') }}
        </p>

        <div class="detail-grid">
            <section class="platform-card">
                <h2>
                    {{ __('platform.health.database') }}
                </h2>

                <span
                    class="health-status {{ $checks['database']
                        ? 'health-status--ok'
                        : 'health-status--critical' }}"
                >
                    {{ $checks['database']
                        ? 'OK'
                        : 'Crítico' }}
                </span>

                <p>
                    {{ $checks['database']
                        ? __('platform.health.ok')
                        : __('platform.health.fail') }}
                </p>
            </section>

            <section class="platform-card">
                <h2>
                    {{ __('platform.health.storage') }}
                </h2>

                <span
                    class="health-status {{ $checks['storage']
                        ? 'health-status--ok'
                        : 'health-status--critical' }}"
                >
                    {{ $checks['storage']
                        ? 'OK'
                        : 'Crítico' }}
                </span>

                <p>
                    {{ $checks['storage']
                        ? __('platform.health.writable')
                        : __('platform.health.unavailable') }}
                </p>
            </section>

            <section class="platform-card">
                <h2>
                    {{ __('platform.health.queue') }}
                </h2>

                @php
                    $queueUnavailable =
                        $checks['queue_pending'] === null
                        || $checks['queue_failed'] === null;

                    $queueCritical =
                        ! $queueUnavailable
                        && (int) $checks['queue_failed'] > 0;
                @endphp

                <span
                    class="health-status {{
                        $queueUnavailable
                            ? 'health-status--critical'
                            : (
                                $queueCritical
                                    ? 'health-status--warning'
                                    : 'health-status--ok'
                            )
                    }}"
                >
                    {{
                        $queueUnavailable
                            ? 'Crítico'
                            : (
                                $queueCritical
                                    ? 'Atenção'
                                    : 'OK'
                            )
                    }}
                </span>

                <p>
                    {{ $checks['queue_pending']
                        ?? __('platform.health.unavailable') }}
                    {{ __('platform.health.pending') }}
                </p>

                <p>
                    {{ $checks['queue_failed']
                        ?? __('platform.health.unavailable') }}
                    {{ __('platform.health.failures') }}
                </p>
            </section>

<section class="platform-card">
    <h2>
        Scheduler
    </h2>

    <span
        class="health-status {{ $checks['scheduler_healthy']
            ? 'health-status--ok'
            : 'health-status--critical' }}"
    >
        {{ $checks['scheduler_healthy']
            ? 'OK'
            : 'Crítico' }}
    </span>

    <p>
        @if ($checks['scheduler_last_run_at'])
            Última execução:
            {{ $checks['scheduler_last_run_at']->format('d/m/Y H:i:s') }}
        @else
            {{ __('platform.empty_states.health_no_executions') }}
        @endif
    </p>
</section>

<section class="platform-card">
    <h2>
        Worker
    </h2>

    <span
        class="health-status {{ $checks['worker_healthy']
            ? 'health-status--ok'
            : 'health-status--critical' }}"
    >
        {{ $checks['worker_healthy']
            ? 'OK'
            : 'Crítico' }}
    </span>

    <p>
        @if ($checks['worker_last_seen_at'])
            Última atividade:
            {{ $checks['worker_last_seen_at']->format('d/m/Y H:i:s') }}
        @else
            {{ __('platform.empty_states.health_no_activity') }}
        @endif
    </p>
</section>

            <section class="platform-card">
                <h2>
                    {{ __('platform.health.mail') }}
                </h2>

                <span
                    class="health-status {{ $checks['mail_configured']
                        ? 'health-status--ok'
                        : 'health-status--warning' }}"
                >
                    {{ $checks['mail_configured']
                        ? 'OK'
                        : 'Atenção' }}
                </span>

                <p>
                    {{ $checks['mail_configured']
                        ? __('platform.health.provider_configured')
                        : __('platform.health.log_only') }}
                </p>
            </section>

            <section class="platform-card">
                <h2>
                    {{ __('platform.health.commercial_contact') }}
                </h2>

                <span
                    class="health-status {{ $checks['contact_recipient']
                        ? 'health-status--ok'
                        : 'health-status--warning' }}"
                >
                    {{ $checks['contact_recipient']
                        ? 'OK'
                        : 'Atenção' }}
                </span>

                <p>
                    {{ $checks['contact_recipient']
                        ? __('platform.health.recipient_configured')
                        : __('platform.health.no_recipient') }}
                </p>
            </section>

            <section class="platform-card">
                <h2>
                    Stripe
                </h2>

                <span
                    class="health-status {{ $checks['stripe_configured']
                        ? 'health-status--ok'
                        : 'health-status--warning' }}"
                >
                    {{ $checks['stripe_configured']
                        ? 'OK'
                        : 'Atenção' }}
                </span>

                <p>
                    @if ($checks['stripe_configured'])
                        Credencial principal configurada.
                    @else
                        Credencial principal não configurada.
                    @endif
                </p>
            </section>

            <section class="platform-card">
                <h2>
                    WhatsApp
                </h2>

                @php
                    $whatsAppUnavailable =
                        $checks['whatsapp_configured_tenants'] === null;

                    $whatsAppConfigured =
                        ! $whatsAppUnavailable
                        && (int) $checks['whatsapp_configured_tenants'] > 0;
                @endphp

                <span
                    class="health-status {{
                        $whatsAppUnavailable
                            ? 'health-status--critical'
                            : (
                                $whatsAppConfigured
                                    ? 'health-status--ok'
                                    : 'health-status--warning'
                            )
                    }}"
                >
                    {{
                        $whatsAppUnavailable
                            ? 'Crítico'
                            : (
                                $whatsAppConfigured
                                    ? 'OK'
                                    : 'Atenção'
                            )
                    }}
                </span>

                <p>
                    @if ($whatsAppUnavailable)
                        Não foi possível verificar as configurações.
                    @elseif ($whatsAppConfigured)
                        {{ $checks['whatsapp_configured_tenants'] }}
                        tenant(s) com provedor ativo.
                    @else
                        {{ __('platform.empty_states.health_no_provider') }}
                    @endif
                </p>
            </section>
        </div>
    </main>
</div>

@endsection
