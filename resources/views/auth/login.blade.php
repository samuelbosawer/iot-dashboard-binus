<x-layouts.guest title="Sign In">
    <div class="flex min-h-screen items-center justify-center bg-linear-to-br from-brand-50 via-white to-brand-100 px-6 py-12">
        <div class="w-full max-w-md rounded-2xl border border-slate-200 bg-white p-8 shadow-xl shadow-brand-600/10">
            <div class="mb-8 flex justify-center">
                <x-ui.application-logo />
            </div>

                <h2 class="text-center text-2xl font-bold text-slate-900">Selamat datang kembali</h2>
                <p class="mt-1 text-center text-sm text-slate-500">Masuk menggunakan akun Admin atau Support Anda.</p>

                @session('status')
                    <x-ui.alert variant="success" class="mt-6">
                        {{ $value }}
                    </x-ui.alert>
                @endsession

                @if ($errors->any())
                    <x-ui.alert variant="error" class="mt-6">
                        {{ $errors->first() }}
                    </x-ui.alert>
                @endif

                <form method="POST" action="{{ route('login') }}" class="mt-6 space-y-5">
                    @csrf

                    <div>
                        <x-ui.label for="email" value="Email" />
                        <x-ui.input
                            id="email"
                            type="email"
                            name="email"
                            icon="fa-envelope"
                            value="{{ old('email') }}"
                            placeholder="nama@iotbinus.com"
                            required
                            autofocus
                            autocomplete="username"
                        />
                        <x-ui.input-error :messages="$errors->get('email')" />
                    </div>

                    <div>
                        <x-ui.label for="password" value="Password" />
                        <div class="relative">
                            <x-ui.input
                                id="password"
                                type="password"
                                name="password"
                                icon="fa-lock"
                                placeholder="••••••••"
                                required
                                autocomplete="current-password"
                                class="pr-10"
                            />
                            <button
                                type="button"
                                onclick="const i=document.getElementById('password'); i.type = i.type === 'password' ? 'text' : 'password'; this.querySelector('i').classList.toggle('fa-eye'); this.querySelector('i').classList.toggle('fa-eye-slash');"
                                class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400 hover:text-slate-600"
                                aria-label="Toggle password visibility"
                            >
                                <i class="fa-solid fa-eye"></i>
                            </button>
                        </div>
                        <x-ui.input-error :messages="$errors->get('password')" />
                    </div>

                    <div class="flex items-center justify-between">
                        <label class="flex items-center gap-2 text-sm text-slate-600">
                            <input type="checkbox" name="remember" class="rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                            Ingat saya
                        </label>
                    </div>

                    <x-ui.button type="submit" class="w-full">
                        <i class="fa-solid fa-right-to-bracket"></i>
                        Sign In
                    </x-ui.button>
                </form>

                <p class="mt-8 text-center text-xs text-slate-400">
                    Akses khusus untuk Admin dan Support IOT BINUS.
                </p>
        </div>
    </div>
</x-layouts.guest>
