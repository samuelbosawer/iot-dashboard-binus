@props(['title' => 'Dashboard'])

<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <x-seo :title="$title" :noindex="true" />

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-slate-50 font-sans antialiased">
    @auth
        @php
            $navLinks = [
                ['href' => route('dashboard'), 'icon' => 'fa-gauge-high', 'label' => 'Dashboard', 'active' => request()->routeIs('dashboard')],
                ['href' => route('dashboard').'#suhu-tanah', 'icon' => 'fa-temperature-half', 'label' => 'Suhu Tanah', 'active' => false],
                ['href' => route('dashboard.prediction'), 'icon' => 'fa-brain', 'label' => 'Prediksi ML', 'active' => request()->routeIs('dashboard.prediction')],
                ['href' => route('visitor'), 'icon' => 'fa-earth-asia', 'label' => 'Halaman Visitor', 'active' => false, 'external' => true],
            ];

            if (auth()->user()->can('manage-users')) {
                $navLinks[] = ['href' => route('users.index'), 'icon' => 'fa-users', 'label' => 'Users', 'active' => request()->routeIs('users.*')];
            }
        @endphp
    @endauth

    <header class="sticky top-0 z-30 border-b border-slate-200 bg-white/95 pt-[env(safe-area-inset-top)] backdrop-blur">
        <div class="mx-auto flex max-w-7xl items-center justify-between gap-3 px-4 py-3 sm:px-6 lg:py-4">
            <div class="flex min-w-0 items-center gap-8">
                <a href="{{ auth()->check() ? route('dashboard') : route('visitor') }}" class="shrink-0"><x-ui.application-logo size="sm" /></a>

                @auth
                    <nav class="hidden items-center gap-1 text-sm font-medium lg:flex">
                        @foreach ($navLinks as $link)
                            <a href="{{ $link['href'] }}" @if ($link['external'] ?? false) target="_blank" rel="noopener" @endif @class([
                                'inline-flex items-center gap-2 rounded-lg px-3 py-2 whitespace-nowrap transition',
                                'bg-brand-50 text-brand-700' => $link['active'],
                                'text-slate-600 hover:bg-slate-100' => ! $link['active'],
                            ])>
                                <i class="fa-solid {{ $link['icon'] }}"></i>
                                {{ $link['label'] }}
                            </a>
                        @endforeach
                    </nav>
                @endauth
            </div>

            @auth
                <div class="flex shrink-0 items-center gap-3">
                    <div class="hidden text-right sm:block">
                        <p class="max-w-40 truncate text-sm font-semibold text-slate-700">{{ auth()->user()->name }}</p>
                        <x-ui.badge :variant="auth()->user()->isAdmin() ? 'brand' : 'slate'">
                            {{ auth()->user()->role->label() }}
                        </x-ui.badge>
                    </div>

                    <form method="POST" action="{{ route('logout') }}" class="hidden lg:block">
                        @csrf
                        <x-ui.button variant="secondary" type="submit">
                            <i class="fa-solid fa-arrow-right-from-bracket"></i>
                            Logout
                        </x-ui.button>
                    </form>

                    <button type="button" id="nav-toggle" aria-controls="nav-mobile" aria-expanded="false" aria-label="Buka menu"
                        class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-slate-200 text-slate-600 transition hover:bg-slate-100 lg:hidden">
                        <i class="fa-solid fa-bars text-lg" data-nav-icon></i>
                    </button>
                </div>
            @endauth
        </div>

        @auth
            <div id="nav-mobile" hidden class="border-t border-slate-200 bg-white lg:hidden">
                <div class="mx-auto max-w-7xl px-4 py-3 sm:px-6">
                    <div class="mb-3 flex items-center justify-between gap-3 rounded-xl bg-slate-50 px-3 py-2.5 sm:hidden">
                        <p class="min-w-0 truncate text-sm font-semibold text-slate-700">{{ auth()->user()->name }}</p>
                        <x-ui.badge :variant="auth()->user()->isAdmin() ? 'brand' : 'slate'">
                            {{ auth()->user()->role->label() }}
                        </x-ui.badge>
                    </div>

                    <nav class="grid gap-1 text-sm font-medium sm:grid-cols-2">
                        @foreach ($navLinks as $link)
                            <a href="{{ $link['href'] }}" data-nav-link @if ($link['external'] ?? false) target="_blank" rel="noopener" @endif @class([
                                'flex items-center gap-3 rounded-lg px-3 py-2.5 transition',
                                'bg-brand-50 text-brand-700' => $link['active'],
                                'text-slate-600 hover:bg-slate-100' => ! $link['active'],
                            ])>
                                <i class="fa-solid {{ $link['icon'] }} w-4 text-center"></i>
                                {{ $link['label'] }}
                            </a>
                        @endforeach
                    </nav>

                    <form method="POST" action="{{ route('logout') }}" class="mt-3 border-t border-slate-100 pt-3">
                        @csrf
                        <x-ui.button variant="secondary" type="submit" class="w-full justify-center">
                            <i class="fa-solid fa-arrow-right-from-bracket"></i>
                            Logout
                        </x-ui.button>
                    </form>
                </div>
            </div>

            <script>
                (() => {
                    const toggle = document.getElementById('nav-toggle');
                    const menu = document.getElementById('nav-mobile');
                    const icon = toggle.querySelector('[data-nav-icon]');

                    const setOpen = (isOpen) => {
                        menu.hidden = !isOpen;
                        toggle.setAttribute('aria-expanded', String(isOpen));
                        toggle.setAttribute('aria-label', isOpen ? 'Tutup menu' : 'Buka menu');
                        icon.classList.toggle('fa-bars', !isOpen);
                        icon.classList.toggle('fa-xmark', isOpen);
                    };

                    toggle.addEventListener('click', () => setOpen(menu.hidden));
                    menu.querySelectorAll('[data-nav-link]').forEach((link) => link.addEventListener('click', () => setOpen(false)));
                    window.matchMedia('(min-width: 64rem)').addEventListener('change', (event) => event.matches && setOpen(false));
                })();
            </script>
        @endauth
    </header>

    <main class="mx-auto max-w-7xl px-4 py-6 sm:px-6 sm:py-10">
        {{ $slot }}
    </main>

    @stack('scripts')
</body>
</html>
