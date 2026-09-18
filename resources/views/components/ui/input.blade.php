@props(['icon' => null, 'type' => 'text'])

<div class="relative">
    @if ($icon)
        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
            <i class="fa-solid {{ $icon }}"></i>
        </span>
    @endif

    <input
        type="{{ $type }}"
        {{ $attributes->merge([
            'class' => 'block w-full rounded-lg border border-slate-300 bg-white py-2.5 pr-3 text-sm text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30 '.($icon ? 'pl-10' : 'pl-3'),
        ]) }}
    >
</div>
