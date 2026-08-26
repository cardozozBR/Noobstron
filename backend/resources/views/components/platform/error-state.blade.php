@props([
    'errors',
    'title' => null,
])

@if ($errors->any())
    <div
        {{ $attributes->class(['platform-error-state']) }}
        role="alert"
    >
        <strong>{{ $title ?: __('platform.error_state_title') }}</strong>

        <ul>
            @foreach ($errors->all() as $message)
                <li>{{ $message }}</li>
            @endforeach
        </ul>
    </div>
@endif
