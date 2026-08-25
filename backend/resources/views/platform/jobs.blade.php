@extends('platform.layout')

@section('title', 'Filas e jobs')

@section('body')

<style>
.platform-jobs-page .platform-toolbar {
    align-items: flex-end;
    gap: 18px;
}

.platform-jobs-page .platform-toolbar h1 {
    margin: 4px 0 0;
    font-size: 32px;
    letter-spacing: -.03em;
}

.platform-jobs-page .platform-card {
    border-radius: 16px;
}

.platform-jobs-page .flash-message {
    margin-bottom: 18px;
}

.platform-jobs-page .flash-message--success {
    border-color: #bbf7d0;
    background: #f0fdf4;
    color: #166534;
}

.platform-jobs-page .flash-message--error {
    border-color: #fecaca;
    background: #fef2f2;
    color: #991b1b;
}

.platform-jobs-page .summary-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 18px;
    margin-bottom: 18px;
}

.platform-jobs-page .summary-count {
    margin-top: 8px;
    font-size: 30px;
    line-height: 1;
    font-weight: 800;
    letter-spacing: -.035em;
}

.platform-jobs-page .section-card {
    margin-bottom: 18px;
}

.platform-jobs-page .section-header {
    margin-bottom: 18px;
}

.platform-jobs-page .section-header h2 {
    margin: 0 0 6px;
    font-size: 20px;
}

.platform-jobs-page .table-wrap {
    overflow-x: auto;
}

.platform-jobs-page table {
    width: 100%;
    border-collapse: collapse;
}

.platform-jobs-page th,
.platform-jobs-page td {
    padding: 14px 12px;
    text-align: left;
    vertical-align: top;
    border-bottom: 1px solid #e2e8f0;
}

.platform-jobs-page th {
    color: #64748b;
    font-size: 12px;
    font-weight: 700;
    letter-spacing: .04em;
    text-transform: uppercase;
}

.platform-jobs-page td {
    font-size: 14px;
}

.platform-jobs-page .job-id,
.platform-jobs-page .job-queue,
.platform-jobs-page .job-attempts,
.platform-jobs-page .job-date,
.platform-jobs-page .job-action {
    white-space: nowrap;
}

.platform-jobs-page .job-name {
    min-width: 240px;
    max-width: 460px;
    overflow-wrap: anywhere;
}

.platform-jobs-page .status-badge {
    display: inline-flex;
    align-items: center;
    padding: 5px 9px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 700;
}

.platform-jobs-page .status-badge--pending {
    background: #fef3c7;
    color: #92400e;
}

.platform-jobs-page .status-badge--failed {
    background: #fee2e2;
    color: #991b1b;
}

.platform-jobs-page .empty-state {
    padding: 28px 10px;
    text-align: center;
}

.platform-jobs-page .pagination-wrap {
    margin-top: 20px;
}

