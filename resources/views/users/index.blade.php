<x-layouts.app title="Manajemen User">
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Manajemen User</h1>
            <p class="text-sm text-slate-500">Kelola akun yang dapat mengakses dashboard IoT.</p>
        </div>

        <a href="{{ route('users.create') }}" class="inline-flex items-center justify-center gap-2 rounded-lg bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700">
            <i class="fa-solid fa-user-plus"></i>
            Tambah User
        </a>
    </div>

    @if (session('status'))
        <x-ui.alert variant="success" class="mb-4">{{ session('status') }}</x-ui.alert>
    @endif

    @if (session('error'))
        <x-ui.alert variant="error" class="mb-4">{{ session('error') }}</x-ui.alert>
    @endif

    <x-ui.card padding="p-0" class="overflow-hidden">
        <form method="GET" action="{{ route('users.index') }}" class="flex flex-wrap items-center gap-3 border-b border-slate-200 px-6 py-4">
            <div class="min-w-0 flex-1">
                <x-ui.input name="search" icon="fa-magnifying-glass" value="{{ $search }}" placeholder="Cari nama atau email…" />
            </div>
            <x-ui.button type="submit" variant="secondary">Cari</x-ui.button>
            @if ($search !== '')
                <a href="{{ route('users.index') }}" class="text-sm font-medium text-slate-500 hover:text-slate-700">Reset</a>
            @endif
        </form>

        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="bg-slate-50 text-xs tracking-wide text-slate-500 uppercase">
                    <tr>
                        <th class="px-6 py-3 font-medium">User</th>
                        <th class="px-4 py-3 font-medium">Role</th>
                        <th class="px-4 py-3 font-medium">Dibuat</th>
                        <th class="px-6 py-3 text-right font-medium">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($users as $user)
                        <tr class="hover:bg-slate-50">
                            <td class="px-6 py-3">
                                <div class="flex items-center gap-3">
                                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-brand-100 text-sm font-bold text-brand-700">
                                        {{ str($user->name)->substr(0, 1)->upper() }}
                                    </span>
                                    <div class="min-w-0">
                                        <p class="font-semibold text-slate-900">
                                            {{ $user->name }}
                                            @if ($user->is(auth()->user()))
                                                <span class="text-xs font-normal text-slate-400">(Anda)</span>
                                            @endif
                                        </p>
                                        <p class="truncate text-xs text-slate-500">{{ $user->email }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <x-ui.badge :variant="$user->isAdmin() ? 'brand' : 'slate'">{{ $user->role->label() }}</x-ui.badge>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-slate-500">{{ $user->created_at?->format('d M Y') }}</td>
                            <td class="px-6 py-3">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('users.edit', $user) }}" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 text-slate-600 transition hover:bg-slate-100" title="Edit">
                                        <i class="fa-solid fa-pen"></i>
                                    </a>
                                    @unless ($user->is(auth()->user()))
                                        <form method="POST" action="{{ route('users.destroy', $user) }}" onsubmit="return confirm('Yakin ingin menghapus user ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-red-200 text-red-600 transition hover:bg-red-50" title="Hapus">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </form>
                                    @endunless
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-10 text-center text-sm text-slate-400">Tidak ada user ditemukan.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($users->hasPages())
            <div class="border-t border-slate-200 px-6 py-4">
                {{ $users->links() }}
            </div>
        @endif
    </x-ui.card>
</x-layouts.app>
