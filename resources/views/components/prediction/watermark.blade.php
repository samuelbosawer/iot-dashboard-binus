{{-- Faded "in development" watermark behind the prediction page title bar; the parent must be relative overflow-hidden --}}
@props(['variant' => 'light'])

<div aria-hidden="true" @class([
    'pointer-events-none absolute inset-0 flex -rotate-6 select-none flex-col items-end justify-center gap-2 pr-4 font-black tracking-widest whitespace-nowrap uppercase sm:pr-10',
    'text-white/10' => $variant === 'light',
    'text-slate-900/5' => $variant === 'dark',
])>
    <span class="text-4xl sm:text-6xl lg:text-7xl">Dalam Pengembangan</span>
    <span class="text-xs tracking-[0.5em] sm:text-sm">Machine Learning · Belum Optimal</span>
</div>
