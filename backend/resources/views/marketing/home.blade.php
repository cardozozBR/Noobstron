@extends('layouts.marketing')

@section('title', __('public.meta_title'))

@section(
    'meta_description',
    __('public.hero.description')
)

@section('canonical', url('/'))

@section('content')

<style>
.public-marketing-page .hero{padding:72px 0 64px}
.public-marketing-page .hero-grid{align-items:center;gap:48px}
.public-marketing-page .hero h1{max-width:760px;font-size:clamp(42px,6vw,72px);line-height:.98;letter-spacing:-.045em}
.public-marketing-page .hero .lead{max-width:700px;font-size:18px;line-height:1.65}
.public-marketing-page .hero-card{padding:28px;border:1px solid #e5e7eb;border-radius:20px;background:#fff;box-shadow:0 18px 50px rgba(15,23,42,.08)}
.public-marketing-page .grid-3{gap:18px}
.public-marketing-page .grid-3 .card{padding:22px;border-radius:16px;transition:transform 160ms ease,box-shadow 160ms ease,border-color 160ms ease}
.public-marketing-page .grid-3 .card:hover{transform:translateY(-2px);border-color:#d1d5db;box-shadow:0 12px 30px rgba(15,23,42,.06)}
.public-marketing-page .pricing-grid{gap:18px}
.public-marketing-page .pricing-card{position:relative;padding:26px;border-radius:18px}
.public-marketing-page .pricing-card--featured{transform:translateY(-4px);box-shadow:0 18px 45px rgba(37,99,235,.12)}
.public-marketing-page .pricing-price strong{font-size:36px;letter-spacing:-.035em}
.public-marketing-page .faq-list{display:grid;gap:12px}
.public-marketing-page .faq-list .card{padding:0;border-radius:14px;overflow:hidden}
.public-marketing-page .faq-list summary{padding:18px 20px;font-weight:700;cursor:pointer}
.public-marketing-page .faq-list details p{padding:0 20px 18px}
.public-marketing-page .contact-form{padding:24px;border:1px solid #e5e7eb;border-radius:18px;background:#fff;box-shadow:0 10px 30px rgba(15,23,42,.05)}
.public-marketing-page .contact-form input,
.public-marketing-page .contact-form textarea{width:100%;border:1px solid #d1d5db;border-radius:10px;padding:11px 12px;background:#fff;outline:none;transition:border-color 160ms ease,box-shadow 160ms ease}
.public-marketing-page .contact-form input:focus,
.public-marketing-page .contact-form textarea:focus{border-color:#2563eb;box-shadow:0 0 0 3px rgba(37,99,235,.10)}
.public-marketing-page .marketing-alert{margin-bottom:18px;padding:14px 16px;border-radius:10px;font-weight:600}
.public-marketing-page .marketing-alert--success{background:#dcfce7;color:#166534}
.public-marketing-page .marketing-alert--error{background:#fee2e2;color:#991b1b}
@media(max-width:800px){.public-marketing-page .hero{padding:48px 0}.public-marketing-page .pricing-card--featured{transform:none}}
</style>

<div class="public-marketing-page">
    <main>
        <section class="hero">
            <div class="shell hero-grid">
                <div>
                    <div class="eyebrow">
                        {{ __('public.hero.eyebrow') }}
                    </div>

                    <h1>
                        {{ __('public.hero.title') }}
                    </h1>

                    <p class="lead">
                        {{ __('public.hero.description') }}
                    </p>

                    <div class="actions">
                        <a
                            href="/register"
                            class="button"
                        >
                            {{ __('marketing.pricing.trial') }}
                        </a>

                        <a
                            href="#recursos"
                            class="button button-secondary"
                        >
                            {{ __('public.hero.resources') }}
                        </a>

                        <a
                            href="{{ route('workspace.login') }}"
                            class="button button-secondary"
                        >
                            {{ __('public.hero.login') }}
                        </a>
                    </div>
                </div>

                <aside class="hero-card">
                    <strong>
                        {{ __('public.hero.card_title') }}
                    </strong>

                    <p>
                        {{ __('public.hero.card_text') }}
                    </p>
                </aside>
            </div>
        </section>

        <section id="recursos" class="section-muted">
            <div class="shell">
                <div class="section-heading">
                    <h2>{{ __('marketing.resources.title') }}</h2>

                    
                </div>

                <div class="grid-3">
                    <article class="card">
                        <h3>{{ __('marketing.resources.cards.0.title') }}</h3>

                        <p>{{ __('marketing.resources.cards.0.text') }}</p>
                    </article>

                    <article class="card">
                        <h3>{{ __('marketing.resources.cards.1.title') }}</h3>

                        <p>{{ __('marketing.resources.cards.1.text') }}</p>
                    </article>

                    <article class="card">
                        <h3>{{ __('marketing.resources.cards.2.title') }}</h3>

                        <p>{{ __('marketing.resources.cards.2.text') }}</p>
                    </article>

                    <article class="card">
                        <h3>{{ __('marketing.resources.cards.3.title') }}</h3>

                        <p>{{ __('marketing.resources.cards.3.text') }}</p>
                    </article>

                    <article class="card">
                        <h3>{{ __('marketing.resources.cards.4.title') }}</h3>

                        <p>{{ __('marketing.resources.cards.4.text') }}</p>
                    </article>

                    <article class="card">
                        <h3>{{ __('marketing.resources.cards.5.title') }}</h3>

                        <p>{{ __('marketing.resources.cards.5.text') }}</p>
                    </article>
                </div>
            </div>
        </section>

        <section id="precos">
            <div class="shell">
                <div class="section-heading pricing-heading">
                    <div>
                        <div class="eyebrow">{{ __('marketing.pricing.eyebrow') }}</div>
                        <h2>{{ __('marketing.pricing.title') }}</h2>

                        <p>{{ __('marketing.pricing.subtitle') }}</p>
                    </div>

                    <div class="pricing-period" aria-label="{{ __('marketing.pricing.period_label') }}">
                        <span class="pricing-period__active">{{ __('marketing.pricing.monthly') }}</span>
                        <span>{{ __('marketing.pricing.annual') }}</span>
                    </div>
                </div>

                <div class="pricing-grid">
                    <article class="pricing-card">
                        <h3>Start</h3>

                        <p>{{ __('marketing.pricing.start_text') }}</p>

                        <p class="pricing-price">
                            <strong>R$ 99</strong>
                            <span>{{ __('marketing.pricing.per_month') }}</span>
                        </p>

                        <p class="pricing-limit">{{ __('marketing.pricing.start_limit') }}</p>

                        <a href="/register" class="button">{{ __('marketing.pricing.trial') }}</a>
                    </article>

                    <article class="pricing-card pricing-card--featured">
                        <div class="pricing-badge">
                            {{ __('marketing.pricing.featured') }}
                        </div>

                        <h3>Pro</h3>

                        <p>{{ __('marketing.pricing.pro_text') }}</p>

                        <p class="pricing-price">
                            <strong>R$ 249</strong>
                            <span>{{ __('marketing.pricing.per_month') }}</span>
                        </p>

                        <p class="pricing-limit">{{ __('marketing.pricing.pro_limit') }}</p>

                        <a href="/register" class="button">{{ __('marketing.pricing.trial') }}</a>
                    </article>

                    <article class="pricing-card">
                        <h3>Business</h3>

                        <p>{{ __('marketing.pricing.business_text') }}</p>

                        <p class="pricing-price">
                            <strong>R$ 499</strong>
                            <span>{{ __('marketing.pricing.per_month') }}</span>
                        </p>

                        <p class="pricing-limit">{{ __('marketing.pricing.business_limit') }}</p>

                        <a href="/register" class="button">{{ __('marketing.pricing.trial') }}</a>
                    </article>

                    <article class="pricing-card">
                        <h3>Enterprise</h3>

                        <p>{{ __('marketing.pricing.enterprise_text') }}</p>

                        <p class="pricing-enterprise">
                            {{ __('marketing.pricing.contact_us') }}
                        </p>

                        <p class="pricing-limit">{{ __('marketing.pricing.enterprise_limit') }}</p>

                        <a href="#contato" class="button button-secondary">{{ __('marketing.pricing.sales') }}</a>
                    </article>
                </div>

                <p class="pricing-note">
                    {{ __('marketing.pricing.note') }}
                </p>
            </div>
        </section>

        <section id="faq" class="section-muted">
            <div class="shell">
                <div class="section-heading">
                    <h2>{{ __('marketing.faq.title') }}</h2>

                    <p>
                        {{ __('marketing.faq.description') }}
                    </p>
                </div>

                <div class="faq-list">
                    <details class="card">
                        <summary>{{ __('marketing.faq.items.0.question') }}</summary>

                        <p>{{ __('marketing.faq.items.0.answer') }}</p>
                    </details>

                    <details class="card">
                        <summary>{{ __('marketing.faq.items.1.question') }}</summary>

                        <p>{{ __('marketing.faq.items.1.answer') }}</p>
                    </details>

                    <details class="card">
                        <summary>{{ __('marketing.faq.items.2.question') }}</summary>

                        <p>{{ __('marketing.faq.items.2.answer') }}</p>
                    </details>

                    <details class="card">
                        <summary>{{ __('marketing.faq.items.3.question') }}</summary>

                        <p>{{ __('marketing.faq.items.3.answer') }}</p>
                    </details>

                    <details class="card">
                        <summary>{{ __('marketing.faq.items.4.question') }}</summary>

                        <p>{{ __('marketing.faq.items.4.answer') }}</p>
                    </details>
                </div>
            </div>
        </section>

        <section id="contato">
            <div class="shell">
                <div class="section-heading">
                    <h2>{{ __('marketing.contact.title') }}</h2>

                    <p>{{ __('marketing.contact.description') }}</p>
                </div>

                @if (session('contact_status') === 'sent')
                    <div
                        role="status"
                        class="marketing-alert marketing-alert--success"
                    >
                        {{ __('marketing.contact.success') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div
                        role="alert"
                        class="marketing-alert marketing-alert--error"
                    >
                        {{ $errors->first() }}
                    </div>
                @endif

                <form
                    class="contact-form"
                    aria-label="{{ __('marketing.contact.form_label') }}"
                    method="POST"
                    action="{{ route('marketing.contact.store') }}"
                >
                    @csrf
                    <div class="contact-form__grid">
                        <div class="contact-form__field">
                            <label for="contact-name">{{ __('marketing.contact.name') }}</label>
                            <input
                                id="contact-name"
                                name="name"
                                type="text"
                                value="{{ old('name') }}"
                                autocomplete="name"
                                required
                            >
                        </div>

                        <div class="contact-form__field">
                            <label for="contact-email">{{ __('marketing.contact.email') }}</label>
                            <input
                                id="contact-email"
                                name="email"
                                type="email"
                                value="{{ old('email') }}"
                                autocomplete="email"
                                required
                            >
                        </div>

                        <div class="contact-form__field contact-form__field--full">
                            <label for="contact-company">{{ __('marketing.contact.company') }}</label>
                            <input
                                id="contact-company"
                                name="company"
                                type="text"
                                value="{{ old('company') }}"
                                autocomplete="organization"
                            >
                        </div>

                        <div class="contact-form__field contact-form__field--full">
                            <label for="contact-message">{{ __('marketing.contact.message') }}</label>
                            <textarea
                                id="contact-message"
                                name="message"
                                rows="5"
                                required
                            >{{ old('message') }}</textarea>
                        </div>
                    </div>

                    <button type="submit" class="button button--primary">{{ __('marketing.contact.send') }}</button>

                    <p class="contact-form__note">
                        {{ __('marketing.contact.note') }}
                    </p>
                </form>
            </div>
        </section>
    </main>

</div>
@endsection