@extends('platform.layout')

@section('title', __('platform.health.title'))

@section('body')

<style>
.platform-health-page .platform-toolbar {
    align-items: flex-end;
    gap: 18px;
}

.platform-health-page .platform-toolbar h1 {
    margin: 4px 0 0;
    font-size: 32px;
    letter-spacing: -.03em;
}

.platform-health-page .detail-grid {
    gap: 14px;
}

.platform-health-page .platform-card {
    min-height: 126px;
    border-radius: 16px;
}

.platform-health-page .platform-card h2 {
    margin-top: 0;
}

.platform-health-page .platform-card p {
    margin: 6px 0 0;
}

.platform-health-page .health-status {
    display: inline-flex;
    align-items: center;
    margin-top: 10px;
    padding: 5px 9px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 700;
}

.platform-health-page .health-status--ok {
    background: #dcfce7;
    color: #166534;
}

.platform-health-page .health-status--warning {
    background: #fef3c7;
    color: #92400e;
}

.platform-health-page .health-status--critical {
    background: #fee2e2;
    color: #991b1b;
}

.platform-health-page .checked-at {
    margin: 0 0 18px;
    text-align: right;
}

@media (max-width: 800px) {
    .platform-health-page .platform-toolbar {
        align-items: flex-start;
        flex-direction: column;
    }

    .platform-health-page .checked-at {
        text-align: left;
    }
}
</style>

<div class="platform-health-page">
    <header class="platform-header">
        <div class="platform-header__inner">
            <a
                class="platform-brand"
                href="{{ route('platform.dashboard') }}"
            >
                {{ __('platform.brand') }}
            </a>
        </div>
    </header>

    <main class="platform-main">
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
            Nenhuma execução recente registrada.
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
            Nenhuma atividade recente registrada.
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
                        tenant(s) com provider ativo.
                    @else
                        Nenhum tenant com provider ativo.
                    @endif
                </p>
            </section>
        </div>
    </main>
</div>

@endsection