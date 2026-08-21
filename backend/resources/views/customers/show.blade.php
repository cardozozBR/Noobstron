@extends('layouts.app')

@section('content')


<style>
    .customer-show-page {
        display: grid;
        gap: 24px;
    }

    .customer-show-page .card {
        margin: 0;
        border: 1px solid #e5e7eb;
        border-radius: 16px;
        background: #fff;
        padding: 22px;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
    }

    .customer-show-page .section-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 18px;
        margin-bottom: 18px;
    }

    .customer-show-page h1 {
        margin: 0;
        font-size: 30px;
        line-height: 1.15;
        letter-spacing: -0.025em;
    }

    .customer-show-page h2 {
        margin: 0 0 14px;
        font-size: 20px;
        line-height: 1.2;
        color: #111827;
    }

    .customer-show-page h3 {
        margin: 22px 0 12px;
        padding-top: 18px;
        border-top: 1px solid #e5e7eb;
        font-size: 15px;
        color: #374151;
    }

    .customer-show-page p {
        color: #4b5563;
        line-height: 1.55;
    }

    .customer-show-page .actions {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }

    .customer-show-page .actions a,
    .customer-show-page .section-header a {
        display: inline-flex;
        min-height: 38px;
        align-items: center;
        justify-content: center;
        padding: 8px 12px;
        border: 1px solid #d1d5db;
        border-radius: 9px;
        background: #fff;
        color: #374151 !important;
        font-size: 13px;
        font-weight: 700;
        text-decoration: none;
        transition: background-color 160ms ease, border-color 160ms ease;
    }

    .customer-show-page .actions a:hover,
    .customer-show-page .section-header a:hover {
        background: #f9fafb;
        border-color: #cbd5e1;
    }

    .customer-show-page form {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 12px;
        margin-top: 12px;
        padding: 16px;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        background: #f9fafb;
    }

    .customer-show-page form + form {
        margin-top: 8px;
        padding-top: 0;
        border-top: 0;
        background: transparent;
        border-left: 0;
        border-right: 0;
        border-bottom: 0;
        border-radius: 0;
    }

    .customer-show-page form input:not([type="checkbox"]),
    .customer-show-page form select,
    .customer-show-page form textarea {
        width: 100%;
        min-height: 40px;
        padding: 9px 11px;
        border: 1px solid #d1d5db;
        border-radius: 9px;
        background: #fff;
        color: #111827;
        font: inherit;
        font-size: 13px;
        outline: none;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.03);
        transition: border-color 160ms ease, box-shadow 160ms ease;
    }

    .customer-show-page form textarea {
        min-height: 88px;
        resize: vertical;
    }

    .customer-show-page form input:not([type="checkbox"]):focus,
    .customer-show-page form select:focus,
    .customer-show-page form textarea:focus {
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.10);
    }

    .customer-show-page form label {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        color: #374151;
        font-size: 13px;
        font-weight: 600;
    }

    .customer-show-page form button {
        min-height: 38px;
        padding: 8px 12px;
        border: 1px solid #d1d5db;
        border-radius: 9px;
        background: #111827;
        color: #fff;
        font-size: 13px;
        font-weight: 700;
        cursor: pointer;
        transition: background-color 160ms ease, opacity 160ms ease;
    }

    .customer-show-page form button:hover {
        background: #1f2937;
    }

    .customer-show-page form[action*="destroy"] {
        display: flex;
        justify-content: flex-end;
    }

    .customer-show-page form[action*="destroy"] button {
        border-color: #fecaca;
        background: #fef2f2;
        color: #b91c1c;
    }

    .customer-show-page form[action*="destroy"] button:hover {
        background: #fee2e2;
    }

    .customer-show-page table {
        width: 100%;
        border-collapse: collapse;
        overflow: hidden;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        background: #fff;
    }

    .customer-show-page thead {
        background: #f9fafb;
    }

    .customer-show-page th {
        padding: 11px 13px;
        border-bottom: 1px solid #e5e7eb;
        color: #6b7280;
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 0.06em;
        text-align: left;
        text-transform: uppercase;
    }

    .customer-show-page td {
        padding: 12px 13px;
        border-bottom: 1px solid #f3f4f6;
        color: #4b5563;
        font-size: 13px;
        vertical-align: top;
    }

    .customer-show-page tbody tr:last-child td {
        border-bottom: 0;
    }

    .customer-show-page code {
        display: inline-flex;
        padding: 3px 7px;
        border: 1px solid #dbeafe;
        border-radius: 999px;
        background: #eff6ff;
        color: #1d4ed8;
        font-size: 11px;
        font-weight: 700;
    }

    .customer-show-page .customer-meta {
        display: grid;
        gap: 10px;
        margin-top: 16px;
        padding-top: 16px;
        border-top: 1px solid #e5e7eb;
    }

    @media (max-width: 800px) {
        .customer-show-page .section-header {
            flex-direction: column;
        }

        .customer-show-page form {
            grid-template-columns: 1fr;
        }

        .customer-show-page .card {
            padding: 18px;
        }
    }
