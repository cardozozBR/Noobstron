@extends('layouts.app')

@section('content')
<div>
    <h1>{{ __('imports.preview') }}</h1>

    <p>
        {{ __('imports.original_name') }}:
        {{ $import->original_name }}
    </p>

    <p>
        {{ __('imports.rows') }}:
        {{ $preview['row_count'] }}
    </p>

    <p>
        {{ __('imports.success') }}:
        {{ $preview['valid_count'] }}
    </p>

    <p>
        {{ __('imports.failed') }}:
        {{ $preview['invalid_count'] }}
    </p>

    @if ($preview['ignored'] !== [])
        <h2>{{ __('imports.ignored_columns') }}</h2>

        <ul>
            @foreach ($preview['ignored'] as $field)
                <li>{{ $field }}</li>
            @endforeach
        </ul>
    @endif

    <h2>{{ __('imports.valid_rows') }}</h2>

    <table>
        <thead>
            <tr>
                <th>{{ __('imports.line') }}</th>
                <th>{{ __('imports.data') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($preview['valid_rows'] as $row)
                <tr>
                    <td>{{ $row['line'] }}</td>
                    <td>
                        <pre>{{ json_encode(
                            $row['data'],
                            JSON_UNESCAPED_UNICODE
                            | JSON_PRETTY_PRINT
                        ) }}</pre>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <h2>{{ __('imports.invalid_rows') }}</h2>

    <table>
        <thead>
            <tr>
                <th>{{ __('imports.line') }}</th>
                <th>{{ __('imports.data') }}</th>
                <th>{{ __('imports.errors') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($preview['invalid_rows'] as $row)
                <tr>
                    <td>{{ $row['line'] }}</td>

                    <td>
                        <pre>{{ json_encode(
                            $row['data'],
                            JSON_UNESCAPED_UNICODE
                            | JSON_PRETTY_PRINT
                        ) }}</pre>
                    </td>

                    <td>
                        <pre>{{ json_encode(
                            $row['errors'],
                            JSON_UNESCAPED_UNICODE
                            | JSON_PRETTY_PRINT
                        ) }}</pre>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <form
        method="POST"
        action="{{ route(
            'imports.dispatch',
            $import->id
        ) }}"
    >
        @csrf

        <button type="submit">
            {{ __('imports.confirm') }}
        </button>
    </form>
</div>
@endsection
