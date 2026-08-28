@extends('layouts.marketing')

@section('title', __('registration.title'))

@section(
    'meta_description',
    __('registration.meta_description')
)

@section('content')

<style>
.registration-page {
    padding-bottom: 72px;
}

.registration-page section {
    padding-top: 72px;
}

.registration-page .section-heading {
    max-width: 760px;
    margin-bottom: 36px;
}

.registration-page .section-heading h1 {
    margin: 0 0 18px;
    font-size: clamp(42px, 6vw, 64px);
    line-height: 1.02;
    letter-spacing: -.04em;
}

.registration-page .section-heading > p:last-child {
    margin: 0;
    max-width: 720px;
    font-size: 18px;
    line-height: 1.7;
    color: #6b7280;
}

.registration-page .registration-card {
    width: 100%;
    max-width: 760px;
    padding: 32px;
    border-radius: 20px;
    box-shadow: 0 12px 30px rgba(17, 24, 39, .06);
}

.registration-page .registration-errors {
    margin-bottom: 24px;
    padding: 16px 18px;
    border: 1px solid #fecaca;
    border-radius: 12px;
    background: #fef2f2;
    color: #991b1b;
}

.registration-page .registration-errors ul {
    margin: 10px 0 0;
    padding-left: 20px;
}

.registration-page .registration-form {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 22px;
}

.registration-page .registration-field {
    min-width: 0;
}

.registration-page .registration-field label {
    display: block;
    margin-bottom: 8px;
    font-size: 14px;
    font-weight: 700;
    color: #111827;
}

.registration-page .registration-field input,
.registration-page .registration-field select {
    display: block;
    width: 100%;
    min-height: 48px;
    margin: 0;
    padding: 11px 13px;
    border: 1px solid #d1d5db;
    border-radius: 10px;
    background: #ffffff;
    color: #111827;
    font: inherit;
    line-height: 1.4;
    transition:
        border-color .15s ease,
        box-shadow .15s ease,
        background-color .15s ease;
}

.registration-page .registration-field input::placeholder {
    color: #9ca3af;
}

.registration-page .registration-field input:hover,
.registration-page .registration-field select:hover {
    border-color: #9ca3af;
}

.registration-page .registration-field input:focus,
.registration-page .registration-field select:focus {
    outline: none;
    border-color: #111827;
    box-shadow: 0 0 0 3px rgba(17, 24, 39, .08);
}

.registration-page .registration-field:nth-child(1),
.registration-page .registration-field:nth-child(2),
.registration-page .registration-field:nth-child(3) {
    grid-column: 1 / -1;
}

.registration-page .field-error {
    margin: 7px 0 0;
    color: #b91c1c;
    font-size: 13px;
    line-height: 1.5;
}

.registration-page .field-help {
    margin: 8px 0 0;
    color: #6b7280;
    font-size: 13px;
    line-height: 1.5;
}

.registration-page .summary-card {
    grid-column: 1 / -1;
    margin: 4px 0 0;
    padding: 24px;
    border-radius: 16px;
    background: #f9fafb;
}

.registration-page .summary-card h3 {
    margin: 0;
    font-size: 18px;
}

.registration-page .summary-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 16px;
    margin-top: 18px;
}

.registration-page .summary-label {
    display: block;
    margin-bottom: 4px;
    color: #6b7280;
    font-size: 13px;
}

.registration-page .summary-note {
    grid-column: 1 / -1;
    margin: 4px 0 0;
    color: #6b7280;
    font-size: 14px;
    line-height: 1.6;
}

.registration-page .registration-footer {
    grid-column: 1 / -1;
    margin-top: 4px;
    padding-top: 22px;
    border-top: 1px solid #e5e7eb;
}

.registration-page .registration-footer label {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    margin-bottom: 18px;
    color: #374151;
    font-size: 14px;
    line-height: 1.55;
}

.registration-page .registration-footer input[type="checkbox"] {
    width: 18px;
    height: 18px;
    margin: 2px 0 0;
    accent-color: #111827;
    flex: 0 0 auto;
}

.registration-page .registration-footer .button,
.registration-page button[type="submit"] {
    min-height: 48px;
    padding: 12px 20px;
    border: 0;
    border-radius: 10px;
    background: #111827;
    color: #ffffff;
    font: inherit;
    font-weight: 700;
    cursor: pointer;
    transition:
        transform .15s ease,
        background-color .15s ease;
}

