@extends('platform.layout')

@section('title', 'Falhas de WhatsApp')

@section('body')

<style>
.platform-whatsapp-failures-page .platform-toolbar {
    align-items: flex-end;
    gap: 18px;
}

.platform-whatsapp-failures-page .platform-toolbar h1 {
    margin: 4px 0 0;
    font-size: 32px;
    letter-spacing: -.03em;
}

.platform-whatsapp-failures-page .platform-card {
    border-radius: 16px;
}

.platform-whatsapp-failures-page .whatsapp-failures-summary {
    margin-bottom: 18px;
}

.platform-whatsapp-failures-page .whatsapp-failures-count {
    margin-top: 8px;
    font-size: 30px;
    line-height: 1;
    font-weight: 800;
    letter-spacing: -.035em;
}

.platform-whatsapp-failures-page .table-wrap {
    overflow-x: auto;
}

.platform-whatsapp-failures-page table {
    width: 100%;
    border-collapse: collapse;
}

.platform-whatsapp-failures-page th,
.platform-whatsapp-failures-page td {
    padding: 14px 12px;
    text-align: left;
    vertical-align: top;
    border-bottom: 1px solid #e2e8f0;
}

.platform-whatsapp-failures-page th {
    color: #64748b;
    font-size: 12px;
    font-weight: 700;
    letter-spacing: .04em;
    text-transform: uppercase;
}

.platform-whatsapp-failures-page td {
    font-size: 14px;
}

.platform-whatsapp-failures-page .recipient {
    min-width: 150px;
}

.platform-whatsapp-failures-page .phone {
    white-space: nowrap;
}

.platform-whatsapp-failures-page .message-body {
    min-width: 220px;
    max-width: 420px;
    white-space: normal;
    overflow-wrap: anywhere;
}

.platform-whatsapp-failures-page .provider {
    white-space: nowrap;
}

.platform-whatsapp-failures-page .failure-reason {
    max-width: 420px;
    white-space: normal;
    overflow-wrap: anywhere;
}

.platform-whatsapp-failures-page .failure-date {
    white-space: nowrap;
}

.platform-whatsapp-failures-page .retry-form {
    margin: 0;
}

.platform-whatsapp-failures-page .retry-button {
    white-space: nowrap;
}

.platform-whatsapp-failures-page .provider-unavailable {
    color: #991b1b;
    font-weight: 700;
}

.platform-whatsapp-failures-page .empty-state {
    padding: 28px 10px;
    text-align: center;
}

@media (max-width: 800px) {
    .platform-whatsapp-failures-page .platform-toolbar {
        align-items: flex-start;
        flex-direction: column;
    }
}
</style>

<div class="platform-whatsapp-failures-page">
    @include('platform.partials.navigation')

    <main class="platform-main">
        @php
            $breadcrumbs = [
                [
                    'label' => __('platform.nav.dashboard'),
                    'url' => route('platform.dashboard'),
                ],
                [
                    'label' => __('platform.nav.whatsapp_failures'),
                    'url' => null,
                ],
            ];
        @endphp

        @include('platform.partials.breadcrumbs')
       @if (session('success'))
    <x-platform.flash type="success">
        {{ session('success') }}
    </x-platform.flash>
@endif

@if (session('error'))
    <x-platform.flash type="error">
        {{ session('error') }}
    </x-platform.flash>
@endif

        <div class="platform-toolbar">
            <div>
                <div class="platform-muted">
                    Operações
                </div>

                <h1>
                    Falhas de WhatsApp
                </h1>

            <p class="platform-muted">
            @if ($tenant !== null)
               Mensagens de WhatsApp com falha de envio do tenant
               <strong>{{ $tenant->name }}</strong>.
            @else
               Mensagens de WhatsApp com falha de envio em todos os tenants.
            @endif
            </p>
            </div>

        <div>
            @if ($tenant !== null)
              <a
                 class="button"
                 href="{{ route('platform.tenants.show', $tenant) }}"
              >
                 Voltar ao tenant
            </a>
        @else
            <a
                 class="button"
                 href="{{ route('platform.dashboard') }}"
             >
            Voltar ao painel
          </a>
         @endif
        </div>
        </div>

        <section class="platform-card whatsapp-failures-summary">
            <div class="platform-muted">
                Falhas encontradas
            </div>

            <div class="whatsapp-failures-count">
                {{ number_format($messages->total(), 0, ',', '.') }}
            </div>
        </section>

        <section class="platform-card">
            @if ($messages->isEmpty())
                                <x-platform.empty-state
                    :title="__('platform.empty_states.whatsapp_failures_empty')"
                    :description="__('platform.empty_states.whatsapp_failures_empty_description')"
                />
            @else
                <div class="table-wrap">
                    <table class="platform-table">
                        <thead>
                            <tr>
                                <th scope="col">Tenant</th>
                                <th scope="col">Destinatário</th>
                                <th scope="col">Telefone</th>
                                <th scope="col">Mensagem</th>
                                <th scope="col">Provider</th>
                                <th scope="col">Status</th>
                                <th scope="col">Motivo</th>
                                <th scope="col">Falhou em</th>
                                <th scope="col">Ação</th>
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
                                        @if (filled($message->recipient_name))
                                            {{ $message->recipient_name }}
                                        @else
                                            <span class="platform-muted">
                                                Não informado
                                            </span>
                                        @endif
                                    </td>

                                    <td class="phone">
                                        {{ $message->phone }}
                                    </td>

                                    <td class="message-body">
                                        {{ $message->body }}
                                    </td>

                                    <td class="provider">
                                        @if (filled($message->provider))
                                            {{ $message->provider }}
                                        @else
                                            <span class="provider-unavailable">
                                                Não disponível
                                            </span>
                                        @endif
                                    </td>

                                    <td>
                                        <x-platform.badge variant="danger">
                                            Falhou
                                        </x-platform.badge>
                                    </td>

                                    <td class="failure-reason">
                                        {{ \App\Support\AdminSensitiveDataSanitizer::sanitize(
                                          $message->failure_reason
                                         ) ?? 'Motivo não informado' }}
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
                                        @if (
                                            $message->tenant !== null
                                            && filled($message->provider)
                                        )
                                            <form
                                                class="retry-form"
                                                method="POST"
                                                action="{{ route(
                                                    'platform.whatsapp-failures.retry',
                                                    $message->id
                                                ) }}"
                                            >
                                                @csrf

                                                <button
                                                    class="button retry-button"
                                                    type="submit"
                                                    onclick="return confirm(
                                                        'Deseja reprocessar esta mensagem de WhatsApp?'
                                                    )"
                                                >
                                                    Reprocessar
                                                </button>
                                            </form>
                                        @else
                                            <span class="platform-muted">
                                                Reprocessamento indisponível
                                            </span>
                                        @endif
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
