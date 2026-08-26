@extends('platform.layout')

@section('title', __('platform.tenants.title'))

@section('body')

<style>
.platform-tenants-index .platform-toolbar{align-items:flex-end;gap:18px}
.platform-tenants-index .platform-toolbar h1{margin:4px 0 0;font-size:32px;letter-spacing:-.03em}
.platform-tenants-index .filter-grid{align-items:end}
.platform-tenants-index .filter-actions{display:flex;gap:10px;flex-wrap:wrap}
.platform-tenants-index .platform-card{border-radius:16px}
.platform-tenants-index .platform-card--table{overflow:hidden}
.platform-tenants-index .platform-table td{vertical-align:middle}
.platform-tenants-index .platform-table td:last-child{text-align:right}
@media(max-width:800px){.platform-tenants-index .platform-toolbar{align-items:flex-start;flex-direction:column}}
</style>

<div class="platform-tenants-index">
    @include('platform.partials.navigation')

    <main class="platform-main">
        @php
            $breadcrumbs = [
                [
                    'label' => __('platform.nav.dashboard'),
                    'url' => route('platform.dashboard'),
                ],
                [
                    'label' => __('platform.nav.tenants'),
                    'url' => null,
                ],
            ];
        @endphp

        @include('platform.partials.breadcrumbs')
        <div class="platform-toolbar">
            <div>
                <h1>{{ __('platform.tenants.title') }}</h1>

                <p class="platform-muted">
                    {{ __('platform.tenants.description') }}
                </p>
            </div>

            <a
                class="button button-secondary"
                href="{{ route('platform.dashboard') }}"
            >
                {{ __('platform.back_dashboard') }}
            </a>
        </div>

        <div class="platform-card">
            <form
                method="GET"
                action="{{ route('platform.tenants.index') }}"
                class="filter-grid"
            >
                <div class="form-group">
                    <label for="tenant-search">
                        {{ __('platform.tenants.search') }}
                    </label>

                    <input
                        class="form-control"
                        id="tenant-search"
                        name="q"
                        value="{{ $search }}"
                        placeholder="{{ __('platform.tenants.search_placeholder') }}"
                    >
                </div>

                <div class="form-group">
                    <label for="tenant-status">
                        {{ __('platform.tenants.status') }}
                    </label>

                    <select
                        class="form-control"
                        id="tenant-status"
                        name="status"
                    >
                        <option value="">
                            {{ __('platform.tenants.all') }}
                        </option>

                        @foreach ([
                            'active' => __('platform.tenants.active'),
                            'blocked' => __('platform.tenants.blocked'),
                            'inactive' => __('platform.tenants.inactive'),
                        ] as $value => $label)
                            <option
                                value="{{ $value }}"
                                @selected($status === $value)
                            >
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="filter-actions">
                    <button
                        class="button"
                        type="submit"
                    >
                        {{ __('platform.tenants.filter') }}
                    </button>

                    <a
                        class="button button-secondary"
                        href="{{ route('platform.tenants.index') }}"
                    >
                        {{ __('platform.tenants.clear') }}
                    </a>
                </div>
            </form>
        </div>

        <div class="platform-card platform-card--table">
            <div class="table-wrap">
                <table class="platform-table">
                    <thead>
                        <tr>
                            <th>{{ __('platform.tenants.tenant') }}</th>
                            <th>{{ __('platform.tenants.status') }}</th>
                            <th>{{ __('platform.tenants.plan') }}</th>
                            <th>{{ __('platform.tenants.subscription') }}</th>
                            <th>{{ __('platform.tenants.trial_until') }}</th>
                            <th>{{ __('platform.tenants.users') }}</th>
                            <th></th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($tenants as $tenant)
                            @php
                                $subscription =
                                    $subscriptions->get(
                                        $tenant->id
                                    );
                            @endphp

                            <tr>
                                <td>
                                    <strong>
                                        {{ $tenant->name }}
                                    </strong>

                                    <div class="platform-muted">
                                        {{ $tenant->slug }}
                                    </div>
                                </td>

                                <td>
                                    <x-platform.badge
                                        :variant="match ($tenant->status) {
                                            'active' => 'success',
                                            'blocked' => 'danger',
                                            'inactive' => 'neutral',
                                            default => 'neutral',
                                        }"
                                    >
                                        {{ $tenant->status }}
                                    </x-platform.badge>
                                </td>

                                <td>
                                    {{ $subscription?->plan?->name ?? __('platform.tenants.no_plan') }}
                                </td>

                                <td>
                                    @if ($subscription?->status !== null)
                                        <x-platform.badge
                                            :variant="match ($subscription->status->value) {
                                                'active' => 'success',
                                                'suspended' => 'warning',
                                                'cancelled',
                                                'expired' => 'neutral',
                                                default => 'neutral',
                                            }"
                                        >
                                            {{ $subscription->status->value }}
                                        </x-platform.badge>
                                    @else
                                        {{ __('platform.tenants.no_subscription') }}
                                    @endif
                                </td>

                                <td>
                                    {{ $tenant->trial_ends_at?->format('d/m/Y') ?? '—' }}
                                </td>

                                <td>
                                    {{ (int) ($userCounts[$tenant->id] ?? 0) }}
                                </td>

                                <td>
                                    <a
                                        href="{{ route('platform.tenants.show', $tenant) }}"
                                    >
                                        {{ __('platform.tenants.details') }}
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td class="platform-empty-cell" colspan="7">
                                    {{ __('platform.tenants.empty') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($tenants->hasPages())
                <div class="pagination-wrap">
                    {{ $tenants->links() }}
                </div>
            @endif
        </div>
    </main>
</div>
@endsection
