{{-- Public site navigation shared by the visitor home page and the prediction page --}}
@php
    $home = route('visitor');
    $links = [
        ['href' => $home.'#live', 'label' => 'Live'],
        ['href' => $home.'#laporan', 'label' => 'Laporan'],
        ['href' => $home.'#grafik', 'label' => 'Grafik'],
        ['href' => $home.'#download', 'label' => 'Download'],
    ];
@endphp

<header class="sticky top-0 z-30 border-b border-white/10 bg-brand-900/80 backdrop-blur">
    <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-6 py-3">
        <a href="{{ $home }}"><x-ui.application-logo size="sm" variant="light" /></a>

        <nav class="hidden items-center gap-1 text-sm font-medium text-brand-100 md:flex">
            @foreach ($links as $link)
                <a href="{{ $link['href'] }}" class="rounded-lg px-3 py-2 transition hover:bg-white/10 hover:text-white">{{ $link['label'] }}</a>
            @endforeach

            <a href="{{ route('prediction') }}" @class([
                'inline-flex items-center gap-2 rounded-lg px-3 py-2 transition',
                'bg-white/15 text-white' => request()->routeIs('prediction'),
                'hover:bg-white/10 hover:text-white' => ! request()->routeIs('prediction'),
            ])>
                <i class="fa-solid fa-brain"></i>
                Prediksi ML
            </a>
        </nav>

        <div class="flex items-center gap-2">
            <a href="{{ route('prediction') }}" class="inline-flex items-center rounded-lg px-3 py-2 text-sm font-semibold text-brand-100 transition hover:bg-white/10 md:hidden" title="Prediksi ML">
                <i class="fa-solid fa-brain"></i>
            </a>
            <a href="{{ auth()->check() ? route('dashboard') : route('login') }}" class="inline-flex items-center gap-2 rounded-lg bg-white px-3 py-2 text-sm font-semibold text-brand-700 shadow-sm transition hover:bg-brand-50">
                <i class="fa-solid {{ auth()->check() ? 'fa-gauge-high' : 'fa-right-to-bracket' }}"></i>
                {{ auth()->check() ? 'Dashboard' : 'Login Admin' }}
            </a>
        </div>
    </div>
</header>
