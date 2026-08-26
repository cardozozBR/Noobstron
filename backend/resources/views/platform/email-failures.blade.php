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

@media (max-width: 800px) {
    .platform-email-failures-page .platform-toolbar {
        align-items: flex-start;
        flex-direction: column;
    }
}
</style>

<div class="platform-email-failures-page">
    @include('platform.partials.navigation')

    <main class="platform-main">
        @php
            $breadcrumbs = [
                [
                    'label' => __('platform.nav.dashboard'),
                    'url' => route('platform.dashboard'),
                ],
                [
                    'label' => __('platform.nav.email_failures'),
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
                    Falhas de e-mail
                </h1>

        <p class="platform-muted">
            @if ($tenant !== null)
                Mensagens de e-mail com falha de envio do tenant
                <strong>{{ $tenant->name }}</strong>.
            @else
                Mensagens de e-mail com falha de envio em todos os tenants.
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
                                <x-platform.empty-state
                    :title="__('platform.empty_states.email_failures_empty')"
                    :description="__('platform.empty_states.email_failures_empty_description')"
                />
            @else
                <div class="table-wrap">
                    <table class="platform-table">
                        <thead>
                            <tr>
                                <th scope="col">Tenant</th>
                                <th scope="col">Destinatário</th>
                                <th scope="col">Assunto</th>
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
                                        {{ $message->to_email }}
                                    </td>

                                    <td class="subject">
                                        {{ $message->subject ?: 'Sem assunto' }}
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
