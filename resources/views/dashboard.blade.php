<x-layouts.app title="Dashboard">
    {{-- Hero --}}
    <section class="relative mb-6 overflow-hidden rounded-3xl bg-linear-to-br from-brand-600 via-brand-700 to-brand-900 p-6 text-white shadow-lg sm:p-8">
        <div class="pointer-events-none absolute -top-16 -right-16 h-56 w-56 rounded-full bg-white/10"></div>
        <div class="pointer-events-none absolute right-24 -bottom-20 h-40 w-40 rounded-full bg-white/5"></div>

        <div class="relative flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="text-xs font-semibold tracking-widest text-brand-200 uppercase">Smart Irrigation · ESP32</p>
                <h1 class="mt-1 text-2xl font-bold sm:text-3xl">IoT Penyiram Tanaman</h1>
                <p class="mt-1 text-sm text-brand-100">Monitoring kelembaban &amp; suhu tanah, air tandon, pompa, dan buzzer secara realtime</p>
            </div>

            <div class="flex items-center gap-2 rounded-full bg-white/10 px-3 py-1.5 text-xs font-medium ring-1 ring-white/20 backdrop-blur">
                <span class="relative flex h-2.5 w-2.5">
                    <span id="live-ping" class="absolute inline-flex h-full w-full rounded-full opacity-75"></span>
                    <span id="live-dot" class="relative inline-flex h-2.5 w-2.5 rounded-full bg-slate-300"></span>
                </span>
                <span id="live-label">Menunggu data…</span>
            </div>
        </div>

        <div class="relative mt-6 flex items-center gap-4 rounded-2xl bg-white/10 p-4 ring-1 ring-white/20">
            <div id="status-icon" class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-white/15 text-white">
                <i class="fa-solid fa-hourglass-half text-xl"></i>
            </div>
            <div class="min-w-0">
                <p id="status-title" class="text-lg font-bold">Belum ada data</p>
                <p id="status-text" class="text-sm text-brand-100">Menunggu ESP32 mengirim data sensor pertama.</p>
            </div>
        </div>
    </section>

    {{-- Row 1: soil moisture sensor --}}
    <x-ui.card class="mb-4">
        <div class="flex flex-col gap-6 md:flex-row md:items-center">
            <div class="flex items-center gap-6">
                <div class="relative h-36 w-36 shrink-0">
                    <svg viewBox="0 0 120 120" class="h-full w-full -rotate-90">
                        <circle cx="60" cy="60" r="52" fill="none" stroke-width="10" class="stroke-slate-100" />
                        <circle id="soil-gauge" cx="60" cy="60" r="52" fill="none" stroke-width="10" stroke-linecap="round"
                            stroke-dasharray="326.73" stroke-dashoffset="326.73" class="stroke-brand-500 transition-all duration-700" />
                    </svg>
                    <div class="absolute inset-0 flex flex-col items-center justify-center">
                        <p class="text-4xl font-bold text-slate-900"><span id="soil-percent">--</span><span class="text-lg text-slate-400">%</span></p>
                        <p class="text-xs text-slate-400">kelembaban</p>
                    </div>
                </div>

                <div>
                    <div class="flex items-center gap-2">
                        <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-brand-50 text-brand-600"><i class="fa-solid fa-seedling"></i></span>
                        <div>
                            <p class="text-sm font-semibold text-slate-900">Sensor Kelembaban Tanah</p>
                            <p class="text-xs text-slate-400">Soil moisture · Pin D34</p>
                        </div>
                    </div>
                    <span id="soil-badge" class="mt-3 inline-flex items-center rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-500">--</span>
                </div>
            </div>

            <dl class="grid flex-1 grid-cols-2 gap-3 sm:grid-cols-4 md:border-l md:border-slate-100 md:pl-6">
                <div class="rounded-xl bg-slate-50 p-3">
                    <dt class="text-xs text-slate-400">Nilai raw</dt>
                    <dd id="soil-raw" class="mt-1 text-xl font-bold text-slate-900">--</dd>
                </div>
                <div class="rounded-xl bg-slate-50 p-3">
                    <dt class="text-xs text-slate-400">Min</dt>
                    <dd class="mt-1 text-xl font-bold text-slate-900"><span id="stat-min">--</span>%</dd>
                </div>
                <div class="rounded-xl bg-slate-50 p-3">
                    <dt class="text-xs text-slate-400">Rata-rata</dt>
                    <dd class="mt-1 text-xl font-bold text-slate-900"><span id="stat-avg">--</span>%</dd>
                </div>
                <div class="rounded-xl bg-slate-50 p-3">
                    <dt class="text-xs text-slate-400">Maks</dt>
                    <dd class="mt-1 text-xl font-bold text-slate-900"><span id="stat-max">--</span>%</dd>
                </div>
            </dl>
        </div>
    </x-ui.card>

    {{-- Row 2: water level sensor + water pump --}}
    <div class="mb-4 grid grid-cols-1 gap-4 md:grid-cols-2">
        <x-ui.card>
            <div class="flex items-start justify-between gap-3">
                <div class="flex items-center gap-3">
                    <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-sky-50 text-sky-600"><i class="fa-solid fa-water"></i></span>
                    <div>
                        <p class="text-sm font-semibold text-slate-900">Water Level Float Switch</p>
                        <p class="text-xs text-slate-400">Pelampung tandon · Pin D15 (LOW = air habis)</p>
                    </div>
                </div>
                <span id="water-badge" class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-500">--</span>
            </div>

            <div class="mt-5 flex items-center gap-5">
                {{-- Tank: the float rides on the water surface --}}
                <div class="relative h-24 w-20 shrink-0 overflow-hidden rounded-b-2xl rounded-t-md border-2 border-slate-300 bg-slate-50">
                    <div id="water-fill" class="absolute inset-x-0 bottom-0 h-0 bg-sky-400/70 transition-all duration-700"></div>
                    <div id="water-float" class="absolute left-1/2 bottom-0 h-3 w-8 -translate-x-1/2 rounded-full bg-amber-400 shadow ring-2 ring-white transition-all duration-700"></div>
                </div>
                <div>
                    <p id="water-state" class="text-3xl font-bold text-slate-400">--</p>
                    <p id="water-text" class="mt-1 text-xs text-slate-400">Menunggu data</p>
                </div>
            </div>
        </x-ui.card>

        <x-ui.card>
            <div class="flex items-start justify-between gap-3">
                <div class="flex items-center gap-3">
                    <span id="pump-icon" class="flex h-11 w-11 items-center justify-center rounded-xl bg-slate-100 text-slate-400 transition"><i class="fa-solid fa-faucet-drip"></i></span>
                    <div>
                        <p class="text-sm font-semibold text-slate-900">Pompa Air</p>
                        <p class="text-xs text-slate-400">Relay · Pin D23</p>
                    </div>
                </div>
                <span id="pump-badge" class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-500">--</span>
            </div>

            <div class="mt-5 flex items-end justify-between gap-4">
                <div>
                    <p id="pump-status" class="text-4xl font-bold text-slate-400">--</p>
                    <p id="pump-text" class="text-xs text-slate-400">Menunggu data</p>
                </div>
                <div class="text-right">
                    <p class="text-2xl font-bold text-emerald-600"><span id="pump-ratio">--</span><span class="text-base text-slate-400">%</span></p>
                    <p class="text-xs text-slate-400">aktif di data terakhir</p>
                </div>
            </div>

            {{-- Pump control: published to the ESP32 on the MQTT pump topic --}}
            <form id="pump-form" method="POST" action="{{ route('pump.command') }}" class="mt-5 border-t border-slate-100 pt-4">
                @csrf
                <div class="flex items-center justify-between gap-2">
                    <p class="text-xs font-semibold text-slate-500"><i class="fa-solid fa-tower-broadcast mr-1"></i> Kontrol pompa (MQTT)</p>
                    <p class="text-xs text-slate-400">Mode: <span id="pump-mode" class="font-semibold text-slate-700">--</span></p>
                </div>
                <div class="mt-2 grid grid-cols-3 gap-2">
                    @foreach ($pumpCommands as $command)
                        <button type="submit" name="command" value="{{ $command->value }}" data-pump-command="{{ $command->value }}"
                            class="inline-flex items-center justify-center gap-1.5 rounded-lg border border-slate-200 bg-white px-2 py-2 text-xs font-semibold text-slate-600 transition hover:border-brand-300 hover:text-brand-700 disabled:cursor-wait disabled:opacity-60 data-active:border-brand-500 data-active:bg-brand-50 data-active:text-brand-700">
                            <i class="fa-solid {{ $command->icon() }}"></i>
                            {{ $command->label() }}
                        </button>
                    @endforeach
                </div>
                <p id="pump-feedback" @class([
                    'mt-2 text-xs',
                    'text-red-600' => $errors->has('command'),
                    'text-emerald-600' => session('status') && ! $errors->has('command'),
                    'text-slate-400' => ! session('status') && ! $errors->has('command'),
                ])>{{ $errors->first('command') ?: session('status', 'Manual ON menyala terus sampai dikirim Otomatis atau Manual OFF.') }}</p>
            </form>
        </x-ui.card>
    </div>

    {{-- Row 2b: DS18B20 soil temperature + buzzer alarm --}}
    <div id="suhu-tanah" class="mb-4 grid scroll-mt-6 grid-cols-1 gap-4 md:grid-cols-2">
        <x-ui.card>
            <div class="flex items-start justify-between gap-3">
                <div class="flex items-center gap-3">
                    <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-orange-50 text-orange-500"><i class="fa-solid fa-temperature-half"></i></span>
                    <div>
                        <p class="text-sm font-semibold text-slate-900">Suhu Tanah</p>
                        <p class="text-xs text-slate-400">Sensor DS18B20 · Pin D26</p>
                    </div>
                </div>
                <span id="soil-temp-badge" class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-500">--</span>
            </div>

            <div class="mt-5 flex items-end justify-between gap-4">
                <p class="text-4xl font-bold text-slate-900"><span id="soil-temp-value">--</span><span class="text-lg text-slate-400">°C</span></p>
                <dl class="grid grid-cols-3 gap-4 text-right text-xs">
                    <div><dt class="text-slate-400">Min</dt><dd class="text-base font-bold text-slate-900"><span id="soil-temp-min">--</span>°</dd></div>
                    <div><dt class="text-slate-400">Rata-rata</dt><dd class="text-base font-bold text-slate-900"><span id="soil-temp-avg">--</span>°</dd></div>
                    <div><dt class="text-slate-400">Maks</dt><dd class="text-base font-bold text-slate-900"><span id="soil-temp-max">--</span>°</dd></div>
                </dl>
            </div>
            <div class="mt-4 h-2.5 overflow-hidden rounded-full bg-slate-100">
                <div id="soil-temp-bar" class="h-full w-0 rounded-full bg-linear-to-r from-amber-300 to-orange-500 transition-all duration-700"></div>
            </div>
            <p class="mt-1 flex justify-between text-[10px] text-slate-400"><span>0°C</span><span>50°C</span></p>
        </x-ui.card>

        <x-ui.card>
            <div class="flex items-start justify-between gap-3">
                <div class="flex items-center gap-3">
                    <span id="buzzer-icon" class="flex h-11 w-11 items-center justify-center rounded-xl bg-slate-100 text-slate-400 transition"><i class="fa-solid fa-bell"></i></span>
                    <div>
                        <p class="text-sm font-semibold text-slate-900">Alarm Buzzer</p>
                        <p class="text-xs text-slate-400">Buzzer pasif · Pin D13</p>
                    </div>
                </div>
                <span id="buzzer-badge" class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-500">--</span>
            </div>

            <div class="mt-5 flex items-end justify-between gap-4">
                <div>
                    <p id="buzzer-state" class="text-4xl font-bold text-slate-400">--</p>
                    <p id="buzzer-text" class="text-xs text-slate-400">Menunggu data</p>
                </div>
                <div class="text-right">
                    <p class="text-2xl font-bold text-amber-500"><span id="buzzer-ratio">--</span><span class="text-base text-slate-400">%</span></p>
                    <p class="text-xs text-slate-400">alarm di data terakhir</p>
                </div>
            </div>
            <p class="mt-4 text-xs text-slate-400">Berbunyi terus selama air tandon habis <span class="font-semibold">atau</span> tanah kering, dan langsung diam saat kondisinya hilang.</p>
        </x-ui.card>
    </div>

    {{-- Row 3: indicator lamps + ESP32 device --}}
    <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-3">
        <x-ui.card>
            <div class="flex items-center gap-4">
                <span id="lamp-d25-icon" class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-slate-100 text-xl text-slate-400 transition"><i class="fa-solid fa-lightbulb"></i></span>
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-semibold text-slate-900">Lampu Indikator 1</p>
                    <p class="text-xs text-slate-400">Pin D25 · Menyala saat pompa ON</p>
                </div>
                <span id="lamp-d25-state" class="rounded-full bg-slate-100 px-3 py-1 text-sm font-bold text-slate-500">--</span>
            </div>
        </x-ui.card>

        <x-ui.card>
            <div class="flex items-center gap-4">
                <span id="lamp-d14-icon" class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-slate-100 text-xl text-slate-400 transition"><i class="fa-solid fa-lightbulb"></i></span>
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-semibold text-slate-900">Lampu Indikator 2</p>
                    <p class="text-xs text-slate-400">Pin D14 · Menyala saat air habis · buzzer D13</p>
                </div>
                <span id="lamp-d14-state" class="rounded-full bg-slate-100 px-3 py-1 text-sm font-bold text-slate-500">--</span>
            </div>
        </x-ui.card>

        <x-ui.card>
            <div class="flex items-center gap-4">
                <span id="device-icon" class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-slate-100 text-xl text-slate-400 transition"><i class="fa-solid fa-microchip"></i></span>
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-semibold text-slate-900">Perangkat ESP32</p>
                    <p id="device-last" class="truncate text-xs text-slate-400">Belum terhubung</p>
                    <p class="text-xs text-slate-400">Interval: <span id="device-interval" class="font-semibold text-slate-600">--</span></p>
                </div>
                <span id="device-state" class="rounded-full bg-slate-100 px-3 py-1 text-sm font-bold text-slate-500">--</span>
            </div>
        </x-ui.card>
    </div>

    {{-- Chart --}}
    <x-ui.card class="mb-6">
        <div class="mb-5 flex flex-wrap items-start justify-between gap-4">
            <div>
                <h2 class="text-lg font-semibold text-slate-900">Grafik Sensor Realtime</h2>
                <p class="text-xs text-slate-400">Kelembaban tanah (%), area merah = air tandon habis (float switch), area hijau = pompa menyala. Klik legenda untuk menyembunyikan.</p>
            </div>

            <div class="inline-flex rounded-lg bg-slate-100 p-1 text-xs font-semibold" id="range-buttons">
                @foreach ([30, 60, 120] as $range)
                    <button type="button" data-range="{{ $range }}" class="rounded-md px-3 py-1.5 text-slate-500 transition hover:text-slate-900">{{ $range }} data</button>
                @endforeach
            </div>
        </div>

        <div id="chart-legend" class="mb-4 flex flex-wrap gap-2 text-xs font-medium"></div>

        <div class="relative h-80 sm:h-112">
            <canvas id="sensor-chart"></canvas>
        </div>
    </x-ui.card>

    {{-- Soil temperature + buzzer chart --}}
    <x-ui.card class="mb-6">
        <div class="mb-5">
            <h2 class="text-lg font-semibold text-slate-900">Grafik Suhu Tanah &amp; Alarm Buzzer</h2>
            <p class="text-xs text-slate-400">DS18B20 (D26) dan buzzer (D13), mengikuti rentang data grafik di atas. Celah pada garis = DS18B20 gagal dibaca, area kuning = buzzer berbunyi.</p>
        </div>

        <div class="relative h-72">
            <canvas id="soil-temp-chart"></canvas>
        </div>
    </x-ui.card>

    {{-- CSV dataset download --}}
    <x-ui.card class="mb-6">
        <div class="mb-5 flex flex-wrap items-start justify-between gap-3">
            <div class="flex items-center gap-3">
                <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600"><i class="fa-solid fa-file-csv text-lg"></i></span>
                <div>
                    <h2 class="text-lg font-semibold text-slate-900">Download Dataset CSV</h2>
                    <p class="text-xs text-slate-400">Pilih data sensor dan rentang tanggal sesuai kebutuhan. Kolom <span class="font-mono">id</span> &amp; <span class="font-mono">waktu</span> selalu disertakan.</p>
                </div>
            </div>
            <div class="flex flex-wrap gap-2 text-xs font-semibold" id="export-quick-dates">
                <button type="button" data-quick="today" class="rounded-full border border-slate-200 px-3 py-1.5 text-slate-600 transition hover:border-emerald-300 hover:text-emerald-700">Hari ini</button>
                <button type="button" data-quick="7" class="rounded-full border border-slate-200 px-3 py-1.5 text-slate-600 transition hover:border-emerald-300 hover:text-emerald-700">7 hari</button>
                <button type="button" data-quick="30" class="rounded-full border border-slate-200 px-3 py-1.5 text-slate-600 transition hover:border-emerald-300 hover:text-emerald-700">30 hari</button>
                <button type="button" data-quick="all" class="rounded-full border border-slate-200 px-3 py-1.5 text-slate-600 transition hover:border-emerald-300 hover:text-emerald-700">Semua</button>
            </div>
        </div>

        @if ($errors->export->any())
            <x-ui.alert variant="error" class="mb-4">{{ $errors->export->first() }}</x-ui.alert>
        @endif

        <form id="export-form" method="GET" action="{{ route('sensor.export') }}">
            <div class="mb-2 flex items-center justify-between">
                <x-ui.label value="Jenis data sensor" class="mb-0" />
                <div class="flex gap-3 text-xs font-semibold">
                    <button type="button" id="export-select-all" class="text-brand-600 hover:text-brand-700">Pilih semua</button>
                    <button type="button" id="export-select-none" class="text-slate-500 hover:text-slate-700">Kosongkan</button>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($datasets as $dataset)
                    <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 p-3 transition hover:bg-slate-50 has-checked:border-emerald-400 has-checked:bg-emerald-50/60">
                        <input type="checkbox" name="datasets[]" value="{{ $dataset->value }}" class="mt-0.5 h-4 w-4 accent-emerald-600"
                            @checked(in_array($dataset->value, old('datasets', array_column($datasets, 'value')), true))>
                        <span>
                            <span class="block text-sm font-semibold text-slate-800">
                                {{ $dataset->label() }}
                                @if ($dataset->pin())
                                    <span class="ml-1 rounded bg-slate-100 px-1.5 py-0.5 font-mono text-xs font-medium text-slate-500">Pin {{ $dataset->pin() }}</span>
                                @endif
                            </span>
                            <span class="block font-mono text-xs text-slate-400">{{ $dataset->description() }}</span>
                        </span>
                    </label>
                @endforeach
            </div>

            <div class="mt-5 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-[1fr_1fr_1fr_auto]">
                <div>
                    <x-ui.label for="export-date-from" value="Dari tanggal" />
                    <x-ui.input id="export-date-from" type="date" name="date_from" icon="fa-calendar" value="{{ old('date_from') }}" />
                </div>
                <div>
                    <x-ui.label for="export-date-to" value="Sampai tanggal" />
                    <x-ui.input id="export-date-to" type="date" name="date_to" icon="fa-calendar-check" value="{{ old('date_to') }}" />
                </div>
                <div>
                    <x-ui.label for="export-status" value="Status" />
                    <select id="export-status" name="status" class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-500/30 focus:outline-none">
                        <option value="">Semua status</option>
                        <option value="ok" @selected(old('status') === 'ok')>Aman - Basah</option>
                        <option value="dry" @selected(old('status') === 'dry')>Tanah kering</option>
                        <option value="empty" @selected(old('status') === 'empty')>Air Habis</option>
                    </select>
                </div>
                <div class="flex items-end">
                    <button type="submit" id="export-submit" class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-60">
                        <i class="fa-solid fa-download"></i>
                        Download CSV
                    </button>
                </div>
            </div>

            <p id="export-summary" class="mt-3 text-xs text-slate-500">Menghitung jumlah data…</p>
        </form>
    </x-ui.card>

    {{-- History table with date filter --}}
    <x-ui.card id="riwayat" padding="p-0" class="scroll-mt-6 overflow-hidden">
        <div class="border-b border-slate-200 px-6 py-4">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900">Riwayat Data</h2>
                    <p id="table-summary" class="text-xs text-slate-400">Memuat data…</p>
                </div>
                <div class="flex flex-wrap gap-2 text-xs font-semibold" id="quick-dates">
                    <button type="button" data-quick="today" class="rounded-full border border-slate-200 px-3 py-1.5 text-slate-600 transition hover:border-brand-300 hover:text-brand-700">Hari ini</button>
                    <button type="button" data-quick="yesterday" class="rounded-full border border-slate-200 px-3 py-1.5 text-slate-600 transition hover:border-brand-300 hover:text-brand-700">Kemarin</button>
                    <button type="button" data-quick="7" class="rounded-full border border-slate-200 px-3 py-1.5 text-slate-600 transition hover:border-brand-300 hover:text-brand-700">7 hari</button>
                    <button type="button" data-quick="30" class="rounded-full border border-slate-200 px-3 py-1.5 text-slate-600 transition hover:border-brand-300 hover:text-brand-700">30 hari</button>
                </div>
            </div>

            <form id="filter-form" method="GET" action="{{ route('dashboard') }}#riwayat" class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-[1fr_1fr_1fr_auto]">
                <div class="col-span-full">
                    <x-ui.label for="filter-search" value="Cari data" />
                    <x-ui.input id="filter-search" type="search" name="q" icon="fa-magnifying-glass" maxlength="50" autocomplete="off"
                        value="{{ $table['filters']['q'] }}" placeholder="Cari angka, tanggal, jam, status, atau mode pompa…" />
                    <p class="mt-1 text-xs text-slate-400">Contoh: 35, 27.5, 2026-10-21, 14:05, kering, habis, menyiram, manual off, alarm</p>
                </div>
                <div>
                    <x-ui.label for="filter-date-from" value="Dari tanggal" />
                    <x-ui.input id="filter-date-from" type="date" name="date_from" icon="fa-calendar" value="{{ $table['filters']['date_from'] }}" />
                </div>
                <div>
                    <x-ui.label for="filter-date-to" value="Sampai tanggal" />
                    <x-ui.input id="filter-date-to" type="date" name="date_to" icon="fa-calendar-check" value="{{ $table['filters']['date_to'] }}" />
                </div>
                <div>
                    <x-ui.label for="filter-status" value="Status" />
                    <select id="filter-status" name="status" class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-500/30 focus:outline-none">
                        <option value="">Semua status</option>
                        <option value="ok" @selected($table['filters']['status'] === 'ok')>Aman - Basah</option>
                        <option value="dry" @selected($table['filters']['status'] === 'dry')>Tanah kering</option>
                        <option value="empty" @selected($table['filters']['status'] === 'empty')>Air Habis</option>
                    </select>
                </div>
                <div class="flex items-end gap-2">
                    <x-ui.button type="submit">
                        <i class="fa-solid fa-filter"></i>
                        Terapkan
                    </x-ui.button>
                    <a href="{{ route('dashboard') }}#riwayat" class="inline-flex items-center justify-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">Reset</a>
                </div>
            </form>
            @if ($table['error'])
                <p class="mt-2 text-sm text-red-600">{{ $table['error'] }} Filter diabaikan.</p>
            @endif
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="bg-slate-50 text-xs tracking-wide text-slate-500 uppercase">
                    <tr>
                        <th class="px-6 py-3 font-medium">Waktu</th>
                        <th class="px-4 py-3 font-medium">Tanah %</th>
                        <th class="px-4 py-3 font-medium">Soil Raw</th>
                        <th class="px-4 py-3 font-medium">Air Tandon</th>
                        <th class="px-4 py-3 font-medium">Suhu Tanah</th>
                        <th class="px-4 py-3 font-medium">Pompa</th>
                        <th class="px-4 py-3 font-medium">Mode</th>
                        <th class="px-4 py-3 font-medium">D25</th>
                        <th class="px-4 py-3 font-medium">D14</th>
                        <th class="px-4 py-3 font-medium">Status</th>
                    </tr>
                </thead>
                <tbody id="history-body" class="divide-y divide-slate-100"></tbody>
            </table>
        </div>
        <p id="history-empty" class="hidden px-6 py-10 text-center text-sm text-slate-400">Tidak ada data pada filter ini.</p>

        <div class="flex items-center justify-between gap-3 border-t border-slate-200 px-6 py-4 text-sm">
            <p id="page-info" class="text-slate-500">-</p>
            <div class="flex gap-2">
                <x-ui.button variant="secondary" id="page-prev" :disabled="! $table['links']['prev']">
                    <i class="fa-solid fa-chevron-left"></i>
                    Sebelumnya
                </x-ui.button>
                <x-ui.button variant="secondary" id="page-next" :disabled="! $table['links']['next']">
                    Berikutnya
                    <i class="fa-solid fa-chevron-right"></i>
                </x-ui.button>
            </div>
        </div>
    </x-ui.card>

    @push('scripts')
        <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
        <x-sensor-stream />
        <script>
            (() => {
                const CLOCK_TICK_MS = 5000;
                const STALE_AFTER_MS = 60000;
                const GAUGE_CIRCUMFERENCE = 326.73;

                const COLORS = {
                    soil: '#2563eb',
                    waterEmpty: 'rgba(239, 68, 68, 0.12)',
                    waterEmptySolid: '#ef4444',
                    pump: 'rgba(16, 185, 129, 0.14)',
                    pumpSolid: '#10b981',
                    dry: '#f59e0b',
                    temperature: '#f97316',
                    buzzer: 'rgba(245, 158, 11, 0.18)',
                };

                const PILL = 'inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold ';
                const STATE_PILL = 'rounded-full px-3 py-1 text-sm font-bold ';
                const ICON_TILE = 'flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl text-xl transition ';
                const OFF = 'bg-slate-100 text-slate-500';

                /** Labels of the history filter (soil / tank condition, not what the pump did). */
                const FILTER_LABELS = { ok: 'Aman - Basah', dry: 'Tanah kering', empty: 'Air habis' };
                const { pumpState, pumpModeLabel } = window.SensorStream;

                const $ = (id) => document.getElementById(id);
                const setText = (id, value) => { $(id).textContent = value; };
                const setClass = (id, classes) => { $(id).className = classes; };
                const clamp = (value, min, max) => Math.min(Math.max(value, min), max);
                // The float switch is on/off only, so the tank drawing shows "full enough" or "empty".
                const waterLevel = (d) => (d.is_water_empty ? 8 : 75);
                const formatDecimal = (value) => Number(value).toLocaleString('id-ID', { minimumFractionDigits: 1, maximumFractionDigits: 1 });
                const hasSoilTemperature = (d) => d.soil_temperature !== null && d.soil_temperature !== undefined;

                const { soilTemperatureLevel, buzzerReason } = window.SensorStream;

                const formatAgo = (ms) => {
                    const seconds = Math.max(Math.round(ms / 1000), 0);
                    if (seconds < 60) return seconds + ' dtk lalu';
                    if (seconds < 3600) return Math.round(seconds / 60) + ' mnt lalu';
                    return Math.round(seconds / 3600) + ' jam lalu';
                };

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

                /* ---------- Component cards ---------- */

                const renderLamp = (key, isOn, onClasses) => {
                    setClass(key + '-icon', ICON_TILE + (isOn ? onClasses : 'bg-slate-100 text-slate-400'));
                    setClass(key + '-state', STATE_PILL + (isOn ? 'bg-slate-900 text-white' : OFF));
                    setText(key + '-state', isOn ? 'ON' : 'OFF');
                };

                const renderLatest = (d) => {
                    const state = pumpState(d);

                    setClass('status-icon', 'flex h-12 w-12 shrink-0 items-center justify-center rounded-xl shadow-md ' + state.solid);
                    $('status-icon').innerHTML = '<i class="fa-solid ' + state.icon + ' text-xl"></i>';
                    setText('status-title', state.label);
                    setText('status-text', state.text + ' Tanah ' + d.soil_percent + '% · air ' + (d.is_water_empty ? 'habis' : 'tersedia') + ' · mode ' + pumpModeLabel(d.pump_command) + '.');

                    setText('soil-percent', d.soil_percent);
                    setText('soil-raw', d.soil_raw);
                    setText('soil-badge', d.is_soil_dry ? 'Kering (< 35%)' : 'Basah (≥ 35%)');
                    setClass('soil-badge', 'mt-3 ' + PILL + (d.is_soil_dry ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700'));
                    $('soil-gauge').style.strokeDashoffset = GAUGE_CIRCUMFERENCE * (1 - clamp(d.soil_percent, 0, 100) / 100);
                    $('soil-gauge').setAttribute('class', 'transition-all duration-700 ' + (d.is_soil_dry ? 'stroke-amber-500' : 'stroke-brand-500'));

                    setText('water-state', d.is_water_empty ? 'HABIS' : 'ADA');
                    setClass('water-state', 'text-3xl font-bold ' + (d.is_water_empty ? 'text-red-600' : 'text-sky-600'));
                    setText('water-text', d.is_water_empty ? 'Pelampung turun, tandon perlu diisi' : 'Pelampung naik, air tandon tersedia');
                    setText('water-badge', d.is_water_empty ? 'Air Habis' : 'Air Tersedia');
                    setClass('water-badge', PILL + (d.is_water_empty ? 'bg-red-100 text-red-700' : 'bg-emerald-100 text-emerald-700'));
                    $('water-fill').style.height = waterLevel(d) + '%';
                    $('water-float').style.bottom = 'calc(' + waterLevel(d) + '% - 6px)';

                    setText('pump-status', d.pump_status ? 'ON' : 'OFF');
                    setClass('pump-status', 'text-4xl font-bold ' + (d.pump_status ? 'text-emerald-600' : 'text-slate-400'));
                    setText('pump-text', state.text);
                    renderPumpMode(d.pump_command);
                    setText('pump-badge', state.label);
                    setClass('pump-badge', PILL + state.badge);
                    setClass('pump-icon', 'flex h-11 w-11 items-center justify-center rounded-xl transition ' + (d.pump_status ? 'animate-pulse bg-emerald-500 text-white' : 'bg-slate-100 text-slate-400'));

                    renderLamp('lamp-d25', d.lampu1_d25, 'bg-amber-400 text-white shadow-lg shadow-amber-400/50');
                    renderLamp('lamp-d14', d.lampu2_d14, 'bg-red-500 text-white shadow-lg shadow-red-500/50');

                    renderSoilTemperature(d);
                    renderBuzzer(d);
                };

                const renderPumpMode = (command) => {
                    setText('pump-mode', pumpModeLabel(command));
                    document.querySelectorAll('[data-pump-command]').forEach((button) => {
                        button.toggleAttribute('data-active', button.dataset.pumpCommand === command);
                    });
                    confirmPumpCommand(command);
                };

                const renderSoilTemperature = (d) => {
                    if (!hasSoilTemperature(d)) {
                        setText('soil-temp-badge', 'DS18B20 Error');
                        setClass('soil-temp-badge', PILL + 'bg-red-100 text-red-700');
                        return;
                    }

                    const level = soilTemperatureLevel(d.soil_temperature);
                    setText('soil-temp-value', formatDecimal(d.soil_temperature));
                    setText('soil-temp-badge', level.label);
                    setClass('soil-temp-badge', PILL + level.badge);
                    $('soil-temp-bar').style.width = clamp((d.soil_temperature / 50) * 100, 0, 100) + '%';
                };

                const renderBuzzer = (d) => {
                    if (d.buzzer_alarm === null || d.buzzer_alarm === undefined) {
                        setText('buzzer-state', '--');
                        setText('buzzer-text', 'Firmware belum mengirim status buzzer');
                        return;
                    }

                    setText('buzzer-state', d.buzzer_alarm ? 'ALARM' : 'DIAM');
                    setClass('buzzer-state', 'text-4xl font-bold ' + (d.buzzer_alarm ? 'text-amber-500' : 'text-slate-400'));
                    setText('buzzer-text', d.buzzer_alarm ? 'Berbunyi: ' + (buzzerReason(d) ?? 'kondisi alarm') : 'Tidak ada kondisi alarm');
                    setText('buzzer-badge', d.buzzer_alarm ? 'Berbunyi' : 'Diam');
                    setClass('buzzer-badge', PILL + (d.buzzer_alarm ? 'bg-amber-100 text-amber-700' : OFF));
                    setClass('buzzer-icon', 'flex h-11 w-11 items-center justify-center rounded-xl transition ' + (d.buzzer_alarm ? 'animate-pulse bg-amber-400 text-white' : 'bg-slate-100 text-slate-400'));
                };

                const renderStats = (rows) => {
                    const values = rows.map((d) => d.soil_percent);
                    const hasData = values.length > 0;
                    const average = (list) => list.reduce((a, b) => a + b, 0) / list.length;

                    setText('stat-min', hasData ? Math.min(...values) : '--');
                    setText('stat-max', hasData ? Math.max(...values) : '--');
                    setText('stat-avg', hasData ? Math.round(average(values)) : '--');
                    setText('pump-ratio', hasData ? Math.round((rows.filter((d) => d.pump_status).length / rows.length) * 100) : '--');

                    const temperatures = rows.filter(hasSoilTemperature).map((d) => d.soil_temperature);
                    const hasTemperatureData = temperatures.length > 0;

                    setText('soil-temp-min', hasTemperatureData ? formatDecimal(Math.min(...temperatures)) : '--');
                    setText('soil-temp-avg', hasTemperatureData ? formatDecimal(average(temperatures)) : '--');
                    setText('soil-temp-max', hasTemperatureData ? formatDecimal(Math.max(...temperatures)) : '--');

                    const buzzerRows = rows.filter((d) => d.buzzer_alarm !== null && d.buzzer_alarm !== undefined);
                    setText('buzzer-ratio', buzzerRows.length ? Math.round((buzzerRows.filter((d) => d.buzzer_alarm).length / buzzerRows.length) * 100) : '--');
                };

                const setLive = (state, label) => {
                    const colors = { live: 'bg-emerald-400', stale: 'bg-amber-400', error: 'bg-red-400', idle: 'bg-slate-300' };
                    setClass('live-dot', 'relative inline-flex h-2.5 w-2.5 rounded-full ' + colors[state]);
                    setClass('live-ping', 'absolute inline-flex h-full w-full rounded-full opacity-75 ' + (state === 'live' ? 'animate-ping ' + colors[state] : ''));
                    setText('live-label', label);
                };

                let streamState = 'connecting';

                const renderDevice = (rows) => {
                    const latest = rows.at(-1);

                    if (streamState !== 'connected') {
                        setLive(streamState === 'offline' ? 'error' : 'idle', streamState === 'offline' ? 'MQTT terputus, mencoba lagi…' : 'Menghubungkan ke broker MQTT…');
                    }

                    if (!latest) {
                        if (streamState === 'connected') {
                            setLive('idle', 'Belum ada data dari perangkat');
                        }
                        return;
                    }

                    const age = Date.now() - new Date(latest.created_at).getTime();
                    const isOnline = age <= STALE_AFTER_MS;

                    if (streamState === 'connected') {
                        setLive(isOnline ? 'live' : 'stale', isOnline ? 'Live MQTT · ' + formatAgo(age) : 'Offline · terakhir ' + latest.created_at_label);
                    }
                    setText('device-last', 'Update ' + latest.created_at_label + ' (' + formatAgo(age) + ')');
                    setText('device-state', isOnline ? 'Online' : 'Offline');
                    setClass('device-state', STATE_PILL + (isOnline ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'));
                    setClass('device-icon', ICON_TILE + (isOnline ? 'bg-brand-600 text-white shadow-lg shadow-brand-600/40' : 'bg-slate-100 text-slate-400'));

                    if (rows.length > 1) {
                        const span = new Date(latest.created_at) - new Date(rows[0].created_at);
                        setText('device-interval', (span / (rows.length - 1) / 1000).toFixed(1) + ' detik');
                    }
                };

                /* ---------- Chart ---------- */

                const areaGradient = (hex, alpha) => (context) => {
                    const { ctx, chartArea } = context.chart;
                    if (!chartArea) return 'transparent';
                    const gradient = ctx.createLinearGradient(0, chartArea.top, 0, chartArea.bottom);
                    gradient.addColorStop(0, hex + alpha);
                    gradient.addColorStop(1, hex + '00');
                    return gradient;
                };

                const chart = new Chart($('sensor-chart'), {
                    data: {
                        labels: [],
                        datasets: [
                            {
                                type: 'line',
                                label: 'Kelembaban tanah',
                                data: [],
                                borderColor: COLORS.soil,
                                backgroundColor: areaGradient(COLORS.soil, '40'),
                                borderWidth: 2.5,
                                fill: true,
                                tension: 0.4,
                                pointRadius: [],
                                pointHoverRadius: 6,
                                pointBackgroundColor: COLORS.dry,
                                pointBorderColor: '#fff',
                                pointBorderWidth: 2,
                                order: 1,
                            },
                            {
                                type: 'bar',
                                label: 'Air habis',
                                data: [],
                                backgroundColor: COLORS.waterEmpty,
                                borderWidth: 0,
                                barPercentage: 1,
                                categoryPercentage: 1,
                                order: 2,
                            },
                            {
                                type: 'bar',
                                label: 'Pompa ON',
                                data: [],
                                backgroundColor: COLORS.pump,
                                borderWidth: 0,
                                barPercentage: 1,
                                categoryPercentage: 1,
                                order: 3,
                            },
                        ],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        animation: { duration: 400 },
                        interaction: { mode: 'index', intersect: false },
                        scales: {
                            x: {
                                grid: { display: false },
                                border: { color: '#e2e8f0' },
                                ticks: { color: '#94a3b8', maxTicksLimit: 10, maxRotation: 0 },
                            },
                            y: {
                                min: 0,
                                max: 100,
                                border: { display: false },
                                grid: { color: '#f1f5f9' },
                                ticks: { color: '#94a3b8', stepSize: 25, callback: (v) => v + '%' },
                            },
                        },
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                backgroundColor: '#0f172a',
                                titleColor: '#fff',
                                bodyColor: '#e2e8f0',
                                padding: 12,
                                cornerRadius: 10,
                                boxPadding: 4,
                                usePointStyle: true,
                                callbacks: {
                                    title: (items) => rows[items[0].dataIndex]?.created_at_label ?? '',
                                    label: (item) => {
                                        if (item.datasetIndex === 1) {
                                            return 'Air tandon: ' + (rows[item.dataIndex]?.is_water_empty ? 'HABIS' : 'ADA');
                                        }
                                        if (item.datasetIndex === 2) {
                                            return 'Pompa: ' + (rows[item.dataIndex]?.pump_status ? 'ON' : 'OFF');
                                        }
                                        return item.dataset.label + ': ' + item.parsed.y + '%';
                                    },
                                    afterBody: (items) => {
                                        const d = rows[items[0].dataIndex];
                                        return d ? ['', 'Status: ' + pumpState(d).label, 'Mode: ' + pumpModeLabel(d.pump_command)] : [];
                                    },
                                },
                            },
                        },
                    },
                });

                const soilTemperatureChart = new Chart($('soil-temp-chart'), {
                    data: {
                        labels: [],
                        datasets: [
                            {
                                type: 'line',
                                label: 'Suhu tanah (°C)',
                                data: [],
                                borderColor: COLORS.temperature,
                                backgroundColor: areaGradient(COLORS.temperature, '33'),
                                borderWidth: 2.5,
                                fill: true,
                                tension: 0.4,
                                pointRadius: 0,
                                pointHoverRadius: 5,
                                yAxisID: 'temperature',
                                order: 1,
                            },
                            {
                                type: 'bar',
                                label: 'Alarm buzzer',
                                data: [],
                                backgroundColor: COLORS.buzzer,
                                borderWidth: 0,
                                barPercentage: 1,
                                categoryPercentage: 1,
                                yAxisID: 'alarm',
                                order: 2,
                            },
                        ],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        animation: { duration: 400 },
                        interaction: { mode: 'index', intersect: false },
                        scales: {
                            x: {
                                grid: { display: false },
                                border: { color: '#e2e8f0' },
                                ticks: { color: '#94a3b8', maxTicksLimit: 10, maxRotation: 0 },
                            },
                            temperature: {
                                position: 'left',
                                suggestedMin: 15,
                                suggestedMax: 40,
                                border: { display: false },
                                grid: { color: '#f1f5f9' },
                                ticks: { color: COLORS.temperature, callback: (v) => v + '°C' },
                            },
                            alarm: { display: false, min: 0, max: 1 },
                        },
                        plugins: {
                            legend: { position: 'bottom', labels: { usePointStyle: true, boxWidth: 8, color: '#475569' } },
                            tooltip: {
                                backgroundColor: '#0f172a',
                                padding: 12,
                                cornerRadius: 10,
                                usePointStyle: true,
                                callbacks: {
                                    title: (items) => rows[items[0].dataIndex]?.created_at_label ?? '',
                                    label: (item) => {
                                        if (item.datasetIndex === 1) {
                                            const alarm = rows[item.dataIndex]?.buzzer_alarm;
                                            return 'Buzzer: ' + (alarm === null || alarm === undefined ? '--' : alarm ? 'ALARM' : 'diam');
                                        }
                                        return item.parsed.y === null ? item.dataset.label + ': --' : item.dataset.label + ': ' + formatDecimal(item.parsed.y);
                                    },
                                },
                            },
                        },
                    },
                });

                const renderLegend = () => {
                    const items = [
                        { index: 0, label: 'Kelembaban tanah', swatch: '<span class="h-0.5 w-4 rounded-full" style="background:' + COLORS.soil + '"></span>' },
                        { index: 1, label: 'Air habis', swatch: '<span class="h-3 w-3 rounded-sm" style="background:' + COLORS.waterEmptySolid + '"></span>' },
                        { index: 2, label: 'Pompa ON', swatch: '<span class="h-3 w-3 rounded-sm" style="background:' + COLORS.pumpSolid + '"></span>' },
                    ];

                    $('chart-legend').replaceChildren(...items.map((item) => {
                        const button = document.createElement('button');
                        const isVisible = chart.isDatasetVisible(item.index);
                        button.type = 'button';
                        button.className = 'inline-flex items-center gap-2 rounded-full border px-3 py-1.5 transition ' + (isVisible ? 'border-slate-200 text-slate-700' : 'border-dashed border-slate-200 text-slate-400 line-through');
                        button.innerHTML = item.swatch + '<span>' + item.label + '</span>';
                        button.addEventListener('click', () => {
                            chart.setDatasetVisibility(item.index, !isVisible);
                            chart.update();
                            renderLegend();
                        });
                        return button;
                    }), (() => {
                        const hint = document.createElement('span');
                        hint.className = 'inline-flex items-center gap-2 px-2 py-1.5 text-slate-400';
                        hint.innerHTML = '<span class="h-2.5 w-2.5 rounded-full border-2 border-white ring-1 ring-amber-400" style="background:' + COLORS.dry + '"></span>Titik = tanah kering';
                        return hint;
                    })());
                };

                const renderChart = () => {
                    chart.data.labels = rows.map((d) => d.time_label);
                    chart.data.datasets[0].data = rows.map((d) => d.soil_percent);
                    chart.data.datasets[0].pointRadius = rows.map((d) => (d.is_soil_dry ? 4 : 0));
                    chart.data.datasets[1].data = rows.map((d) => (d.is_water_empty ? 100 : null));
                    chart.data.datasets[2].data = rows.map((d) => (d.pump_status ? 100 : null));
                    chart.update();

                    soilTemperatureChart.data.labels = rows.map((d) => d.time_label);
                    soilTemperatureChart.data.datasets[0].data = rows.map((d) => d.soil_temperature ?? null);
                    soilTemperatureChart.data.datasets[1].data = rows.map((d) => (d.buzzer_alarm ? 1 : null));
                    soilTemperatureChart.update();
                };

                /* ---------- Live MQTT feed ---------- */

                /** The newest readings (up to historyMax) are rendered with the page; the range buttons only slice them. */
                const historyMax = @json($historyMax);
                let historyLimit = @json($historyLimit);
                let allRows = @json($history);
                let rows = allRows.slice(-historyLimit);

                const renderLive = () => {
                    if (rows.length) {
                        renderLatest(rows.at(-1));
                    }
                    renderStats(rows);
                    renderChart();
                    renderDevice(rows);
                };

                /**
                 * Every message the ESP32 publishes arrives here straight from the broker.
                 */
                const pushReading = (row) => {
                    allRows = [...allRows, row].slice(-historyMax);
                    rows = allRows.slice(-historyLimit);
                    renderLive();
                    prependLiveRow(row);
                };

                const onStreamStatus = (state) => {
                    streamState = state;
                    renderDevice(rows);
                };

                const setRange = (range) => {
                    historyLimit = range;
                    document.querySelectorAll('#range-buttons button').forEach((button) => {
                        const isActive = Number(button.dataset.range) === range;
                        button.className = 'rounded-md px-3 py-1.5 transition ' + (isActive ? 'bg-white text-brand-700 shadow-sm' : 'text-slate-500 hover:text-slate-900');
                    });

                    rows = allRows.slice(-range);
                    renderLive();
                };

                /* ---------- History table with filters ---------- */

                const table = @json($table);
                const filters = table.filters;
                let tableRows = table.data;

                const cell = (text, classes = 'px-4 py-3 text-slate-600') => {
                    const td = document.createElement('td');
                    td.className = classes;
                    td.textContent = text;
                    return td;
                };

                const onOffCell = (isOn) => {
                    const td = document.createElement('td');
                    td.className = 'px-4 py-3';
                    const pill = document.createElement('span');
                    pill.className = PILL + (isOn ? 'bg-slate-900 text-white' : OFF);
                    pill.textContent = isOn ? 'ON' : 'OFF';
                    td.append(pill);
                    return td;
                };

                const renderTable = (records, highlightIds = new Set()) => {
                    const body = $('history-body');
                    body.replaceChildren();
                    $('history-empty').classList.toggle('hidden', records.length > 0);

                    records.forEach((d) => {
                        const s = pumpState(d);
                        const tr = document.createElement('tr');
                        tr.className = 'transition-colors duration-1000 hover:bg-slate-50' + (highlightIds.has(d) ? ' bg-brand-50' : '');
                        tr.append(
                            cell(d.created_at_label, 'whitespace-nowrap px-6 py-3 text-slate-600'),
                            cell(d.soil_percent + '%', 'px-4 py-3 font-semibold text-slate-900'),
                            cell(d.soil_raw),
                            cell(d.is_water_empty ? 'Habis' : 'Ada', 'px-4 py-3 font-medium ' + (d.is_water_empty ? 'text-red-600' : 'text-sky-700')),
                            cell(hasSoilTemperature(d) ? formatDecimal(d.soil_temperature) + ' °C' : '--'),
                            onOffCell(d.pump_status),
                            cell(d.pump_command ? pumpModeLabel(d.pump_command) : '--', 'whitespace-nowrap px-4 py-3 text-slate-600'),
                            onOffCell(d.lampu1_d25),
                            onOffCell(d.lampu2_d14),
                        );

                        const statusCell = document.createElement('td');
                        statusCell.className = 'px-4 py-3';
                        const badge = document.createElement('span');
                        badge.className = PILL + s.badge;
                        badge.textContent = s.label;
                        badge.title = s.text;
                        statusCell.append(badge);
                        tr.append(statusCell);

                        body.append(tr);
                    });

                    if (highlightIds.size) {
                        setTimeout(() => body.querySelectorAll('tr.bg-brand-50').forEach((tr) => tr.classList.remove('bg-brand-50')), 1500);
                    }
                };

                const describeFilters = () => {
                    const parts = [];
                    if (filters.date_from || filters.date_to) {
                        parts.push((filters.date_from || '…') + ' s/d ' + (filters.date_to || '…'));
                    }
                    if (filters.status) {
                        parts.push(FILTER_LABELS[filters.status]);
                    }
                    if (filters.q) {
                        parts.push('cari "' + filters.q + '"');
                    }
                    return parts.length ? 'Filter: ' + parts.join(' · ') : 'Semua data, terbaru di atas';
                };

                const conditionOf = (d) => (d.is_water_empty ? 'empty' : d.is_soil_dry ? 'dry' : 'ok');

                const renderTableMeta = () => {
                    const meta = table.meta;
                    setText('table-summary', describeFilters() + ' · ' + meta.total.toLocaleString('id-ID') + ' data');
                    setText('page-info', meta.total ? 'Menampilkan ' + meta.from + '–' + meta.to + ' dari ' + meta.total.toLocaleString('id-ID') + ' · Hal. ' + meta.current_page + '/' + meta.last_page : 'Tidak ada data');
                };

                /**
                 * A live MQTT reading joins the first page when it matches the active filter, so the
                 * table follows the device without asking the server again.
                 */
                const prependLiveRow = (row) => {
                    const today = toYmd(new Date());
                    const matchesFilter = !filters.q
                        && (!filters.date_to || filters.date_to >= today)
                        && (!filters.date_from || filters.date_from <= today)
                        && (!filters.status || filters.status === conditionOf(row));

                    if (table.meta.current_page !== 1 || !matchesFilter) {
                        return;
                    }

                    tableRows = [row, ...tableRows].slice(0, table.meta.per_page);
                    table.meta.total += 1;
                    table.meta.from = 1;
                    table.meta.to = tableRows.length;
                    renderTable(tableRows, new Set([row]));
                    renderTableMeta();
                };

                $('filter-form').addEventListener('submit', () => {
                    // Empty fields would otherwise end up in the URL as ?date_from=&status=
                    $('filter-form').querySelectorAll('input, select').forEach((field) => { field.disabled = !field.value; });
                });
                // Re-enable the fields when the page comes back from the browser's back/forward cache.
                window.addEventListener('pageshow', () => {
                    $('filter-form').querySelectorAll('input, select').forEach((field) => { field.disabled = false; });
                });

                document.querySelectorAll('#quick-dates button').forEach((button) => {
                    button.addEventListener('click', () => {
                        const quick = button.dataset.quick;
                        const ranges = {
                            today: [daysAgo(0), daysAgo(0)],
                            yesterday: [daysAgo(1), daysAgo(1)],
                            7: [daysAgo(6), daysAgo(0)],
                            30: [daysAgo(29), daysAgo(0)],
                        };
                        [$('filter-date-from').value, $('filter-date-to').value] = ranges[quick];
                        $('filter-form').requestSubmit();
                    });
                });

                $('page-prev').addEventListener('click', () => { if (table.links.prev) window.location = table.links.prev; });
                $('page-next').addEventListener('click', () => { if (table.links.next) window.location = table.links.next; });

                /* ---------- CSV export ---------- */

                const exportForm = $('export-form');
                const exportCheckboxes = () => [...exportForm.querySelectorAll('input[name="datasets[]"]')];

                const updateExportSummary = () => {
                    const selected = exportCheckboxes().filter((input) => input.checked).length;
                    $('export-submit').disabled = selected === 0;
                    setText('export-summary', selected === 0
                        ? 'Pilih minimal satu jenis data sensor.'
                        : selected + ' jenis data sensor akan di-download. Kolom id & waktu selalu disertakan.');
                };

                exportForm.addEventListener('change', updateExportSummary);

                $('export-select-all').addEventListener('click', () => {
                    exportCheckboxes().forEach((input) => { input.checked = true; });
                    updateExportSummary();
                });

                $('export-select-none').addEventListener('click', () => {
                    exportCheckboxes().forEach((input) => { input.checked = false; });
                    updateExportSummary();
                });

                document.querySelectorAll('#export-quick-dates button').forEach((button) => {
                    button.addEventListener('click', () => {
                        const ranges = {
                            today: [daysAgo(0), daysAgo(0)],
                            7: [daysAgo(6), daysAgo(0)],
                            30: [daysAgo(29), daysAgo(0)],
                            all: ['', ''],
                        };
                        [$('export-date-from').value, $('export-date-to').value] = ranges[button.dataset.quick];
                        updateExportSummary();
                    });
                });

                /* ---------- Pump control over MQTT ---------- */

                const PUMP_CONFIRM_TIMEOUT_MS = 20000;
                const pumpForm = $('pump-form');
                const pumpButtons = [...pumpForm.querySelectorAll('[data-pump-command]')];
                let pendingCommand = null;
                let pendingTimer = null;

                const setPumpFeedback = (text, tone) => {
                    setText('pump-feedback', text);
                    setClass('pump-feedback', 'mt-2 text-xs ' + tone);
                };

                const clearPending = () => {
                    pendingCommand = null;
                    clearTimeout(pendingTimer);
                    pumpButtons.forEach((button) => button.classList.remove('animate-pulse'));
                };

                /**
                 * The ESP32 publishes right after it applies a command, so the first reading carrying
                 * the requested mode is the confirmation.
                 */
                const confirmPumpCommand = (command) => {
                    if (pendingCommand && command === pendingCommand) {
                        setPumpFeedback('✓ ESP32 sudah menjalankan mode ' + pumpModeLabel(command) + '.', 'text-emerald-600');
                        clearPending();
                    }
                };

                pumpForm.addEventListener('submit', async (event) => {
                    event.preventDefault();

                    const button = event.submitter;
                    const body = new FormData(pumpForm);
                    body.set('command', button.value);
                    clearPending();
                    pumpButtons.forEach((item) => { item.disabled = true; });
                    setPumpFeedback('Mengirim perintah ' + pumpModeLabel(button.value) + '…', 'text-slate-500');

                    try {
                        const response = await fetch(pumpForm.action, { method: 'POST', headers: { Accept: 'application/json' }, body });
                        const result = await response.json().catch(() => ({}));

                        if (!response.ok) {
                            const fallback = response.status === 419 ? 'Sesi login kedaluwarsa, muat ulang halaman.'
                                : response.status === 429 ? 'Terlalu banyak perintah, tunggu sebentar.'
                                : 'Gagal mengirim perintah (HTTP ' + response.status + ').';
                            throw new Error(result.message || fallback);
                        }

                        pendingCommand = button.value;
                        button.classList.add('animate-pulse');
                        setPumpFeedback('Perintah terkirim ke broker, menunggu konfirmasi ESP32…', 'text-brand-600');
                        pendingTimer = setTimeout(() => {
                            setPumpFeedback('Belum ada konfirmasi dari ESP32. Pastikan perangkat online dan firmware terbaru sudah di-upload.', 'text-amber-600');
                            clearPending();
                        }, PUMP_CONFIRM_TIMEOUT_MS);
                    } catch (error) {
                        setPumpFeedback(error.message, 'text-red-600');
                    } finally {
                        pumpButtons.forEach((item) => { item.disabled = false; });
                    }
                });

                /* ---------- Boot ---------- */

                $('filter-date-from').max = $('filter-date-to').max = toYmd(new Date());
                $('export-date-from').max = $('export-date-to').max = toYmd(new Date());
                updateExportSummary();
                document.querySelectorAll('#range-buttons button').forEach((button) => {
                    button.addEventListener('click', () => setRange(Number(button.dataset.range)));
                });

                renderLegend();
                setRange(historyLimit);
                renderTable(tableRows);
                renderTableMeta();
                window.SensorStream.connect({ onReading: pushReading, onStatus: onStreamStatus });
                // Keeps "x dtk lalu" fresh and flips to Offline when the ESP32 stops publishing.
                setInterval(() => renderDevice(rows), CLOCK_TICK_MS);
            })();
        </script>
    @endpush
</x-layouts.app>
