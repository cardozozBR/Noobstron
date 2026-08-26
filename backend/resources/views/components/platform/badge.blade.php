@props([
    'variant' => 'neutral',
])

@php
    $allowedVariants = [
        'neutral',
        'success',
        'warning',
        'danger',
        'info',
    ];

    $resolvedVariant = in_array(
        $variant,
        $allowedVariants,
        true
    )
        ? $variant
        : 'neutral';
@endphp

<span
    {{ $attributes->class([
        'status-badge',
        'status-badge--'.$resolvedVariant,
    ]) }}
>
    {{ $slot }}
</span>