.registration-page .registration-footer .button:hover,
.registration-page button[type="submit"]:hover {
    background: #1f2937;
    transform: translateY(-1px);
}

.registration-page .registration-footer + p,
.registration-page .registration-footer p {
    color: #6b7280;
}

@media (max-width: 760px) {
    .registration-page section {
        padding-top: 48px;
    }

    .registration-page .section-heading h1 {
        font-size: clamp(38px, 11vw, 52px);
    }

    .registration-page .registration-card {
        padding: 22px;
    }

    .registration-page .registration-form {
        grid-template-columns: 1fr;
        gap: 18px;
    }

    .registration-page .registration-field,
    .registration-page .registration-field:nth-child(1),
    .registration-page .registration-field:nth-child(2),
    .registration-page .registration-field:nth-child(3),
    .registration-page .summary-card,
    .registration-page .registration-footer {
        grid-column: 1;
    }

    .registration-page .summary-grid {
        grid-template-columns: 1fr;
    }

    .registration-page .summary-note {
        grid-column: 1;
    }
}

.registration-page input[type="checkbox"] {
    width: 18px;
    height: 18px;
    min-height: 0;
    padding: 0;
    margin: 2px 0 0;
    border-radius: 4px;
    accent-color: #111827;
    flex: 0 0 auto;
}

/* Terms acceptance: checkbox and text on the same line */
.registration-page .registration-footer label {
    display: flex;
    align-items: center;
    justify-content: flex-start;
    gap: 10px;
    margin: 0 0 18px;
    font-size: 14px;
    line-height: 1.5;
    color: #374151;
    cursor: pointer;
}

.registration-page .registration-footer label input[type="checkbox"] {
    appearance: auto;
    -webkit-appearance: checkbox;
    display: block;
    width: 18px;
    height: 18px;
    min-width: 18px;
    min-height: 18px;
    max-width: 18px;
    max-height: 18px;
    margin: 0;
    padding: 0;
    flex: 0 0 18px;
    accent-color: #111827;
}

.registration-page .registration-footer label a {
    font-weight: 600;
    text-decoration: underline;
    text-underline-offset: 2px;
}
</style>

