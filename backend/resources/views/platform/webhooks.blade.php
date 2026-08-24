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

.platform-webhooks-page .webhook-event-id,
.platform-webhooks-page .webhook-reference {
    font-family: monospace;
    font-size: 13px;
}

.platform-webhooks-page .status-badge--stripe {
    background: #eef2ff;
}

@media (max-width: 800px) {
    .platform-webhooks-page .platform-toolbar {
        align-items: flex-start;
        flex-direction: column;
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

        <section class="platform-card platform-card--table">
            <div class="table-wrap">
                <table class="platform-table">
                    <thead>
                        <tr>
                            <th>Provedor</th>
                            <th>Evento</th>
                            <th>Tipo</th>
                            <th>Referência</th>
                            <th>Processado em</th>
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

                                <td>
                                    {{ $receipt->event_type }}
                                </td>

                                <td class="webhook-reference">
                                    {{ $receipt->external_reference }}
                                </td>

                                <td>
                                    {{ $receipt->processed_at?->format('d/m/Y H:i:s') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5">
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