</style>

<div class="customer-show-page">

<div class="card">
    <div class="section-header">
        <div>
            <h1>{{ $customer->name }}</h1>

            <p>
                {{ $customer->type->label() }}

                @if ($customer->legal_name)
                    — {{ $customer->legal_name }}
                @endif
            </p>
        </div>

        <div class="actions">
            @if (
                auth()->user()->hasPermission(
                    \App\Enums\Permission::CUSTOMERS_UPDATE
                )
            )
                <a
                    href="{{ route(
                        'customers.edit',
                        $customer->id
                    ) }}"
                >
                    {{ __('customers.edit') }}
                </a>
            @endif

            <a href="{{ route('customers.index') }}">
                {{ __('customers.back') }}
            </a>
        </div>
    </div>

    @if (session('success'))
        <div>
            {{ session('success') }}
        </div>
    @endif

    <div class="customer-meta">
    @if ($customer->tax_identifier)
        <p>
            {{ __('customers.tax_identifier') }}:
            {{ $customer->tax_identifier_type }}
            {{ $customer->tax_identifier }}
        </p>
    @endif

    <p>
        {{ __('customers.responsible') }}:
        {{ $customer->responsible?->name ?? __('customers.without_responsible') }}
    </p>

    @if ($customer->tags)
        <p>
            {{ __('customers.tags') }}:
            {{ implode(', ', $customer->tags) }}
        </p>
    @endif

    @if ($customer->notes)
        <p>
            {{ $customer->notes }}
        </p>
    @endif
    </div>
</div>

<div class="card">
    <h2>{{ __('customers.contacts') }}</h2>

    @forelse ($customer->contacts as $contact)
        <form
            method="POST"
            action="{{ route(
                'customers.contacts.update',
                [
                    $customer->id,
                    $contact->id,
                ]
            ) }}"
        >
            @csrf
            @method('PUT')

            <input
                name="name"
                value="{{ $contact->name }}"
                required
            >

            <input
                name="role"
                value="{{ $contact->role }}"
                placeholder="{{ __('customers.role') }}"
            >

            <select name="type">
                @foreach ($contactTypes as $type)
                    <option
                        value="{{ $type->value }}"
                        @selected(
                            $contact->type === $type
                        )
                    >
                        {{ $type->value }}
                    </option>
                @endforeach
            </select>

            <textarea
                name="notes"
            >{{ $contact->notes }}</textarea>

            <button type="submit">
                {{ __('customers.update_contact') }}
            </button>
        </form>

        <form
            method="POST"
            action="{{ route(
                'customers.contacts.destroy',
                [
                    $customer->id,
                    $contact->id,
                ]
            ) }}"
        >
            @csrf
            @method('DELETE')

            <button type="submit">
                {{ __('customers.delete_contact') }}
            </button>
        </form>
    @empty
        <p>{{ __('customers.no_contacts') }}</p>
    @endforelse

    <h3>{{ __('customers.new_contact') }}</h3>

    <form
        method="POST"
        action="{{ route(
            'customers.contacts.store',
            $customer->id
        ) }}"
    >
        @csrf

        <input
            name="name"
            placeholder="{{ __('customers.name') }}"
            required
        >

        <input
            name="role"
            placeholder="Cargo / função"
        >

        <select name="type">
            @foreach ($contactTypes as $type)
                <option value="{{ $type->value }}">
                    {{ $type->value }}
                </option>
            @endforeach
        </select>

        <textarea
            name="notes"
            placeholder="{{ __('customers.notes') }}"
        ></textarea>

        <button type="submit">
            {{ __('customers.add_contact') }}
        </button>
    </form>
</div>

