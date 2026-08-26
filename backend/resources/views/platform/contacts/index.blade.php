@extends('platform.layout')

@section('title', __('platform.contacts.title'))

@section('body')

<style>
.platform-contacts-page .platform-toolbar {
    align-items: flex-end;
    gap: 18px;
}

.platform-contacts-page .platform-toolbar h1 {
    margin: 4px 0 0;
    font-size: 32px;
    letter-spacing: -.03em;
}

.platform-contacts-page .platform-card {
    border-radius: 16px;
}

.platform-contacts-page .platform-table td {
    vertical-align: top;
}

.platform-contacts-page .contact-message {
    max-width: 420px;
    white-space: pre-wrap;
    line-height: 1.5;
}

.platform-contacts-page .contacts-filter {
    display: flex;
    align-items: flex-end;
    gap: 12px;
    padding: 18px;
    border-bottom: 1px solid rgba(148, 163, 184, .2);
}

.platform-contacts-page .contacts-filter__field,
.platform-contacts-page .conversion-form {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.platform-contacts-page .contacts-filter select,
.platform-contacts-page .contact-status-form select,
.platform-contacts-page .conversion-form select {
    min-height: 38px;
}

.platform-contacts-page .contact-status-form,
.platform-contacts-page .conversion-form {
    margin: 0;
}

.platform-contacts-page .converted-tenant {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.platform-contacts-page .pagination-wrap {
    margin-top: 16px;
}

@media (max-width: 800px) {
    .platform-contacts-page .platform-toolbar {
        align-items: flex-start;
        flex-direction: column;
    }

    .platform-contacts-page .contacts-filter {
        align-items: stretch;
        flex-direction: column;
    }
}
</style>

<div class="platform-contacts-page">
    <header class="platform-header">
        <div class="platform-header__inner">
            <a
                class="platform-brand"
                href="{{ route('platform.dashboard') }}"
            >
                {{ __('platform.brand') }}
            </a>
        </div>
    </header>

    <main class="platform-main">
        <div class="platform-toolbar">
            <div>
                <div class="platform-muted">
                    {{ __('platform.contacts.section') }}
                </div>

                <h1>
                    {{ __('platform.contacts.title') }}
                </h1>

                <p class="platform-muted">
                    {{ __('platform.contacts.description') }}
                </p>
            </div>

            <a
                class="button"
                href="{{ route('platform.dashboard') }}"
            >
                {{ __('platform.back_dashboard') }}
            </a>
        </div>

        <div class="platform-card">
            <form
                method="GET"
                action="{{ route('platform.contacts.index') }}"
                class="contacts-filter"
            >
                <div class="contacts-filter__field">
                    <label for="contact-status-filter">
                        {{ __('platform.contacts.filter_status') }}
                    </label>

                    <select
                        id="contact-status-filter"
                        name="status"
                        onchange="this.form.submit()"
                    >
                        <option value="">
                            {{ __('platform.contacts.all_statuses') }}
                        </option>

                        @foreach ($statuses as $item)
                            <option
                                value="{{ $item->value }}"
                                @selected(
                                    $status === $item->value
                                )
                            >
                                {{ $item->label() }}
                            </option>
                        @endforeach
                    </select>
                </div>

                @if ($status !== '')
                    <a
                        class="button"
                        href="{{ route('platform.contacts.index') }}"
                    >
                        {{ __('platform.contacts.all_statuses') }}
                    </a>
                @endif
            </form>

            <div class="table-wrap">
                <table class="platform-table">
                    <thead>
                        <tr>
                            <th>{{ __('platform.contacts.date') }}</th>
                            <th>{{ __('platform.contacts.name') }}</th>
                            <th>{{ __('platform.contacts.company') }}</th>
                            <th>{{ __('platform.email') }}</th>
                            <th>{{ __('platform.contacts.status') }}</th>
                            <th>{{ __('platform.contacts.tenant') }}</th>
                            <th>{{ __('platform.contacts.contracted_plan') }}</th>
                            <th>{{ __('platform.contacts.message') }}</th>
                        </tr>
                    </thead>

                    <tbody>
                    @forelse ($contacts as $contact)
                        <tr>
                            <td>
                                {{ $contact->created_at?->format('d/m/Y H:i') }}
                            </td>

                            <td>{{ $contact->name }}</td>

                            <td>
                                {{ $contact->company ?: '—' }}
                            </td>

                            <td>
                                <a href="mailto:{{ $contact->email }}">
                                    {{ $contact->email }}
                                </a>
                            </td>

                            <td>
                                <form
                                    method="POST"
                                    action="{{ route(
                                        'platform.contacts.status.update',
                                        $contact
                                    ) }}"
                                    class="contact-status-form"
                                >
                                    @csrf
                                    @method('PATCH')

                                    <select
                                        name="status"
                                        aria-label="{{ __('platform.contacts.status') }}"
                                        onchange="this.form.submit()"
                                    >
                                        @foreach ($statuses as $item)
                                            <option
                                                value="{{ $item->value }}"
                                                @selected(
                                                    $contact->status === $item
                                                )
                                            >
                                                {{ $item->label() }}
                                            </option>
                                        @endforeach
                                    </select>
                                </form>
                            </td>

                            <td>
                                @if ($contact->convertedTenant)
                                    <div class="converted-tenant">
                                        <strong>
                                            {{ $contact->convertedTenant->name }}
                                        </strong>

                                        <a
                                            href="{{ route(
                                                'platform.tenants.show',
                                                $contact->convertedTenant
                                            ) }}"
                                        >
                                            {{ __('platform.contacts.converted_tenant') }}
                                        </a>
                                    </div>
                                @else
                                    <form
                                        method="POST"
                                        action="{{ route(
                                            'platform.contacts.convert',
                                            $contact
                                        ) }}"
                                        class="conversion-form"
                                    >
                                        @csrf
                                        @method('PATCH')

                                        <select
                                            name="tenant_id"
                                            required
                                            aria-label="{{ __('platform.contacts.select_tenant') }}"
                                        >
                                            <option value="">
                                                {{ __('platform.contacts.select_tenant') }}
                                            </option>

                                            @foreach ($tenants as $tenant)
                                                <option value="{{ $tenant->id }}">
                                                    {{ $tenant->name }}
                                                    ({{ $tenant->slug }})
                                                </option>
                                            @endforeach
                                        </select>

                                        <button
                                            type="submit"
                                            class="button"
                                        >
                                            {{ __('platform.contacts.convert') }}
                                        </button>
                                    </form>
                                @endif
                            </td>

                            <td class="contracted-plan">
                                {{
                                    $contact->convertedTenant
                                        ?->latestSubscription
                                        ?->plan
                                        ?->name
                                    ?? '—'
                                }}
                            </td>

                            <td class="contact-message">
                                {{ $contact->message }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8">
                                {{ __('platform.contacts.empty') }}
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div class="pagination-wrap">
                {{ $contacts->links() }}
            </div>
        </div>
    </main>
</div>
@endsection