<div class="registration-page">
    <main class="shell">
        <section>
            <div class="section-heading">
                <p class="eyebrow">
                    {{ __('registration.eyebrow') }}
                </p>

                <h1>
                    {{ __('registration.heading') }}
                </h1>

                <p>
                    {{ __('registration.intro') }}
                </p>
            </div>

            <div class="card registration-card">
                @if ($errors->any())
                    <div
                        role="alert"
                        style="
                            margin-bottom:20px;
                            padding:14px 16px;
                            border:1px solid #fecaca;
                            border-radius:10px;
                            background:#fef2f2;
                            color:#991b1b;
                        "
                    >
                        <strong>
                            {{ __('registration.errors_title') }}
                        </strong>

                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form
                    method="POST"
                    action="{{ route('register.store') }}"
                >
                    @csrf
                    <div class="registration-form">
                        <div class="registration-field">
                            <label for="register-company">{{ __('registration.company') }}</label>

                            <input
                                id="register-company"
                                name="company_name"
                                type="text"
                                value="{{ old('company_name') }}"
                                autocomplete="organization"
                                required
                                
                            >
                            @error('company_name')
                                <p class="field-error">
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>
                        <div class="registration-field">
                            <label for="register-name">{{ __('registration.name') }}</label>

                            <input
                                id="register-name"
                                name="name"
                                type="text"
                                value="{{ old('name') }}"
                                autocomplete="name"
                                required
                                
                            >
                            @error('name')
                                <p class="field-error">
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                        <div class="registration-field">
                            <label for="register-email">{{ __('registration.email') }}</label>

                            <input
                                id="register-email"
                                name="email"
                                type="email"
                                value="{{ old('email') }}"
                                autocomplete="email"
                                required
                                
                            >
                            @error('email')
                                <p class="field-error">
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                        <div class="registration-field">
                            <label for="register-password">{{ __('registration.password') }}</label>

                            <input
                                id="register-password"
                                name="password"
                                type="password"
                                autocomplete="new-password"
                                required
                                
                            >
                            @error('password')
                                <p class="field-error">
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                        <div class="registration-field">
                            <label for="register-password-confirmation">{{ __('registration.password_confirmation') }}</label>

                            <input
                                id="register-password-confirmation"
                                name="password_confirmation"
                                type="password"
                                autocomplete="new-password"
                                required
                                
                            >
                            @error('password_confirmation')
                                <p class="field-error">
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                        <div class="registration-field">
                            <label for="register-country">{{ __('registration.country') }}</label>

                            <select
                                id="register-country"
                                name="country_code"
                                required
                                
                            >
                                @foreach ($countries as $countryCode => $countryName)
                                    <option
                                        value="{{ $countryCode }}"
                                        @selected(
                                            old(
                                                'country_code',
                                                $defaultCountry
                                            ) === $countryCode
                                        )
                                    >
                                        {{ $countryName }}
                                    </option>
                                @endforeach
                            </select>
                            @error('country_code')
                                <p class="field-error">
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>
                        <div class="registration-field">
                            <label for="register-locale">{{ __('registration.language') }}</label>

                            <select
                                id="register-locale"
                                name="locale"
                                required
                                
                            >
                                @foreach ($locales as $localeCode => $localeName)
                                    <option
                                        value="{{ $localeCode }}"
                                        @selected(
                                            old(
                                                'locale',
                                                $defaultLocale
                                            ) === $localeCode
                                        )
                                    >
                                        {{ $localeName }}
                                    </option>
                                @endforeach
                            </select>
                            @error('locale')
                                <p class="field-error">
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>
                        <div class="registration-field">
                            <label for="register-plan">{{ __('registration.plan') }}</label>

                            <select
                                id="register-plan"
                                name="plan_code"
                                required
                                
                            >
                                @foreach ($plans as $plan)
                                    <option
                                        value="{{ $plan['code'] }}"
                                        @selected(
                                            old(
                                                'plan_code',
                                                $defaultPlan
                                            ) === $plan['code']
                                        )
                                    >
                                        {{ $plan['name'] }}
                                    </option>
                                @endforeach
                            </select>
                            @error('plan_code')
                                <p class="field-error">
                                    {{ $message }}
                                </p>
                            @enderror

                            <p class="field-help">
                                {{ __('registration.enterprise_note') }}
                            </p>
                        </div>
                        <div class="card summary-card">
                            <strong>
                                {{ __('registration.summary') }}
                            </strong>

                            <div class="summary-grid">
                                <div>
                                    <span class="summary-label">
                                        {{ __('registration.selected_plan') }}
                                    </span>

                                    <strong>
                                        {{ collect($plans)->firstWhere('code', old('plan_code', $defaultPlan))['name'] ?? old('plan_code', $defaultPlan) }}
                                    </strong>
                                </div>

                                <div>
                                    <span class="summary-label">
                                        {{ __('registration.trial_period') }}
                                    </span>

                                    <strong>
                                        {{ __('registration.trial_days', ['days' => $trialDays]) }}
                                    </strong>
                                </div>
                            </div>

                            <p class="summary-note">
                                {{ __('registration.trial_note') }}
                            </p>
                        </div>
                        <div>
                                        <div class="registration-field">
                <label>
                    <input
                        type="checkbox"
                        name="terms_accepted"
                        value="1"
                        required
                        @checked(old('terms_accepted'))
                    >

                    Li e aceito os
                    <a
                        href="{{ route('marketing.terms') }}"
                        target="_blank"
                        rel="noopener noreferrer"
                    >Termos de Uso</a>
                    e a
                    <a
                        href="{{ route('marketing.privacy') }}"
                        target="_blank"
                        rel="noopener noreferrer"
                    >Política de Privacidade</a>.
                </label>

                @error('terms_accepted')
                    <p class="field-error">
                        {{ $message }}
                    </p>
                @enderror
            </div>
<button
                                type="submit"
                                class="button"
                            >{{ __('registration.create') }}</button>
                        </div>
                    </div>
                </form>
            </div>

            <p class="registration-footer">
                {{ __('registration.already_have_account') }}
                <a href="{{ route('workspace.login') }}">{{ __('registration.login') }}</a>
            </p>
        </section>
    </main>
</div>
@endsection
