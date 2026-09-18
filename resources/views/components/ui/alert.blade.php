@props(['variant' => 'info'])

@php
    $variants = [
        'success' => ['wrap' => 'border-emerald-200 bg-emerald-50 text-emerald-700', 'icon' => 'fa-circle-check'],
        'error' => ['wrap' => 'border-red-200 bg-red-50 text-red-700', 'icon' => 'fa-circle-exclamation'],
        'info' => ['wrap' => 'border-brand-200 bg-brand-50 text-brand-700', 'icon' => 'fa-circle-info'],
    ];

    $styles = $variants[$variant] ?? $variants['info'];
@endphp

<div {{ $attributes->merge(['class' => 'flex items-start gap-3 rounded-lg border px-4 py-3 text-sm '.$styles['wrap']]) }}>
    <i class="fa-solid {{ $styles['icon'] }} mt-0.5"></i>
    <div>{{ $slot }}</div>
</div>