@media (max-width: 800px) {
    .platform-jobs-page .platform-toolbar {
        align-items: flex-start;
        flex-direction: column;
    }

    .platform-jobs-page .summary-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="platform-jobs-page">
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
                    Operações
                </div>

                <h1>
                    Filas e jobs
                </h1>

                <p class="platform-muted">
                    Visão global dos jobs pendentes e falhos da plataforma.
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

        @if (session('success'))
            <section class="platform-card flash-message flash-message--success">
                <strong>
                    {{ session('success') }}
                </strong>
            </section>
        @endif

        @if (session('error'))
            <section class="platform-card flash-message flash-message--error">
                <strong>
                    {{ session('error') }}
                </strong>
            </section>
        @endif

        <div class="summary-grid">
            <section class="platform-card">
                <div class="platform-muted">
                    Jobs pendentes
                </div>

                <div class="summary-count">
                    {{ number_format(
                        $pendingJobs->total(),
                        0,
                        ',',
                        '.'
                    ) }}
                </div>
            </section>

            <section class="platform-card">
                <div class="platform-muted">
                    Jobs falhos
                </div>

                <div class="summary-count">
                    {{ number_format(
                        $failedJobs->total(),
                        0,
                        ',',
                        '.'
                    ) }}
                </div>
            </section>
        </div>

        <section class="platform-card section-card">
            <div class="section-header">
                <h2>
                    Jobs pendentes
                </h2>

                <div class="platform-muted">
                    Jobs aguardando processamento pela fila.
                </div>
            </div>

            @if ($pendingJobs->isEmpty())
                <div class="empty-state">
                    <strong>
                        Nenhum job pendente.
                    </strong>

                    <p class="platform-muted">
                        A fila não possui jobs aguardando processamento.
                    </p>
                </div>
            @else
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Fila</th>
                                <th>Job</th>
                                <th>Tentativas</th>
                                <th>Disponível em</th>
                                <th>Criado em</th>
                                <th>Status</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach ($pendingJobs as $job)
                                @php
                                    $payload = json_decode(
                                        $job->payload,
                                        true
                                    );

                                    $displayName =
                                        data_get(
                                            $payload,
                                            'displayName'
                                        )
                                        ?? 'Job não identificado';
                                @endphp

                                <tr>
                                    <td class="job-id">
                                        #{{ $job->id }}
                                    </td>

                                    <td class="job-queue">
                                        {{ $job->queue }}
                                    </td>

                                    <td class="job-name">
                                        {{ $displayName }}
                                    </td>

                                    <td class="job-attempts">
                                        {{ $job->attempts }}
                                    </td>

                                    <td class="job-date">
                                        {{ \Illuminate\Support\Carbon::createFromTimestamp(
                                            $job->available_at
                                        )->format('d/m/Y H:i:s') }}
                                    </td>

                                    <td class="job-date">
                                        {{ \Illuminate\Support\Carbon::createFromTimestamp(
                                            $job->created_at
                                        )->format('d/m/Y H:i:s') }}
                                    </td>

                                    <td>
                                        <span
                                            class="status-badge status-badge--pending"
                                        >
                                            Pendente
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if ($pendingJobs->hasPages())
                    <div class="pagination-wrap">
                        {{ $pendingJobs->links() }}
                    </div>
                @endif
            @endif
        </section>

        <section class="platform-card section-card">
            <div class="section-header">
                <h2>
                    Jobs falhos
                </h2>

                <div class="platform-muted">
                    Jobs que excederam as tentativas ou falharam durante o processamento.
                </div>
            </div>

            @if ($failedJobs->isEmpty())
                <div class="empty-state">
                    <strong>
                        Nenhum job falho.
                    </strong>

                    <p class="platform-muted">
                        Não existem jobs registrados como falhos.
                    </p>
                </div>
            @else
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>UUID</th>
                                <th>Conexão</th>
                                <th>Fila</th>
                                <th>Job</th>
                                <th>Falhou em</th>
                                <th>Status</th>
                                <th>Ação</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach ($failedJobs as $job)
                                @php
                                    $payload = json_decode(
                                        $job->payload,
                                        true
                                    );

                                    $displayName =
                                        data_get(
                                            $payload,
                                            'displayName'
                                        )
                                        ?? 'Job não identificado';
                                @endphp

                                <tr>
                                    <td class="job-id">
                                        #{{ $job->id }}
                                    </td>

                                    <td class="job-name">
                                        {{ $job->uuid }}
                                    </td>

                                    <td>
                                        {{ $job->connection }}
                                    </td>

                                    <td class="job-queue">
                                        {{ $job->queue }}
                                    </td>

                                    <td class="job-name">
                                        {{ $displayName }}
                                    </td>

                                    <td class="job-date">
                                        {{ \Illuminate\Support\Carbon::parse(
                                            $job->failed_at
                                        )->format('d/m/Y H:i:s') }}
                                    </td>

                                    <td>
                                        <span
                                            class="status-badge status-badge--failed"
                                        >
                                            Falhou
                                        </span>
                                    </td>

                                    <td class="job-action">
                                        <form
                                            method="POST"
                                            action="{{ route(
                                                'platform.jobs.failed.retry',
                                                $job->uuid
                                            ) }}"
                                        >
                                            @csrf

                                            <button
                                                class="button"
                                                type="submit"
                                                onclick="return confirm(
                                                    'Deseja reprocessar este job falho?'
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

                @if ($failedJobs->hasPages())
                    <div class="pagination-wrap">
                        {{ $failedJobs->links() }}
                    </div>
                @endif
            @endif
        </section>
    </main>
</div>

@endsection