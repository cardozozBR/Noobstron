@extends('layouts.marketing')

@section(
    'title',
    __('learn.getting_started.meta_title')
)

@section(
    'meta_description',
    __('learn.getting_started.meta_description')
)

@section('content')

<style>
.guide-page {
    padding-bottom: 80px;
}

.guide-page .container {
    width: min(1180px, calc(100% - 48px));
    margin-left: auto;
    margin-right: auto;
}

.guide-hero {
    padding: 64px 0 48px;
}

.guide-hero-inner {
    max-width: 860px;
}

.guide-back {
    display: inline-flex;
    margin-bottom: 24px;
    color: #64748b;
    font-size: 14px;
    font-weight: 700;
    text-decoration: none;
}

.guide-back:hover {
    color: #0f172a;
}

.guide-eyebrow {
    display: inline-block;
    margin-bottom: 14px;
    color: var(--primary);
    font-size: 14px;
    font-weight: 800;
    letter-spacing: .08em;
    text-transform: uppercase;
}

.guide-title {
    max-width: 850px;
    margin: 0;
    font-size: clamp(38px, 6vw, 64px);
    line-height: 1.04;
    letter-spacing: -.045em;
}

.guide-lead {
    max-width: 760px;
    margin: 22px 0 0;
    color: #64748b;
    font-size: 19px;
    line-height: 1.75;
}

.guide-progress {
    display: grid;
    grid-template-columns: repeat(9, minmax(0, 1fr));
    gap: 8px;
    margin-top: 38px;
}

.guide-progress-item {
    padding: 12px 8px;
    border-radius: 10px;
    background: #f8fafc;
    color: #64748b;
    font-size: 12px;
    font-weight: 700;
    text-align: center;
}

.guide-layout {
    display: grid;
    grid-template-columns:
        minmax(0, 240px)
        minmax(0, 760px);
    gap: 54px;
    align-items: start;
}

.guide-nav {
    position: sticky;
    top: 24px;
    padding: 20px;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    background: #fff;
}

.guide-nav-title {
    display: block;
    margin-bottom: 14px;
    font-size: 14px;
    font-weight: 800;
    color: #0f172a;
}

.guide-nav a {
    display: block;
    padding: 8px 0;
    color: #64748b;
    font-size: 14px;
    line-height: 1.4;
    text-decoration: none;
}

.guide-nav a:hover {
    color: var(--primary);
}

.guide-content {
    min-width: 0;
}

.guide-section {
    padding: 18px 0 54px;
    border-bottom: 1px solid #e2e8f0;
    scroll-margin-top: 32px;
}

.guide-section:last-child {
    border-bottom: 0;
}

.guide-step {
    display: inline-flex;
    min-width: 38px;
    height: 38px;
    align-items: center;
    justify-content: center;
    margin-bottom: 16px;
    border-radius: 999px;
    background: #eff6ff;
    color: var(--primary);
    font-size: 14px;
    font-weight: 800;
}

.guide-section h2 {
    margin: 0 0 16px;
    font-size: 31px;
    line-height: 1.2;
    letter-spacing: -.03em;
}

.guide-section h3 {
    margin: 30px 0 12px;
    font-size: 20px;
}

.guide-section p {
    margin: 0 0 16px;
    color: #475569;
    font-size: 16px;
    line-height: 1.8;
}

.guide-section ul {
    margin: 16px 0 22px;
    padding-left: 22px;
    color: #475569;
}

.guide-section li {
    margin-bottom: 9px;
    line-height: 1.65;
}

.guide-box {
    margin: 24px 0;
    padding: 22px;
    border-radius: 16px;
    background: #f8fafc;
}

.guide-box strong {
    display: block;
    margin-bottom: 8px;
    color: #0f172a;
}

.guide-box p {
    margin: 0;
}

.guide-example {
    margin: 24px 0;
    padding: 22px;
    border-left: 4px solid var(--primary);
    border-radius: 0 14px 14px 0;
    background: #eff6ff;
}

.guide-example strong {
    display: block;
    margin-bottom: 8px;
}

.guide-flow {
    display: grid;
    gap: 10px;
    margin: 24px 0;
}

.guide-flow-item {
    padding: 16px 18px;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    background: #fff;
}

