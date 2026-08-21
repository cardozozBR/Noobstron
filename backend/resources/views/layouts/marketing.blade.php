<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <link
        rel="icon"
        type="image/png"
        href="{{ asset('brand/noobstron-symbol.png') }}"
    >

    <title>
        @yield('title', 'Noobstron')
    </title>

    <meta
        name="description"
        content="@yield(
            'meta_description',
            __('public.hero.description')
        )"
    >

    <link
        rel="canonical"
        href="@yield('canonical', url('/'))"
    >

    <meta
        property="og:title"
        content="@yield('title', 'Noobstron')"
    >

    <meta
        property="og:description"
        content="@yield(
            'meta_description',
            __('public.hero.description')
        )"
    >

    <meta
        property="og:type"
        content="website"
    >

    <meta
        property="og:url"
        content="@yield('canonical', url('/'))"
    >

    <meta
        name="twitter:card"
        content="summary"
    >

    <style>
        * {
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
            background: #ffffff;
            color: #111827;
        }

        a {
            color: inherit;
        }

        .shell {
            width: min(1120px, calc(100% - 40px));
            margin: 0 auto;
        }

        .site-header {
            position: sticky;
            top: 0;
            z-index: 20;
            border-bottom: 1px solid #e5e7eb;
            background: rgba(255,255,255,.96);
        }

        .nav {
            min-height: 72px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 24px;
        }

        .brand {
            font-size: 20px;
            font-weight: 800;
            text-decoration: none;
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 20px;
            flex-wrap: wrap;
        }

        .nav-links a {
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
        }

        .button {
            display: inline-block;
            padding: 12px 18px;
            border-radius: 10px;
            background: #111827;
            color: #ffffff;
            text-decoration: none;
            font-weight: 700;
        }

        .button-secondary {
            background: #ffffff;
            color: #111827;
            border: 1px solid #d1d5db;
        }

        .hero {
            padding: 96px 0 72px;
        }

        .hero-grid {
            display: grid;
            grid-template-columns: 1.25fr .75fr;
            gap: 48px;
            align-items: center;
        }

        .eyebrow {
            margin-bottom: 14px;
            font-size: 13px;
            font-weight: 800;
            letter-spacing: .08em;
            text-transform: uppercase;
            color: #4b5563;
        }

        h1 {
            margin: 0 0 20px;
            font-size: clamp(40px, 6vw, 68px);
            line-height: 1.02;
            letter-spacing: -.04em;
        }

        .lead {
            margin: 0 0 28px;
            max-width: 720px;
            font-size: 20px;
            line-height: 1.6;
            color: #4b5563;
        }

        .actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .hero-card {
            padding: 28px;
            border: 1px solid #e5e7eb;
            border-radius: 20px;
            background: #f9fafb;
        }

        .hero-card strong {
            display: block;
            margin-bottom: 12px;
            font-size: 18px;
        }

        .hero-card p {
            margin: 0;
            line-height: 1.6;
            color: #6b7280;
        }

        section {
            padding: 72px 0;
        }

        .section-muted {
            background: #f9fafb;
        }

        .section-heading {
            max-width: 720px;
            margin-bottom: 36px;
        }

        .section-heading h2 {
            margin: 0 0 12px;
            font-size: 34px;
        }

        .section-heading p {
            margin: 0;
            line-height: 1.7;
            color: #6b7280;
        }

        .grid-3 {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
        }

        .card {
            padding: 24px;
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            background: #ffffff;
        }

        .card h3 {
            margin: 0 0 10px;
        }

        .card p {
            margin: 0;
            color: #6b7280;
            line-height: 1.6;
        }

        .pricing-heading {
            max-width: none;
            display: flex;
            justify-content: space-between;
            gap: 28px;
            align-items: flex-end;
        }

        .pricing-heading > div:first-child {
            max-width: 720px;
        }

        .pricing-period {
            display: inline-flex;
            flex: 0 0 auto;
            padding: 4px;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            background: #f9fafb;
            color: #6b7280;
            font-size: 14px;
            font-weight: 700;
        }

        .pricing-period span {
            padding: 8px 14px;
            border-radius: 9px;
        }

        .pricing-period__active {
            background: #ffffff;
            color: #111827;
            box-shadow: 0 1px 2px rgba(17, 24, 39, .08);
        }

        .pricing-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 18px;
            align-items: stretch;
        }

        .pricing-card {
            position: relative;
            display: flex;
            min-width: 0;
            flex-direction: column;
            padding: 26px;
            border: 1px solid #e5e7eb;
            border-radius: 18px;
            background: #ffffff;
        }

        .pricing-card--featured {
            border: 2px solid #111827;
            box-shadow: 0 12px 30px rgba(17, 24, 39, .08);
        }

        .pricing-card h3 {
            margin: 0 0 10px;
            font-size: 22px;
        }

        .pricing-card p {
            color: #6b7280;
            line-height: 1.6;
        }

        .pricing-card .button {
            margin-top: auto;
            text-align: center;
        }

        .pricing-badge {
            display: inline-flex;
            align-self: flex-start;
            margin-bottom: 14px;
            padding: 6px 10px;
            border-radius: 999px;
            background: #111827;
            color: #ffffff;
            font-size: 12px;
            font-weight: 800;
        }

        .pricing-price {
            margin: 24px 0 0;
        }

        .pricing-price strong {
            color: #111827;
            font-size: 34px;
            letter-spacing: -.03em;
        }

        .pricing-price span {
            color: #6b7280;
        }

        .pricing-limit {
            margin: 18px 0 24px;
            font-size: 14px;
        }

        .pricing-enterprise {
            margin: 24px 0 0;
            color: #111827 !important;
            font-size: 24px;
            font-weight: 800;
        }

        .pricing-note {
            max-width: 760px;
            margin: 28px auto 0;
            color: #6b7280;
            font-size: 14px;
            line-height: 1.6;
            text-align: center;
        }

        .faq-list {
            display: grid;
            gap: 12px;
        }

        .faq-list summary {
            cursor: pointer;
            font-weight: 700;
        }

        .faq-list details[open] summary {
            margin-bottom: 12px;
        }

        .contact-form {
            max-width: 820px;
            padding: 28px;
            border: 1px solid #e5e7eb;
            border-radius: 18px;
            background: #f9fafb;
        }

        .contact-form__grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 18px;
            margin-bottom: 20px;
        }

        .contact-form__field {
            min-width: 0;
        }

        .contact-form__field--full {
            grid-column: 1 / -1;
        }

        .contact-form label {
            display: block;
            margin-bottom: 7px;
            font-size: 14px;
            font-weight: 700;
        }

        .contact-form input,
        .contact-form textarea {
            display: block;
            width: 100%;
            border: 1px solid #d1d5db;
            border-radius: 10px;
            background: #ffffff;
            padding: 11px 12px;
            color: #111827;
            font: inherit;
            line-height: 1.4;
        }

        .contact-form textarea {
            min-height: 132px;
            resize: vertical;
        }

        .contact-form input:focus,
        .contact-form textarea:focus {
            border-color: #111827;
            outline: 3px solid rgba(17, 24, 39, .10);
            outline-offset: 1px;
        }

        .contact-form button {
            border: 0;
            cursor: pointer;
            font: inherit;
        }

        .contact-form__note {
            margin: 14px 0 0;
            color: #6b7280;
            font-size: 14px;
            line-height: 1.5;
        }

        .footer {
            padding: 36px 0;
            border-top: 1px solid #e5e7eb;
            color: #6b7280;
            font-size: 14px;
        }

        @media (max-width: 960px) {
            .pricing-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 800px) {
            .hero-grid,
            .grid-3,
            .pricing-grid,
            .contact-form__grid {
                grid-template-columns: 1fr;
            }

            .pricing-heading {
                align-items: flex-start;
                flex-direction: column;
            }

            .contact-form__field--full {
                grid-column: auto;
            }

            .nav {
                padding: 16px 0;
                align-items: flex-start;
                flex-direction: column;
            }

            .hero {
                padding-top: 64px;
            }
        }
    </style>
