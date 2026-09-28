@php
    $isEditing = $user->exists;
@endphp

<x-layouts.app :title="$isEditing ? 'Edit User' : 'Tambah User'">
    <div class="mx-auto max-w-2xl">
        <a href="{{ route('users.index') }}" class="mb-4 inline-flex items-center gap-2 text-sm font-medium text-slate-500 hover:text-slate-700">
            <i class="fa-solid fa-arrow-left"></i>
            Kembali ke daftar user
        </a>

        <x-ui.card>
            <div class="mb-6 flex items-center gap-3">
                <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-brand-50 text-brand-600">
                    <i class="fa-solid {{ $isEditing ? 'fa-user-pen' : 'fa-user-plus' }}"></i>
                </span>
                <div>
                    <h1 class="text-lg font-bold text-slate-900">{{ $isEditing ? 'Edit User' : 'Tambah User' }}</h1>
                    <p class="text-sm text-slate-500">{{ $isEditing ? 'Perbarui data akun '.$user->name.'.' : 'Buat akun baru untuk mengakses dashboard.' }}</p>
                </div>
            </div>

            <form method="POST" action="{{ $isEditing ? route('users.update', $user) : route('users.store') }}" class="space-y-5">
                @csrf
                @if ($isEditing)
                    @method('PUT')
                @endif

                <div>
                    <x-ui.label for="name" value="Nama" />
                    <x-ui.input id="name" name="name" icon="fa-user" value="{{ old('name', $user->name) }}" placeholder="Nama lengkap" required autofocus />
                    <x-ui.input-error :messages="$errors->get('name')" />
                </div>

                <div>
                    <x-ui.label for="email" value="Email" />
                    <x-ui.input id="email" type="email" name="email" icon="fa-envelope" value="{{ old('email', $user->email) }}" placeholder="nama@binus.ac.id" required />
                    <x-ui.input-error :messages="$errors->get('email')" />
                </div>

                <div>
                    <x-ui.label value="Role" />
                    <div class="grid grid-cols-2 gap-3">
                        @foreach ($roles as $role)
                            <label class="flex cursor-pointer items-center gap-3 rounded-lg border border-slate-300 px-4 py-3 text-sm has-checked:border-brand-500 has-checked:bg-brand-50 has-checked:ring-2 has-checked:ring-brand-500/30">
                                <input type="radio" name="role" value="{{ $role->value }}" class="accent-brand-600" @checked(old('role', $user->role?->value) === $role->value)>
                                <span class="font-medium text-slate-700">{{ $role->label() }}</span>
                            </label>
                        @endforeach
                    </div>
                    <x-ui.input-error :messages="$errors->get('role')" />
                </div>

                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <x-ui.label for="password" value="Password" />
                        <x-ui.input id="password" type="password" name="password" icon="fa-lock" :placeholder="$isEditing ? 'Kosongkan jika tidak diubah' : 'Minimal 6 karakter'" :required="! $isEditing" autocomplete="new-password" />
                        <x-ui.input-error :messages="$errors->get('password')" />
                    </div>

                    <div>
                        <x-ui.label for="password_confirmation" value="Konfirmasi Password" />
                        <x-ui.input id="password_confirmation" type="password" name="password_confirmation" icon="fa-lock" placeholder="Ulangi password" :required="! $isEditing" autocomplete="new-password" />
                    </div>
                </div>

                <div class="flex justify-end gap-3 border-t border-slate-100 pt-5">
                    <a href="{{ route('users.index') }}" class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">Batal</a>
                    <x-ui.button type="submit">
                        <i class="fa-solid fa-floppy-disk"></i>
                        {{ $isEditing ? 'Simpan Perubahan' : 'Simpan User' }}
                    </x-ui.button>
                </div>
            </form>
        </x-ui.card>
    </div>
</x-layouts.app>