.guide-flow-arrow {
    color: #94a3b8;
    text-align: center;
    font-weight: 800;
}

.guide-checklist {
    display: grid;
    gap: 10px;
    margin-top: 22px;
}

.guide-check {
    display: flex;
    gap: 12px;
    padding: 15px;
    border-radius: 12px;
    background: #f8fafc;
}

.guide-check-icon {
    flex: 0 0 auto;
    font-weight: 800;
    color: #16a34a;
}

.guide-next {
    margin-top: 54px;
    padding: 38px;
    border-radius: 22px;
    background: #0f172a;
    color: #fff;
}

.guide-next h2 {
    margin: 0 0 12px;
    font-size: 30px;
}

.guide-next p {
    max-width: 620px;
    margin: 0 0 24px;
    color: #cbd5e1;
    line-height: 1.7;
}

.guide-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
}

@media (max-width: 900px) {
    .guide-layout {
        grid-template-columns: 1fr;
    }

    .guide-nav {
        position: static;
    }

    .guide-progress {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }
}

@media (max-width: 600px) {
    .guide-progress {
        grid-template-columns: 1fr 1fr;
    }

    .guide-next {
        padding: 28px 20px;
    }

    .guide-page .container {
        width: min(100% - 28px, 1180px);
    }
}
</style>

<div class="guide-page">

<section class="guide-hero">
<div class="container guide-hero-inner">

    <a
        href="{{ route('marketing.learn.index') }}"
        class="guide-back"
    >
        {{ __('learn.getting_started.back') }}
    </a>

    <span class="guide-eyebrow">
        {{ __('learn.getting_started.eyebrow') }}
    </span>

    <h1 class="guide-title">
        {{ __('learn.getting_started.title') }}
    </h1>

    <p class="guide-lead">
        {{ __('learn.getting_started.lead') }}
    </p>

    <div class="guide-progress">

        @foreach (
            __('learn.getting_started.progress')
            as $item
        )
            <div class="guide-progress-item">
                {{ $item }}
            </div>
        @endforeach

    </div>

</div>
</section>

<section>
<div class="container guide-layout">

<aside class="guide-nav">

    <span class="guide-nav-title">
        {{ __('learn.getting_started.nav_title') }}
    </span>

    <a href="#visao-geral">
        {{ __('learn.getting_started.nav.overview') }}
    </a>

    <a href="#empresa">
        {{ __('learn.getting_started.nav.company') }}
    </a>

    <a href="#equipe">
        {{ __('learn.getting_started.nav.team') }}
    </a>

    <a href="#clientes">
        {{ __('learn.getting_started.nav.customers') }}
    </a>

    <a href="#pipeline">
        {{ __('learn.getting_started.nav.pipeline') }}
    </a>

    <a href="#leads">
        {{ __('learn.getting_started.nav.leads') }}
    </a>

    <a href="#oportunidades">
        {{ __('learn.getting_started.nav.opportunities') }}
    </a>

    <a href="#atividades">
        {{ __('learn.getting_started.nav.activities') }}
    </a>

    <a href="#propostas">
        {{ __('learn.getting_started.nav.proposals') }}
    </a>

    <a href="#venda">
        {{ __('learn.getting_started.nav.sale') }}
    </a>

    <a href="#evoluir">
        {{ __('learn.getting_started.nav.evolve') }}
    </a>

</aside>

<main class="guide-content">

<section
    id="visao-geral"
    class="guide-section"
>

    <span class="guide-step">00</span>

    <h2>
        {{ __('learn.getting_started.overview.title') }}
    </h2>

    @foreach (
        __('learn.getting_started.overview.paragraphs')
        as $paragraph
    )
        <p>{{ $paragraph }}</p>
    @endforeach

    <div class="guide-flow">

        @foreach (
            __('learn.getting_started.overview.flow')
            as $item
        )

            <div class="guide-flow-item">
                {{ $item }}
            </div>

            @if (!$loop->last)
                <div class="guide-flow-arrow">
                    ↓
                </div>
            @endif

        @endforeach

    </div>

    <div class="guide-box">
        <strong>
            {{ __('learn.getting_started.overview.box_title') }}
        </strong>

        <p>
            {{ __('learn.getting_started.overview.box_text') }}
        </p>
    </div>

