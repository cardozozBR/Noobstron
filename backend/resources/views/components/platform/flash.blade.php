@props([
    'type' => 'success',
])

@php
    $allowedTypes = [
        'success',
        'error',
    ];

    $resolvedType = in_array(
        $type,
        $allowedTypes,
        true
    )
        ? $type
        : 'success';
@endphp

<section
    {{ $attributes->class([
        'platform-flash',
        'platform-flash--'.$resolvedType,
    ]) }}
    role="{{ $resolvedType === 'error' ? 'alert' : 'status' }}"
>
    <strong>
        {{ $slot }}
    </strong>
</section>
