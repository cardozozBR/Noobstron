@extends('layouts.app')

@section('title', __('ui.profile.title'))

@section('content')
<style>
    .profile-page {
        display: grid;
        gap: 20px;
    }

    .profile-card {
        overflow: hidden;
    }

    .profile-form {
        display: grid;
        gap: 24px;
    }

    .profile-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 18px;
    }

    .profile-section {
        display: grid;
        gap: 18px;
    }

    .profile-section + .profile-section {
        padding-top: 24px;
        border-top: 1px solid #e5e7eb;
    }

    .profile-section .section-header {
        margin-bottom: 0;
    }

    .profile-section .section-header h2 {
        margin-bottom: 6px;
    }

    .profile-form .form-group {
        margin: 0;
    }

    .profile-form .form-actions {
        display: flex;
        justify-content: flex-end;
        padding-top: 22px;
        border-top: 1px solid #e5e7eb;
    }

    @media (max-width: 760px) {
        .profile-grid {
            grid-template-columns: 1fr;
        }

        .profile-form .form-actions .btn {
            width: 100%;
        }
    }
</style>

<div class="profile-page">
    <div class="page-header">
        <div>
            <span class="eyebrow">{{ __('ui.profile.eyebrow') }}</span>

            <h1>{{ __('ui.profile.heading') }}</h1>

            <p>
                {{ __('ui.profile.description') }}
            </p>
        </div>

        <div class="actions">
            <a href="{{ route('dashboard') }}" class="btn btn-secondary">
                {{ __('ui.profile.back_dashboard') }}
            </a>
        </div>
    </div>

    <div class="card profile-card">
        <form
            method="POST"
            action="{{ route('profile.update') }}"
            class="profile-form"
        >
            @csrf
            @method('PUT')

            <section class="profile-section">
                <div class="profile-grid">
                    <div class="form-group">
                        <label for="name" class="form-label">
                            {{ __('ui.profile.name') }}
                        </label>

                        <input
                            type="text"
                            id="name"
                            name="name"
                            value="{{ old('name', $user->name) }}"
                            required
                            class="form-control"
                        >
                    </div>

                    <div class="form-group">
                        <label for="email" class="form-label">
                            {{ __('ui.profile.email') }}
                        </label>

                        <input
                            type="email"
                            id="email"
                            name="email"
                            value="{{ old('email', $user->email) }}"
                            required
                            class="form-control"
                        >
                    </div>
                </div>
            </section>

            <section class="profile-section">
                <div class="section-header">
                    <div>
                        <span class="eyebrow">
                            {{ __('ui.profile.security') }}
                        </span>

                        <h2>{{ __('ui.profile.change_password') }}</h2>

                        <p>
                            {{ __('ui.profile.password_help') }}
                        </p>
                    </div>
                </div>

                <div class="profile-grid">
                    <div class="form-group">
                        <label for="password" class="form-label">
                            {{ __('ui.profile.new_password') }}
                        </label>

                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="form-control"
                            autocomplete="new-password"
                        >
                    </div>

                    <div class="form-group">
                        <label
                            for="password_confirmation"
                            class="form-label"
                        >
                            {{ __('ui.profile.confirm_password') }}
                        </label>

                        <input
                            type="password"
                            id="password_confirmation"
                            name="password_confirmation"
                            class="form-control"
                            autocomplete="new-password"
                        >
                    </div>
                </div>
            </section>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    {{ __('ui.profile.save') }}
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
