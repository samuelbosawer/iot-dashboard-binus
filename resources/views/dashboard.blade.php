<x-layouts.app title="Dashboard">
    {{-- Hero --}}
    <section class="relative mb-6 overflow-hidden rounded-3xl bg-linear-to-br from-brand-600 via-brand-700 to-brand-900 p-6 text-white shadow-lg sm:p-8">
        <div class="pointer-events-none absolute -top-16 -right-16 h-56 w-56 rounded-full bg-white/10"></div>
        <div class="pointer-events-none absolute right-24 -bottom-20 h-40 w-40 rounded-full bg-white/5"></div>

        <div class="relative flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="text-xs font-semibold tracking-widest text-brand-200 uppercase">Smart Irrigation · ESP32</p>
                <h1 class="mt-1 text-2xl font-bold sm:text-3xl">IoT Penyiram Tanaman Binus</h1>
                <p class="mt-1 text-sm text-brand-100">Monitoring Soil &amp; Water secara realtime</p>
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
                    <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-violet-50 text-violet-600"><i class="fa-solid fa-water"></i></span>
                    <div>
                        <p class="text-sm font-semibold text-slate-900">Sensor Level Air</p>
                        <p class="text-xs text-slate-400">Tandon air · Pin D33</p>
                    </div>
                </div>
                <span id="water-badge" class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-500">--</span>
            </div>

            <div class="mt-5 flex items-end justify-between gap-4">
                <div>
                    <p id="water-raw" class="text-4xl font-bold text-slate-900">--</p>
                    <p class="text-xs text-slate-400">Nilai raw ADC (0 – 4095)</p>
                </div>
                <p class="text-2xl font-bold text-violet-600"><span id="water-percent">--</span><span class="text-base text-slate-400">%</span></p>
            </div>
            <div class="mt-4 h-2.5 overflow-hidden rounded-full bg-slate-100">
                <div id="water-bar" class="h-full w-0 rounded-full bg-violet-500 transition-all duration-700"></div>
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
        </x-ui.card>
    </div>

    {{-- Row 3: indicator lamps + ESP32 device --}}
    <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-3">
        <x-ui.card>
            <div class="flex items-center gap-4">
                <span id="lamp-d25-icon" class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-slate-100 text-xl text-slate-400 transition"><i class="fa-solid fa-lightbulb"></i></span>
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-semibold text-slate-900">Lampu Indikator 1</p>
                    <p class="text-xs text-slate-400">Pin D25 · Tanah kering / menyiram</p>
                </div>
                <span id="lamp-d25-state" class="rounded-full bg-slate-100 px-3 py-1 text-sm font-bold text-slate-500">--</span>
            </div>
        </x-ui.card>

        <x-ui.card>
            <div class="flex items-center gap-4">
                <span id="lamp-d14-icon" class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-slate-100 text-xl text-slate-400 transition"><i class="fa-solid fa-lightbulb"></i></span>
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-semibold text-slate-900">Lampu Indikator 2</p>
                    <p class="text-xs text-slate-400">Pin D14 · Air tandon habis</p>
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
                <p class="text-xs text-slate-400">Kelembaban tanah &amp; level air (%), area hijau = pompa menyala. Klik legenda untuk menyembunyikan.</p>
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

    {{-- History table with date filter --}}
    <x-ui.card padding="p-0" class="overflow-hidden">
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

            <form id="filter-form" class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-[1fr_1fr_1fr_auto]">
                <div>
                    <x-ui.label for="filter-date-from" value="Dari tanggal" />
                    <x-ui.input id="filter-date-from" type="date" name="date_from" icon="fa-calendar" />
                </div>
                <div>
                    <x-ui.label for="filter-date-to" value="Sampai tanggal" />
                    <x-ui.input id="filter-date-to" type="date" name="date_to" icon="fa-calendar-check" />
                </div>
                <div>
                    <x-ui.label for="filter-status" value="Status" />
                    <select id="filter-status" name="status" class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-500/30 focus:outline-none">
                        <option value="">Semua status</option>
                        <option value="ok">Aman</option>
                        <option value="dry">Tanah Kering</option>
                        <option value="empty">Air Habis</option>
                    </select>
                </div>
                <div class="flex items-end gap-2">
                    <x-ui.button type="submit">
                        <i class="fa-solid fa-filter"></i>
                        Terapkan
                    </x-ui.button>
                    <x-ui.button type="button" variant="secondary" id="filter-reset">Reset</x-ui.button>
                </div>
            </form>
            <p id="filter-error" class="mt-2 hidden text-sm text-red-600"></p>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="bg-slate-50 text-xs tracking-wide text-slate-500 uppercase">
                    <tr>
                        <th class="px-6 py-3 font-medium">Waktu</th>
                        <th class="px-4 py-3 font-medium">Tanah %</th>
                        <th class="px-4 py-3 font-medium">Soil Raw</th>
                        <th class="px-4 py-3 font-medium">Water Raw</th>
                        <th class="px-4 py-3 font-medium">Pompa</th>
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
                <x-ui.button variant="secondary" id="page-prev" disabled>
                    <i class="fa-solid fa-chevron-left"></i>
                    Sebelumnya
                </x-ui.button>
                <x-ui.button variant="secondary" id="page-next" disabled>
                    Berikutnya
                    <i class="fa-solid fa-chevron-right"></i>
                </x-ui.button>
            </div>
        </div>
    </x-ui.card>

    @push('scripts')
        <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
        <script>
            (() => {
                const HISTORY_URL = @json(url('/api/sensor/history'));
                const RECORDS_URL = @json(url('/api/sensor'));
                const REFRESH_MS = 1500;
                const REQUEST_TIMEOUT_MS = 5000;
                const STALE_AFTER_MS = 60000;
                const ADC_MAX = 4095;
                const GAUGE_CIRCUMFERENCE = 326.73;

                const COLORS = {
                    soil: '#2563eb',
                    water: '#7c3aed',
                    pump: 'rgba(16, 185, 129, 0.14)',
                    pumpSolid: '#10b981',
                    dry: '#f59e0b',
                };

                const PILL = 'inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold ';
                const STATE_PILL = 'rounded-full px-3 py-1 text-sm font-bold ';
                const ICON_TILE = 'flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl text-xl transition ';
                const OFF = 'bg-slate-100 text-slate-500';

                const STATUS = {
                    empty: {
                        icon: 'bg-red-500 text-white',
                        iconClass: 'fa-triangle-exclamation',
                        label: 'ALERT: Air Habis',
                        text: 'Air tandon habis. Lampu D14 menyala dan pompa tidak dapat menyiram.',
                        badge: 'bg-red-100 text-red-700',
                    },
                    dry: {
                        icon: 'bg-amber-400 text-white',
                        iconClass: 'fa-seedling',
                        label: 'Tanah Kering',
                        text: 'Tanah kering. Lampu D25 menyala dan penyiraman sedang berlangsung.',
                        badge: 'bg-amber-100 text-amber-700',
                    },
                    ok: {
                        icon: 'bg-emerald-500 text-white',
                        iconClass: 'fa-circle-check',
                        label: 'Aman',
                        text: 'Kelembaban tanah dan level air dalam kondisi baik.',
                        badge: 'bg-emerald-100 text-emerald-700',
                    },
                };

                const $ = (id) => document.getElementById(id);
                const setText = (id, value) => { $(id).textContent = value; };
                const setClass = (id, classes) => { $(id).className = classes; };
                const clamp = (value, min, max) => Math.min(Math.max(value, min), max);
                const statusKey = (d) => (d.is_water_empty ? 'empty' : d.is_soil_dry ? 'dry' : 'ok');
                const waterPercent = (d) => Math.round(clamp((d.water_raw / ADC_MAX) * 100, 0, 100));

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

                const fetchJson = async (url) => {
                    const controller = new AbortController();
                    const timeout = setTimeout(() => controller.abort(), REQUEST_TIMEOUT_MS);

                    try {
                        const response = await fetch(url, {
                            headers: { Accept: 'application/json' },
                            cache: 'no-store',
                            signal: controller.signal,
                        });
                        const body = await response.json();

                        if (!response.ok) {
                            const error = new Error(body.message || 'Bad response');
                            error.body = body;
                            throw error;
                        }

                        return body;
                    } finally {
                        clearTimeout(timeout);
                    }
                };

                /* ---------- Component cards ---------- */

                const renderLamp = (key, isOn, onClasses) => {
                    setClass(key + '-icon', ICON_TILE + (isOn ? onClasses : 'bg-slate-100 text-slate-400'));
                    setClass(key + '-state', STATE_PILL + (isOn ? 'bg-slate-900 text-white' : OFF));
                    setText(key + '-state', isOn ? 'ON' : 'OFF');
                };

                const renderLatest = (d) => {
                    const s = STATUS[statusKey(d)];

                    setClass('status-icon', 'flex h-12 w-12 shrink-0 items-center justify-center rounded-xl shadow-md ' + s.icon);
                    $('status-icon').innerHTML = '<i class="fa-solid ' + s.iconClass + ' text-xl"></i>';
                    setText('status-title', s.label);
                    setText('status-text', s.text);

                    setText('soil-percent', d.soil_percent);
                    setText('soil-raw', d.soil_raw);
                    setText('soil-badge', d.is_soil_dry ? 'Tanah Kering' : 'Tanah Lembab');
                    setClass('soil-badge', 'mt-3 ' + PILL + (d.is_soil_dry ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700'));
                    $('soil-gauge').style.strokeDashoffset = GAUGE_CIRCUMFERENCE * (1 - clamp(d.soil_percent, 0, 100) / 100);
                    $('soil-gauge').setAttribute('class', 'transition-all duration-700 ' + (d.is_soil_dry ? 'stroke-amber-500' : 'stroke-brand-500'));

                    setText('water-raw', d.water_raw);
                    setText('water-percent', waterPercent(d));
                    setText('water-badge', d.is_water_empty ? 'Air Habis' : 'Air Tersedia');
                    setClass('water-badge', PILL + (d.is_water_empty ? 'bg-red-100 text-red-700' : 'bg-emerald-100 text-emerald-700'));
                    $('water-bar').style.width = waterPercent(d) + '%';
                    setClass('water-bar', 'h-full rounded-full transition-all duration-700 ' + (d.is_water_empty ? 'bg-red-500' : 'bg-violet-500'));

                    setText('pump-status', d.pump_status ? 'ON' : 'OFF');
                    setClass('pump-status', 'text-4xl font-bold ' + (d.pump_status ? 'text-emerald-600' : 'text-slate-400'));
                    setText('pump-text', d.pump_status ? 'Sedang menyiram tanaman' : 'Pompa tidak aktif');
                    setText('pump-badge', d.pump_status ? 'Menyiram' : 'Standby');
                    setClass('pump-badge', PILL + (d.pump_status ? 'bg-emerald-100 text-emerald-700' : OFF));
                    setClass('pump-icon', 'flex h-11 w-11 items-center justify-center rounded-xl transition ' + (d.pump_status ? 'animate-pulse bg-emerald-500 text-white' : 'bg-slate-100 text-slate-400'));

                    renderLamp('lamp-d25', d.lampu1_d25, 'bg-amber-400 text-white shadow-lg shadow-amber-400/50');
                    renderLamp('lamp-d14', d.lampu2_d14, 'bg-red-500 text-white shadow-lg shadow-red-500/50');
                };

                const renderStats = (rows) => {
                    const values = rows.map((d) => d.soil_percent);
                    const hasData = values.length > 0;

                    setText('stat-min', hasData ? Math.min(...values) : '--');
                    setText('stat-max', hasData ? Math.max(...values) : '--');
                    setText('stat-avg', hasData ? Math.round(values.reduce((a, b) => a + b, 0) / values.length) : '--');
                    setText('pump-ratio', hasData ? Math.round((rows.filter((d) => d.pump_status).length / rows.length) * 100) : '--');
                };

                const setLive = (state, label) => {
                    const colors = { live: 'bg-emerald-400', stale: 'bg-amber-400', error: 'bg-red-400', idle: 'bg-slate-300' };
                    setClass('live-dot', 'relative inline-flex h-2.5 w-2.5 rounded-full ' + colors[state]);
                    setClass('live-ping', 'absolute inline-flex h-full w-full rounded-full opacity-75 ' + (state === 'live' ? 'animate-ping ' + colors[state] : ''));
                    setText('live-label', label);
                };

                const renderDevice = (rows) => {
                    const latest = rows.at(-1);

                    if (!latest) {
                        setLive('idle', 'Belum ada data dari perangkat');
                        return;
                    }

                    const age = Date.now() - new Date(latest.created_at).getTime();
                    const isOnline = age <= STALE_AFTER_MS;

                    setLive(isOnline ? 'live' : 'stale', isOnline ? 'Live · ' + formatAgo(age) : 'Offline · terakhir ' + latest.created_at_label);
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
                                type: 'line',
                                label: 'Level air',
                                data: [],
                                borderColor: COLORS.water,
                                backgroundColor: areaGradient(COLORS.water, '1f'),
                                borderWidth: 2,
                                borderDash: [6, 4],
                                fill: true,
                                tension: 0.4,
                                pointRadius: 0,
                                pointHoverRadius: 5,
                                pointBackgroundColor: COLORS.water,
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
                                        if (item.dataset.type === 'bar') {
                                            return 'Pompa: ' + (rows[item.dataIndex]?.pump_status ? 'ON' : 'OFF');
                                        }
                                        return item.dataset.label + ': ' + item.parsed.y + '%';
                                    },
                                    afterBody: (items) => {
                                        const d = rows[items[0].dataIndex];
                                        return d ? ['', 'Status: ' + STATUS[statusKey(d)].label.replace('ALERT: ', '')] : [];
                                    },
                                },
                            },
                        },
                    },
                });

                const renderLegend = () => {
                    const items = [
                        { index: 0, label: 'Kelembaban tanah', swatch: '<span class="h-0.5 w-4 rounded-full" style="background:' + COLORS.soil + '"></span>' },
                        { index: 1, label: 'Level air', swatch: '<span class="w-4 border-t-2 border-dashed" style="border-color:' + COLORS.water + '"></span>' },
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
                    chart.data.datasets[1].data = rows.map(waterPercent);
                    chart.data.datasets[2].data = rows.map((d) => (d.pump_status ? 100 : null));
                    chart.update();
                };

                /* ---------- Live polling ---------- */

                let historyLimit = @json($historyLimit);
                let rows = @json($history);
                let lastId = rows.length ? rows.at(-1).id : 0;
                let isPolling = false;

                const renderLive = () => {
                    if (rows.length) {
                        renderLatest(rows.at(-1));
                    }
                    renderStats(rows);
                    renderChart();
                    renderDevice(rows);
                };

                /**
                 * Poll only readings newer than the last one we have, so each tick is a tiny request.
                 */
                const poll = async () => {
                    if (isPolling || document.hidden) {
                        return;
                    }

                    isPolling = true;

                    try {
                        const fresh = await fetchJson(HISTORY_URL + '?limit=' + historyLimit + (lastId ? '&after_id=' + lastId : ''));

                        if (fresh.length) {
                            rows = [...rows, ...fresh].slice(-historyLimit);
                            lastId = rows.at(-1).id;
                            renderLive();
                            refreshTableIfLive(new Set(fresh.map((d) => d.id)));
                        } else {
                            renderDevice(rows);
                        }
                    } catch (error) {
                        setLive('error', 'Gagal memuat data, mencoba lagi…');
                    } finally {
                        isPolling = false;
                    }
                };

                const setRange = async (range) => {
                    historyLimit = range;
                    document.querySelectorAll('#range-buttons button').forEach((button) => {
                        const isActive = Number(button.dataset.range) === range;
                        button.className = 'rounded-md px-3 py-1.5 transition ' + (isActive ? 'bg-white text-brand-700 shadow-sm' : 'text-slate-500 hover:text-slate-900');
                    });

                    try {
                        rows = await fetchJson(HISTORY_URL + '?limit=' + range);
                        lastId = rows.length ? rows.at(-1).id : 0;
                        renderLive();
                    } catch (error) {
                        setLive('error', 'Gagal memuat grafik, mencoba lagi…');
                    }
                };

                /* ---------- History table with filters ---------- */

                const filters = { date_from: '', date_to: '', status: '' };
                let page = 1;

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
                        const s = STATUS[statusKey(d)];
                        const tr = document.createElement('tr');
                        tr.className = 'transition-colors duration-1000 hover:bg-slate-50' + (highlightIds.has(d.id) ? ' bg-brand-50' : '');
                        tr.append(
                            cell(d.created_at_label, 'whitespace-nowrap px-6 py-3 text-slate-600'),
                            cell(d.soil_percent + '%', 'px-4 py-3 font-semibold text-slate-900'),
                            cell(d.soil_raw),
                            cell(d.water_raw),
                            onOffCell(d.pump_status),
                            onOffCell(d.lampu1_d25),
                            onOffCell(d.lampu2_d14),
                        );

                        const statusCell = document.createElement('td');
                        statusCell.className = 'px-4 py-3';
                        const badge = document.createElement('span');
                        badge.className = PILL + s.badge;
                        badge.textContent = s.label.replace('ALERT: ', '');
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
                        parts.push(STATUS[filters.status].label.replace('ALERT: ', ''));
                    }
                    return parts.length ? 'Filter: ' + parts.join(' · ') : 'Semua data, terbaru di atas';
                };

                const loadTable = async (highlightIds) => {
                    const params = new URLSearchParams({ page, per_page: 15 });
                    Object.entries(filters).forEach(([key, value]) => value && params.set(key, value));

                    $('filter-error').classList.add('hidden');

                    try {
                        const result = await fetchJson(RECORDS_URL + '?' + params);
                        const meta = result.meta;

                        renderTable(result.data, highlightIds);
                        setText('table-summary', describeFilters() + ' · ' + meta.total + ' data');
                        setText('page-info', meta.total ? 'Menampilkan ' + meta.from + '–' + meta.to + ' dari ' + meta.total + ' · Hal. ' + meta.current_page + '/' + meta.last_page : 'Tidak ada data');
                        $('page-prev').disabled = meta.current_page <= 1;
                        $('page-next').disabled = meta.current_page >= meta.last_page;
                    } catch (error) {
                        const errors = error.body?.errors;
                        setText('filter-error', errors ? Object.values(errors).flat()[0] : 'Gagal memuat riwayat data.');
                        $('filter-error').classList.remove('hidden');
                    }
                };

                /**
                 * Only the first page of a filter that can still receive new readings follows the live feed.
                 */
                const refreshTableIfLive = (highlightIds) => {
                    const today = toYmd(new Date());
                    if (page === 1 && (!filters.date_to || filters.date_to >= today)) {
                        loadTable(highlightIds);
                    }
                };

                const applyFilters = () => {
                    filters.date_from = $('filter-date-from').value;
                    filters.date_to = $('filter-date-to').value;
                    filters.status = $('filter-status').value;
                    page = 1;
                    loadTable();
                };

                $('filter-form').addEventListener('submit', (event) => {
                    event.preventDefault();
                    applyFilters();
                });

                $('filter-reset').addEventListener('click', () => {
                    $('filter-form').reset();
                    applyFilters();
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
                        applyFilters();
                    });
                });

                $('page-prev').addEventListener('click', () => { page -= 1; loadTable(); });
                $('page-next').addEventListener('click', () => { page += 1; loadTable(); });

                /* ---------- Boot ---------- */

                $('filter-date-from').max = $('filter-date-to').max = toYmd(new Date());
                document.querySelectorAll('#range-buttons button').forEach((button) => {
                    button.addEventListener('click', () => setRange(Number(button.dataset.range)));
                });

                renderLegend();
                renderLive();
                setRange(historyLimit);
                loadTable();
                setInterval(poll, REFRESH_MS);
                document.addEventListener('visibilitychange', () => {
                    if (!document.hidden) {
                        poll();
                    }
                });
            })();
        </script>
    @endpush
</x-layouts.app>
