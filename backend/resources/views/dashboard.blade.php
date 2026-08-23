@extends('layouts.app')

@php
    use App\Enums\Feature;
    use App\Enums\Permission;
    use App\Enums\Role;
    use App\Support\TenantCapabilities;
    use App\Support\TenantMoneyFormatter;
    $tenantCapabilities = app(TenantCapabilities::class);

    $opportunitiesEnabled = $tenantCapabilities->enabled(
        $tenant,
        Feature::OPPORTUNITIES
    );

    $activitiesEnabled = $tenantCapabilities->enabled(
        $tenant,
        Feature::ACTIVITIES
    );

    $leadsEnabled = $tenantCapabilities->enabled(
        $tenant,
        Feature::LEADS
    );
@endphp

@section('title', __('ui.dashboard.title'))

@section('content')


<style>
    .dashboard-page {
        display: grid;
        gap: 24px;
    }

    .dashboard-page .page-header {
        display: flex;
        align-items: flex-end;
        justify-content: space-between;
        gap: 20px;
        margin-bottom: 0;
    }

    .dashboard-page .page-header h1 {
        margin: 0;
        font-size: 32px;
        line-height: 1.15;
        letter-spacing: -0.025em;
    }

    .dashboard-page .page-header p {
        margin: 7px 0 0;
        font-size: 14px;
    }

    .dashboard-page .card {
        margin-bottom: 0;
        border-radius: 16px;
        border-color: #e5e7eb;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
    }

    .dashboard-page > .card:first-of-type {
        position: relative;
        overflow: hidden;
        padding: 30px;
        background:
            linear-gradient(135deg, rgba(37, 99, 235, 0.07), rgba(255, 255, 255, 0) 48%),
            #fff;
    }

    .dashboard-page > .card:first-of-type::after {
        content: "";
        position: absolute;
        top: -45px;
        right: -35px;
        width: 150px;
        height: 150px;
        border-radius: 999px;
        background: rgba(37, 99, 235, 0.06);
        pointer-events: none;
    }

    .dashboard-page > .card:first-of-type h2 {
        margin: 2px 0 8px;
        font-size: 22px;
        letter-spacing: -0.015em;
    }

    .dashboard-page .dashboard-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 14px;
        margin: 0;
    }

    .dashboard-page .stat-card {
        min-height: 126px;
        padding: 18px;
        border: 1px solid #e5e7eb;
        border-radius: 14px;
        background: #fff;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.035);
        transition:
            transform 160ms ease,
            box-shadow 160ms ease,
            border-color 160ms ease;
    }

    .dashboard-page .stat-card:hover {
        transform: translateY(-1px);
        border-color: #d1d5db;
        box-shadow: 0 8px 22px rgba(15, 23, 42, 0.06);
    }

    .dashboard-page .stat-label {
        margin-bottom: 7px;
        color: #6b7280;
        font-size: 12px;
        font-weight: 700;
        letter-spacing: 0.07em;
        text-transform: uppercase;
    }

    .dashboard-page .stat-value {
        font-size: 30px;
        line-height: 1.05;
        letter-spacing: -0.03em;
    }

    .dashboard-page .stat-description {
        margin-top: 8px;
        color: #6b7280;
        font-size: 13px;
        line-height: 1.45;
    }

    .dashboard-page .section-header {
        align-items: center;
        margin-bottom: 18px;
    }

    .dashboard-page .section-header h2 {
        margin-bottom: 4px;
        font-size: 19px;
    }

    .dashboard-page .section-header p {
        font-size: 13px;
        line-height: 1.5;
    }

    .dashboard-page .table-wrapper {
        overflow: hidden;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
    }

    .dashboard-page .data-table {
        margin: 0;
    }

    .dashboard-page .data-table thead {
        background: #f9fafb;
    }

    .dashboard-page .data-table th {
        padding: 11px 13px;
        border-bottom: 1px solid #e5e7eb;
        color: #6b7280;
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 0.06em;
        text-transform: uppercase;
    }

    .dashboard-page .data-table td {
        padding: 13px;
        font-size: 13px;
        line-height: 1.45;
    }

    .dashboard-page .action-code {
        border-color: #dbeafe;
        background: #eff6ff;
        color: #1d4ed8;
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        font-weight: 600;
    }

    .dashboard-page .empty-state {
        padding: 34px 22px;
        border: 1px dashed #d1d5db;
        border-radius: 12px;
        background: #fafafa;
    }

    .dashboard-page .empty-state strong {
        color: #111827;
    }

    .dashboard-page .quick-actions {
        gap: 14px;
    }

    .dashboard-page .quick-action {
        padding: 18px;
        border-radius: 14px;
        background: #fff;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.035);
        transition:
            transform 160ms ease,
            box-shadow 160ms ease,
            border-color 160ms ease;
    }

    .dashboard-page .quick-action:hover {
        transform: translateY(-1px);
        border-color: #cbd5e1;
        box-shadow: 0 8px 22px rgba(15, 23, 42, 0.06);
        text-decoration: none;
    }

    .dashboard-page .btn {
        min-height: 40px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 9px 14px;
        border-radius: 10px;
        font-weight: 700;
    }

    .dashboard-page .btn-secondary {
        background: #fff;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
    }

    .dashboard-page .eyebrow {
        color: #6b7280;
        letter-spacing: 0.12em;
    }

    .dashboard-page .billing-banner {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 20px;
        padding: 18px 20px;
        border: 1px solid #dbeafe;
        border-radius: 14px;
        background: #eff6ff;
    }

    .dashboard-page .billing-banner.is-inactive {
        border-color: #fde68a;
        background: #fffbeb;
    }

    .dashboard-page .billing-banner-copy {
        min-width: 0;
    }

    .dashboard-page .billing-banner-title {
        display: block;
        margin-bottom: 4px;
        color: #111827;
        font-size: 15px;
        font-weight: 700;
    }

    .dashboard-page .billing-banner p {
        margin: 0;
        color: #4b5563;
        font-size: 13px;
        line-height: 1.5;
    }

    @media (max-width: 900px) {
        .dashboard-page .dashboard-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .dashboard-page .page-header {
            align-items: flex-start;
            flex-direction: column;
        }
    }

    @media (max-width: 640px) {
        .dashboard-page {
            gap: 18px;
        }

        .dashboard-page .dashboard-grid,
        .dashboard-page .quick-actions {
            grid-template-columns: 1fr;
        }

        .dashboard-page .card,
        .dashboard-page > .card:first-of-type {
            padding: 20px;
        }

        .dashboard-page .page-header h1 {
            font-size: 26px;
        }

        .dashboard-page .billing-banner {
            align-items: flex-start;
            flex-direction: column;
        }

        .dashboard-page .billing-banner .btn {
            width: 100%;
        }
    }

    /* DASHBOARD-STAT-SPACING-V1:START */
    .dashboard-page .stat-card {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
    }

    .dashboard-page .stat-label {
        display: block;
        width: 100%;
        margin-bottom: 10px;
    }

    .dashboard-page .stat-value {
        display: block;
        width: 100%;
        margin: 0;
        font-size: 32px;
        line-height: 1;
        font-weight: 700;
        letter-spacing: -0.03em;
    }

    .dashboard-page .stat-description {
        display: block;
        width: 100%;
        margin-top: 10px;
    }
    /* DASHBOARD-STAT-SPACING-V1:END */
