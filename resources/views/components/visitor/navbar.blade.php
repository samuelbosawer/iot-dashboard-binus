{{-- Public site navigation shared by the visitor home page and the prediction page --}}
@php
    // Plain fragments on the home page so the chosen report period (?days=) is kept while scrolling.
    $home = request()->routeIs('visitor') ? '' : route('visitor');
    $isMobileApp = \App\Http\Middleware\EnsureWebBrowser::isMobileApp(request());
    $links = [
        ['href' => $home.'#live', 'label' => 'Live'],
        ['href' => $home.'#laporan', 'label' => 'Laporan'],
        ['href' => $home.'#grafik', 'label' => 'Grafik'],
        ['href' => $home.'#tabel', 'label' => 'Tabel'],
        ['href' => $home.'#download', 'label' => 'Download CSV'],
    ];

    // The Android app page is blocked inside the app itself.
    if (! $isMobileApp) {
        $links[] = ['href' => route('app-download'), 'label' => 'Download', 'active' => request()->routeIs('app-download')];
    }
@endphp

<header class="sticky top-0 z-30 border-b border-white/10 bg-brand-900/90 pt-[env(safe-area-inset-top)] backdrop-blur">
    <div class="mx-auto flex max-w-7xl items-center justify-between gap-3 px-4 py-3 sm:px-6">
        <a href="{{ route('visitor') }}"><x-ui.application-logo size="sm" variant="light" /></a>

        <nav class="hidden items-center gap-1 text-sm font-medium text-brand-100 md:flex">
            @foreach ($links as $link)
                <a href="{{ $link['href'] }}" @class([
                    'rounded-lg px-3 py-2 transition',
                    'bg-white/15 text-white' => $link['active'] ?? false,
                    'hover:bg-white/10 hover:text-white' => ! ($link['active'] ?? false),
                ])>{{ $link['label'] }}</a>
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
            @unless ($isMobileApp)
                <a href="{{ route('app-download') }}" @class([
                    'inline-flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-semibold text-white ring-1 ring-white/20 transition',
                    'bg-white/25' => request()->routeIs('app-download'),
                    'bg-white/10 hover:bg-white/20' => ! request()->routeIs('app-download'),
                ]) title="Download aplikasi Android">
                    <i class="fa-brands fa-android"></i>
                    <span class="hidden sm:inline">Aplikasi Android</span>
                </a>
            @endunless
            <button type="button" data-pwa-install hidden class="inline-flex items-center gap-2 rounded-lg bg-white/10 px-3 py-2 text-sm font-semibold text-white ring-1 ring-white/20 transition hover:bg-white/20" title="Install aplikasi">
                <i class="fa-solid fa-mobile-screen-button"></i>
                <span class="hidden sm:inline">Install App</span>
            </button>
            <a href="{{ auth()->check() ? route('dashboard') : route('login') }}" class="inline-flex items-center gap-2 rounded-lg bg-white px-3 py-2 text-sm font-semibold text-brand-700 shadow-sm transition hover:bg-brand-50" title="{{ auth()->check() ? 'Dashboard' : 'Login Admin' }}">
                <i class="fa-solid {{ auth()->check() ? 'fa-gauge-high' : 'fa-right-to-bracket' }}"></i>
                <span class="hidden sm:inline">{{ auth()->check() ? 'Dashboard' : 'Login Admin' }}</span>
            </a>
        </div>
    </div>
</header>
