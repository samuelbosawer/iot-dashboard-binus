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
        <div class="mx-auto flex max-w-7xl items-center justify-between px-6 py-4">
            <x-ui.application-logo size="sm" />

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
</body>
</html>