</style>

<div class="dashboard-page">

<div class="page-header">
    <div>
        <h1>{{ __('ui.dashboard.heading') }}</h1>

        <p>
            {{ __('ui.dashboard.tenant_overview') }}
            <strong>{{ $tenant->name }}</strong>
        </p>
    </div>

    <div class="actions">
        @if (auth()->user()->hasPermission(Permission::USERS_VIEW))
            <a class="btn btn-primary" href="{{ route('users.index') }}">
                {{ __('ui.navigation.users') }}
            </a>
        @endif

        @if (auth()->user()->hasPermission(Permission::AUDIT_VIEW))
            <a class="btn btn-secondary" href="{{ route('audit.index') }}">
                {{ __('ui.navigation.audit') }}
            </a>
        @endif
    </div>
</div>

<div
    class="billing-banner{{ $billingState === 'inactive' ? ' is-inactive' : '' }}"
>
    <div class="billing-banner-copy">
        @if ($billingState === 'trial')
            <strong class="billing-banner-title">
                Período de teste ativo
            </strong>

            <p>
                @if ($billingPlanName)
                    Plano selecionado: {{ $billingPlanName }}.
                @endif

                @if ($trialDaysRemaining !== null)
                    Restam aproximadamente
                    {{ $trialDaysRemaining }}
                    {{ __('billing.days_remaining') }}.
                @endif
            </p>
        @elseif ($billingState === 'active')
            <strong class="billing-banner-title">
                Assinatura ativa
            </strong>

            <p>
                @if ($billingPlanName)
                    Seu plano atual é {{ $billingPlanName }}.
                @endif
                Gerencie cobrança ou altere seu plano quando precisar.
            </p>
        @else
            <strong class="billing-banner-title">
                Assinatura inativa
            </strong>

            <p>
                Você pode consultar seus dados.
                Assine novamente para voltar a criar e alterar registros.
            </p>
        @endif
    </div>

    <a
        class="btn {{ $billingState === 'inactive' ? 'btn-primary' : 'btn-secondary' }}"
        href="{{ route('billing.index') }}"
    >
        @if ($billingState === 'trial')
            Ver planos
        @elseif ($billingState === 'active')
            {{ __('billing.manage_subscription') }}
        @else
            Assinar novamente
        @endif
    </a>
