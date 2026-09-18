@props(['variant' => 'brand'])

@php
    $styles = [
        'brand' => 'bg-brand-100 text-brand-700',
        'slate' => 'bg-slate-100 text-slate-600',
    ][$variant] ?? 'bg-brand-100 text-brand-700';
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium '.$styles]) }}>
    {{ $slot }}
</span>
