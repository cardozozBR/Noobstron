@extends('platform.layout')

@section('title', $tenant->name)

@section('body')

<style>
.platform-tenant-show .platform-toolbar{align-items:flex-end;gap:18px}
.platform-tenant-show .platform-toolbar h1{margin:4px 0 0;font-size:32px;letter-spacing:-.03em}
.platform-tenant-show .detail-grid{gap:14px}
.platform-tenant-show .platform-card{border-radius:16px}
.platform-tenant-show .platform-card h2{margin-top:0}
.platform-tenant-show .detail-list{display:grid;gap:0}
.platform-tenant-show .detail-list>div{padding:12px 0;border-bottom:1px solid #e5e7eb}
.platform-tenant-show .detail-list>div:last-child{border-bottom:0}
.platform-tenant-show .detail-list dt{color:#64748b;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.04em}
.platform-tenant-show .detail-list dd{margin:5px 0 0;font-weight:600}
@media(max-width:800px){.platform-tenant-show .platform-toolbar{align-items:flex-start;flex-direction:column}}
</style>

<div class="platform-tenant-show">
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
                    {{ __('platform.tenants.tenant') }}
                </div>

                <h1>{{ $tenant->name }}</h1>

                <p class="platform-muted">
                    {{ $tenant->slug }}
                </p>
            </div>

            <a
                class="button button-secondary"
                href="{{ route('platform.tenants.index') }}"
            >
                {{ __('platform.tenants.back') }}
            </a>
        </div>

        <div class="detail-grid">
            <section class="platform-card">
                <h2>{{ __('platform.tenants.basic_data') }}</h2>

                <dl class="detail-list">
                    <div>
                        <dt>{{ __('platform.tenants.status') }}</dt>
                        <dd>{{ $tenant->status }}</dd>
                    </div>

                    <div>
                        <dt>{{ __('platform.tenants.country') }}</dt>
                        <dd>{{ $tenant->country_code }}</dd>
                    </div>

                    <div>
                        <dt>{{ __('platform.tenants.language') }}</dt>
                        <dd>{{ $tenant->locale }}</dd>
                    </div>

                    <div>
                        <dt>{{ __('platform.tenants.segment') }}</dt>
                        <dd>{{ $tenant->segment ?? '—' }}</dd>
                    </div>

                    <div>
                        <dt>{{ __('platform.tenants.users') }}</dt>
                        <dd>{{ $userCount }}</dd>
                    </div>
                </dl>
            </section>

            <section class="platform-card">
                <h2>{{ __('platform.tenants.trial') }}</h2>

                <dl class="detail-list">
                    <div>
                        <dt>{{ __('platform.tenants.start') }}</dt>
                        <dd>
                            {{ $tenant->trial_started_at?->format('d/m/Y H:i') ?? __('platform.tenants.not_started') }}
                        </dd>
                    </div>

                    <div>
                        <dt>{{ __('platform.tenants.end') }}</dt>
                        <dd>
                            {{ $tenant->trial_ends_at?->format('d/m/Y H:i') ?? '—' }}
                        </dd>
                    </div>
                </dl>
            </section>

            <section class="platform-card">
                <h2>{{ __('platform.tenants.subscription') }}</h2>

                @if ($subscription)
                    <dl class="detail-list">
                        <div>
                            <dt>{{ __('platform.tenants.plan') }}</dt>
                            <dd>
                                {{ $subscription->plan?->name ?? '—' }}
                            </dd>
                        </div>

                        <div>
                            <dt>{{ __('platform.tenants.status') }}</dt>
                            <dd>
                                {{ $subscription->status->value }}
                            </dd>
                        </div>

                        <div>
                            <dt>{{ __('platform.tenants.current_period') }}</dt>
                            <dd>
                                {{ $subscription->current_period_start?->format('d/m/Y') }}
                                —
                                {{ $subscription->current_period_end?->format('d/m/Y') }}
                            </dd>
                        </div>
                    </dl>
                @else
                    <p class="platform-muted">
                        {{ __('platform.tenants.no_subscription') }}.
                    </p>
                @endif
            </section>

            <section class="platform-card">
                <h2>{{ __('platform.tenants.features') }}</h2>

                @if ($features->isEmpty())
                    <p class="platform-muted">
                        {{ __('platform.tenants.no_features') }}
                    </p>
                @else
                    <div class="table-wrap">
                        <table class="platform-table">
                            <thead>
                                <tr>
                                    <th>{{ __('platform.tenants.feature') }}</th>
                                    <th>{{ __('platform.tenants.enabled') }}</th>
                                    <th>{{ __('platform.tenants.limit') }}</th>
                                </tr>
                            </thead>

                            <tbody>
                                @foreach ($features as $feature)
                                    <tr>
                                        <td>
                                            {{ $feature->feature->value }}
                                        </td>
                                        <td>
                                            {{ $feature->enabled ? __('platform.tenants.yes') : __('platform.tenants.no') }}
                                        </td>
                                        <td>
                                            {{ $feature->limit_value ?? __('platform.tenants.unlimited') }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>

            <section class="platform-card">
                <h2>{{ __('platform.tenants.plan_limits') }}</h2>

                @if ($usageLimits->isEmpty())
                    <p class="platform-muted">
                        {{ __('platform.tenants.no_limits') }}
                    </p>
                @else
                    <div class="table-wrap">
                        <table class="platform-table">
                            <thead>
                                <tr>
                                    <th>{{ __('platform.tenants.metric') }}</th>
                                    <th>{{ __('platform.tenants.limit') }}</th>
                                </tr>
                            </thead>

                            <tbody>
                                @foreach ($usageLimits as $limit)
                                    <tr>
                                        <td>
                                            {{ $limit->metric->value }}
                                        </td>
                                        <td>
                                            {{ $limit->limit_value ?? __('platform.tenants.unlimited') }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>
        </div>
    </main>
</div>
@endsection