<div class="card">
    <h2>{{ __('customers.phones') }}</h2>

    @forelse ($customer->phones as $phone)
        <form
            method="POST"
            action="{{ route(
                'customers.phones.update',
                [
                    $customer->id,
                    $phone->id,
                ]
            ) }}"
        >
            @csrf
            @method('PUT')

            <input
                name="label"
                value="{{ $phone->label }}"
                placeholder="{{ __('customers.label') }}"
            >

            <select name="country_code">
                @foreach ($countries as $countryCode)
                    <option
                        value="{{ $countryCode }}"
                        @selected(
                            $phone->country_code
                                === $countryCode
                        )
                    >
                        {{ $countryCode }}
                    </option>
                @endforeach
            </select>

            <input
                name="national_number"
                value="{{ $phone->national_number }}"
                required
            >

            <select name="customer_contact_id">
                <option value="">
                    {{ __('customers.without_contact') }}
                </option>

                @foreach ($customer->contacts as $contact)
                    <option
                        value="{{ $contact->id }}"
                        @selected(
                            $phone->customer_contact_id
                                === $contact->id
                        )
                    >
                        {{ $contact->name }}
                    </option>
                @endforeach
            </select>

            <label>
                <input
                    type="checkbox"
                    name="is_primary"
                    value="1"
                    @checked($phone->is_primary)
                >
                {{ __('customers.primary') }}
            </label>

            <button type="submit">
                {{ __('customers.update_phone') }}
            </button>
        </form>

        <form
            method="POST"
            action="{{ route(
                'customers.phones.destroy',
                [
                    $customer->id,
                    $phone->id,
                ]
            ) }}"
        >
            @csrf
            @method('DELETE')

            <button type="submit">
                {{ __('customers.delete_phone') }}
            </button>
        </form>
    @empty
        <p>{{ __('customers.no_phones') }}</p>
    @endforelse

    <h3>{{ __('customers.new_phone') }}</h3>

    <form
        method="POST"
        action="{{ route(
            'customers.phones.store',
            $customer->id
        ) }}"
    >
        @csrf

        <input
            name="label"
            placeholder="{{ __('customers.label') }}"
        >

        <select name="country_code">
            @foreach ($countries as $countryCode)
                <option value="{{ $countryCode }}">
                    {{ $countryCode }}
                </option>
            @endforeach
        </select>

        <input
            name="national_number"
            placeholder="{{ __('customers.phone') }}"
            required
        >

        <select name="customer_contact_id">
            <option value="">
                {{ __('customers.without_contact') }}
            </option>

            @foreach ($customer->contacts as $contact)
                <option value="{{ $contact->id }}">
                    {{ $contact->name }}
                </option>
            @endforeach
        </select>

        <label>
            <input
                type="checkbox"
                name="is_primary"
                value="1"
            >
            {{ __('customers.primary') }}
        </label>

        <button type="submit">
            {{ __('customers.add_phone') }}
        </button>
    </form>
</div>

<div class="card">
    <h2>{{ __('customers.emails') }}</h2>

    @forelse ($customer->emails as $email)
        <form
            method="POST"
            action="{{ route(
                'customers.emails.update',
                [
                    $customer->id,
                    $email->id,
                ]
            ) }}"
        >
            @csrf
            @method('PUT')

            <input
                name="label"
                value="{{ $email->label }}"
                placeholder="{{ __('customers.label') }}"
            >

            <input
                type="email"
                name="email"
                value="{{ $email->email }}"
                required
            >

            <select name="customer_contact_id">
                <option value="">
                    {{ __('customers.without_contact') }}
                </option>

                @foreach ($customer->contacts as $contact)
                    <option
                        value="{{ $contact->id }}"
                        @selected(
                            $email->customer_contact_id
                                === $contact->id
                        )
                    >
                        {{ $contact->name }}
                    </option>
                @endforeach
            </select>

            <label>
                <input
                    type="checkbox"
                    name="is_primary"
                    value="1"
                    @checked($email->is_primary)
                >
                {{ __('customers.primary') }}
            </label>

            <button type="submit">
                {{ __('customers.update_email') }}
            </button>
        </form>

        <form
            method="POST"
            action="{{ route(
                'customers.emails.destroy',
                [
                    $customer->id,
                    $email->id,
                ]
            ) }}"
        >
            @csrf
            @method('DELETE')

            <button type="submit">
                {{ __('customers.delete_email') }}
            </button>
        </form>
    @empty
        <p>{{ __('customers.no_emails') }}</p>
    @endforelse

    <h3>{{ __('customers.new_email') }}</h3>

    <form
        method="POST"
        action="{{ route(
            'customers.emails.store',
            $customer->id
        ) }}"
    >
        @csrf

        <input
            name="label"
            placeholder="{{ __('customers.label') }}"
        >

        <input
            type="email"
            name="email"
            placeholder="{{ __('customers.email') }}"
            required
        >

        <select name="customer_contact_id">
            <option value="">
                {{ __('customers.without_contact') }}
            </option>

            @foreach ($customer->contacts as $contact)
                <option value="{{ $contact->id }}">
                    {{ $contact->name }}
                </option>
            @endforeach
        </select>

        <label>
            <input
                type="checkbox"
                name="is_primary"
                value="1"
            >
            {{ __('customers.primary') }}
        </label>

        <button type="submit">
            {{ __('customers.add_email') }}
        </button>
    </form>
