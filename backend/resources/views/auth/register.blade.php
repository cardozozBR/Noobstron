@extends('layouts.marketing')

@section('title', __('registration.title'))

@section(
    'meta_description',
    __('registration.meta_description')
)

@section('content')

<style>
.registration-page{padding-bottom:48px}
.registration-page .registration-card{max-width:680px;border-radius:18px}
.registration-page .registration-errors{margin-bottom:20px;padding:14px 16px;border:1px solid #fecaca;border-radius:10px;background:#fef2f2;color:#991b1b}
.registration-page .registration-errors ul{margin:8px 0 0;padding-left:20px}
.registration-page .registration-form{display:grid;gap:20px}
.registration-page .registration-field input,
.registration-page .registration-field select{display:block;width:100%;margin-top:8px}
.registration-page .field-error{margin:6px 0 0;color:#b91c1c;font-size:14px}
.registration-page .field-help{margin:8px 0 0;color:#6b7280;font-size:14px}
.registration-page .summary-card{margin:0}
.registration-page .summary-grid{display:grid;gap:12px;margin-top:16px}
.registration-page .summary-label{display:block;color:#6b7280}
.registration-page .summary-note{margin:12px 0 0;color:#6b7280}
.registration-page .registration-footer{margin-top:24px}
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
