@extends('platform.layout')

@section('title', 'Webhooks')

@section('body')

<style>
.platform-webhooks-page .platform-toolbar {
    align-items: flex-end;
    gap: 18px;
}

.platform-webhooks-page .platform-toolbar h1 {
    margin: 4px 0 0;
    font-size: 32px;
    letter-spacing: -.03em;
}

.platform-webhooks-page .platform-card--table {
    padding: 22px;
}

.platform-webhooks-page .platform-table {
    table-layout: fixed;
    min-width: 100%;
}

.platform-webhooks-page .platform-table th,
.platform-webhooks-page .platform-table td {
    padding: 13px 10px;
}

.platform-webhooks-page .col-provider {
    width: 9%;
}

.platform-webhooks-page .col-event {
    width: 18%;
}

.platform-webhooks-page .col-type {
    width: 20%;
}

.platform-webhooks-page .col-reference {
    width: 18%;
}

.platform-webhooks-page .col-status {
    width: 10%;
}

.platform-webhooks-page .col-attempts {
    width: 8%;
}

.platform-webhooks-page .col-error {
    width: 9%;
}

.platform-webhooks-page .col-processed {
    width: 13%;
}

.platform-webhooks-page .webhook-event-id,
.platform-webhooks-page .webhook-reference {
    font-family: monospace;
    font-size: 12px;
    overflow-wrap: anywhere;
    word-break: break-word;
}

.platform-webhooks-page .webhook-type {
    overflow-wrap: anywhere;
    word-break: break-word;
}

.platform-webhooks-page .webhook-status,
.platform-webhooks-page .webhook-attempts,
.platform-webhooks-page .webhook-processed {
    white-space: nowrap;
}

.platform-webhooks-page .status-badge--stripe {
    background: #eef2ff;
}

.platform-webhooks-page .status-badge--processed {
    background: #dcfce7;
    color: #166534;
}

.platform-webhooks-page .status-badge--processing {
    background: #fef3c7;
    color: #92400e;
}

.platform-webhooks-page .status-badge--failed {
    background: #fee2e2;
    color: #991b1b;
}

.platform-webhooks-page .webhook-error {
    white-space: normal;
    overflow-wrap: anywhere;
    word-break: break-word;
    color: #991b1b;
    font-size: 12px;
}

.platform-webhooks-page .webhook-filters {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-bottom: 18px;
}

.platform-webhooks-page .webhook-filter {
    display: inline-flex;
    align-items: center;
    padding: 9px 14px;
    border: 1px solid #d1d5db;
    border-radius: 999px;
    background: white;
    color: #374151;
    text-decoration: none;
    font-size: 14px;
    font-weight: 600;
}

.platform-webhooks-page .webhook-filter--active {
    background: #111827;
    border-color: #111827;
    color: white;
}

@media (max-width: 1000px) {
    .platform-webhooks-page .platform-table {
        min-width: 1050px;
        table-layout: auto;
    }

    .platform-webhooks-page .webhook-event-id,
    .platform-webhooks-page .webhook-reference {
        min-width: 170px;
    }

    .platform-webhooks-page .webhook-type {
        min-width: 190px;
    }

    .platform-webhooks-page .webhook-processed {
        min-width: 145px;
    }
}

@media (max-width: 800px) {
    .platform-webhooks-page .platform-toolbar {
        align-items: flex-start;
        flex-direction: column;
    }

    .platform-webhooks-page .platform-card--table {
        padding: 16px;
    }
}
</style>

<div class="platform-webhooks-page">
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
                    Operações
                </div>

                <h1>Webhooks</h1>

                <p class="platform-muted">
                    Últimos eventos recebidos dos provedores de pagamento.
                </p>
            </div>

            <a
                class="button"
                href="{{ route('platform.dashboard') }}"
            >
                {{ __('platform.back_dashboard') }}
            </a>
        </div>

        <div class="webhook-filters">
    <a
        href="{{ route('platform.webhooks') }}"
        class="webhook-filter
        {{ $status === '' ? 'webhook-filter--active' : '' }}"
    >
        Todos
    </a>

    <a
        href="{{ route('platform.webhooks', ['status' => 'processed']) }}"
        class="webhook-filter
        {{ $status === 'processed' ? 'webhook-filter--active' : '' }}"
    >
        Processados
    </a>

    <a
        href="{{ route('platform.webhooks', ['status' => 'processing']) }}"
        class="webhook-filter
        {{ $status === 'processing' ? 'webhook-filter--active' : '' }}"
    >
        Em processamento
    </a>

    <a
        href="{{ route('platform.webhooks', ['status' => 'failed']) }}"
        class="webhook-filter
        {{ $status === 'failed' ? 'webhook-filter--active' : '' }}"
    >
        Falhos
    </a>
</div>

        <section class="platform-card platform-card--table">
            <div class="table-wrap">
                <table class="platform-table">
                    <thead>
                        <tr>
                            <th class="col-provider">Provedor</th>
                            <th class="col-event">Evento</th>
                            <th class="col-type">Tipo</th>
                            <th class="col-reference">Referência</th>
                            <th class="col-status">Status</th>
                            <th class="col-attempts">Tentativas</th>
                            <th class="col-error">Último erro</th>
                            <th class="col-processed">Processado em</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($receipts as $receipt)
                            <tr>
                                <td>
                                    <span
                                        class="status-badge
                                        {{ $receipt->provider === 'stripe'
                                            ? 'status-badge--stripe'
                                            : '' }}"
                                    >
                                        {{ strtoupper($receipt->provider) }}
                                    </span>
                                </td>

                                <td class="webhook-event-id">
                                    {{ $receipt->event_id }}
                                </td>

                                <td class="webhook-type">
                                    {{ $receipt->event_type }}
                                </td>

                                <td class="webhook-reference">
                                    {{ $receipt->external_reference }}
                                </td>

                                <td class="webhook-status">
                                    <span
                                        class="status-badge
                                        status-badge--{{ $receipt->status }}"
                                    >
                                        {{ strtoupper($receipt->status) }}
                                    </span>
                                </td>

                                <td class="webhook-attempts">
                                    {{ $receipt->attempts }}
                                </td>

                                <td class="webhook-error">
                                    {{ $receipt->last_error ?: '—' }}
                                </td>

                                <td class="webhook-processed">
                                    {{ $receipt->processed_at?->format('d/m/Y H:i:s') ?? '—' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8">
                                    Nenhum webhook processado ainda.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($receipts->hasPages())
                <div class="pagination-wrap">
                    {{ $receipts->links() }}
                </div>
            @endif
        </section>
    </main>
</div>
@endsection