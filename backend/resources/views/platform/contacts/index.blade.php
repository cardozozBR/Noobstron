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

.platform-contacts-page .contacts-filter__field {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.platform-contacts-page .contacts-filter select,
.platform-contacts-page .contact-status-form select {
    min-height: 38px;
}

.platform-contacts-page .contact-status-form {
    margin: 0;
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
                            <th>
                                {{ __('platform.contacts.date') }}
                            </th>

                            <th>
                                {{ __('platform.contacts.name') }}
                            </th>

                            <th>
                                {{ __('platform.contacts.company') }}
                            </th>

                            <th>
                                {{ __('platform.email') }}
                            </th>

                            <th>
                                {{ __('platform.contacts.status') }}
                            </th>

                            <th>
                                {{ __('platform.contacts.message') }}
                            </th>
                        </tr>
                    </thead>

                    <tbody>
                    @forelse ($contacts as $contact)
                        <tr>
                            <td>
                                {{ $contact->created_at?->format('d/m/Y H:i') }}
                            </td>

                            <td>
                                {{ $contact->name }}
                            </td>

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

                            <td class="contact-message">
                                {{ $contact->message }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">
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