</section>

<section id="empresa" class="guide-section">

    <span class="guide-step">01</span>

    <h2>
        {{ __('learn.getting_started.company.title') }}
    </h2>

    @foreach (
        __('learn.getting_started.company.paragraphs')
        as $paragraph
    )
        <p>{{ $paragraph }}</p>
    @endforeach

    <h3>
        {{ __('learn.getting_started.company.subtitle') }}
    </h3>

    <ul>
        @foreach (
            __('learn.getting_started.company.items')
            as $item
        )
            <li>{{ $item }}</li>
        @endforeach
    </ul>

    <p>
        {{ __('learn.getting_started.company.after_list') }}
    </p>

    <div class="guide-example">
        <strong>
            {{ __('learn.getting_started.company.example_title') }}
        </strong>

        {{ __('learn.getting_started.company.example_text') }}
    </div>

</section>

<section id="equipe" class="guide-section">

    <span class="guide-step">02</span>

    <h2>
        {{ __('learn.getting_started.team.title') }}
    </h2>

    @foreach (
        __('learn.getting_started.team.paragraphs')
        as $paragraph
    )
        <p>{{ $paragraph }}</p>
    @endforeach

    <h3>
        {{ __('learn.getting_started.team.subtitle') }}
    </h3>

    <ul>
        @foreach (
            __('learn.getting_started.team.items')
            as $item
        )
            <li>{{ $item }}</li>
        @endforeach
    </ul>

    <div class="guide-box">
        <strong>
            {{ __('learn.getting_started.team.box_title') }}
        </strong>

        <p>
            {{ __('learn.getting_started.team.box_text') }}
        </p>
    </div>

</section>

<section id="clientes" class="guide-section">

    <span class="guide-step">03</span>

    <h2>
        {{ __('learn.getting_started.customers.title') }}
    </h2>

    @foreach (
        __('learn.getting_started.customers.paragraphs')
        as $paragraph
    )
        <p>{{ $paragraph }}</p>
    @endforeach

    <h3>
        {{ __('learn.getting_started.customers.subtitle') }}
    </h3>

    <ul>
        @foreach (
            __('learn.getting_started.customers.items')
            as $item
        )
            <li>{{ $item }}</li>
        @endforeach
    </ul>

    <p>
        {{ __('learn.getting_started.customers.after_list') }}
    </p>

    <div class="guide-example">
        <strong>
            {{ __('learn.getting_started.customers.example_title') }}
        </strong>

        {{ __('learn.getting_started.customers.example_text') }}
    </div>

    <h3>
        {{ __('learn.getting_started.customers.import_title') }}
    </h3>

    <p>
        {{ __('learn.getting_started.customers.import_text') }}
    </p>

</section>

<section id="pipeline" class="guide-section">

    <span class="guide-step">04</span>

    <h2>
        {{ __('learn.getting_started.pipeline.title') }}
    </h2>

    @foreach (
        __('learn.getting_started.pipeline.paragraphs')
        as $paragraph
    )
        <p>{{ $paragraph }}</p>
    @endforeach

    <div class="guide-flow">

        @foreach (
            __('learn.getting_started.pipeline.flow')
            as $item
        )

            <div class="guide-flow-item">
                {{ $item }}
            </div>

            @if (!$loop->last)
                <div class="guide-flow-arrow">↓</div>
            @endif

        @endforeach

    </div>

    <div class="guide-box">
        <strong>
            {{ __('learn.getting_started.pipeline.box_title') }}
        </strong>

        <p>
            {{ __('learn.getting_started.pipeline.box_text') }}
        </p>
    </div>

</section>

<section id="leads" class="guide-section">

    <span class="guide-step">05</span>

    <h2>
        {{ __('learn.getting_started.leads.title') }}
    </h2>

    @foreach (
        __('learn.getting_started.leads.paragraphs')
        as $paragraph
    )
        <p>{{ $paragraph }}</p>
    @endforeach

    <h3>
        {{ __('learn.getting_started.leads.subtitle') }}
    </h3>

    <ul>
        @foreach (
            __('learn.getting_started.leads.items')
            as $item
        )
            <li>{{ $item }}</li>
        @endforeach
    </ul>

    <p>
        {{ __('learn.getting_started.leads.after_list') }}
    </p>

