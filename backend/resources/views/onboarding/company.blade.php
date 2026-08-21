@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="card">
            <h1>{{ __('onboarding.title') }}</h1>

            <p>
                {{ __('onboarding.intro') }}
            </p>

            @if ($errors->any())
                <div>
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form
                method="POST"
                action="{{ route('onboarding.company.update') }}"
            >
                @csrf
                @method('PUT')

                <div style="display: grid; gap: 20px;">
                    <div>
                        <label for="onboarding-company-name">
                            {{ __('onboarding.company') }}
                        </label>

                        <input
                            id="onboarding-company-name"
                            name="company_name"
                            type="text"
                            value="{{ old('company_name', $tenant->name) }}"
                            autocomplete="organization"
                            required
                            style="display: block; width: 100%; margin-top: 8px;"
                        >
                    </div>

                    <div>
                        <label for="onboarding-segment">
                            {{ __('onboarding.segment') }}
                        </label>

                        <select
                            id="onboarding-segment"
                            name="segment"
                            style="display: block; width: 100%; margin-top: 8px;"
                        >
                            <option value="">
                                {{ __('onboarding.select_segment') }}
                            </option>

                            @foreach ($segments as $value => $label)
                                <option
                                    value="{{ $value }}"
                                    @selected(
                                        old(
                                            'segment',
                                            $tenant->segment
                                        ) === $value
                                    )
                                >
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <strong>{{ __('onboarding.country') }}</strong>

                        <p>
                            {{ $countryName }}
                        </p>

                        <small>
                            {{ $tenant->country_code }}
                        </small>
                    </div>

                    <div>
                        <strong>{{ __('onboarding.language') }}</strong>

                        <p>
                            {{ $localeName }}
                        </p>

                        <small>
                            {{ $tenant->locale }}
                        </small>
                    </div>

                    <div>
                        <h2>{{ __('onboarding.team') }}</h2>

                        <p>
                            {{ __('onboarding.team_description') }}
                        </p>

                        <div style="display: grid; gap: 12px; margin-top: 16px;">
                            @foreach ($teamMembers as $member)
                                <div class="card">
                                    <strong>
                                        {{ $member->name }}
                                    </strong>

                                    <p style="margin: 4px 0 0;">
                                        {{ $member->email }}
                                    </p>
                                </div>
                            @endforeach
                        </div>

                        <div style="margin-top: 16px;">
                            <a
                                href="{{ route('users.create') }}"
                                class="button button-secondary"
                            >
                                {{ __('onboarding.add_person') }}
                            </a>
                        </div>
                    </div>
                    <div>
                        <h2>{{ __('onboarding.initial_pipeline') }}</h2>

                        <p>
                            {{ __('onboarding.pipeline_description') }}
                        </p>

                        @if ($defaultPipeline)
                            <div class="card" style="margin-top: 16px;">
                                <strong>
                                    {{ $defaultPipeline->name }}
                                </strong>

                                @if ($defaultPipeline->description)
                                    <p style="margin: 8px 0 0;">
                                        {{ $defaultPipeline->description }}
                                    </p>
                                @endif

                                @if ($defaultPipeline->stages->isNotEmpty())
                                    <ul style="margin: 12px 0 0;">
                                        @foreach ($defaultPipeline->stages as $stage)
                                            <li>
                                                {{ $stage->name }}
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif
                            </div>
                        @else
                            <p style="margin-top: 16px;">
                                {{ __('onboarding.no_default_pipeline') }}
                            </p>
                        @endif

                        <div style="margin-top: 16px;">
                            <a
                                href="{{ route('pipelines.index') }}"
                                class="button button-secondary"
                            >
                                {{ __('onboarding.configure_pipeline') }}
                            </a>
                        </div>
                    </div>
                    <div>
                        <h2>{{ __('onboarding.import') }}</h2>

                        <p>
                            {{ __('onboarding.import_description') }}
                        </p>

                        <div style="margin-top: 16px;">
                            <a
                                href="{{ route('imports.create') }}"
                                class="button button-secondary"
                            >
                                {{ __('onboarding.import_data') }}
                            </a>
                        </div>
                    </div>
                    <div>
                        <h2>{{ __('onboarding.checklist') }}</h2>

                        <p>
                            {{ __('onboarding.checklist_description') }}
                        </p>

                        <div style="display: grid; gap: 12px; margin-top: 16px;">
                            @foreach ($checklist as $item)
                                <div class="card">
                                    <strong>
                                        {{ $item['label'] }}
                                    </strong>

                                    <p style="margin: 4px 0 0;">
                                        @if ($item['status'] === true)
                                            {{ __('onboarding.completed') }}
                                        @elseif ($item['status'] === null)
                                            {{ __('onboarding.optional') }}
                                        @else
                                            {{ __('onboarding.pending') }}
                                        @endif
                                    </p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <div>
                        <h2>{{ __('onboarding.first_value') }}</h2>

                        <p>
                            {{ __('onboarding.first_value_description') }}
                        </p>

                        <div style="margin-top: 16px;">
                            <a
                                href="{{ route('leads.create') }}"
                                class="button"
                            >
                                {{ __('onboarding.create_first_lead') }}
                            </a>
                        </div>
                    </div>
                    <div>
                        <button
                            type="submit"
                            class="button"
                        >
                            {{ __('onboarding.save_continue') }}
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection