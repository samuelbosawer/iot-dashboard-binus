<x-layouts.guest title="Register">
    <div class="flex min-h-screen items-center justify-center bg-linear-to-br from-brand-50 via-white to-brand-100 px-6 py-12">
        <div class="w-full max-w-md rounded-2xl border border-slate-200 bg-white p-8 shadow-xl shadow-brand-600/10">
            <div class="mb-8 flex justify-center">
                <x-ui.application-logo />
            </div>

            <h2 class="text-center text-2xl font-bold text-slate-900">Buat akun baru</h2>
            <p class="mt-1 text-center text-sm text-slate-500">Daftar untuk memantau penyiram tanaman IoT.</p>

            @if ($errors->any())
                <x-ui.alert variant="error" class="mt-6">
                    {{ $errors->first() }}
                </x-ui.alert>
            @endif

            <form method="POST" action="{{ route('register') }}" class="mt-6 space-y-5">
                @csrf

                <div>
                    <x-ui.label for="name" value="Nama" />
                    <x-ui.input
                        id="name"
                        type="text"
                        name="name"
                        icon="fa-user"
                        value="{{ old('name') }}"
                        placeholder="Nama lengkap"
                        required
                        autofocus
                        autocomplete="name"
                    />
                    <x-ui.input-error :messages="$errors->get('name')" />
                </div>

                <div>
                    <x-ui.label for="email" value="Email" />
                    <x-ui.input
                        id="email"
                        type="email"
                        name="email"
                        icon="fa-envelope"
                        value="{{ old('email') }}"
                        placeholder="nama@binus.ac.id"
                        required
                        autocomplete="username"
                    />
                    <x-ui.input-error :messages="$errors->get('email')" />
                </div>

                <div>
                    <x-ui.label for="password" value="Password" />
                    <x-ui.input
                        id="password"
                        type="password"
                        name="password"
                        icon="fa-lock"
                        placeholder="Minimal 6 karakter"
                        required
                        autocomplete="new-password"
                    />
                    <x-ui.input-error :messages="$errors->get('password')" />
                </div>

                <div>
                    <x-ui.label for="password_confirmation" value="Konfirmasi Password" />
                    <x-ui.input
                        id="password_confirmation"
                        type="password"
                        name="password_confirmation"
                        icon="fa-lock"
                        placeholder="Ulangi password"
                        required
                        autocomplete="new-password"
                    />
                </div>

                <x-ui.button type="submit" class="w-full">
                    <i class="fa-solid fa-user-plus"></i>
                    Daftar
                </x-ui.button>
            </form>

            <p class="mt-8 text-center text-sm text-slate-500">
                Sudah punya akun?
                <a href="{{ route('login') }}" class="font-semibold text-brand-600 hover:text-brand-700">Masuk</a>
            </p>
        </div>
    </div>
</x-layouts.guest>
