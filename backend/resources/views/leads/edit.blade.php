@extends('layouts.app')

@section('content')
<div>
    <div>
        <h1>{{ __('leads.edit') }}</h1>

        <a href="{{ route('leads.index') }}">
            {{ __('leads.actions.back') }}
        </a>
    </div>

    <form
        method="POST"
        action="{{ route(
            'leads.update',
            $lead->id
        ) }}"
    >
        @csrf
        @method('PUT')

        @include('leads._form')

        <button type="submit">
            {{ __('leads.actions.save') }}
        </button>

        <a href="{{ route('leads.index') }}">
            {{ __('leads.actions.cancel') }}
        </a>
    </form>
</div>
@endsection


<section class="lead-conversion">
    <h2>{{ __('leads.conversion') }}</h2>

    @if ($lead->converted_customer_id)
        <p>
            <strong>{{ __('leads.converted') }}</strong>
        </p>

        @if ($lead->converted_at)
            <p>
                {{ __('leads.converted_at') }}:
                {{ $lead->converted_at->format('Y-m-d H:i') }}
            </p>
        @endif

        @if ($lead->convertedCustomer)
            <p>
                <a
                    href="{{ route(
                        'customers.show',
                        $lead->convertedCustomer->id
                    ) }}"
                >
                    {{ __('leads.view_customer') }}
                </a>
            </p>
        @endif
    @elseif (
        auth()->user()->hasPermission(
            \App\Enums\Permission::LEADS_UPDATE
        )
        && app(
            \App\Support\TenantCapabilities::class
        )->enabled(
            app(
                \App\Services\TenantContext::class
            )->get(),
            \App\Enums\Feature::LEADS
        )
        && app(
            \App\Support\TenantCapabilities::class
        )->enabled(
            app(
                \App\Services\TenantContext::class
            )->get(),
            \App\Enums\Feature::CUSTOMERS
        )
    )
        <p>{{ __('leads.convert_help') }}</p>

        <form
            method="POST"
            action="{{ route(
                'leads.convert',
                $lead->id
            ) }}"
        >
            @csrf

            <div>
                <label for="customer_type">
                    {{ __('leads.customer_type') }}
                </label>

                <select
                    id="customer_type"
                    name="customer_type"
                    required
                >
                    <option value="individual">
                        {{ __('leads.individual') }}
                    </option>

                    <option value="company">
                        {{ __('leads.company') }}
                    </option>
                </select>

                @error('customer_type')
                    <div>{{ $message }}</div>
                @enderror
            </div>

            <button type="submit">
                {{ __('leads.convert') }}
            </button>
        </form>
    @endif
</section>
