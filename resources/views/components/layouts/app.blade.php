@props(['title' => 'Dashboard'])

<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} · IOT BINUS</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-slate-50 font-sans antialiased">
    <header class="border-b border-slate-200 bg-white">
        <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-4 px-6 py-4">
            <div class="flex items-center gap-8">
                <x-ui.application-logo size="sm" />

                @auth
                    <nav class="flex items-center gap-1 text-sm font-medium">
                        <a href="{{ route('dashboard') }}" @class([
                            'inline-flex items-center gap-2 rounded-lg px-3 py-2 transition',
                            'bg-brand-50 text-brand-700' => request()->routeIs('dashboard'),
                            'text-slate-600 hover:bg-slate-100' => ! request()->routeIs('dashboard'),
                        ])>
                            <i class="fa-solid fa-gauge-high"></i>
                            Dashboard
                        </a>

                        @can('manage-users')
                            <a href="{{ route('users.index') }}" @class([
                                'inline-flex items-center gap-2 rounded-lg px-3 py-2 transition',
                                'bg-brand-50 text-brand-700' => request()->routeIs('users.*'),
                                'text-slate-600 hover:bg-slate-100' => ! request()->routeIs('users.*'),
                            ])>
                                <i class="fa-solid fa-users"></i>
                                Users
                            </a>
                        @endcan
                    </nav>
                @endauth
            </div>

            @auth
                <div class="flex items-center gap-4">
                    <div class="text-right">
                        <p class="text-sm font-semibold text-slate-700">{{ auth()->user()->name }}</p>
                        <x-ui.badge :variant="auth()->user()->isAdmin() ? 'brand' : 'slate'">
                            {{ auth()->user()->role->label() }}
                        </x-ui.badge>
                    </div>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <x-ui.button variant="secondary" type="submit">
                            <i class="fa-solid fa-arrow-right-from-bracket"></i>
                            Logout
                        </x-ui.button>
                    </form>
                </div>
            @endauth
        </div>
    </header>

    <main class="mx-auto max-w-7xl px-6 py-10">
        {{ $slot }}
    </main>

    @stack('scripts')
</body>
</html>