</head>

<body>
    <header class="site-header">
        <div class="shell nav">
            <a
                href="{{ route('marketing.home') }}"
                class="brand"
                aria-label="Noobstron"
            >
                <img
                    src="{{ asset('brand/noobstron-logo.png') }}"
                    alt="Noobstron"
                    style="display:block;height:42px;width:auto;max-width:220px;"
                >
            </a>

            <nav class="nav-links">
                <a href="{{ route('marketing.home') }}#recursos">
                    {{ __('public.nav.resources') }}
                </a>

                <a href="{{ route('marketing.home') }}#precos">
                    {{ __('public.nav.pricing') }}
                </a>

                <a href="{{ route('marketing.home') }}#faq">
                    {{ __('public.nav.faq') }}
                </a>

                <a href="{{ route('marketing.home') }}#contato">
                    {{ __('public.nav.contact') }}
                </a>

                <form
                    method="GET"
                    action="{{ route('public.locale.update') }}"
                    style="display:flex;align-items:center;gap:6px;"
                >
                    <label
                        for="public-locale"
                        style="position:absolute;left:-9999px;"
                    >
                        {{ __('public.language.label') }}
                    </label>

                    <select
                        id="public-locale"
                        name="locale"
                        style="border:1px solid #d1d5db;border-radius:8px;padding:8px;background:white;"
                    >
                        @foreach (config('global.locales', []) as $localeCode => $localeName)
                            <option
                                value="{{ $localeCode }}"
                                @selected(app()->getLocale() === $localeCode)
                            >
                                {{ $localeName }}
                            </option>
                        @endforeach
                    </select>

                    <input
                        type="hidden"
                        name="return"
                        value="{{ request()->getRequestUri() }}"
                    >

                    <button
                        type="submit"
                        class="button button-secondary"
                        style="padding:8px 10px;"
                    >
                        {{ __('public.language.apply') }}
                    </button>
                </form>

                <a
                    href="{{ route('workspace.login') }}"
                    class="button"
                >
                    {{ __('public.nav.login') }}
                </a>
            </nav>
        </div>
    </header>

    @yield('content')

    <footer class="footer">
        <div
            class="shell"
            style="display:flex;justify-content:space-between;gap:16px;flex-wrap:wrap;align-items:center;"
        >
            <span>Noobstron</span>

            <span style="display:flex;gap:16px;flex-wrap:wrap;">
                <a href="{{ route('marketing.terms') }}">{{ __('legal.terms.title') }}</a>
                <a href="{{ route('marketing.privacy') }}">{{ __('legal.privacy.title') }}</a>
            </span>
        </div>
    </footer>
</body>
</html>