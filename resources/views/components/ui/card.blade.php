@props(['padding' => 'p-6'])

<div {{ $attributes->merge(['class' => 'rounded-2xl border border-slate-200 bg-white shadow-sm '.$padding]) }}>
    {{ $slot }}
</div>
