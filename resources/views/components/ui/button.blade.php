@props(['variant' => 'primary'])

@php
    $base = 'inline-flex items-center justify-center gap-2 rounded-lg px-4 py-2.5 text-sm font-semibold transition focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60';

    $variants = [
        'primary' => 'bg-brand-600 text-white shadow-sm hover:bg-brand-700 focus-visible:ring-brand-500',
        'secondary' => 'border border-slate-300 bg-white text-slate-700 hover:bg-slate-50 focus-visible:ring-brand-500',
    ];
@endphp

<button {{ $attributes->merge(['type' => 'button', 'class' => $base.' '.($variants[$variant] ?? $variants['primary'])]) }}>
    {{ $slot }}
</button>
