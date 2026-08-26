@props([
    'title',
    'description' => null,
])

<div
    {{ $attributes->class(['platform-empty-state']) }}
    role="status"
>
    <strong>{{ $title }}</strong>

    @if (filled($description))
        <p class="platform-muted">
            {{ $description }}
        </p>
    @endif
</div>
