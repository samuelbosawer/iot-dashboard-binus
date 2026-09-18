<x-layouts.app title="Dashboard">
    <x-ui.card class="text-center">
        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-brand-100 text-brand-600">
            <i class="fa-solid fa-microchip text-2xl"></i>
        </div>

        <h1 class="mt-4 text-xl font-bold text-slate-900">
            Login berhasil, {{ auth()->user()->name }} 👋
        </h1>

        <p class="mx-auto mt-2 max-w-md text-sm text-slate-500">
            Anda masuk sebagai <strong>{{ auth()->user()->role->label() }}</strong>. Tampilan dashboard data sensor IoT akan dibangun pada tahap berikutnya menggunakan komponen yang sudah disiapkan.
        </p>
    </x-ui.card>
</x-layouts.app>