</section>

<section id="oportunidades" class="guide-section">

    <span class="guide-step">06</span>

    <h2>
        {{ __('learn.getting_started.opportunities.title') }}
    </h2>

    @foreach (
        __('learn.getting_started.opportunities.paragraphs')
        as $paragraph
    )
        <p>{{ $paragraph }}</p>
    @endforeach

    <div class="guide-example">
        <strong>
            {{ __('learn.getting_started.opportunities.example_title') }}
        </strong>

        {{ __('learn.getting_started.opportunities.example_text') }}
    </div>

    <h3>
        {{ __('learn.getting_started.opportunities.subtitle') }}
    </h3>

    <p>
        {{ __('learn.getting_started.opportunities.after_example') }}
    </p>

</section>

<section id="atividades" class="guide-section">

    <span class="guide-step">07</span>

    <h2>
        {{ __('learn.getting_started.activities.title') }}
    </h2>

    @foreach (
        __('learn.getting_started.activities.paragraphs')
        as $paragraph
    )
        <p>{{ $paragraph }}</p>
    @endforeach

    <ul>
        @foreach (
            __('learn.getting_started.activities.items')
            as $item
        )
            <li>{{ $item }}</li>
        @endforeach
    </ul>

    <div class="guide-box">
        <strong>
            {{ __('learn.getting_started.activities.box_title') }}
        </strong>

        <p>
            {{ __('learn.getting_started.activities.box_text') }}
        </p>
    </div>

</section>

<section id="propostas" class="guide-section">

    <span class="guide-step">08</span>

    <h2>
        {{ __('learn.getting_started.proposals.title') }}
    </h2>

    @foreach (
        __('learn.getting_started.proposals.paragraphs')
        as $paragraph
    )
        <p>{{ $paragraph }}</p>
    @endforeach

    <h3>
        {{ __('learn.getting_started.proposals.subtitle') }}
    </h3>

    <ul>
        @foreach (
            __('learn.getting_started.proposals.items')
            as $item
        )
            <li>{{ $item }}</li>
        @endforeach
    </ul>

    <p>
        {{ __('learn.getting_started.proposals.after_list') }}
    </p>

</section>

<section id="venda" class="guide-section">

    <span class="guide-step">09</span>

    <h2>
        {{ __('learn.getting_started.sale.title') }}
    </h2>

    @foreach (
        __('learn.getting_started.sale.paragraphs')
        as $paragraph
    )
        <p>{{ $paragraph }}</p>
    @endforeach

    <div class="guide-flow">

        @foreach (
            __('learn.getting_started.sale.flow')
            as $item
        )

            <div class="guide-flow-item">
                {{ $item }}
            </div>

            @if (!$loop->last)
                <div class="guide-flow-arrow">↓</div>
            @endif

        @endforeach

    </div>

</section>

<section id="evoluir" class="guide-section">

    <span class="guide-step">10</span>

    <h2>
        {{ __('learn.getting_started.evolve.title') }}
    </h2>

    @foreach (
        __('learn.getting_started.evolve.paragraphs')
        as $paragraph
    )
        <p>{{ $paragraph }}</p>
    @endforeach

    <div class="guide-checklist">

        @foreach (
            __('learn.getting_started.evolve.checklist')
            as $item
        )

            <div class="guide-check">

                <span class="guide-check-icon">
                    ✓
                </span>

                <span>
                    {{ $item }}
                </span>

            </div>

        @endforeach

    </div>

    <div class="guide-next">

        <h2>
            {{ __('learn.getting_started.evolve.cta_title') }}
        </h2>

        <p>
            {{ __('learn.getting_started.evolve.cta_text') }}
        </p>

        <div class="guide-actions">

            <a
                href="{{ route('register') }}"
                class="button"
            >
                {{ __('learn.getting_started.evolve.trial_button') }}
            </a>

            <a
                href="{{ route('marketing.learn.index') }}"
                class="button button-secondary"
            >
                {{ __('learn.getting_started.evolve.guides_button') }}
            </a>

        </div>

    </div>

</section>

</main>

</div>
</section>

</div>

@endsection