</div>

<div class="card">
    <div>
        <span class="eyebrow">{{ __('ui.dashboard.hello', ['name' => auth()->user()->name]) }}</span>

        <h2>
            {{ __('ui.dashboard.welcome', ['tenant' => $tenant->name]) }}
        </h2>

        <p>
            {{ __('ui.dashboard.connected_as') }}
            <strong>
                {{ auth()->user()->role === Role::ADMIN ? __('ui.common.administrator') : __('ui.common.user') }}
            </strong>.
        </p>
    </div>
</div>

@if (auth()->user()->hasPermission(Permission::AUDIT_VIEW))

    <div class="dashboard-grid">

        <div class="stat-card">
            <span class="stat-label">{{ __('ui.dashboard.users') }}</span>
            <strong class="stat-value">{{ $totalUsers }}</strong>
            <span class="stat-description">
                {{ __('ui.dashboard.registered_users') }}
            </span>
        </div>

        <div class="stat-card">
            <span class="stat-label">{{ __('ui.dashboard.audit_events') }}</span>
            <strong class="stat-value">{{ $totalAuditLogs }}</strong>
            <span class="stat-description">
                {{ __('ui.dashboard.registered_events') }}
            </span>
        </div>

        <div class="stat-card">
            <span class="stat-label">{{ __('ui.dashboard.different_actions') }}</span>
            <strong class="stat-value">{{ $totalActions }}</strong>
            <span class="stat-description">
                {{ __('ui.dashboard.registered_action_types') }}
            </span>
        </div>

    </div>

    <div class="card">

        <div class="section-header">
            <div>
                <h2>{{ __('ui.dashboard.latest_events') }}</h2>

                <p>
                    {{ __('ui.dashboard.latest_activity') }}
                </p>
            </div>

            <a class="btn btn-secondary" href="{{ route('audit.index') }}">
                {{ __('ui.dashboard.view_audit') }}
            </a>
        </div>

        @if ($recentLogs->isEmpty())

            <div class="empty-state">
                <strong>{{ __('ui.dashboard.no_events') }}</strong>

                <p>
                    {{ __('ui.dashboard.no_events_description') }}
                </p>
            </div>

        @else

            <div class="table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>{{ __('ui.dashboard.date') }}</th>
                            <th>{{ __('ui.dashboard.user') }}</th>
                            <th>{{ __('ui.dashboard.action') }}</th>
                            <th>{{ __('ui.dashboard.description') }}</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($recentLogs as $log)
                            <tr>
                                <td class="nowrap">
                                    {{ app(\App\Support\TenantDateTime::class)->formatForTenant($log->created_at) }}
                                </td>

                                <td>
                                    {{ $log->user?->name ?? __('ui.common.system') }}
                                </td>

                                <td>
                                    <code class="action-code">
                                        {{ $log->action }}
                                    </code>
                                </td>

                                <td>
                                    {{ $log->description }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

        @endif

    </div>

@endif

@if (
    (
        $leadsEnabled
        && auth()->user()->hasPermission(Permission::LEADS_VIEW)
    )
    || (
        $opportunitiesEnabled
        && auth()->user()->hasPermission(Permission::OPPORTUNITIES_VIEW)
    )
    || (
        $activitiesEnabled
        && auth()->user()->hasPermission(Permission::ACTIVITIES_VIEW)
    )
)

    <div class="card">

        <div class="section-header">
            <div>
                <span class="eyebrow">
                    {{ __('ui.dashboard.commercial') }}
                </span>

                <h2>
                    {{ __('ui.dashboard.commercial_overview') }}
                </h2>

                <p>
                    {{ __('ui.dashboard.commercial_description') }}
                </p>
            </div>

            <div class="actions">
                @if ($opportunitiesEnabled && auth()->user()->hasPermission(Permission::OPPORTUNITIES_VIEW))
                    <a
                        class="btn btn-secondary"
                        href="{{ route('opportunities.index') }}"
                    >
                        {{ __('ui.dashboard.view_opportunities') }}
                    </a>
                @endif

                @if ($activitiesEnabled && auth()->user()->hasPermission(Permission::ACTIVITIES_VIEW))
                    <a
                        class="btn btn-secondary"
                        href="{{ route('activities.index') }}"
                    >
                        {{ __('ui.dashboard.view_activities') }}
                    </a>
                @endif
            </div>
        </div>

        <div class="dashboard-grid">

            @if ($leadsEnabled && auth()->user()->hasPermission(Permission::LEADS_VIEW))

                <div class="stat-card">
                    <span class="stat-label">
                        {{ __('ui.dashboard.leads') }}
                    </span>

                    <strong class="stat-value">
                        {{ $crmMetrics['total_leads'] }}
                    </strong>

                    <span class="stat-description">
                        {{ __('ui.dashboard.leads_description') }}
                    </span>
                </div>

                <div class="stat-card">
                    <span class="stat-label">
                        {{ __('ui.dashboard.converted_leads') }}
                    </span>

                    <strong class="stat-value">
                        {{ $crmMetrics['converted_leads'] }}
                    </strong>

                    <span class="stat-description">
                        {{ __('ui.dashboard.converted_leads_description') }}
                    </span>
                </div>

                <div class="stat-card">
                    <span class="stat-label">
                        {{ __('ui.dashboard.conversion_rate') }}
                    </span>

                    <strong class="stat-value">
                        {{ number_format($crmMetrics['lead_conversion_rate'], 2) }}%
                    </strong>

                    <span class="stat-description">
                        {{ __('ui.dashboard.conversion_rate_description') }}
                    </span>
                </div>

            @endif

            @if ($opportunitiesEnabled && auth()->user()->hasPermission(Permission::OPPORTUNITIES_VIEW))

                <div class="stat-card">
                    <span class="stat-label">
                        {{ __('ui.dashboard.open_opportunities') }}
                    </span>

                    <strong class="stat-value">
                        {{ $crmMetrics['total_opportunities'] }}
                    </strong>

                    <span class="stat-description">
                        {{ __('ui.dashboard.open_opportunities_description') }}
                    </span>
                </div>

                <div class="stat-card">
                    <span class="stat-label">
                        {{ __('ui.dashboard.pipeline_value') }}
                    </span>

                    <strong class="stat-value">
                        {{
                            app(TenantMoneyFormatter::class)->formatMinor(
                                $crmMetrics['pipeline_value_minor']
                            )
                        }}
                    </strong>

                    <span class="stat-description">
                        {{ __('ui.dashboard.pipeline_value_description') }}
                    </span>
                </div>

                <div class="stat-card">
                    <span class="stat-label">
                        {{ __('ui.dashboard.weighted_pipeline') }}
                    </span>

                    <strong class="stat-value">
                        {{
                            app(TenantMoneyFormatter::class)->formatMinor(
                                $crmMetrics['weighted_pipeline_value_minor']
                            )
                        }}
                    </strong>

                    <span class="stat-description">
                        {{ __('ui.dashboard.weighted_pipeline_description') }}
                    </span>
                </div>

            @endif

            @if ($activitiesEnabled && auth()->user()->hasPermission(Permission::ACTIVITIES_VIEW))

                <div class="stat-card">
                    <span class="stat-label">
                        {{ __('ui.dashboard.pending_activities') }}
                    </span>

                    <strong class="stat-value">
                        {{ $crmMetrics['pending_activities'] }}
                    </strong>

                    <span class="stat-description">
                        {{ __('ui.dashboard.pending_activities_description') }}
                    </span>
                </div>

                <div class="stat-card">
                    <span class="stat-label">
                        {{ __('ui.dashboard.overdue_activities') }}
                    </span>

                    <strong class="stat-value">
                        {{ $crmMetrics['overdue_activities'] }}
                    </strong>

                    <span class="stat-description">
                        {{ __('ui.dashboard.overdue_activities_description') }}
                    </span>
                </div>

                <div class="stat-card">
                    <span class="stat-label">
                        {{ __('ui.dashboard.due_soon_activities') }}
                    </span>

                    <strong class="stat-value">
                        {{ $crmMetrics['due_soon_activities'] }}
                    </strong>

                    <span class="stat-description">
                        {{ __('ui.dashboard.due_soon_activities_description') }}
                    </span>
                </div>

            @endif

        </div>

    </div>

    @if ($opportunitiesEnabled && auth()->user()->hasPermission(Permission::OPPORTUNITIES_VIEW))

        <div class="card">

            <div class="section-header">
                <div>
                    <h2>{{ __('ui.dashboard.pipeline_by_stage') }}</h2>

                    <p>
                        {{ __('ui.dashboard.pipeline_by_stage_description') }}
                    </p>
                </div>
            </div>

            @if ($opportunitiesByStage->isEmpty())

                <div class="empty-state">
                    <strong>
                        {{ __('ui.dashboard.no_pipeline_stages') }}
                    </strong>
                </div>

            @else

                <div class="table-wrapper">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>{{ __('ui.dashboard.stage') }}</th>
                                <th>{{ __('ui.dashboard.opportunities') }}</th>
                                <th>{{ __('ui.dashboard.value') }}</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach ($opportunitiesByStage as $stage)
                                <tr>
                                    <td>{{ $stage->name }}</td>

                                    <td>
                                        {{ $stage->opportunities_count }}
                                    </td>

                                    <td>
                                        {{
                                            app(TenantMoneyFormatter::class)
                                                ->formatMinor(
                                                    (int) (
                                                        $stage->opportunities_sum_value_minor
                                                        ?? 0
                                                    )
                                                )
                                        }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

            @endif

        </div>

    @endif

    @if ($opportunitiesEnabled && auth()->user()->hasPermission(Permission::OPPORTUNITIES_VIEW))

        <div class="card">

            <div class="section-header">
                <div>
                    <h2>
                        {{ __('ui.dashboard.opportunities_by_responsible') }}
                    </h2>

                    <p>
                        {{ __('ui.dashboard.opportunities_by_responsible_description') }}
                    </p>
                </div>
            </div>

            @if ($opportunitiesByResponsible->isEmpty())

                <div class="empty-state">
                    <strong>
                        {{ __('ui.dashboard.no_responsible_opportunities') }}
                    </strong>
                </div>

            @else

                <div class="table-wrapper">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>{{ __('ui.dashboard.responsible') }}</th>
                                <th>{{ __('ui.dashboard.opportunities') }}</th>
                                <th>{{ __('ui.dashboard.value') }}</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach ($opportunitiesByResponsible as $responsible)
                                <tr>
                                    <td>{{ $responsible->name }}</td>

                                    <td>
                                        {{ $responsible->assigned_opportunities_count }}
                                    </td>

                                    <td>
                                        {{
                                            app(TenantMoneyFormatter::class)
                                                ->formatMinor(
                                                    (int) (
                                                        $responsible
                                                            ->assigned_opportunities_sum_value_minor
                                                        ?? 0
                                                    )
                                                )
                                        }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

            @endif

        </div>

    @endif

    @if ($activitiesEnabled && auth()->user()->hasPermission(Permission::ACTIVITIES_VIEW))

        <div class="card">

            <div class="section-header">
                <div>
                    <h2>{{ __('ui.dashboard.upcoming_activities') }}</h2>

                    <p>
                        {{ __('ui.dashboard.upcoming_activities_description') }}
                    </p>
                </div>
            </div>

            @if ($upcomingActivities->isEmpty())

                <div class="empty-state">
                    <strong>
                        {{ __('ui.dashboard.no_upcoming_activities') }}
                    </strong>
                </div>

            @else

                <div class="table-wrapper">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>{{ __('ui.dashboard.activity') }}</th>
                                <th>{{ __('ui.dashboard.customer') }}</th>
                                <th>{{ __('ui.dashboard.responsible') }}</th>
                                <th>{{ __('ui.dashboard.due_at') }}</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach ($upcomingActivities as $activity)
                                <tr>
                                    <td>
                                        {{ $activity->title }}
                                    </td>

                                    <td>
                                        {{ $activity->customer?->name ?? '—' }}
                                    </td>

                                    <td>
                                        {{ $activity->responsible?->name ?? '—' }}
                                    </td>

                                    <td class="nowrap">
                                        {{
                                            app(\App\Support\TenantDateTime::class)
                                                ->formatForTenant(
                                                    $activity->due_at
                                                )
                                        }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

            @endif

        </div>

    @endif

@endif
@if (auth()->user()->role === Role::ADMIN)

    <div class="card">

        <div class="section-header">
            <div>
                <span class="eyebrow">{{ __('ui.dashboard.administration') }}</span>

                <h2>{{ __('ui.dashboard.environment_management') }}</h2>

                <p>
                    {{ __('ui.dashboard.admin_resources') }}
                </p>
            </div>
        </div>

        <div class="quick-actions">

            <a class="quick-action" href="{{ route('users.index') }}">
                <strong>{{ __('ui.dashboard.manage_users') }}</strong>

                <span>
                    {{ __('ui.dashboard.manage_users_description') }}
                </span>
            </a>

            <a class="quick-action" href="{{ route('audit.index') }}">
                <strong>{{ __('ui.dashboard.consult_audit') }}</strong>

                <span>
                    {{ __('ui.dashboard.consult_audit_description') }}
                </span>
            </a>

        </div>

    </div>

@endif

</div>

@endsection
