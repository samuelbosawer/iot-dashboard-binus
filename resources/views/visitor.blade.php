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

<x-layouts.guest title="Monitoring Tanaman Realtime" description="Pantau kelembaban tanah, air tandon, suhu & kelembaban udara, serta aktivitas penyiraman otomatis secara realtime. Lihat laporan, tabel data, grafik, dan download dataset CSV.">
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

        <div class="relative mx-auto grid max-w-7xl items-center gap-10 px-6 py-16 md:grid-cols-[1.4fr_1fr] md:py-24">
            <div class="reveal">
                <span class="inline-flex items-center gap-2 rounded-full bg-white/10 px-3 py-1.5 text-xs font-semibold tracking-widest text-brand-100 uppercase ring-1 ring-white/20">
                    <span class="relative flex h-2.5 w-2.5">
                        <span id="v-live-ping" class="absolute inline-flex h-full w-full rounded-full opacity-75"></span>
                        <span id="v-live-dot" class="relative inline-flex h-2.5 w-2.5 rounded-full bg-slate-300"></span>
                    </span>
                    <span id="v-live-label">Menghubungkan…</span>
                </span>

                <h1 class="mt-5 text-4xl font-bold tracking-tight sm:text-5xl">
                    Taman Pintar <span class="bg-linear-to-r from-emerald-300 via-brand-200 to-emerald-300 bg-[length:200%_100%] bg-clip-text text-transparent motion-safe-only animate-shimmer">IoT</span>
                </h1>
                <p class="mt-4 max-w-xl text-base text-brand-100 sm:text-lg">
                    Pantau kondisi tanaman secara langsung: kelembaban tanah, level air tandon, suhu &amp; kelembaban udara, serta aktivitas penyiraman otomatis oleh ESP32.
                </p>

                <div class="mt-8 flex flex-wrap gap-3">
                    <a href="#laporan" class="inline-flex items-center gap-2 rounded-xl bg-white px-5 py-3 text-sm font-semibold text-brand-700 shadow-lg shadow-brand-900/30 transition hover:-translate-y-0.5">
                        <i class="fa-solid fa-chart-pie"></i>
                        Lihat Laporan
                    </a>
                    <a href="#download" class="inline-flex items-center gap-2 rounded-xl bg-white/10 px-5 py-3 text-sm font-semibold text-white ring-1 ring-white/30 transition hover:-translate-y-0.5 hover:bg-white/20">
                        <i class="fa-solid fa-file-csv"></i>
                        Download CSV
                    </a>
                </div>
            </div>

            {{-- Swaying plant with live status --}}
            <div class="reveal flex justify-center [transition-delay:150ms]">
                <div class="relative flex h-64 w-64 items-center justify-center">
                    <div class="absolute inset-0 rounded-full bg-white/5 ring-1 ring-white/10"></div>
                    <div class="motion-safe-only absolute inset-6 animate-[spin_18s_linear_infinite] rounded-full border-2 border-dashed border-white/20"></div>
                    <div class="relative flex flex-col items-center">
                        <i class="fa-solid fa-seedling motion-safe-only origin-bottom animate-sway text-7xl text-emerald-300 drop-shadow-lg"></i>
                        <p id="v-hero-status" class="mt-4 rounded-full bg-white/15 px-4 py-1.5 text-sm font-semibold ring-1 ring-white/20">--</p>
                    </div>
                </div>
            </div>
        </div>

        <svg class="relative block h-12 w-full text-slate-50" viewBox="0 0 1440 48" preserveAspectRatio="none" aria-hidden="true">
            <path fill="currentColor" d="M0 48h1440V20c-120 18-240 24-360 14S840 0 720 4 480 30 360 32 120 22 0 12z" />
        </svg>
    </section>

    <main class="mx-auto max-w-7xl space-y-16 px-6 py-12">
        {{-- Live readings --}}
        <section id="live" class="scroll-mt-24">
            <div class="reveal mb-6">
                <p class="text-xs font-semibold tracking-widest text-brand-600 uppercase">Kondisi Saat Ini</p>
                <h2 class="mt-1 text-2xl font-bold text-slate-900">Data Sensor Live</h2>
                <p id="v-updated" class="text-sm text-slate-500">Menunggu data…</p>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                {{-- Soil moisture --}}
                <x-ui.card class="reveal transition hover:-translate-y-1 hover:shadow-lg">
                    <div class="flex items-center justify-between">
                        <p class="text-sm font-semibold text-slate-700">Kelembaban Tanah</p>
                        <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-brand-50 text-brand-600"><i class="fa-solid fa-seedling"></i></span>
                    </div>
                    <div class="relative mx-auto mt-4 h-32 w-32">
                        <svg viewBox="0 0 120 120" class="h-full w-full -rotate-90">
                            <circle cx="60" cy="60" r="52" fill="none" stroke-width="10" class="stroke-slate-100" />
                            <circle id="v-soil-gauge" cx="60" cy="60" r="52" fill="none" stroke-width="10" stroke-linecap="round"
                                stroke-dasharray="326.73" stroke-dashoffset="326.73" class="stroke-brand-500 transition-all duration-1000" />
                        </svg>
                        <div class="absolute inset-0 flex items-center justify-center">
                            <p class="text-3xl font-bold text-slate-900"><span id="v-soil">--</span><span class="text-base text-slate-400">%</span></p>
                        </div>
                    </div>
                    <p id="v-soil-label" class="mt-3 text-center text-xs font-semibold text-slate-500">--</p>
                </x-ui.card>

                {{-- Water tank with moving wave --}}
                <x-ui.card class="reveal transition hover:-translate-y-1 hover:shadow-lg [transition-delay:80ms]">
                    <div class="flex items-center justify-between">
                        <p class="text-sm font-semibold text-slate-700">Air Tandon (Float Switch)</p>
                        <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-violet-50 text-violet-600"><i class="fa-solid fa-water"></i></span>
                    </div>
                    <div class="relative mx-auto mt-4 h-32 w-24 overflow-hidden rounded-b-3xl rounded-t-lg border-4 border-slate-200 bg-slate-50">
                        <div id="v-water-fill" class="absolute inset-x-0 bottom-0 h-0 bg-violet-500/80 transition-all duration-1000">
                            <svg class="motion-safe-only absolute -top-3 left-0 h-4 w-[200%] animate-wave text-violet-500/80" viewBox="0 0 200 16" preserveAspectRatio="none" aria-hidden="true">
                                <path fill="currentColor" d="M0 8c12.5 0 12.5-8 25-8s12.5 8 25 8 12.5-8 25-8 12.5 8 25 8 12.5-8 25-8 12.5 8 25 8 12.5-8 25-8 12.5 8 25 8v8H0z" />
                            </svg>
                        </div>
                        <p id="v-water" class="absolute inset-0 flex items-center justify-center text-xl font-bold text-slate-900 mix-blend-multiply">--</p>
                    </div>
                    <p id="v-water-label" class="mt-3 text-center text-xs font-semibold text-slate-500">--</p>
                </x-ui.card>

                {{-- Air temperature --}}
                <x-ui.card class="reveal transition hover:-translate-y-1 hover:shadow-lg [transition-delay:160ms]">
                    <div class="flex items-center justify-between">
                        <p class="text-sm font-semibold text-slate-700">Suhu Udara</p>
                        <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-orange-50 text-orange-500"><i class="fa-solid fa-temperature-half"></i></span>
                    </div>
                    <div class="mt-4 flex h-32 items-end justify-center gap-5">
                        <div class="relative flex h-full w-6 items-end overflow-hidden rounded-full bg-slate-100">
                            <div id="v-temp-bar" class="w-full rounded-full bg-linear-to-t from-orange-500 to-amber-300 transition-all duration-1000" style="height: 0%"></div>
                        </div>
                        <p class="pb-2 text-4xl font-bold text-slate-900"><span id="v-temp">--</span><span class="text-base text-slate-400">°C</span></p>
                    </div>
                    <p id="v-temp-label" class="mt-3 text-center text-xs font-semibold text-slate-500">Sensor DHT</p>
                </x-ui.card>

                {{-- Air humidity --}}
                <x-ui.card class="reveal transition hover:-translate-y-1 hover:shadow-lg [transition-delay:240ms]">
                    <div class="flex items-center justify-between">
                        <p class="text-sm font-semibold text-slate-700">Kelembaban Udara</p>
                        <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-cyan-50 text-cyan-600"><i class="fa-solid fa-droplet"></i></span>
                    </div>
                    <div class="mt-4 flex h-32 items-center justify-center">
                        <div class="relative h-28 w-28">
                            <span class="absolute inset-0 flex items-center justify-center"><i class="fa-solid fa-droplet text-[7rem] text-slate-100"></i></span>
                            <div id="v-hum-clip" class="absolute inset-0 flex items-center justify-center transition-all duration-1000" style="clip-path: inset(100% 0 0 0)">
                                <i class="fa-solid fa-droplet text-[7rem] text-cyan-400"></i>
                            </div>
                            <p class="absolute inset-0 flex items-center justify-center pt-6 text-2xl font-bold text-slate-900"><span id="v-hum">--</span><span class="text-sm">%</span></p>
                        </div>
                    </div>
                    <p id="v-hum-label" class="mt-3 text-center text-xs font-semibold text-slate-500">Sensor DHT</p>
                </x-ui.card>
            </div>

            {{-- Pump strip --}}
            <div class="reveal mt-4 flex flex-wrap items-center justify-between gap-4 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <div class="flex items-center gap-3">
                    <span id="v-pump-icon" class="flex h-11 w-11 items-center justify-center rounded-xl bg-slate-100 text-slate-400 transition"><i class="fa-solid fa-faucet-drip"></i></span>
                    <div>
                        <p class="text-sm font-semibold text-slate-900">Pompa Penyiram</p>
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
                    <h2 class="mt-1 text-2xl font-bold text-slate-900">Ringkasan {{ $periodLabels[$days] }}</h2>
                    <p class="text-sm text-slate-500">{{ $from->format('d M Y') }} – {{ now()->format('d M Y') }}</p>
                </div>

                <div class="inline-flex rounded-xl bg-slate-200/70 p-1 text-sm font-semibold">
                    @foreach ($periods as $period)
                        <a href="{{ route('visitor', ['days' => $period]) }}#laporan" @class([
                            'rounded-lg px-4 py-2 transition',
                            'bg-white text-brand-700 shadow-sm' => $period === $days,
                            'text-slate-500 hover:text-slate-900' => $period !== $days,
                        ])>{{ $periodLabels[$period] }}</a>
                    @endforeach
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4 lg:grid-cols-5">
                @foreach ([
                    ['label' => 'Total data', 'value' => $summary['total'], 'decimals' => 0, 'unit' => '', 'icon' => 'fa-database', 'tone' => 'slate', 'hint' => 'pembacaan sensor'],
                    ['label' => 'Rata-rata tanah', 'value' => $summary['avg_soil'], 'decimals' => 1, 'unit' => '%', 'icon' => 'fa-seedling', 'tone' => 'brand', 'hint' => 'kelembaban tanah'],
                    ['label' => 'Rata-rata suhu', 'value' => $summary['avg_temperature'], 'decimals' => 1, 'unit' => '°C', 'icon' => 'fa-temperature-half', 'tone' => 'amber', 'hint' => $format($summary['min_temperature']).' – '.$format($summary['max_temperature']).' °C'],
                    ['label' => 'Rata-rata udara', 'value' => $summary['avg_humidity'], 'decimals' => 1, 'unit' => '%', 'icon' => 'fa-droplet', 'tone' => 'brand', 'hint' => $format($summary['min_humidity']).' – '.$format($summary['max_humidity']).' %'],
                    ['label' => 'Pompa aktif', 'value' => $summary['pump_percent'], 'decimals' => 0, 'unit' => '%', 'icon' => 'fa-faucet-drip', 'tone' => 'emerald', 'hint' => 'dari seluruh data'],
                ] as $index => $tile)
                    <x-ui.card class="reveal" style="transition-delay: {{ $index * 70 }}ms">
                        <span class="flex h-10 w-10 items-center justify-center rounded-xl {{ $tones[$tile['tone']] }}"><i class="fa-solid {{ $tile['icon'] }}"></i></span>
                        <p class="mt-4 text-xs font-medium text-slate-500">{{ $tile['label'] }}</p>
                        <p class="mt-1 text-3xl font-bold text-slate-900">
                            <span @if ($tile['value'] !== null) data-count="{{ $tile['value'] }}" data-decimals="{{ $tile['decimals'] }}" @endif>{{ $format($tile['value'] === null ? null : (float) $tile['value'], $tile['decimals']) }}</span><span class="text-base text-slate-400">{{ $tile['unit'] }}</span>
                        </p>
                        <p class="mt-1 text-xs text-slate-400">{{ $tile['hint'] }}</p>
                    </x-ui.card>
                @endforeach
            </div>

            <div class="mt-4 grid grid-cols-1 gap-4 lg:grid-cols-[1fr_1.3fr]">
                {{-- Status distribution --}}
                <x-ui.card class="reveal">
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
                            ['label' => 'Siram ON', 'count' => $summary['dry'], 'percent' => $summary['dry_percent'], 'dot' => 'bg-amber-400'],
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
                <x-ui.card class="reveal [transition-delay:120ms]">
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
                <h2 class="mt-1 text-2xl font-bold text-slate-900">Grafik Sensor</h2>
            </div>

            <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                <x-ui.card class="reveal">
                    <h3 class="text-base font-semibold text-slate-900">Rata-rata Harian · {{ $periodLabels[$days] }}</h3>
                    <p class="text-xs text-slate-400">Batang = kelembaban tanah (%), garis = suhu (°C) &amp; kelembaban udara (%)</p>
                    <div class="relative mt-4 h-72">
                        <canvas id="v-daily-chart"></canvas>
                    </div>
                </x-ui.card>

                <x-ui.card class="reveal [transition-delay:120ms]">
                    <h3 class="text-base font-semibold text-slate-900">Suhu &amp; Kelembaban Udara Live</h3>
                    <p class="text-xs text-slate-400">{{ count($recent) }} pembacaan terakhir, diperbarui otomatis</p>
                    <div class="relative mt-4 h-72">
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
                    <h2 class="mt-1 text-2xl font-bold text-slate-900">Tabel Data Sensor</h2>
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
                <form id="v-table-form" class="grid grid-cols-1 gap-3 border-b border-slate-200 px-6 py-4 sm:grid-cols-2 lg:grid-cols-[1fr_1fr_1fr_auto]">
                    <div>
                        <x-ui.label for="v-table-from" value="Dari tanggal" />
                        <x-ui.input id="v-table-from" type="date" name="date_from" icon="fa-calendar" />
                    </div>
                    <div>
                        <x-ui.label for="v-table-to" value="Sampai tanggal" />
                        <x-ui.input id="v-table-to" type="date" name="date_to" icon="fa-calendar-check" />
                    </div>
                    <div>
                        <x-ui.label for="v-table-status" value="Status" />
                        <select id="v-table-status" name="status" class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-500/30 focus:outline-none">
                            <option value="">Semua status</option>
                            <option value="ok">Aman - Basah</option>
                            <option value="dry">Siram ON</option>
                            <option value="empty">Air Habis</option>
                        </select>
                    </div>
                    <div class="flex items-end gap-2">
                        <x-ui.button type="submit">
                            <i class="fa-solid fa-filter"></i>
                            Terapkan
                        </x-ui.button>
                        <x-ui.button type="button" variant="secondary" id="v-table-reset">Reset</x-ui.button>
                    </div>
                </form>
                <p id="v-table-error" class="hidden px-6 pt-3 text-sm text-red-600"></p>

                <div class="overflow-x-auto">
                    <table class="min-w-full text-left text-sm">
                        <thead class="bg-slate-50 text-xs tracking-wide text-slate-500 uppercase">
                            <tr>
                                <th class="px-6 py-3 font-medium">Waktu</th>
                                <th class="px-4 py-3 font-medium">Kelembaban Tanah</th>
                                <th class="px-4 py-3 font-medium">Air Tandon</th>
                                <th class="px-4 py-3 font-medium">Suhu</th>
                                <th class="px-4 py-3 font-medium">Kelembaban Udara</th>
                                <th class="px-4 py-3 font-medium">Pompa</th>
                                <th class="px-4 py-3 font-medium">Status</th>
                            </tr>
                        </thead>
                        <tbody id="v-table-body" class="divide-y divide-slate-100"></tbody>
                    </table>
                </div>
                <p id="v-table-empty" class="hidden px-6 py-10 text-center text-sm text-slate-400">Tidak ada data pada filter ini.</p>

                <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-200 px-6 py-4 text-sm">
                    <p id="v-table-page" class="text-slate-500">-</p>
                    <div class="flex gap-2">
                        <x-ui.button variant="secondary" id="v-table-prev" disabled>
                            <i class="fa-solid fa-chevron-left"></i>
                            Sebelumnya
                        </x-ui.button>
                        <x-ui.button variant="secondary" id="v-table-next" disabled>
                            Berikutnya
                            <i class="fa-solid fa-chevron-right"></i>
                        </x-ui.button>
                    </div>
                </div>
            </x-ui.card>
        </section>

        {{-- CSV download --}}
        <section id="download" class="scroll-mt-24">
            <x-ui.card class="reveal relative overflow-hidden">
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
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
                        @foreach ($datasets as $dataset)
                            <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 bg-white p-3 transition hover:bg-slate-50 has-checked:border-emerald-400 has-checked:bg-emerald-50/60">
                                <input type="checkbox" name="datasets[]" value="{{ $dataset->value }}" class="mt-0.5 h-4 w-4 accent-emerald-600"
                                    @checked(in_array($dataset->value, old('datasets', array_column($datasets, 'value')), true))>
                                <span class="block text-sm font-semibold text-slate-800">{{ $dataset->label() }}</span>
                            </label>
                        @endforeach
                    </div>

                    <div class="mt-5 grid grid-cols-1 gap-3 sm:grid-cols-[1fr_1fr_auto]">
                        <div>
                            <x-ui.label for="v-export-from" value="Dari tanggal" />
                            <x-ui.input id="v-export-from" type="date" name="date_from" icon="fa-calendar" value="{{ old('date_from') }}" />
                        </div>
                        <div>
                            <x-ui.label for="v-export-to" value="Sampai tanggal" />
                            <x-ui.input id="v-export-to" type="date" name="date_to" icon="fa-calendar-check" value="{{ old('date_to') }}" />
                        </div>
                        <div class="flex items-end">
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
        <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-3 px-6 py-6 text-xs text-slate-400">
            <p>&copy; {{ now()->year }} Taman Pintar IoT · Smart Irrigation ESP32</p>
            <p>Data diperbarui otomatis setiap beberapa detik.</p>
        </div>
    </footer>

    @push('scripts')
        <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
        <script>
            (() => {
                const HISTORY_URL = @json(url('/api/sensor/history'));
                const RECORDS_URL = @json(url('/api/sensor'));
                const REFRESH_MS = 5000;
                const STALE_AFTER_MS = 60000;
                const RECENT_LIMIT = 30;
                const GAUGE_CIRCUMFERENCE = 326.73;
                const REDUCED_MOTION = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

                const $ = (id) => document.getElementById(id);
                const setText = (id, value) => { $(id).textContent = value; };
                const clamp = (value, min, max) => Math.min(Math.max(value, min), max);
                const number = (value, decimals = 1) => Number(value).toLocaleString('id-ID', { minimumFractionDigits: decimals, maximumFractionDigits: decimals });
                const hasDht = (d) => d.temperature !== null && d.humidity !== null;
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

                const STATUS = {
                    empty: { label: 'Air Habis', live: 'Air tandon habis' },
                    dry: { label: 'Siram ON', live: 'Sedang menyiram' },
                    ok: { label: 'Aman - Basah', live: 'Tanaman sehat' },
                };
                const statusKey = (d) => (d.is_water_empty ? 'empty' : d.is_soil_dry ? 'dry' : 'ok');

                const temperatureLabel = (t) => (t < 20 ? 'Sejuk' : t <= 30 ? 'Normal' : t <= 35 ? 'Hangat' : 'Panas');
                const humidityLabel = (h) => (h < 40 ? 'Udara kering' : h <= 70 ? 'Ideal' : 'Udara lembab');

                const renderLatest = (d) => {
                    setText('v-hero-status', STATUS[statusKey(d)].live);

                    setText('v-soil', d.soil_percent);
                    $('v-soil-gauge').style.strokeDashoffset = GAUGE_CIRCUMFERENCE * (1 - clamp(d.soil_percent, 0, 100) / 100);
                    $('v-soil-gauge').setAttribute('class', 'transition-all duration-1000 ' + (d.is_soil_dry ? 'stroke-amber-500' : 'stroke-brand-500'));
                    setText('v-soil-label', d.is_soil_dry ? 'Tanah kering' : 'Tanah lembab');

                    setText('v-water', d.is_water_empty ? 'HABIS' : 'ADA');
                    $('v-water-fill').style.height = waterLevel(d) + '%';
                    setText('v-water-label', d.is_water_empty ? 'Air habis, perlu diisi' : 'Air tersedia');

                    if (hasDht(d)) {
                        setText('v-temp', number(d.temperature));
                        $('v-temp-bar').style.height = clamp((d.temperature / 50) * 100, 0, 100) + '%';
                        setText('v-temp-label', temperatureLabel(d.temperature) + ' · Sensor DHT');

                        setText('v-hum', Math.round(d.humidity));
                        $('v-hum-clip').style.clipPath = 'inset(' + (100 - clamp(d.humidity, 0, 100)) + '% 0 0 0)';
                        setText('v-hum-label', humidityLabel(d.humidity) + ' · Sensor DHT');
                    } else {
                        setText('v-temp-label', 'Sensor DHT tidak terbaca');
                        setText('v-hum-label', 'Sensor DHT tidak terbaca');
                    }

                    setText('v-pump-text', d.pump_status ? 'Pompa sedang menyiram tanaman' : 'Pompa dalam posisi standby');
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

                const renderDevice = () => {
                    const latest = rows.at(-1);

                    if (!latest) {
                        setLive('idle', 'Belum ada data');
                        setText('v-updated', 'Menunggu ESP32 mengirim data pertama.');
                        return;
                    }

                    const age = Date.now() - new Date(latest.created_at).getTime();
                    const isOnline = age <= STALE_AFTER_MS;
                    setLive(isOnline ? 'live' : 'stale', isOnline ? 'Live' : 'Offline');
                    setText('v-updated', 'Update terakhir ' + latest.created_at_label + ' (' + formatAgo(age) + ')');
                };

                /* ---------- Charts ---------- */

                const COLORS = { soil: '#2563eb', temperature: '#f97316', humidity: '#06b6d4' };
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
                            { type: 'line', label: 'Suhu (°C)', data: daily.map((d) => d.avg_temperature), borderColor: COLORS.temperature, backgroundColor: COLORS.temperature, tension: 0.4, spanGaps: true, yAxisID: 'temperature', order: 1 },
                            { type: 'line', label: 'Udara (%)', data: daily.map((d) => d.avg_humidity), borderColor: COLORS.humidity, backgroundColor: COLORS.humidity, borderDash: [6, 4], tension: 0.4, spanGaps: true, yAxisID: 'percent', order: 2 },
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
                            { label: 'Suhu (°C)', data: [], borderColor: COLORS.temperature, backgroundColor: COLORS.temperature + '22', fill: true, tension: 0.4, pointRadius: 0, spanGaps: true, yAxisID: 'temperature' },
                            { label: 'Kelembaban udara (%)', data: [], borderColor: COLORS.humidity, backgroundColor: COLORS.humidity, borderDash: [6, 4], tension: 0.4, pointRadius: 0, spanGaps: true, yAxisID: 'percent' },
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
                    liveChart.data.datasets[0].data = rows.map((d) => d.temperature);
                    liveChart.data.datasets[1].data = rows.map((d) => d.humidity);
                    liveChart.update();
                };

                /* ---------- Live polling ---------- */

                let rows = @json($recent);
                let lastId = rows.length ? rows.at(-1).id : 0;
                let isPolling = false;

                const render = () => {
                    if (rows.length) renderLatest(rows.at(-1));
                    renderLiveChart();
                    renderDevice();
                };

                const poll = async () => {
                    if (isPolling || document.hidden) return;
                    isPolling = true;

                    try {
                        const response = await fetch(HISTORY_URL + '?limit=' + RECENT_LIMIT + (lastId ? '&after_id=' + lastId : ''), {
                            headers: { Accept: 'application/json' },
                            cache: 'no-store',
                        });
                        if (!response.ok) throw new Error('Bad response');

                        const fresh = await response.json();
                        if (fresh.length) {
                            rows = [...rows, ...fresh].slice(-RECENT_LIMIT);
                            lastId = rows.at(-1).id;
                            render();
                        } else {
                            renderDevice();
                        }
                    } catch (error) {
                        setLive('error', 'Koneksi terputus');
                    } finally {
                        isPolling = false;
                    }
                };

                /* ---------- Data table ---------- */

                const STATUS_BADGES = {
                    empty: 'bg-red-100 text-red-700',
                    dry: 'bg-amber-100 text-amber-700',
                    ok: 'bg-emerald-100 text-emerald-700',
                };
                const tableFilters = { date_from: '', date_to: '', status: '' };
                let tablePage = 1;

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

                const loadTable = async () => {
                    const params = new URLSearchParams({ page: tablePage, per_page: 10 });
                    Object.entries(tableFilters).forEach(([key, value]) => value && params.set(key, value));
                    $('v-table-error').classList.add('hidden');

                    try {
                        const response = await fetch(RECORDS_URL + '?' + params, { headers: { Accept: 'application/json' }, cache: 'no-store' });
                        const result = await response.json();

                        if (!response.ok) {
                            throw new Error(Object.values(result.errors || {}).flat()[0] || 'Gagal memuat data.');
                        }

                        const meta = result.meta;
                        $('v-table-body').replaceChildren(...result.data.map((d) => {
                            const tr = document.createElement('tr');
                            tr.className = 'hover:bg-slate-50';
                            tr.append(
                                tableCell(d.created_at_label, 'whitespace-nowrap px-6 py-3 text-slate-600'),
                                tableCell(d.soil_percent + '%', 'px-4 py-3 font-semibold text-slate-900'),
                                badgeCell(d.is_water_empty ? 'Habis' : 'Ada', d.is_water_empty ? 'bg-red-100 text-red-700' : 'bg-sky-100 text-sky-700'),
                                tableCell(d.temperature === null ? '--' : number(d.temperature) + ' °C'),
                                tableCell(d.humidity === null ? '--' : Math.round(d.humidity) + '%'),
                                badgeCell(d.pump_status ? 'ON' : 'OFF', d.pump_status ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500'),
                                badgeCell(STATUS[statusKey(d)].label, STATUS_BADGES[statusKey(d)]),
                            );
                            return tr;
                        }));

                        $('v-table-empty').classList.toggle('hidden', result.data.length > 0);
                        setText('v-table-summary', meta.total.toLocaleString('id-ID') + ' data ditemukan, terbaru di atas.');
                        setText('v-table-page', meta.total ? 'Menampilkan ' + meta.from + '–' + meta.to + ' dari ' + meta.total.toLocaleString('id-ID') + ' · Hal. ' + meta.current_page + '/' + meta.last_page : 'Tidak ada data');
                        $('v-table-prev').disabled = meta.current_page <= 1;
                        $('v-table-next').disabled = meta.current_page >= meta.last_page;
                    } catch (error) {
                        setText('v-table-error', error.message);
                        $('v-table-error').classList.remove('hidden');
                    }
                };

                const applyTableFilters = () => {
                    tableFilters.date_from = $('v-table-from').value;
                    tableFilters.date_to = $('v-table-to').value;
                    tableFilters.status = $('v-table-status').value;
                    tablePage = 1;
                    loadTable();
                };

                $('v-table-form').addEventListener('submit', (event) => {
                    event.preventDefault();
                    applyTableFilters();
                });

                $('v-table-reset').addEventListener('click', () => {
                    $('v-table-form').reset();
                    applyTableFilters();
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
                        applyTableFilters();
                    });
                });

                $('v-table-prev').addEventListener('click', () => { tablePage -= 1; loadTable(); });
                $('v-table-next').addEventListener('click', () => { tablePage += 1; loadTable(); });

                /* ---------- CSV export ---------- */

                const exportForm = $('v-export-form');
                let exportCountTimer = null;

                const updateExportSummary = () => {
                    clearTimeout(exportCountTimer);
                    exportCountTimer = setTimeout(async () => {
                        const selected = exportForm.querySelectorAll('input[name="datasets[]"]:checked').length;
                        $('v-export-submit').disabled = selected === 0;

                        if (selected === 0) {
                            setText('v-export-summary', 'Pilih minimal satu jenis data.');
                            return;
                        }

                        const params = new URLSearchParams({ per_page: 5 });
                        ['date_from', 'date_to'].forEach((key) => {
                            const value = exportForm.elements[key].value;
                            if (value) params.set(key, value);
                        });

                        try {
                            const response = await fetch(RECORDS_URL + '?' + params, { headers: { Accept: 'application/json' } });
                            const result = await response.json();
                            setText('v-export-summary', response.ok
                                ? result.meta.total.toLocaleString('id-ID') + ' baris · ' + selected + ' jenis data akan di-download.'
                                : Object.values(result.errors || {}).flat()[0] || 'Tidak dapat menghitung jumlah data.');
                        } catch (error) {
                            setText('v-export-summary', 'Tidak dapat menghitung jumlah data.');
                        }
                    }, 300);
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
                loadTable();
                render();
                setInterval(poll, REFRESH_MS);
                document.addEventListener('visibilitychange', () => {
                    if (!document.hidden) poll();
                });
            })();
        </script>
    @endpush
</x-layouts.guest>