</div>

<div class="card">
    <h2>{{ __('customers.addresses') }}</h2>

    @forelse ($customer->addresses as $address)
        <form
            method="POST"
            action="{{ route(
                'customers.addresses.update',
                [
                    $customer->id,
                    $address->id,
                ]
            ) }}"
        >
            @csrf
            @method('PUT')

            <input
                name="label"
                value="{{ $address->label }}"
                placeholder="{{ __('customers.label') }}"
            >

            <select name="country_code">
                @foreach ($countries as $countryCode)
                    <option
                        value="{{ $countryCode }}"
                        @selected(
                            $address->country_code
                                === $countryCode
                        )
                    >
                        {{ $countryCode }}
                    </option>
                @endforeach
            </select>

            <input
                name="line1"
                value="{{ $address->line1 }}"
                required
            >

            <input
                name="line2"
                value="{{ $address->line2 }}"
            >

            <input
                name="city"
                value="{{ $address->city }}"
                required
            >

            <input
                name="region"
                value="{{ $address->region }}"
            >

            <input
                name="postal_code"
                value="{{ $address->postal_code }}"
            >

            <label>
                <input
                    type="checkbox"
                    name="is_primary"
                    value="1"
                    @checked($address->is_primary)
                >
                {{ __('customers.primary') }}
            </label>

            <button type="submit">
                {{ __('customers.update_address') }}
            </button>
        </form>

        <form
            method="POST"
            action="{{ route(
                'customers.addresses.destroy',
                [
                    $customer->id,
                    $address->id,
                ]
            ) }}"
        >
            @csrf
            @method('DELETE')

            <button type="submit">
                {{ __('customers.delete_address') }}
            </button>
        </form>
    @empty
        <p>{{ __('customers.no_addresses') }}</p>
    @endforelse

    <h3>{{ __('customers.new_address') }}</h3>

    <form
        method="POST"
        action="{{ route(
            'customers.addresses.store',
            $customer->id
        ) }}"
    >
        @csrf

        <input
            name="label"
            placeholder="{{ __('customers.label') }}"
        >

        <select name="country_code">
            @foreach ($countries as $countryCode)
                <option value="{{ $countryCode }}">
                    {{ $countryCode }}
                </option>
            @endforeach
        </select>

        <input
            name="line1"
            placeholder="{{ __('customers.address') }}"
            required
        >

        <input
            name="line2"
            placeholder="{{ __('customers.address_line2') }}"
        >

        <input
            name="city"
            placeholder="{{ __('customers.city') }}"
            required
        >

        <input
            name="region"
            placeholder="{{ __('customers.region') }}"
        >

        <input
            name="postal_code"
            placeholder="{{ __('customers.postal_code') }}"
        >

        <label>
            <input
                type="checkbox"
                name="is_primary"
                value="1"
            >
            {{ __('customers.primary') }}
        </label>

        <button type="submit">
            {{ __('customers.add_address') }}
        </button>
    </form>
</div>

<div class="card">
    <h2>{{ __('customers.history') }}</h2>

    <table>
        <thead>
            <tr>
                <th>{{ __('customers.date') }}</th>
                <th>{{ __('customers.user') }}</th>
                <th>{{ __('customers.event') }}</th>
                <th>{{ __('customers.description') }}</th>
            </tr>
        </thead>

        <tbody>
            @forelse (
                $customer->history
                    ->sortByDesc('created_at')
                as $entry
            )
                <tr>
                    <td>
                        {{ $entry->created_at->format(
                            'd/m/Y H:i:s'
                        ) }}
                    </td>

                    <td>
                        {{ $entry->user?->name ?? __('customers.system') }}
                    </td>

                    <td>
                        <code>
                            {{ $entry->event }}
                        </code>
                    </td>

                    <td>
                        {{ $entry->description }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4">
                        {{ __('customers.no_history') }}
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

</div>

@endsection