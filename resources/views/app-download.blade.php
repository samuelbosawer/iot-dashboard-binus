@php
    $steps = [
        ['icon' => 'fa-download', 'title' => 'Download APK', 'text' => 'Tekan tombol download dan tunggu file '.$fileName.' selesai diunduh.'],
        ['icon' => 'fa-folder-open', 'title' => 'Buka file', 'text' => 'Buka file dari notifikasi download atau folder Download di HP Android.'],
        ['icon' => 'fa-shield-halved', 'title' => 'Izinkan instalasi', 'text' => 'Jika diminta, aktifkan "Izinkan dari sumber ini" (Install unknown apps) untuk browser Anda.'],
        ['icon' => 'fa-circle-check', 'title' => 'Install & buka', 'text' => 'Tekan Install, lalu buka aplikasi Monitoring Tanaman Realtime.'],
    ];

    $features = [
        ['icon' => 'fa-house-signal', 'text' => 'Data sensor live kelembaban & suhu tanah'],
        ['icon' => 'fa-chart-line', 'text' => 'Grafik, laporan, dan tabel riwayat data'],
        ['icon' => 'fa-faucet-drip', 'text' => 'Status pompa, air tandon, dan alarm buzzer'],
        ['icon' => 'fa-file-csv', 'text' => 'Download dataset CSV langsung dari HP'],
    ];
@endphp

<x-layouts.guest title="Download Aplikasi Android" description="Download aplikasi Android Monitoring Tanaman Realtime untuk memantau kelembaban & suhu tanah, air tandon, pompa, dan alarm buzzer langsung dari HP.">
    <x-visitor.navbar />

    <section class="relative overflow-hidden bg-linear-to-br from-brand-600 via-brand-800 to-brand-900 text-white">
        <div class="motion-safe-only pointer-events-none absolute -top-24 -right-24 h-80 w-80 animate-float rounded-full bg-emerald-400/20 blur-3xl"></div>

        <div class="relative mx-auto grid max-w-7xl items-center gap-8 px-4 py-10 sm:px-6 sm:py-14 md:grid-cols-[1.4fr_1fr]">
            <div>
                <span class="inline-flex items-center gap-2 rounded-full bg-white/10 px-3 py-1.5 text-xs font-semibold tracking-widest text-brand-100 uppercase ring-1 ring-white/20">
                    <i class="fa-brands fa-android"></i>
                    Aplikasi Android
                </span>
                <h1 class="mt-4 text-2xl font-bold tracking-tight sm:text-4xl">Monitoring Tanaman Realtime</h1>
                <p class="mt-3 max-w-2xl text-brand-100">Pantau kondisi tanaman langsung dari HP Android: kelembaban &amp; suhu tanah, air tandon, pompa, dan alarm buzzer secara realtime.</p>

                <div class="mt-6 flex flex-wrap items-center gap-3">
                    @if ($isAvailable)
                        <a href="{{ route('app-download.file') }}" download="{{ $fileName }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-white px-5 py-3 text-sm font-semibold text-brand-700 shadow-lg shadow-brand-900/30 transition hover:-translate-y-0.5">
                            <i class="fa-solid fa-download"></i>
                            Download APK
                        </a>
                        <span class="text-sm text-brand-100">{{ $fileSize }} · Diperbarui {{ $updatedAt->format('d M Y') }}</span>
                    @else
                        <span class="inline-flex items-center gap-2 rounded-xl bg-white/10 px-5 py-3 text-sm font-semibold text-brand-100 ring-1 ring-white/20">
                            <i class="fa-solid fa-clock"></i>
                            File aplikasi belum tersedia
                        </span>
                    @endif
                </div>
            </div>

            <div class="hidden justify-center md:flex" aria-hidden="true">
                <div class="relative flex h-64 w-36 flex-col items-center justify-center rounded-4xl bg-white/10 ring-4 ring-white/20">
                    <span class="absolute top-3 h-1.5 w-12 rounded-full bg-white/30"></span>
                    <i class="fa-solid fa-seedling motion-safe-only origin-bottom animate-sway text-5xl text-emerald-300"></i>
                    <span class="mt-4 text-xs font-semibold text-brand-100">Taman Pintar IoT</span>
                </div>
            </div>
        </div>
    </section>

    <main class="mx-auto grid max-w-7xl gap-6 px-4 py-8 sm:px-6 sm:py-10 lg:grid-cols-[1.4fr_1fr]">
        <x-ui.card padding="p-4 sm:p-6">
            <h2 class="text-lg font-semibold text-slate-900">Cara install</h2>
            <p class="text-xs text-slate-400">Aplikasi diinstall langsung dari file APK, bukan dari Play Store.</p>

            <ol class="mt-5 space-y-4">
                @foreach ($steps as $index => $step)
                    <li class="flex gap-4">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-600">
                            <i class="fa-solid {{ $step['icon'] }}"></i>
                        </span>
                        <div>
                            <p class="text-sm font-semibold text-slate-900">{{ $index + 1 }}. {{ $step['title'] }}</p>
                            <p class="text-sm text-slate-500">{{ $step['text'] }}</p>
                        </div>
                    </li>
                @endforeach
            </ol>

            <x-ui.alert variant="info" class="mt-6">
                Google Play Protect mungkin menampilkan peringatan karena aplikasi tidak berasal dari Play Store. Pilih "Install anyway" (Tetap install) untuk melanjutkan.
            </x-ui.alert>
        </x-ui.card>

        <x-ui.card padding="p-4 sm:p-6">
            <h2 class="text-lg font-semibold text-slate-900">Fitur aplikasi</h2>

            <ul class="mt-5 space-y-3">
                @foreach ($features as $feature)
                    <li class="flex items-center gap-3 text-sm text-slate-600">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600">
                            <i class="fa-solid {{ $feature['icon'] }}"></i>
                        </span>
                        {{ $feature['text'] }}
                    </li>
                @endforeach
            </ul>

            <dl class="mt-6 grid grid-cols-2 gap-3 border-t border-slate-100 pt-5 text-sm">
                <div>
                    <dt class="text-xs text-slate-400">Platform</dt>
                    <dd class="font-semibold text-slate-900">Android</dd>
                </div>
                <div>
                    <dt class="text-xs text-slate-400">Ukuran</dt>
                    <dd class="font-semibold text-slate-900">{{ $fileSize ?? '--' }}</dd>
                </div>
                <div class="col-span-2">
                    <dt class="text-xs text-slate-400">Nama file</dt>
                    <dd class="font-semibold break-all text-slate-900">{{ $fileName }}</dd>
                </div>
            </dl>
        </x-ui.card>
    </main>

    <x-visitor.bottom-nav />
</x-layouts.guest>
