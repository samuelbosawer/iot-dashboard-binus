@props(['size' => 'md', 'variant' => 'default'])

@php
    $sizes = [
        'sm' => ['box' => 'h-8 w-8', 'icon' => 'text-sm', 'title' => 'text-base'],
        'md' => ['box' => 'h-11 w-11', 'icon' => 'text-lg', 'title' => 'text-xl'],
        'lg' => ['box' => 'h-14 w-14', 'icon' => 'text-2xl', 'title' => 'text-2xl'],
    ][$size];

    $colors = [
        'default' => ['title' => 'text-slate-900', 'accent' => 'text-brand-600', 'subtitle' => 'text-slate-400'],
        'light' => ['title' => 'text-white', 'accent' => 'text-brand-200', 'subtitle' => 'text-brand-100'],
    ][$variant];
@endphp

<div {{ $attributes->merge(['class' => 'flex items-center gap-3']) }}>
    <span class="flex {{ $sizes['box'] }} items-center justify-center rounded-xl bg-linear-to-br from-brand-500 to-brand-700 text-white shadow-md shadow-brand-600/20">
        <i class="fa-solid fa-satellite-dish {{ $sizes['icon'] }}"></i>
    </span>
    <div class="leading-tight">
        <p class="{{ $sizes['title'] }} font-bold {{ $colors['title'] }}">IOT <span class="{{ $colors['accent'] }}">BINUS</span></p>
        <p class="text-xs font-medium {{ $colors['subtitle'] }}">Sensor Dashboard</p>
    </div>
</div>
