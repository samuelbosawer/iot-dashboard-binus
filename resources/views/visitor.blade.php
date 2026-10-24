@php
    $format = fn (?float $value, int $decimals = 1): string => $value === null ? '--' : number_format($value, $decimals, ',', '.');

    $tones = [
        'brand' => 'bg-brand-50 text-brand-600',
        'emerald' => 'bg-emerald-50 text-emerald-600',
        'amber' => 'bg-amber-50 text-amber-600',
        'red' => 'bg-red-50 text-red-600',
        'slate' => 'bg-slate-100 text-slate-500',
    ];

    $periodLabels = [1 => 'Hari ini', 7 => '7 hari', 30 => '30 hari'];
@endphp

<x-layouts.guest title="Monitoring Tanaman Realtime" description="Pantau kelembaban & suhu tanah, air tandon, alarm buzzer, serta aktivitas penyiraman otomatis secara realtime. Lihat laporan, tabel data, grafik, dan download dataset CSV.">
    {{-- Top navigation --}}
    <x-visitor.navbar />

    {{-- Animated hero --}}
    <section class="relative overflow-hidden bg-linear-to-br from-brand-600 via-brand-800 to-brand-900 text-white">
        <div class="motion-safe-only pointer-events-none absolute -top-24 -left-24 h-80 w-80 animate-float rounded-full bg-brand-400/30 blur-3xl"></div>
        <div class="motion-safe-only pointer-events-none absolute -right-20 bottom-0 h-96 w-96 animate-float rounded-full bg-emerald-400/20 blur-3xl [animation-delay:-3s]"></div>

        {{-- Rising water droplets --}}
        <div class="pointer-events-none absolute inset-x-0 bottom-0 h-80" aria-hidden="true">
            @foreach ([8, 18, 31, 44, 57, 69, 81, 92] as $index => $left)
                <i class="fa-solid fa-droplet motion-safe-only absolute bottom-0 animate-rise text-brand-200/50"
                    style="left: {{ $left }}%; font-size: {{ 10 + ($index % 3) * 6 }}px; animation-delay: -{{ $index * 0.9 }}s; animation-duration: {{ 6 + ($index % 4) }}s"></i>
            @endforeach
        </div>

        <div class="relative mx-auto grid max-w-7xl items-center gap-8 px-4 py-10 sm:px-6 sm:py-16 md:grid-cols-[1.4fr_1fr] md:gap-10 md:py-24">
            <div class="reveal">
                <span class="inline-flex items-center gap-2 rounded-full bg-white/10 px-3 py-1.5 text-xs font-semibold tracking-widest text-brand-100 uppercase ring-1 ring-white/20">
                    <span class="relative flex h-2.5 w-2.5">
                        <span id="v-live-ping" class="absolute inline-flex h-full w-full rounded-full opacity-75"></span>
                        <span id="v-live-dot" class="relative inline-flex h-2.5 w-2.5 rounded-full bg-slate-300"></span>
                    </span>
                    <span id="v-live-label">Menghubungkan…</span>
                </span>

                <h1 class="mt-5 text-3xl font-bold tracking-tight sm:text-5xl">
                    Taman Pintar <span class="bg-linear-to-r from-emerald-300 via-brand-200 to-emerald-300 bg-[length:200%_100%] bg-clip-text text-transparent motion-safe-only animate-shimmer">IoT</span>
                </h1>
                <p class="mt-4 max-w-xl text-sm text-brand-100 sm:text-lg">
                    Pantau kondisi tanaman secara langsung: kelembaban &amp; suhu tanah, level air tandon, alarm buzzer, serta aktivitas penyiraman otomatis oleh ESP32.
                </p>

                <div class="mt-8 grid grid-cols-2 gap-3 sm:flex sm:flex-wrap">
                    <a href="#laporan" class="inline-flex items-center justify-center gap-2 rounded-xl bg-white px-4 py-3 sm:px-5 text-sm font-semibold text-brand-700 shadow-lg shadow-brand-900/30 transition hover:-translate-y-0.5">
                        <i class="fa-solid fa-chart-pie"></i>
                        Lihat Laporan
                    </a>
                    <a href="#download" class="inline-flex items-center justify-center gap-2 rounded-xl bg-white/10 px-4 py-3 sm:px-5 text-sm font-semibold text-white ring-1 ring-white/30 transition hover:-translate-y-0.5 hover:bg-white/20">
                        <i class="fa-solid fa-file-csv"></i>
                        Download CSV
                    </a>
                </div>
            </div>

            {{-- Swaying plant with live status --}}
            <div class="reveal flex justify-center [transition-delay:150ms]">
                <div class="relative flex h-44 w-44 items-center justify-center sm:h-64 sm:w-64">
                    <div class="absolute inset-0 rounded-full bg-white/5 ring-1 ring-white/10"></div>
                    <div class="motion-safe-only absolute inset-4 animate-[spin_18s_linear_infinite] rounded-full border-2 border-dashed border-white/20 sm:inset-6"></div>
                    <div class="relative flex flex-col items-center">
                        <i class="fa-solid fa-seedling motion-safe-only origin-bottom animate-sway text-5xl text-emerald-300 sm:text-7xl drop-shadow-lg"></i>
                        <p id="v-hero-status" class="mt-3 rounded-full bg-white/15 px-3 py-1 text-xs sm:mt-4 sm:px-4 sm:py-1.5 sm:text-sm font-semibold ring-1 ring-white/20">--</p>
                    </div>
                </div>
            </div>
        </div>

        <svg class="relative block h-8 w-full text-slate-50 sm:h-12" viewBox="0 0 1440 48" preserveAspectRatio="none" aria-hidden="true">
            <path fill="currentColor" d="M0 48h1440V20c-120 18-240 24-360 14S840 0 720 4 480 30 360 32 120 22 0 12z" />
        </svg>
    </section>

    <main class="mx-auto max-w-7xl space-y-12 px-4 py-8 sm:space-y-16 sm:px-6 sm:py-12">
        {{-- Live readings --}}
        <section id="live" class="scroll-mt-24">
            <div class="reveal mb-6">
                <p class="text-xs font-semibold tracking-widest text-brand-600 uppercase">Kondisi Saat Ini</p>
                <h2 class="mt-1 text-xl font-bold text-slate-900 sm:text-2xl">Data Sensor Live</h2>
                <p id="v-updated" class="text-sm text-slate-500">Menunggu data…</p>
            </div>

            <div class="grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-4">
                {{-- Soil moisture --}}
                <x-ui.card padding="p-4 sm:p-6" class="reveal transition hover:-translate-y-1 hover:shadow-lg">
                    <div class="flex items-start justify-between gap-2">
                        <p class="text-xs font-semibold text-slate-700 sm:text-sm">Kelembaban Tanah</p>
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg sm:h-9 sm:w-9 bg-brand-50 text-brand-600"><i class="fa-solid fa-seedling"></i></span>
                    </div>
                    <div class="relative mx-auto mt-4 h-24 w-24 sm:h-32 sm:w-32">
                        <svg viewBox="0 0 120 120" class="h-full w-full -rotate-90">
                            <circle cx="60" cy="60" r="52" fill="none" stroke-width="10" class="stroke-slate-100" />
                            <circle id="v-soil-gauge" cx="60" cy="60" r="52" fill="none" stroke-width="10" stroke-linecap="round"
                                stroke-dasharray="326.73" stroke-dashoffset="326.73" class="stroke-brand-500 transition-all duration-1000" />
                        </svg>
                        <div class="absolute inset-0 flex items-center justify-center">
                            <p class="text-2xl font-bold text-slate-900 sm:text-3xl"><span id="v-soil">--</span><span class="text-base text-slate-400">%</span></p>
                        </div>
                    </div>
                    <p id="v-soil-label" class="mt-3 text-center text-[11px] font-semibold text-slate-500 sm:text-xs">--</p>
                </x-ui.card>

                {{-- Water tank with moving wave --}}
                <x-ui.card padding="p-4 sm:p-6" class="reveal transition hover:-translate-y-1 hover:shadow-lg [transition-delay:80ms]">
                    <div class="flex items-start justify-between gap-2">
                        <p class="text-xs font-semibold text-slate-700 sm:text-sm">Air Tandon (Float Switch)</p>
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg sm:h-9 sm:w-9 bg-violet-50 text-violet-600"><i class="fa-solid fa-water"></i></span>
                    </div>
                    <div class="relative mx-auto mt-4 h-24 w-20 overflow-hidden sm:h-32 sm:w-24 rounded-b-3xl rounded-t-lg border-4 border-slate-200 bg-slate-50">
                        <div id="v-water-fill" class="absolute inset-x-0 bottom-0 h-0 bg-violet-500/80 transition-all duration-1000">
                            <svg class="motion-safe-only absolute -top-3 left-0 h-4 w-[200%] animate-wave text-violet-500/80" viewBox="0 0 200 16" preserveAspectRatio="none" aria-hidden="true">
                                <path fill="currentColor" d="M0 8c12.5 0 12.5-8 25-8s12.5 8 25 8 12.5-8 25-8 12.5 8 25 8 12.5-8 25-8 12.5 8 25 8 12.5-8 25-8 12.5 8 25 8v8H0z" />
                            </svg>
                        </div>
                        <p id="v-water" class="absolute inset-0 flex items-center justify-center text-base font-bold text-slate-900 mix-blend-multiply sm:text-xl">--</p>
                    </div>
                    <p id="v-water-label" class="mt-3 text-center text-[11px] font-semibold text-slate-500 sm:text-xs">--</p>
                </x-ui.card>

                {{-- Soil temperature (DS18B20) --}}
                <x-ui.card padding="p-4 sm:p-6" class="reveal transition hover:-translate-y-1 hover:shadow-lg [transition-delay:160ms]">
                    <div class="flex items-start justify-between gap-2">
                        <p class="text-xs font-semibold text-slate-700 sm:text-sm">Suhu Tanah</p>
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg sm:h-9 sm:w-9 bg-orange-50 text-orange-500"><i class="fa-solid fa-temperature-half"></i></span>
                    </div>
                    <div class="mt-4 flex h-24 items-end justify-center gap-3 sm:h-32 sm:gap-5">
                        <div class="relative flex h-full w-5 items-end sm:w-6 overflow-hidden rounded-full bg-slate-100">
                            <div id="v-soil-temp-bar" class="w-full rounded-full bg-linear-to-t from-orange-500 to-amber-300 transition-all duration-1000" style="height: 0%"></div>
                        </div>
                        <p class="pb-1 text-2xl font-bold text-slate-900 sm:pb-2 sm:text-4xl"><span id="v-soil-temp">--</span><span class="text-base text-slate-400">°C</span></p>
                    </div>
                    <p id="v-soil-temp-label" class="mt-3 text-center text-[11px] font-semibold text-slate-500 sm:text-xs">Sensor DS18B20</p>
                </x-ui.card>

                {{-- Buzzer alarm --}}
                <x-ui.card padding="p-4 sm:p-6" class="reveal transition hover:-translate-y-1 hover:shadow-lg [transition-delay:240ms]">
                    <div class="flex items-start justify-between gap-2">
                        <p class="text-xs font-semibold text-slate-700 sm:text-sm">Alarm Buzzer</p>
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg sm:h-9 sm:w-9 bg-amber-50 text-amber-500"><i class="fa-solid fa-bell"></i></span>
                    </div>
                    <div class="mt-4 flex h-24 flex-col items-center justify-center gap-2 sm:h-32">
                        <span id="v-buzzer-icon" class="flex h-14 w-14 items-center justify-center rounded-full bg-slate-100 text-2xl text-slate-300 transition sm:h-20 sm:w-20 sm:text-4xl"><i class="fa-solid fa-bell"></i></span>
                        <p id="v-buzzer" class="text-base font-bold text-slate-400 sm:text-xl">--</p>
                    </div>
                    <p id="v-buzzer-label" class="mt-3 text-center text-[11px] font-semibold text-slate-500 sm:text-xs">Air habis / tanah kering</p>
                </x-ui.card>
            </div>

            {{-- Pump strip --}}
            <div class="reveal mt-4 flex flex-wrap items-center justify-between gap-4 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <div class="flex items-center gap-3">
                    <span id="v-pump-icon" class="flex h-11 w-11 items-center justify-center rounded-xl bg-slate-100 text-slate-400 transition"><i class="fa-solid fa-faucet-drip"></i></span>
                    <div>
                        <p class="text-sm font-semibold text-slate-900">Pompa Penyiram <span id="v-pump-mode" class="ml-1 rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-semibold text-slate-500">--</span></p>
                        <p id="v-pump-text" class="text-xs text-slate-500">Menunggu data</p>
                    </div>
                </div>
                <span id="v-pump-badge" class="rounded-full bg-slate-100 px-3 py-1 text-sm font-bold text-slate-500">--</span>
            </div>
        </section>

        {{-- General report --}}
        <section id="laporan" class="scroll-mt-24">
            <div class="reveal mb-6 flex flex-wrap items-end justify-between gap-4">
                <div>
                    <p class="text-xs font-semibold tracking-widest text-brand-600 uppercase">Laporan Umum</p>
                    <h2 class="mt-1 text-xl font-bold text-slate-900 sm:text-2xl">Ringkasan {{ $periodLabels[$days] }}</h2>
                    <p class="text-sm text-slate-500">{{ $from->format('d M Y') }} – {{ now()->format('d M Y') }}</p>
                </div>

                <div class="flex w-full rounded-xl bg-slate-200/70 p-1 text-sm font-semibold sm:inline-flex sm:w-auto">
                    @foreach ($periods as $period)
                        <a href="{{ route('visitor', ['days' => $period]) }}#laporan" @class([
                            'flex-1 rounded-lg px-4 py-2 text-center transition sm:flex-none',
                            'bg-white text-brand-700 shadow-sm' => $period === $days,
                            'text-slate-500 hover:text-slate-900' => $period !== $days,
                        ])>{{ $periodLabels[$period] }}</a>
                    @endforeach
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-5">
                @foreach ([
                    ['label' => 'Total data', 'value' => $summary['total'], 'decimals' => 0, 'unit' => '', 'icon' => 'fa-database', 'tone' => 'slate', 'hint' => 'pembacaan sensor'],
                    ['label' => 'Rata-rata tanah', 'value' => $summary['avg_soil'], 'decimals' => 1, 'unit' => '%', 'icon' => 'fa-seedling', 'tone' => 'brand', 'hint' => 'kelembaban tanah'],
                    ['label' => 'Rata-rata suhu tanah', 'value' => $summary['avg_soil_temperature'], 'decimals' => 1, 'unit' => '°C', 'icon' => 'fa-temperature-half', 'tone' => 'amber', 'hint' => $format($summary['min_soil_temperature']).' – '.$format($summary['max_soil_temperature']).' °C'],
                    ['label' => 'Alarm buzzer', 'value' => $summary['buzzer_percent'], 'decimals' => 0, 'unit' => '%', 'icon' => 'fa-bell', 'tone' => 'red', 'hint' => 'air habis / tanah kering'],
                    ['label' => 'Pompa aktif', 'value' => $summary['pump_percent'], 'decimals' => 0, 'unit' => '%', 'icon' => 'fa-faucet-drip', 'tone' => 'emerald', 'hint' => 'dari seluruh data'],
                ] as $index => $tile)
                    <x-ui.card padding="p-4 sm:p-6" class="reveal first:col-span-2 lg:first:col-span-1" style="transition-delay: {{ $index * 70 }}ms">
                        <span class="flex h-10 w-10 items-center justify-center rounded-xl {{ $tones[$tile['tone']] }}"><i class="fa-solid {{ $tile['icon'] }}"></i></span>
                        <p class="mt-4 text-xs font-medium text-slate-500">{{ $tile['label'] }}</p>
                        <p class="mt-1 text-2xl font-bold text-slate-900 sm:text-3xl">
                            <span @if ($tile['value'] !== null) data-count="{{ $tile['value'] }}" data-decimals="{{ $tile['decimals'] }}" @endif>{{ $format($tile['value'] === null ? null : (float) $tile['value'], $tile['decimals']) }}</span><span class="text-base text-slate-400">{{ $tile['unit'] }}</span>
                        </p>
                        <p class="mt-1 text-xs text-slate-400">{{ $tile['hint'] }}</p>
                    </x-ui.card>
                @endforeach
            </div>

            <div class="mt-3 grid grid-cols-1 gap-3 sm:mt-4 sm:gap-4 lg:grid-cols-[1fr_1.3fr]">
                {{-- Status distribution --}}
                <x-ui.card padding="p-4 sm:p-6" class="reveal">
                    <h3 class="text-base font-semibold text-slate-900">Distribusi Status Sistem</h3>
                    <p class="text-xs text-slate-400">Persentase pembacaan per kondisi</p>

                    <div class="mt-5 flex h-4 overflow-hidden rounded-full bg-slate-100">
                        <div class="h-full bg-emerald-500 transition-all duration-1000" data-width="{{ $summary['ok_percent'] }}" style="width: 0%"></div>
                        <div class="h-full bg-amber-400 transition-all duration-1000 [transition-delay:200ms]" data-width="{{ $summary['dry_percent'] }}" style="width: 0%"></div>
                        <div class="h-full bg-red-500 transition-all duration-1000 [transition-delay:400ms]" data-width="{{ $summary['empty_percent'] }}" style="width: 0%"></div>
                    </div>

                    <ul class="mt-5 space-y-3 text-sm">
                        @foreach ([
                            ['label' => 'Aman - Basah', 'count' => $summary['ok'], 'percent' => $summary['ok_percent'], 'dot' => 'bg-emerald-500'],
                            ['label' => 'Tanah Kering', 'count' => $summary['dry'], 'percent' => $summary['dry_percent'], 'dot' => 'bg-amber-400'],
                            ['label' => 'Air Habis', 'count' => $summary['empty'], 'percent' => $summary['empty_percent'], 'dot' => 'bg-red-500'],
                        ] as $status)
                            <li class="flex items-center justify-between">
                                <span class="flex items-center gap-2 text-slate-600"><span class="h-2.5 w-2.5 rounded-full {{ $status['dot'] }}"></span>{{ $status['label'] }}</span>
                                <span class="font-semibold text-slate-900">{{ $status['percent'] }}% <span class="font-normal text-slate-400">({{ number_format($status['count'], 0, ',', '.') }})</span></span>
                            </li>
                        @endforeach
                    </ul>
                </x-ui.card>

                {{-- Plain-language conclusions --}}
                <x-ui.card padding="p-4 sm:p-6" class="reveal [transition-delay:120ms]">
                    <h3 class="text-base font-semibold text-slate-900">Kesimpulan</h3>
                    <p class="text-xs text-slate-400">Dibuat otomatis dari data periode ini</p>

                    <ul class="mt-4 space-y-3">
                        @foreach ($conclusions as $conclusion)
                            <li class="flex items-start gap-3">
                                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg {{ $tones[$conclusion['tone']] }}"><i class="fa-solid {{ $conclusion['icon'] }} text-sm"></i></span>
                                <p class="pt-1.5 text-sm text-slate-700">{{ $conclusion['text'] }}</p>
                            </li>
                        @endforeach
                    </ul>
                </x-ui.card>
            </div>
        </section>

        {{-- Charts --}}
        <section id="grafik" class="scroll-mt-24">
            <div class="reveal mb-6">
                <p class="text-xs font-semibold tracking-widest text-brand-600 uppercase">Tren</p>
                <h2 class="mt-1 text-xl font-bold text-slate-900 sm:text-2xl">Grafik Sensor</h2>
            </div>

            <div class="grid grid-cols-1 gap-3 sm:gap-4 lg:grid-cols-2">
                <x-ui.card padding="p-4 sm:p-6" class="reveal">
                    <h3 class="text-base font-semibold text-slate-900">Rata-rata Harian · {{ $periodLabels[$days] }}</h3>
                    <p class="text-xs text-slate-400">Batang = kelembaban tanah (%), garis = suhu tanah (°C)</p>
                    <div class="relative mt-4 h-60 sm:h-72">
                        <canvas id="v-daily-chart"></canvas>
                    </div>
                </x-ui.card>

                <x-ui.card padding="p-4 sm:p-6" class="reveal [transition-delay:120ms]">
                    <h3 class="text-base font-semibold text-slate-900">Kelembaban &amp; Suhu Tanah Live</h3>
                    <p class="text-xs text-slate-400">{{ count($recent) }} pembacaan terakhir, diperbarui otomatis</p>
                    <div class="relative mt-4 h-60 sm:h-72">
                        <canvas id="v-live-chart"></canvas>
                    </div>
                </x-ui.card>
            </div>
        </section>

        {{-- Data table --}}
        <section id="tabel" class="scroll-mt-24">
            <div class="reveal mb-6 flex flex-wrap items-end justify-between gap-4">
                <div>
                    <p class="text-xs font-semibold tracking-widest text-brand-600 uppercase">Data</p>
                    <h2 class="mt-1 text-xl font-bold text-slate-900 sm:text-2xl">Tabel Data Sensor</h2>
                    <p id="v-table-summary" class="text-sm text-slate-500">Memuat data…</p>
                </div>
                <div class="flex flex-wrap gap-2 text-xs font-semibold" id="v-table-quick">
                    <button type="button" data-quick="today" class="rounded-full border border-slate-200 bg-white px-3 py-1.5 text-slate-600 transition hover:border-brand-300 hover:text-brand-700">Hari ini</button>
                    <button type="button" data-quick="7" class="rounded-full border border-slate-200 bg-white px-3 py-1.5 text-slate-600 transition hover:border-brand-300 hover:text-brand-700">7 hari</button>
                    <button type="button" data-quick="30" class="rounded-full border border-slate-200 bg-white px-3 py-1.5 text-slate-600 transition hover:border-brand-300 hover:text-brand-700">30 hari</button>
                    <button type="button" data-quick="all" class="rounded-full border border-slate-200 bg-white px-3 py-1.5 text-slate-600 transition hover:border-brand-300 hover:text-brand-700">Semua</button>
                </div>
            </div>

            <x-ui.card padding="p-0" class="reveal overflow-hidden">
                <form id="v-table-form" method="GET" action="{{ route('visitor') }}#tabel" class="grid grid-cols-2 gap-3 border-b border-slate-200 px-4 py-4 sm:px-6 lg:grid-cols-[1fr_1fr_1fr_auto]">
                    <input type="hidden" name="days" value="{{ $days }}">
                    <div class="col-span-full">
                        <x-ui.label for="v-table-search" value="Cari data" />
                        <x-ui.input id="v-table-search" type="search" name="q" icon="fa-magnifying-glass" maxlength="50" autocomplete="off"
                            value="{{ $table['filters']['q'] }}" placeholder="Cari angka, tanggal, jam, atau status…" />
                        <p class="mt-1 text-xs text-slate-400">Contoh: 35, 27.5, 2026-10-21, 14:05, kering, habis, menyiram, manual off, alarm</p>
                    </div>
                    <div>
                        <x-ui.label for="v-table-from" value="Dari tanggal" />
                        <x-ui.input id="v-table-from" type="date" name="date_from" icon="fa-calendar" value="{{ $table['filters']['date_from'] }}" />
                    </div>
                    <div>
                        <x-ui.label for="v-table-to" value="Sampai tanggal" />
                        <x-ui.input id="v-table-to" type="date" name="date_to" icon="fa-calendar-check" value="{{ $table['filters']['date_to'] }}" />
                    </div>
                    <div class="col-span-2 lg:col-span-1">
                        <x-ui.label for="v-table-status" value="Status" />
                        <select id="v-table-status" name="status" class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-500/30 focus:outline-none">
                            <option value="">Semua status</option>
                            <option value="ok" @selected($table['filters']['status'] === 'ok')>Aman - Basah</option>
                            <option value="dry" @selected($table['filters']['status'] === 'dry')>Tanah kering</option>
                            <option value="empty" @selected($table['filters']['status'] === 'empty')>Air Habis</option>
                        </select>
                    </div>
                    <div class="col-span-2 flex items-end gap-2 lg:col-span-1">
                        <x-ui.button type="submit" class="flex-1 lg:flex-none">
                            <i class="fa-solid fa-filter"></i>
                            Terapkan
                        </x-ui.button>
                        <a href="{{ route('visitor', ['days' => $days]) }}#tabel" class="inline-flex flex-1 items-center justify-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 lg:flex-none">Reset</a>
                    </div>
                </form>
                @if ($table['error'])
                    <p class="px-4 pt-3 text-sm text-red-600 sm:px-6">{{ $table['error'] }} Filter diabaikan.</p>
                @endif

                <ul id="v-table-cards" class="divide-y divide-slate-100 sm:hidden"></ul>

                <div class="hidden overflow-x-auto sm:block">
                    <table class="min-w-full text-left text-sm">
                        <thead class="bg-slate-50 text-xs tracking-wide text-slate-500 uppercase">
                            <tr>
                                <th class="px-6 py-3 font-medium">Waktu</th>
                                <th class="px-4 py-3 font-medium">Kelembaban Tanah</th>
                                <th class="px-4 py-3 font-medium">Air Tandon</th>
                                <th class="px-4 py-3 font-medium">Suhu Tanah</th>
                                <th class="px-4 py-3 font-medium">Pompa</th>
                                <th class="px-4 py-3 font-medium">Mode</th>
                                <th class="px-4 py-3 font-medium">Status</th>
                            </tr>
                        </thead>
                        <tbody id="v-table-body" class="divide-y divide-slate-100"></tbody>
                    </table>
                </div>
                <p id="v-table-empty" class="hidden px-6 py-10 text-center text-sm text-slate-400">Tidak ada data pada filter ini.</p>

                <div class="flex items-center justify-between gap-3 border-t border-slate-200 px-4 py-4 text-sm sm:px-6">
                    <p id="v-table-page" class="text-xs text-slate-500 sm:text-sm">-</p>
                    <div class="flex gap-2">
                        <x-ui.button variant="secondary" id="v-table-prev" aria-label="Halaman sebelumnya" :disabled="! $table['links']['prev']">
                            <i class="fa-solid fa-chevron-left"></i>
                            <span class="hidden sm:inline">Sebelumnya</span>
                        </x-ui.button>
                        <x-ui.button variant="secondary" id="v-table-next" aria-label="Halaman berikutnya" :disabled="! $table['links']['next']">
                            <span class="hidden sm:inline">Berikutnya</span>
                            <i class="fa-solid fa-chevron-right"></i>
                        </x-ui.button>
                    </div>
                </div>
            </x-ui.card>
        </section>

        {{-- CSV download --}}
        <section id="download" class="scroll-mt-24">
            <x-ui.card padding="p-4 sm:p-6" class="reveal relative overflow-hidden">
                <div class="pointer-events-none absolute -top-16 -right-16 h-48 w-48 rounded-full bg-emerald-100/60"></div>

                <div class="relative mb-5 flex flex-wrap items-start justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600"><i class="fa-solid fa-file-csv text-lg"></i></span>
                        <div>
                            <h2 class="text-lg font-semibold text-slate-900">Download Data CSV</h2>
                            <p class="text-xs text-slate-400">Pilih jenis data dan rentang tanggal. File dapat dibuka di Excel atau Google Sheets.</p>
                        </div>
                    </div>
                    <div class="flex flex-wrap gap-2 text-xs font-semibold" id="v-export-quick">
                        <button type="button" data-quick="today" class="rounded-full border border-slate-200 bg-white px-3 py-1.5 text-slate-600 transition hover:border-emerald-300 hover:text-emerald-700">Hari ini</button>
                        <button type="button" data-quick="7" class="rounded-full border border-slate-200 bg-white px-3 py-1.5 text-slate-600 transition hover:border-emerald-300 hover:text-emerald-700">7 hari</button>
                        <button type="button" data-quick="30" class="rounded-full border border-slate-200 bg-white px-3 py-1.5 text-slate-600 transition hover:border-emerald-300 hover:text-emerald-700">30 hari</button>
                        <button type="button" data-quick="all" class="rounded-full border border-slate-200 bg-white px-3 py-1.5 text-slate-600 transition hover:border-emerald-300 hover:text-emerald-700">Semua</button>
                    </div>
                </div>

                @if ($errors->export->any())
                    <x-ui.alert variant="error" class="relative mb-4">{{ $errors->export->first() }}</x-ui.alert>
                @endif

                <form id="v-export-form" method="GET" action="{{ route('visitor.export') }}" class="relative">
                    <div class="grid grid-cols-2 gap-2 sm:gap-3 lg:grid-cols-4">
                        @foreach ($datasets as $dataset)
                            <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 bg-white p-3 transition hover:bg-slate-50 has-checked:border-emerald-400 has-checked:bg-emerald-50/60">
                                <input type="checkbox" name="datasets[]" value="{{ $dataset->value }}" class="mt-0.5 h-4 w-4 accent-emerald-600"
                                    @checked(in_array($dataset->value, old('datasets', array_column($datasets, 'value')), true))>
                                <span class="block text-xs font-semibold text-slate-800 sm:text-sm">{{ $dataset->label() }}</span>
                            </label>
                        @endforeach
                    </div>

                    <div class="mt-5 grid grid-cols-2 gap-3 sm:grid-cols-[1fr_1fr_auto]">
                        <div>
                            <x-ui.label for="v-export-from" value="Dari tanggal" />
                            <x-ui.input id="v-export-from" type="date" name="date_from" icon="fa-calendar" value="{{ old('date_from') }}" />
                        </div>
                        <div>
                            <x-ui.label for="v-export-to" value="Sampai tanggal" />
                            <x-ui.input id="v-export-to" type="date" name="date_to" icon="fa-calendar-check" value="{{ old('date_to') }}" />
                        </div>
                        <div class="col-span-2 flex items-end sm:col-span-1">
                            <button type="submit" id="v-export-submit" class="group inline-flex w-full items-center justify-center gap-2 rounded-lg bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-60">
                                <i class="fa-solid fa-download transition group-hover:translate-y-0.5"></i>
                                Download CSV
                            </button>
                        </div>
                    </div>

                    <p id="v-export-summary" class="mt-3 text-xs text-slate-500">Kolom id &amp; waktu selalu disertakan.</p>
                </form>
            </x-ui.card>
        </section>
    </main>

    <footer class="border-t border-slate-200 bg-white">
        <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-3 px-4 py-6 text-xs text-slate-400 sm:px-6">
            <p>&copy; {{ now()->year }} Taman Pintar IoT · Smart Irrigation ESP32</p>
            <p>Data diperbarui otomatis setiap beberapa detik.</p>
        </div>
    </footer>

    <x-visitor.bottom-nav />

    @push('scripts')
        <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
        <x-sensor-stream />
        <script>
            (() => {
                const CLOCK_TICK_MS = 5000;
                const STALE_AFTER_MS = 60000;
                const RECENT_LIMIT = 30;
                const GAUGE_CIRCUMFERENCE = 326.73;
                const REDUCED_MOTION = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

                const $ = (id) => document.getElementById(id);
                const setText = (id, value) => { $(id).textContent = value; };
                const clamp = (value, min, max) => Math.min(Math.max(value, min), max);
                const number = (value, decimals = 1) => Number(value).toLocaleString('id-ID', { minimumFractionDigits: decimals, maximumFractionDigits: decimals });
                const hasSoilTemperature = (d) => d.soil_temperature !== null && d.soil_temperature !== undefined;
                // The float switch is on/off only, so the tank shows "full enough" or "empty".
                const waterLevel = (d) => (d.is_water_empty ? 8 : 75);

                const toYmd = (date) => [
                    date.getFullYear(),
                    String(date.getMonth() + 1).padStart(2, '0'),
                    String(date.getDate()).padStart(2, '0'),
                ].join('-');

                const daysAgo = (days) => {
                    const date = new Date();
                    date.setDate(date.getDate() - days);
                    return toYmd(date);
                };

                const formatAgo = (ms) => {
                    const seconds = Math.max(Math.round(ms / 1000), 0);
                    if (seconds < 60) return seconds + ' dtk lalu';
                    if (seconds < 3600) return Math.round(seconds / 60) + ' mnt lalu';
                    return Math.round(seconds / 3600) + ' jam lalu';
                };

                /* ---------- Scroll reveal, count-up and bar animations ---------- */

                const countUp = (element) => {
                    const target = Number(element.dataset.count);
                    const decimals = Number(element.dataset.decimals || 0);

                    if (REDUCED_MOTION) {
                        element.textContent = number(target, decimals);
                        return;
                    }

                    const start = performance.now();
                    const duration = 1200;
                    const step = (now) => {
                        const progress = Math.min((now - start) / duration, 1);
                        const eased = 1 - Math.pow(1 - progress, 3);
                        element.textContent = number(target * eased, decimals);
                        if (progress < 1) requestAnimationFrame(step);
                    };
                    requestAnimationFrame(step);
                };

                const revealObserver = new IntersectionObserver((entries) => {
                    entries.forEach((entry) => {
                        if (!entry.isIntersecting) return;

                        entry.target.classList.add('is-visible');
                        entry.target.querySelectorAll('[data-count]').forEach(countUp);
                        entry.target.querySelectorAll('[data-width]').forEach((bar) => { bar.style.width = bar.dataset.width + '%'; });
                        revealObserver.unobserve(entry.target);
                    });
                }, { threshold: 0.15 });

                document.querySelectorAll('.reveal').forEach((element) => revealObserver.observe(element));

                /* ---------- Live cards ---------- */

                const { pumpState, pumpModeLabel, soilTemperatureLevel, buzzerReason } = window.SensorStream;


                const renderLatest = (d) => {
                    const state = pumpState(d);
                    setText('v-hero-status', state.label);

                    setText('v-soil', d.soil_percent);
                    $('v-soil-gauge').style.strokeDashoffset = GAUGE_CIRCUMFERENCE * (1 - clamp(d.soil_percent, 0, 100) / 100);
                    $('v-soil-gauge').setAttribute('class', 'transition-all duration-1000 ' + (d.is_soil_dry ? 'stroke-amber-500' : 'stroke-brand-500'));
                    setText('v-soil-label', d.is_soil_dry ? 'Tanah kering' : 'Tanah lembab');

                    setText('v-water', d.is_water_empty ? 'HABIS' : 'ADA');
                    $('v-water-fill').style.height = waterLevel(d) + '%';
                    setText('v-water-label', d.is_water_empty ? 'Air habis, perlu diisi' : 'Air tersedia');

                    if (hasSoilTemperature(d)) {
                        setText('v-soil-temp', number(d.soil_temperature));
                        $('v-soil-temp-bar').style.height = clamp((d.soil_temperature / 50) * 100, 0, 100) + '%';
                        setText('v-soil-temp-label', soilTemperatureLevel(d.soil_temperature).label + ' · Sensor DS18B20');
                    } else {
                        setText('v-soil-temp-label', 'Sensor DS18B20 tidak terbaca');
                    }

                    const hasBuzzer = d.buzzer_alarm !== null && d.buzzer_alarm !== undefined;
                    setText('v-buzzer', hasBuzzer ? (d.buzzer_alarm ? 'BERBUNYI' : 'DIAM') : '--');
                    $('v-buzzer').className = 'text-base font-bold sm:text-xl ' + (d.buzzer_alarm ? 'text-amber-500' : 'text-slate-400');
                    $('v-buzzer-icon').className = 'flex h-14 w-14 items-center justify-center rounded-full text-2xl transition sm:h-20 sm:w-20 sm:text-4xl '
                        + (d.buzzer_alarm ? 'animate-pulse bg-amber-400 text-white' : 'bg-slate-100 text-slate-300');
                    setText('v-buzzer-label', d.buzzer_alarm ? (buzzerReason(d) ?? 'Kondisi alarm') : hasBuzzer ? 'Tidak ada kondisi alarm' : 'Firmware belum mengirim status');

                    setText('v-pump-text', state.text);
                    setText('v-pump-mode', pumpModeLabel(d.pump_command));
                    setText('v-pump-badge', d.pump_status ? 'ON' : 'OFF');
                    $('v-pump-badge').className = 'rounded-full px-3 py-1 text-sm font-bold ' + (d.pump_status ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500');
                    $('v-pump-icon').className = 'flex h-11 w-11 items-center justify-center rounded-xl transition ' + (d.pump_status ? 'animate-pulse bg-emerald-500 text-white' : 'bg-slate-100 text-slate-400');
                };

                const setLive = (state, label) => {
                    const colors = { live: 'bg-emerald-400', stale: 'bg-amber-400', error: 'bg-red-400', idle: 'bg-slate-300' };
                    $('v-live-dot').className = 'relative inline-flex h-2.5 w-2.5 rounded-full ' + colors[state];
                    $('v-live-ping').className = 'absolute inline-flex h-full w-full rounded-full opacity-75 ' + (state === 'live' ? 'animate-ping ' + colors[state] : '');
                    setText('v-live-label', label);
                };

                let streamState = 'connecting';

                const renderDevice = () => {
                    const latest = rows.at(-1);
                    const age = latest ? Date.now() - new Date(latest.created_at).getTime() : null;
                    const isOnline = age !== null && age <= STALE_AFTER_MS;

                    setText('v-updated', latest
                        ? 'Update terakhir ' + latest.created_at_label + ' (' + formatAgo(age) + ')'
                        : 'Menunggu ESP32 mengirim data pertama.');

                    if (streamState === 'offline') {
                        setLive('error', 'MQTT terputus');
                    } else if (streamState === 'connecting') {
                        setLive('idle', 'Menghubungkan MQTT…');
                    } else if (!latest) {
                        setLive('idle', 'Belum ada data');
                    } else {
                        setLive(isOnline ? 'live' : 'stale', isOnline ? 'Live' : 'Offline');
                    }
                };

                /* ---------- Charts ---------- */

                const COLORS = { soil: '#2563eb', temperature: '#f97316' };
                const axis = { grid: { color: '#f1f5f9' }, border: { display: false }, ticks: { color: '#94a3b8' } };
                const tooltip = { backgroundColor: '#0f172a', padding: 12, cornerRadius: 10, usePointStyle: true };
                const legend = { position: 'bottom', labels: { usePointStyle: true, boxWidth: 8, color: '#475569' } };
                const animation = REDUCED_MOTION ? false : { duration: 1200, easing: 'easeOutQuart' };

                const daily = @json($daily);

                new Chart($('v-daily-chart'), {
                    data: {
                        labels: daily.map((d) => d.label),
                        datasets: [
                            { type: 'bar', label: 'Tanah (%)', data: daily.map((d) => d.avg_soil), backgroundColor: COLORS.soil + '33', borderColor: COLORS.soil, borderWidth: 1.5, borderRadius: 6, yAxisID: 'percent', order: 3 },
                            { type: 'line', label: 'Suhu tanah (°C)', data: daily.map((d) => d.avg_soil_temperature), borderColor: COLORS.temperature, backgroundColor: COLORS.temperature, tension: 0.4, spanGaps: true, yAxisID: 'temperature', order: 1 },
                        ],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        animation,
                        interaction: { mode: 'index', intersect: false },
                        scales: {
                            x: { ...axis, grid: { display: false } },
                            percent: { ...axis, position: 'left', min: 0, max: 100, ticks: { ...axis.ticks, callback: (v) => v + '%' } },
                            temperature: { ...axis, position: 'right', suggestedMin: 15, suggestedMax: 40, grid: { display: false }, ticks: { ...axis.ticks, callback: (v) => v + '°' } },
                        },
                        plugins: { legend, tooltip: { ...tooltip, callbacks: { afterTitle: (items) => daily[items[0].dataIndex].total + ' data' } } },
                    },
                });

                const liveChart = new Chart($('v-live-chart'), {
                    type: 'line',
                    data: {
                        labels: [],
                        datasets: [
                            { label: 'Suhu tanah (°C)', data: [], borderColor: COLORS.temperature, backgroundColor: COLORS.temperature + '22', fill: true, tension: 0.4, pointRadius: 0, spanGaps: true, yAxisID: 'temperature' },
                            { label: 'Kelembaban tanah (%)', data: [], borderColor: COLORS.soil, backgroundColor: COLORS.soil, borderDash: [6, 4], tension: 0.4, pointRadius: 0, spanGaps: true, yAxisID: 'percent' },
                        ],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        animation,
                        interaction: { mode: 'index', intersect: false },
                        scales: {
                            x: { ...axis, grid: { display: false }, ticks: { ...axis.ticks, maxTicksLimit: 6, maxRotation: 0 } },
                            temperature: { ...axis, position: 'left', suggestedMin: 15, suggestedMax: 40, ticks: { ...axis.ticks, callback: (v) => v + '°' } },
                            percent: { ...axis, position: 'right', min: 0, max: 100, grid: { display: false }, ticks: { ...axis.ticks, callback: (v) => v + '%' } },
                        },
                        plugins: { legend, tooltip },
                    },
                });

                const renderLiveChart = () => {
                    liveChart.data.labels = rows.map((d) => d.time_label);
                    liveChart.data.datasets[0].data = rows.map((d) => d.soil_temperature ?? null);
                    liveChart.data.datasets[1].data = rows.map((d) => d.soil_percent);
                    liveChart.update();
                };

                /* ---------- Live MQTT feed ---------- */

                let rows = @json($recent);

                const render = () => {
                    if (rows.length) renderLatest(rows.at(-1));
                    renderLiveChart();
                    renderDevice();
                };

                const pushReading = (row) => {
                    rows = [...rows, row].slice(-RECENT_LIMIT);
                    render();
                    prependLiveRow(row);
                };

                const onStreamStatus = (state) => {
                    streamState = state;
                    renderDevice();
                };

                /* ---------- Data table ---------- */

                const table = @json($table);
                const tableFilters = table.filters;
                let tableRows = table.data;

                const tableCell = (text, classes = 'px-4 py-3 text-slate-600') => {
                    const td = document.createElement('td');
                    td.className = classes;
                    td.textContent = text;
                    return td;
                };

                const badgeCell = (text, classes) => {
                    const td = document.createElement('td');
                    td.className = 'px-4 py-3';
                    const badge = document.createElement('span');
                    badge.className = 'inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold ' + classes;
                    badge.textContent = text;
                    td.append(badge);
                    return td;
                };

                /** Compact card used instead of the wide table on phones. */
                const mobileRow = (d) => {
                    const li = document.createElement('li');
                    li.className = 'px-4 py-3';

                    const head = document.createElement('div');
                    head.className = 'flex items-center justify-between gap-2';
                    const time = document.createElement('p');
                    time.className = 'text-xs font-medium text-slate-500';
                    time.textContent = d.created_at_label;
                    const badge = document.createElement('span');
                    badge.className = 'inline-flex items-center rounded-full px-2.5 py-0.5 text-[11px] font-semibold ' + pumpState(d).badge;
                    badge.textContent = pumpState(d).label;
                    head.append(time, badge);

                    const stats = document.createElement('dl');
                    stats.className = 'mt-2 grid grid-cols-4 gap-1 text-center';
                    [
                        ['Tanah', d.soil_percent + '%'],
                        ['Suhu', hasSoilTemperature(d) ? number(d.soil_temperature) + '°' : '--'],
                        ['Air', d.is_water_empty ? 'Habis' : 'Ada'],
                        ['Pompa', d.pump_status ? 'ON' : 'OFF'],
                    ].forEach(([label, value]) => {
                        const cell = document.createElement('div');
                        cell.className = 'rounded-lg bg-slate-50 px-1 py-1.5';
                        const dt = document.createElement('dt');
                        dt.className = 'text-[10px] text-slate-400';
                        dt.textContent = label;
                        const dd = document.createElement('dd');
                        dd.className = 'text-sm font-semibold text-slate-900';
                        dd.textContent = value;
                        cell.append(dt, dd);
                        stats.append(cell);
                    });

                    li.append(head, stats);
                    return li;
                };

                const tableRow = (d) => {
                    const tr = document.createElement('tr');
                    tr.className = 'transition-colors duration-1000 hover:bg-slate-50';
                    tr.append(
                        tableCell(d.created_at_label, 'whitespace-nowrap px-6 py-3 text-slate-600'),
                        tableCell(d.soil_percent + '%', 'px-4 py-3 font-semibold text-slate-900'),
                        badgeCell(d.is_water_empty ? 'Habis' : 'Ada', d.is_water_empty ? 'bg-red-100 text-red-700' : 'bg-sky-100 text-sky-700'),
                        tableCell(hasSoilTemperature(d) ? number(d.soil_temperature) + ' °C' : '--'),
                        badgeCell(d.pump_status ? 'ON' : 'OFF', d.pump_status ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500'),
                        tableCell(d.pump_command ? pumpModeLabel(d.pump_command) : '--', 'whitespace-nowrap px-4 py-3 text-slate-600'),
                        badgeCell(pumpState(d).label, pumpState(d).badge),
                    );
                    return tr;
                };

                /**
                 * Rows come with the page (no API); a highlighted row is a live MQTT reading.
                 */
                const renderTable = (highlight = null) => {
                    const meta = table.meta;
                    $('v-table-body').replaceChildren(...tableRows.map((d) => {
                        const tr = tableRow(d);
                        if (d === highlight) tr.classList.add('bg-brand-50');
                        return tr;
                    }));
                    $('v-table-cards').replaceChildren(...tableRows.map(mobileRow));
                    $('v-table-empty').classList.toggle('hidden', tableRows.length > 0);
                    setText('v-table-summary', meta.total.toLocaleString('id-ID') + ' data ditemukan' + (tableFilters.q ? ' untuk "' + tableFilters.q + '"' : '') + ', terbaru di atas.');
                    setText('v-table-page', meta.total ? 'Menampilkan ' + meta.from + '–' + meta.to + ' dari ' + meta.total.toLocaleString('id-ID') + ' · Hal. ' + meta.current_page + '/' + meta.last_page : 'Tidak ada data');

                    if (highlight) {
                        setTimeout(() => $('v-table-body').querySelectorAll('tr.bg-brand-50').forEach((tr) => tr.classList.remove('bg-brand-50')), 1500);
                    }
                };

                const conditionOf = (d) => (d.is_water_empty ? 'empty' : d.is_soil_dry ? 'dry' : 'ok');

                /**
                 * A live MQTT reading joins the first page when it matches the active filter.
                 */
                const prependLiveRow = (row) => {
                    const today = toYmd(new Date());
                    const matchesFilter = !tableFilters.q
                        && (!tableFilters.date_to || tableFilters.date_to >= today)
                        && (!tableFilters.date_from || tableFilters.date_from <= today)
                        && (!tableFilters.status || tableFilters.status === conditionOf(row));

                    if (table.meta.current_page !== 1 || !matchesFilter) {
                        return;
                    }

                    tableRows = [row, ...tableRows].slice(0, table.meta.per_page);
                    table.meta.total += 1;
                    table.meta.from = 1;
                    table.meta.to = tableRows.length;
                    renderTable(row);
                };

                $('v-table-form').addEventListener('submit', () => {
                    // Empty fields would otherwise end up in the URL as ?date_from=&status=
                    $('v-table-form').querySelectorAll('input, select').forEach((field) => { field.disabled = !field.value; });
                });

                // Re-enable the fields when the page comes back from the browser's back/forward cache.
                window.addEventListener('pageshow', () => {
                    $('v-table-form').querySelectorAll('input, select').forEach((field) => { field.disabled = false; });
                });

                document.querySelectorAll('#v-table-quick button').forEach((button) => {
                    button.addEventListener('click', () => {
                        const ranges = {
                            today: [daysAgo(0), daysAgo(0)],
                            7: [daysAgo(6), daysAgo(0)],
                            30: [daysAgo(29), daysAgo(0)],
                            all: ['', ''],
                        };
                        [$('v-table-from').value, $('v-table-to').value] = ranges[button.dataset.quick];
                        $('v-table-form').requestSubmit();
                    });
                });

                $('v-table-prev').addEventListener('click', () => { if (table.links.prev) window.location = table.links.prev; });
                $('v-table-next').addEventListener('click', () => { if (table.links.next) window.location = table.links.next; });

                /* ---------- CSV export ---------- */

                const exportForm = $('v-export-form');

                const updateExportSummary = () => {
                    const selected = exportForm.querySelectorAll('input[name="datasets[]"]:checked').length;
                    $('v-export-submit').disabled = selected === 0;
                    setText('v-export-summary', selected === 0
                        ? 'Pilih minimal satu jenis data.'
                        : selected + ' jenis data akan di-download. Kolom id & waktu selalu disertakan.');
                };

                exportForm.addEventListener('change', updateExportSummary);

                document.querySelectorAll('#v-export-quick button').forEach((button) => {
                    button.addEventListener('click', () => {
                        const ranges = {
                            today: [daysAgo(0), daysAgo(0)],
                            7: [daysAgo(6), daysAgo(0)],
                            30: [daysAgo(29), daysAgo(0)],
                            all: ['', ''],
                        };
                        [$('v-export-from').value, $('v-export-to').value] = ranges[button.dataset.quick];
                        updateExportSummary();
                    });
                });

                /* ---------- Boot ---------- */

                $('v-export-from').max = $('v-export-to').max = toYmd(new Date());
                $('v-table-from').max = $('v-table-to').max = toYmd(new Date());
                updateExportSummary();
                renderTable();
                render();
                window.SensorStream.connect({ onReading: pushReading, onStatus: onStreamStatus });
                // Keeps "x dtk lalu" fresh and flips to Offline when the ESP32 stops publishing.
                setInterval(renderDevice, CLOCK_TICK_MS);
            })();
        </script>
    @endpush
</x-layouts.guest>
