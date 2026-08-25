@extends('platform.layout')

@section('title', 'Falhas de e-mail')

@section('body')

<style>
.platform-email-failures-page .platform-toolbar {
    align-items: flex-end;
    gap: 18px;
}

.platform-email-failures-page .platform-toolbar h1 {
    margin: 4px 0 0;
    font-size: 32px;
    letter-spacing: -.03em;
}

.platform-email-failures-page .platform-card {
    border-radius: 16px;
}

.platform-email-failures-page .email-failures-summary {
    margin-bottom: 18px;
}

.platform-email-failures-page .email-failures-count {
    margin-top: 8px;
    font-size: 30px;
    line-height: 1;
    font-weight: 800;
    letter-spacing: -.035em;
}

.platform-email-failures-page .flash-message {
    margin-bottom: 18px;
}

.platform-email-failures-page .flash-message--success {
    border-color: #bbf7d0;
    background: #f0fdf4;
    color: #166534;
}

.platform-email-failures-page .flash-message--error {
    border-color: #fecaca;
    background: #fef2f2;
    color: #991b1b;
}

.platform-email-failures-page .table-wrap {
    overflow-x: auto;
}

.platform-email-failures-page table {
    width: 100%;
    border-collapse: collapse;
}

.platform-email-failures-page th,
.platform-email-failures-page td {
    padding: 14px 12px;
    text-align: left;
    vertical-align: top;
    border-bottom: 1px solid #e2e8f0;
}

.platform-email-failures-page th {
    color: #64748b;
    font-size: 12px;
    font-weight: 700;
    letter-spacing: .04em;
    text-transform: uppercase;
}

.platform-email-failures-page td {
    font-size: 14px;
}

.platform-email-failures-page .failure-reason {
    max-width: 420px;
    white-space: normal;
    overflow-wrap: anywhere;
}

.platform-email-failures-page .recipient {
    white-space: nowrap;
}

.platform-email-failures-page .subject {
    min-width: 180px;
}

.platform-email-failures-page .failure-date {
    white-space: nowrap;
}

.platform-email-failures-page .status-badge {
    display: inline-flex;
    align-items: center;
    padding: 5px 9px;
    border-radius: 999px;
    background: #fee2e2;
    color: #991b1b;
    font-size: 12px;
    font-weight: 700;
}

.platform-email-failures-page .retry-form {
    margin: 0;
}

.platform-email-failures-page .retry-button {
    white-space: nowrap;
}

.platform-email-failures-page .empty-state {
    padding: 28px 10px;
    text-align: center;
}

.platform-email-failures-page .pagination-wrap {
    margin-top: 20px;
}

@media (max-width: 800px) {
    .platform-email-failures-page .platform-toolbar {
        align-items: flex-start;
        flex-direction: column;
    }
}
</style>

<div class="platform-email-failures-page">
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
        @if (session('success'))
            <section
                class="platform-card flash-message flash-message--success"
            >
                <strong>
                    {{ session('success') }}
                </strong>
            </section>
        @endif

        @if (session('error'))
            <section
                class="platform-card flash-message flash-message--error"
            >
                <strong>
                    {{ session('error') }}
                </strong>
            </section>
        @endif

        <div class="platform-toolbar">
            <div>
                <div class="platform-muted">
                    Operações
                </div>

                <h1>
                    Falhas de e-mail
                </h1>

                <p class="platform-muted">
                    Mensagens de e-mail com falha de envio em todos os tenants.
                </p>
            </div>

            <div>
                <a
                    class="button"
                    href="{{ route('platform.dashboard') }}"
                >
                    Voltar ao painel
                </a>
            </div>
        </div>

        <section class="platform-card email-failures-summary">
            <div class="platform-muted">
                Falhas encontradas
            </div>

            <div class="email-failures-count">
                {{ number_format($messages->total(), 0, ',', '.') }}
            </div>
        </section>

        <section class="platform-card">
            @if ($messages->isEmpty())
                <div class="empty-state">
                    <strong>
                        Nenhuma falha de e-mail encontrada.
                    </strong>

                    <p class="platform-muted">
                        Não existem mensagens de e-mail com status de falha.
                    </p>
                </div>
            @else
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Tenant</th>
                                <th>Destinatário</th>
                                <th>Assunto</th>
                                <th>Status</th>
                                <th>Motivo</th>
                                <th>Falhou em</th>
                                <th>Ação</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach ($messages as $message)
                                <tr>
                                    <td>
                                        @if ($message->tenant !== null)
                                            <strong>
                                                {{ $message->tenant->name }}
                                            </strong>

                                            <div class="platform-muted">
                                                {{ $message->tenant->slug }}
                                            </div>
                                        @else
                                            <span class="platform-muted">
                                                Tenant indisponível
                                            </span>
                                        @endif
                                    </td>

                                    <td class="recipient">
                                        {{ $message->to_email }}
                                    </td>

                                    <td class="subject">
                                        {{ $message->subject ?: 'Sem assunto' }}
                                    </td>

                                    <td>
                                        <span class="status-badge">
                                            Falhou
                                        </span>
                                    </td>

                                    <td class="failure-reason">
                                        @if (filled($message->failure_reason))
                                            {{ $message->failure_reason }}
                                        @else
                                            <span class="platform-muted">
                                                Motivo não informado
                                            </span>
                                        @endif
                                    </td>

                                    <td class="failure-date">
                                        @if ($message->failed_at !== null)
                                            {{ $message->failed_at->format('d/m/Y H:i') }}
                                        @else
                                            <span class="platform-muted">
                                                Data não informada
                                            </span>
                                        @endif
                                    </td>

                                    <td>
                                        <form
                                            class="retry-form"
                                            method="POST"
                                            action="{{ route(
                                                'platform.email-failures.retry',
                                                $message->id
                                            ) }}"
                                        >
                                            @csrf

                                            <button
                                                class="button retry-button"
                                                type="submit"
                                                onclick="return confirm(
                                                    'Deseja reprocessar este e-mail?'
                                                )"
                                            >
                                                Reprocessar
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if ($messages->hasPages())
                    <div class="pagination-wrap">
                        {{ $messages->links() }}
                    </div>
                @endif
            @endif
        </section>
    </main>
</div>

@endsection