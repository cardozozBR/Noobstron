@extends('layouts.marketing')

@section('title', __('learn.sales.meta_title'))

@section(
    'meta_description',
    __('learn.sales.meta_description')
)

@section('content')

<style>
.guide-page{padding-bottom:80px}
.guide-page .container{
    width:min(1180px,calc(100% - 48px));
    margin-left:auto;
    margin-right:auto
}
.guide-hero{padding:64px 0 48px}
.guide-hero-inner{max-width:860px}
.guide-back{
    display:inline-flex;
    margin-bottom:24px;
    color:#64748b;
    font-size:14px;
    font-weight:700;
    text-decoration:none
}
.guide-eyebrow{
    display:inline-block;
    margin-bottom:14px;
    color:var(--primary);
    font-size:14px;
    font-weight:800;
    letter-spacing:.08em;
    text-transform:uppercase
}
.guide-title{
    max-width:850px;
    margin:0;
    font-size:clamp(38px,6vw,64px);
    line-height:1.04;
    letter-spacing:-.045em
}
.guide-lead{
    max-width:760px;
    margin:22px 0 0;
    color:#64748b;
    font-size:19px;
    line-height:1.75
}
.guide-layout{
    display:grid;
    grid-template-columns:minmax(0,240px) minmax(0,760px);
    gap:54px;
    align-items:start
}
.guide-nav{
    position:sticky;
    top:24px;
    padding:20px;
    border:1px solid #e2e8f0;
    border-radius:16px;
    background:#fff
}
.guide-nav-title{
    display:block;
    margin-bottom:14px;
    font-size:14px;
    font-weight:800
}
.guide-nav a{
    display:block;
    padding:8px 0;
    color:#64748b;
    font-size:14px;
    text-decoration:none
}
.guide-nav a:hover{color:var(--primary)}
.guide-section{
    padding:18px 0 54px;
    border-bottom:1px solid #e2e8f0;
    scroll-margin-top:32px
}
.guide-section h2{
    margin:0 0 16px;
    font-size:31px;
    letter-spacing:-.03em
}
.guide-section h3{
    margin:30px 0 12px;
    font-size:20px
}
.guide-section p{
    margin:0 0 16px;
    color:#475569;
    line-height:1.8
}
.guide-section ul{
    margin:16px 0 22px;
    padding-left:22px;
    color:#475569
}
.guide-section li{
    margin-bottom:9px;
    line-height:1.65
}
.guide-step{
    display:inline-flex;
    min-width:38px;
    height:38px;
    align-items:center;
    justify-content:center;
    margin-bottom:16px;
    border-radius:999px;
    background:#eff6ff;
    color:var(--primary);
    font-size:14px;
    font-weight:800
}
.guide-box{
    margin:24px 0;
    padding:22px;
    border-radius:16px;
    background:#f8fafc
}
.guide-box strong{
    display:block;
    margin-bottom:8px
}
.guide-example{
    margin:24px 0;
    padding:22px;
    border-left:4px solid var(--primary);
    border-radius:0 14px 14px 0;
    background:#eff6ff
}
.guide-example strong{
    display:block;
    margin-bottom:8px
}
.guide-checklist{
    display:grid;
    gap:10px;
    margin-top:22px
}
.guide-check{
    display:flex;
    gap:12px;
    padding:15px;
    border-radius:12px;
    background:#f8fafc
}
.guide-check-icon{
    font-weight:800;
    color:#16a34a
}
.guide-next{
    margin-top:54px;
    padding:38px;
    border-radius:22px;
    background:#0f172a;
    color:#fff
}
.guide-next h2{
    margin:0 0 12px;
    font-size:30px
}
.guide-next p{
    max-width:620px;
    margin:0 0 24px;
    color:#cbd5e1;
    line-height:1.7
}
.guide-actions{
    display:flex;
    flex-wrap:wrap;
    gap:12px
}
@media(max-width:900px){
    .guide-layout{grid-template-columns:1fr}
    .guide-nav{position:static}
}
@media(max-width:600px){
    .guide-next{padding:28px 20px}
    .guide-page .container{
        width:min(100% - 28px,1180px)
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
        {{ __('learn.sales.back') }}
    </a>

    <span class="guide-eyebrow">
        {{ __('learn.sales.eyebrow') }}
    </span>

    <h1 class="guide-title">
        {{ __('learn.sales.title') }}
    </h1>

    <p class="guide-lead">
        {{ __('learn.sales.lead') }}
    </p>

</div>
</section>

<section>
<div class="container guide-layout">

<aside class="guide-nav">

    <span class="guide-nav-title">
        {{ __('learn.sales.nav_title') }}
    </span>

    @foreach(__('learn.sales.nav') as $anchor => $label)
        <a href="#{{ $anchor }}">
            {{ $label }}
        </a>
    @endforeach

</aside>

<main>

@foreach(__('learn.sales.sections') as $index => $section)

<section
    id="{{ $section['id'] }}"
    class="guide-section"
>

    <span class="guide-step">
        {{ str_pad(
            (string) ($index + 1),
            2,
            '0',
            STR_PAD_LEFT
        ) }}
    </span>

    <h2>{{ $section['title'] }}</h2>

    @foreach($section['paragraphs'] ?? [] as $paragraph)
        <p>{{ $paragraph }}</p>
    @endforeach

    @if (!empty($section['subtitle']))
        <h3>{{ $section['subtitle'] }}</h3>
    @endif

    @if (!empty($section['items']))
        <ul>
            @foreach($section['items'] as $item)
                <li>{{ $item }}</li>
            @endforeach
        </ul>
    @endif

    @if (!empty($section['after_list']))
        <p>{{ $section['after_list'] }}</p>
    @endif

    @if (!empty($section['box_title']))
        <div class="guide-box">
            <strong>{{ $section['box_title'] }}</strong>
            <p>{{ $section['box_text'] }}</p>
        </div>
    @endif

    @if (!empty($section['example_title']))
        <div class="guide-example">
            <strong>{{ $section['example_title'] }}</strong>
            {{ $section['example_text'] }}
        </div>
    @endif

</section>

@endforeach

<section class="guide-section">

    <span class="guide-step">✓</span>

    <h2>{{ __('learn.sales.checklist_title') }}</h2>

    <p>{{ __('learn.sales.checklist_description') }}</p>

    <div class="guide-checklist">

        @foreach(__('learn.sales.checklist') as $item)
            <div class="guide-check">
                <span class="guide-check-icon">✓</span>
                <span>{{ $item }}</span>
            </div>
        @endforeach

    </div>

    <div class="guide-next">

        <h2>{{ __('learn.sales.cta.title') }}</h2>

        <p>{{ __('learn.sales.cta.description') }}</p>

        <div class="guide-actions">

            <a
                href="{{ route('register') }}"
                class="button"
            >
                {{ __('learn.sales.cta.trial') }}
            </a>

            <a
                href="{{ route('marketing.learn.customers') }}"
                class="button button-secondary"
            >
                {{ __('learn.sales.cta.previous') }}
            </a>

        </div>

    </div>

</section>

</main>

</div>
</section>

</div>

@endsection