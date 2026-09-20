<x-layouts.app title="Dashboard">
    <div class="mb-6 flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="text-xl font-bold text-slate-900 sm:text-2xl">IoT Penyiram Tanaman Binus</h1>
            <p class="text-sm text-slate-500">Monitoring Soil &amp; Water · data realtime dari ESP32</p>
        </div>

        <div class="flex items-center gap-2 text-xs text-slate-500">
            <span id="live-dot" class="h-2.5 w-2.5 rounded-full bg-slate-300"></span>
            <span id="live-label">Menunggu data…</span>
        </div>
    </div>

    {{-- Status banner --}}
    <div id="status-banner" class="mb-6 flex items-center gap-4 rounded-2xl border border-slate-200 bg-white p-5">
        <div id="status-icon" class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-slate-100 text-slate-500">
            <i class="fa-solid fa-hourglass-half text-xl"></i>
        </div>
        <div>
            <p id="status-title" class="text-lg font-bold text-slate-700">Belum ada data</p>
            <p id="status-text" class="text-sm text-slate-500">Menunggu ESP32 mengirim data sensor pertama.</p>
        </div>
    </div>

    {{-- 4 realtime status cards --}}
    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-ui.card>
            <div class="flex items-center justify-between">
                <p class="text-sm font-medium text-slate-500">Kelembaban Tanah</p>
                <i class="fa-solid fa-droplet text-brand-500"></i>
            </div>
            <p class="mt-3 text-4xl font-bold text-slate-900"><span id="soil-percent">--</span><span class="text-2xl text-slate-400">%</span></p>
            <div class="mt-3 h-2 overflow-hidden rounded-full bg-slate-100">
                <div id="soil-bar" class="h-full w-0 rounded-full bg-brand-500 transition-all duration-500"></div>
            </div>
            <p class="mt-2 text-xs text-slate-400">Raw D34: <span id="soil-raw">--</span></p>
        </x-ui.card>

        <x-ui.card>
            <div class="flex items-center justify-between">
                <p class="text-sm font-medium text-slate-500">Water Level (Raw)</p>
                <i class="fa-solid fa-water text-brand-500"></i>
            </div>
            <p id="water-raw" class="mt-3 text-4xl font-bold text-slate-900">--</p>
            <p class="mt-3"><span id="water-badge" class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600">-</span></p>
            <p class="mt-2 text-xs text-slate-400">Sensor D33</p>
        </x-ui.card>

        <x-ui.card>
            <div class="flex items-center justify-between">
                <p class="text-sm font-medium text-slate-500">Pompa Air D23</p>
                <i class="fa-solid fa-faucet-drip text-brand-500"></i>
            </div>
            <p id="pump-status" class="mt-3 text-4xl font-bold text-slate-400">--</p>
            <p id="pump-text" class="mt-3 text-sm text-slate-500">Menunggu data</p>
        </x-ui.card>

        <x-ui.card>
            <div class="flex items-center justify-between">
                <p class="text-sm font-medium text-slate-500">Lampu Indikator</p>
                <i class="fa-solid fa-lightbulb text-brand-500"></i>
            </div>
            <ul class="mt-3 space-y-3">
                <li class="flex items-center justify-between">
                    <span class="text-sm text-slate-600">D25 · Tanah kering / menyiram</span>
                    <span id="lampu1" class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-500">--</span>
                </li>
                <li class="flex items-center justify-between">
                    <span class="text-sm text-slate-600">D14 · Air tandon habis</span>
                    <span id="lampu2" class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-500">--</span>
                </li>
            </ul>
        </x-ui.card>
    </div>

    {{-- Chart --}}
    <x-ui.card class="mb-6">
        <div class="mb-4 flex items-center justify-between">
            <h2 class="text-base font-semibold text-slate-900">Riwayat Kelembaban Tanah</h2>
            <span class="text-xs text-slate-400">{{ $historyLimit }} data terakhir</span>
        </div>
        <div class="relative h-64 sm:h-72">
            <canvas id="soil-chart"></canvas>
        </div>
    </x-ui.card>

    {{-- History table --}}
    <x-ui.card padding="p-0" class="overflow-hidden">
        <div class="border-b border-slate-200 px-6 py-4">
            <h2 class="text-base font-semibold text-slate-900">Riwayat Data</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
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
        <p id="history-empty" class="hidden px-6 py-8 text-center text-sm text-slate-400">Belum ada riwayat data.</p>
    </x-ui.card>

    @push('scripts')
        <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
        <script>
            (() => {
                const LATEST_URL = @json(url('/api/sensor/latest'));
                const HISTORY_URL = @json(url('/api/sensor/history')) + '?limit=' + @json($historyLimit);
                const REFRESH_MS = 3000;
                const STALE_AFTER_MS = 60000;

                const STATUS = {
                    empty: {
                        banner: 'border-red-200 bg-red-50',
                        icon: 'bg-red-100 text-red-600',
                        iconClass: 'fa-triangle-exclamation',
                        title: 'text-red-700',
                        label: 'ALERT: Air Habis',
                        text: 'Air tandon habis. Lampu D14 menyala dan pompa tidak dapat menyiram.',
                        badge: 'bg-red-100 text-red-700',
                    },
                    dry: {
                        banner: 'border-amber-200 bg-amber-50',
                        icon: 'bg-amber-100 text-amber-600',
                        iconClass: 'fa-seedling',
                        title: 'text-amber-700',
                        label: 'Tanah Kering',
                        text: 'Tanah kering. Lampu D25 menyala dan penyiraman sedang berlangsung.',
                        badge: 'bg-amber-100 text-amber-700',
                    },
                    ok: {
                        banner: 'border-emerald-200 bg-emerald-50',
                        icon: 'bg-emerald-100 text-emerald-600',
                        iconClass: 'fa-circle-check',
                        title: 'text-emerald-700',
                        label: 'Aman',
                        text: 'Kelembaban tanah dan level air dalam kondisi baik.',
                        badge: 'bg-emerald-100 text-emerald-700',
                    },
                };

                const $ = (id) => document.getElementById(id);

                const statusKey = (d) => (d.is_water_empty ? 'empty' : d.is_soil_dry ? 'dry' : 'ok');

                const setText = (id, value) => { $(id).textContent = value; };

                const onOffPill = (el, isOn, onClasses) => {
                    el.textContent = isOn ? 'ON' : 'OFF';
                    el.className = 'rounded-full px-2.5 py-1 text-xs font-semibold ' + (isOn ? onClasses : 'bg-slate-100 text-slate-500');
                };

                const renderLatest = (d) => {
                    const s = STATUS[statusKey(d)];

                    $('status-banner').className = 'mb-6 flex items-center gap-4 rounded-2xl border p-5 ' + s.banner;
                    $('status-icon').className = 'flex h-12 w-12 shrink-0 items-center justify-center rounded-full ' + s.icon;
                    $('status-icon').innerHTML = '<i class="fa-solid ' + s.iconClass + ' text-xl"></i>';
                    $('status-title').className = 'text-lg font-bold ' + s.title;
                    setText('status-title', s.label);
                    setText('status-text', s.text);

                    setText('soil-percent', d.soil_percent);
                    setText('soil-raw', d.soil_raw);
                    $('soil-bar').style.width = Math.min(Math.max(d.soil_percent, 0), 100) + '%';
                    $('soil-bar').className = 'h-full rounded-full transition-all duration-500 ' + (d.is_soil_dry ? 'bg-amber-500' : 'bg-brand-500');

                    setText('water-raw', d.water_raw);
                    setText('water-badge', d.is_water_empty ? 'Air Habis' : 'Air Tersedia');
                    $('water-badge').className = 'inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium ' + (d.is_water_empty ? 'bg-red-100 text-red-700' : 'bg-emerald-100 text-emerald-700');

                    setText('pump-status', d.pump_status ? 'ON' : 'OFF');
                    $('pump-status').className = 'mt-3 text-4xl font-bold ' + (d.pump_status ? 'text-emerald-600' : 'text-slate-400');
                    setText('pump-text', d.pump_status ? 'Sedang menyiram tanaman' : 'Pompa tidak aktif');

                    onOffPill($('lampu1'), d.lampu1_d25, 'bg-amber-100 text-amber-700');
                    onOffPill($('lampu2'), d.lampu2_d14, 'bg-red-100 text-red-700');
                };

                const cell = (text, classes = 'px-4 py-3') => {
                    const td = document.createElement('td');
                    td.className = classes;
                    td.textContent = text;
                    return td;
                };

                const renderTable = (rows) => {
                    const body = $('history-body');
                    body.replaceChildren();
                    $('history-empty').classList.toggle('hidden', rows.length > 0);

                    [...rows].reverse().forEach((d) => {
                        const s = STATUS[statusKey(d)];
                        const tr = document.createElement('tr');
                        tr.append(
                            cell(d.created_at_label, 'whitespace-nowrap px-6 py-3 text-slate-600'),
                            cell(d.soil_percent + '%', 'px-4 py-3 font-semibold text-slate-900'),
                            cell(d.soil_raw),
                            cell(d.water_raw),
                            cell(d.pump_status ? 'ON' : 'OFF'),
                            cell(d.lampu1_d25 ? 'ON' : 'OFF'),
                            cell(d.lampu2_d14 ? 'ON' : 'OFF'),
                        );

                        const statusCell = document.createElement('td');
                        statusCell.className = 'px-4 py-3';
                        const badge = document.createElement('span');
                        badge.className = 'rounded-full px-2.5 py-1 text-xs font-medium ' + s.badge;
                        badge.textContent = s.label.replace('ALERT: ', '');
                        statusCell.append(badge);
                        tr.append(statusCell);

                        body.append(tr);
                    });
                };

                const chart = new Chart($('soil-chart'), {
                    type: 'line',
                    data: {
                        labels: [],
                        datasets: [{
                            label: 'Kelembaban tanah (%)',
                            data: [],
                            borderColor: '#2563eb',
                            backgroundColor: 'rgba(37, 99, 235, 0.12)',
                            borderWidth: 2,
                            pointRadius: 3,
                            tension: 0.35,
                            fill: true,
                        }],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        animation: false,
                        scales: {
                            y: { min: 0, max: 100, ticks: { callback: (v) => v + '%' } },
                        },
                        plugins: { legend: { display: false } },
                    },
                });

                const renderChart = (rows) => {
                    chart.data.labels = rows.map((d) => d.time_label);
                    chart.data.datasets[0].data = rows.map((d) => d.soil_percent);
                    chart.update();
                };

                const setLive = (state, label) => {
                    const colors = { live: 'bg-emerald-500', stale: 'bg-amber-500', error: 'bg-red-500', idle: 'bg-slate-300' };
                    $('live-dot').className = 'h-2.5 w-2.5 rounded-full ' + colors[state];
                    setText('live-label', label);
                };

                const refresh = async () => {
                    try {
                        const [latestRes, historyRes] = await Promise.all([
                            fetch(LATEST_URL, { headers: { Accept: 'application/json' }, cache: 'no-store' }),
                            fetch(HISTORY_URL, { headers: { Accept: 'application/json' }, cache: 'no-store' }),
                        ]);

                        if (latestRes.status === 404) {
                            setLive('idle', 'Belum ada data dari perangkat');
                            return;
                        }

                        if (!latestRes.ok || !historyRes.ok) {
                            throw new Error('Bad response');
                        }

                        const latest = await latestRes.json();
                        const history = await historyRes.json();

                        renderLatest(latest);
                        renderChart(history);
                        renderTable(history);

                        const age = Date.now() - new Date(latest.created_at).getTime();
                        if (age > STALE_AFTER_MS) {
                            setLive('stale', 'Perangkat tidak mengirim data · terakhir ' + latest.created_at_label);
                        } else {
                            setLive('live', 'Live · update ' + latest.created_at_label);
                        }
                    } catch (error) {
                        setLive('error', 'Gagal memuat data, mencoba lagi…');
                    }
                };

                const initialLatest = @json($latest);
                const initialHistory = @json($history);

                if (initialLatest) {
                    renderLatest(initialLatest);
                    renderChart(initialHistory);
                    renderTable(initialHistory);
                } else {
                    renderTable([]);
                }

                refresh();
                setInterval(refresh, REFRESH_MS);
            })();
        </script>
    @endpush
</x-layouts.app>
