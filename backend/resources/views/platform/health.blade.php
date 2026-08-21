@extends('platform.layout')

@section('title', __('platform.health.title'))

@section('body')

<style>
.platform-health-page .platform-toolbar{align-items:flex-end;gap:18px}
.platform-health-page .platform-toolbar h1{margin:4px 0 0;font-size:32px;letter-spacing:-.03em}
.platform-health-page .detail-grid{gap:14px}
.platform-health-page .platform-card{min-height:126px;border-radius:16px}
.platform-health-page .platform-card h2{margin-top:0}
.platform-health-page .platform-card p{margin:6px 0 0}
@media(max-width:800px){.platform-health-page .platform-toolbar{align-items:flex-start;flex-direction:column}}
</style>

<div class="platform-health-page">
    <header class="platform-header">
        <div class="platform-header__inner">
            <a class="platform-brand" href="{{ route('platform.dashboard') }}">{{ __('platform.brand') }}</a>
        </div>
    </header>

    <main class="platform-main">
        <div class="platform-toolbar">
            <div>
                <div class="platform-muted">{{ __('platform.health.eyebrow') }}</div>
                <h1>{{ __('platform.health.title') }}</h1>
                <p class="platform-muted">{{ __('platform.health.description') }}</p>
            </div>
            <a class="button" href="{{ route('platform.dashboard') }}">{{ __('platform.back_dashboard') }}</a>
        </div>

        <div class="detail-grid">
            <section class="platform-card"><h2>{{ __('platform.health.database') }}</h2><p>{{ $checks['database'] ? __('platform.health.ok') : __('platform.health.fail') }}</p></section>
            <section class="platform-card"><h2>{{ __('platform.health.storage') }}</h2><p>{{ $checks['storage'] ? __('platform.health.writable') : __('platform.health.unavailable') }}</p></section>
            <section class="platform-card"><h2>{{ __('platform.health.queue') }}</h2><p>{{ $checks['queue_pending'] ?? __('platform.health.unavailable') }} {{ __('platform.health.pending') }}</p><p>{{ $checks['queue_failed'] ?? __('platform.health.unavailable') }} {{ __('platform.health.failures') }}</p></section>
            <section class="platform-card"><h2>{{ __('platform.health.mail') }}</h2><p>{{ $checks['mail_configured'] ? __('platform.health.provider_configured') : __('platform.health.log_only') }}</p></section>
            <section class="platform-card"><h2>{{ __('platform.health.commercial_contact') }}</h2><p>{{ $checks['contact_recipient'] ? __('platform.health.recipient_configured') : __('platform.health.no_recipient') }}</p></section>
        </div>
    </main>
</div>
@endsection
