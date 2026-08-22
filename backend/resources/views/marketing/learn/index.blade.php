@extends('layouts.marketing')

@section('title', __('learn.meta_title'))

@section(
    'meta_description',
    __('learn.meta_description')
)

@section('content')

<style>
.learn-page{padding-bottom:72px}

.learn-page .container{
    width:min(1180px, calc(100% - 48px));
    margin-left:auto;
    margin-right:auto;
}

.learn-hero{
    padding:72px 0 56px;
    text-align:center
}

.learn-hero-inner{
    max-width:820px;
    margin:0 auto
}

.learn-eyebrow{
    display:inline-block;
    margin-bottom:14px;
    font-size:14px;
    font-weight:700;
    letter-spacing:.08em;
    text-transform:uppercase;
    color:var(--primary)
}

.learn-title{
    margin:0;
    font-size:clamp(36px,6vw,62px);
    line-height:1.05;
    letter-spacing:-.04em
}

.learn-description{
    max-width:720px;
    margin:20px auto 0;
    font-size:19px;
    line-height:1.7;
    color:#64748b
}

.learn-section{padding:36px 0}

.learn-section-header{
    max-width:720px;
    margin-bottom:28px
}

.learn-section-header h2{
    margin:0 0 10px;
    font-size:30px;
    letter-spacing:-.03em
}

.learn-section-header p{
    margin:0;
    color:#64748b;
    line-height:1.7
}

.learn-grid{
    display:grid;
    grid-template-columns:repeat(3,minmax(0,1fr));
    gap:20px
}

.learn-card{
    display:flex;
    flex-direction:column;
    min-height:240px;
    padding:24px;
    border:1px solid #e2e8f0;
    border-radius:18px;
    background:#fff;
    text-decoration:none;
    color:inherit;
    transition:.2s ease
}

.learn-card:hover{
    transform:translateY(-3px);
    border-color:#cbd5e1;
    box-shadow:0 18px 40px rgba(15,23,42,.08)
}

.learn-card-number{
    display:inline-flex;
    width:36px;
    height:36px;
    align-items:center;
    justify-content:center;
    margin-bottom:18px;
    border-radius:999px;
    background:#eff6ff;
    color:var(--primary);
    font-size:14px;
    font-weight:800
}

.learn-card h3{
    margin:0 0 10px;
    font-size:21px
}

.learn-card p{
    margin:0;
    color:#64748b;
    line-height:1.65
}

.learn-card-link{
    margin-top:auto;
    padding-top:20px;
    font-weight:700;
    color:var(--primary)
}

.learn-path{
    display:grid;
    grid-template-columns:repeat(6,minmax(0,1fr));
    gap:12px
}

.learn-path-step{
    padding:20px 14px;
    border-radius:14px;
    background:#f8fafc;
    text-align:center
}

.learn-path-step strong{
    display:block;
    margin-bottom:6px
}

.learn-path-step span{
    color:#64748b;
    font-size:14px
}

.learn-cta{
    margin-top:48px;
    padding:40px;
    border-radius:22px;
    background:#0f172a;
    color:white;
    text-align:center
}

.learn-cta h2{
    margin:0;
    font-size:32px
}

.learn-cta p{
    max-width:620px;
    margin:14px auto 24px;
    color:#cbd5e1;
    line-height:1.7
}

@media(max-width:900px){
    .learn-grid{
        grid-template-columns:1fr 1fr
    }

    .learn-path{
        grid-template-columns:repeat(3,minmax(0,1fr))
    }
}

@media(max-width:640px){
    .learn-grid,
    .learn-path{
        grid-template-columns:1fr
    }

    .learn-hero{
        padding-top:48px
    }

    .learn-cta{
        padding:30px 20px
    }

    .learn-page .container{
        width:min(100% - 28px,1180px)
    }
}
</style>

<div class="learn-page">

<section class="learn-hero">
    <div class="container learn-hero-inner">

        <span class="learn-eyebrow">
            {{ __('learn.index.eyebrow') }}
        </span>

        <h1 class="learn-title">
            {{ __('learn.index.title') }}
        </h1>

        <p class="learn-description">
            {{ __('learn.index.description') }}
        </p>

    </div>
</section>

<section class="learn-section">
<div class="container">

    <div class="learn-section-header">

        <h2>
            {{ __('learn.index.start_title') }}
        </h2>

        <p>
            {{ __('learn.index.start_description') }}
        </p>

    </div>

    <div class="learn-grid">

        @foreach (
            __('learn.index.cards')
            as $key => $card
        )

            @if (in_array(
                $key,
                [
                    'getting_started',
                    'customers',
                    'sales',
                    'follow_up',
                ],
                true
            ))

                <a
                    href="{{ route(
                        match ($key) {
                            'getting_started' =>
                                'marketing.learn.getting-started',

                            'customers' =>
                                'marketing.learn.customers',

                            'sales' =>
                                'marketing.learn.sales',

                            'follow_up' =>
                                'marketing.learn.follow-up',
                        }
                    ) }}"
                    class="learn-card"
                >

                    <span class="learn-card-number">
                        {{ $card['number'] }}
                    </span>

                    <h3>
                        {{ $card['title'] }}
                    </h3>

                    <p>
                        {{ $card['description'] }}
                    </p>

                    <span class="learn-card-link">
                        {{ $card['action'] }}
                    </span>

                </a>

            @else

                <div class="learn-card">

                    <span class="learn-card-number">
                        {{ $card['number'] }}
                    </span>

                    <h3>
                        {{ $card['title'] }}
                    </h3>

                    <p>
                        {{ $card['description'] }}
                    </p>

                    <span class="learn-card-link">
                        {{ $card['action'] }}
                    </span>

                </div>

            @endif

        @endforeach

    </div>

</div>
</section>

<section class="learn-section">
<div class="container">

    <div class="learn-section-header">

        <h2>
            {{ __('learn.index.path_title') }}
        </h2>

        <p>
            {{ __('learn.index.path_description') }}
        </p>

    </div>

    <div class="learn-path">

        @foreach (
            __('learn.index.path')
            as $step
        )

            <div class="learn-path-step">

                <strong>
                    {{ $step['title'] }}
                </strong>

                <span>
                    {{ $step['description'] }}
                </span>

            </div>

        @endforeach

    </div>

    <div class="learn-cta">

        <h2>
            {{ __('learn.index.cta.title') }}
        </h2>

        <p>
            {{ __('learn.index.cta.description') }}
        </p>

        <a
            href="{{ route('register') }}"
            class="button"
        >
            {{ __('learn.index.cta.button') }}
        </a>

    </div>

</div>
</section>

</div>

@endsection