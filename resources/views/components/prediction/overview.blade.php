@props(['dataset', 'models', 'hourly', 'trend', 'recommendations', 'soilDryPercent', 'downloadUrl'])

@php
    $tones = [
        'brand' => ['icon' => 'bg-brand-50 text-brand-600', 'bar' => 'bg-brand-500'],
        'sky' => ['icon' => 'bg-sky-50 text-sky-600', 'bar' => 'bg-sky-500'],
    ];

    $recommendationTones = [
        'red' => 'border-red-200 bg-red-50 text-red-600',
        'amber' => 'border-amber-200 bg-amber-50 text-amber-600',
        'brand' => 'border-brand-200 bg-brand-50 text-brand-600',
        'emerald' => 'border-emerald-200 bg-emerald-50 text-emerald-600',
        'slate' => 'border-slate-200 bg-slate-50 text-slate-500',
    ];

    $number = fn (float $value): string => number_format($value, 1, ',', '.');

    $steps = [
        ['icon' => 'fa-satellite-dish', 'title' => 'Kumpulkan data', 'text' => 'ESP32 mengirim data soil, float switch, DHT, dan pompa secara rutin.'],
        ['icon' => 'fa-file-csv', 'title' => 'Siapkan dataset', 'text' => 'Download CSV dari menu Download untuk dibersihkan dan dilabeli.'],
        ['icon' => 'fa-brain', 'title' => 'Latih model', 'text' => 'Latih & evaluasi model (mis. Python scikit-learn) memakai dataset tersebut.'],
        ['icon' => 'fa-plug', 'title' => 'Integrasi prediksi', 'text' => 'Hasil model ditampilkan di halaman ini secara realtime.'],
    ];
@endphp

<div class="space-y-6">
    {{-- Dataset readiness summary --}}
    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        @foreach ([
            ['label' => 'Total data sensor', 'value' => number_format($dataset['total'], 0, ',', '.'), 'icon' => 'fa-database'],
            ['label' => 'Hari terekam', 'value' => $dataset['days'], 'icon' => 'fa-calendar-days'],
            ['label' => 'Kejadian tanah kering', 'value' => number_format($dataset['dry'], 0, ',', '.'), 'icon' => 'fa-seedling'],
            ['label' => 'Kejadian air habis', 'value' => number_format($dataset['empty'], 0, ',', '.'), 'icon' => 'fa-droplet-slash'],
        ] as $stat)
            <x-ui.card>
                <div class="flex items-center justify-between">
                    <p class="text-xs font-medium text-slate-500">{{ $stat['label'] }}</p>
                    <i class="fa-solid {{ $stat['icon'] }} text-slate-300"></i>
                </div>
                <p class="mt-2 text-2xl font-bold text-slate-900">{{ $stat['value'] }}</p>
            </x-ui.card>
        @endforeach
    </div>

    {{-- Real data chart + recommendations --}}
    <div class="grid grid-cols-1 gap-6 xl:grid-cols-[1.6fr_1fr]">
        <x-ui.card>
            <div class="mb-4 flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 class="text-base font-semibold text-slate-900">Grafik Data Real · {{ count($hourly) }} Jam Terakhir</h2>
                    <p class="text-xs text-slate-400">Rata-rata per jam dari data sensor asli. Garis putus-putus = tren linear 24 jam + proyeksi 6 jam (baseline statistik, bukan model ML).</p>
                </div>
                @if ($trend['available'])
                    <div class="flex gap-4 text-right">
                        <div>
                            <p class="text-xs text-slate-400">Tren</p>
                            <p @class(['text-lg font-bold', 'text-amber-600' => $trend['slope'] < 0, 'text-emerald-600' => $trend['slope'] >= 0])>
                                {{ $trend['slope'] >= 0 ? '+' : '' }}{{ $number($trend['slope']) }}<span class="text-xs font-medium text-slate-400">%/jam</span>
                            </p>
                        </div>
                        <div>
                            <p class="text-xs text-slate-400">Kering dalam</p>
                            <p class="text-lg font-bold text-slate-900">{{ $trend['hours_until_dry'] !== null ? '±'.$number($trend['hours_until_dry']).' jam' : '--' }}</p>
                        </div>
                    </div>
                @endif
            </div>

            <div class="relative h-80">
                <canvas id="prediction-chart"></canvas>
            </div>

            @unless ($trend['available'])
                <p class="mt-3 text-xs text-slate-400">Tren belum dapat dihitung: dibutuhkan data minimal 3 jam berbeda dalam 24 jam terakhir.</p>
            @endunless
        </x-ui.card>

        <x-ui.card>
            <h2 class="text-base font-semibold text-slate-900">Rekomendasi</h2>
            <p class="text-xs text-slate-400">Dibuat otomatis dari data sensor terbaru</p>

            <ul class="mt-4 space-y-3">
                @foreach ($recommendations as $recommendation)
                    <li class="flex items-start gap-3 rounded-xl border p-3 {{ $recommendationTones[$recommendation['tone']] }}">
                        <i class="fa-solid {{ $recommendation['icon'] }} mt-0.5"></i>
                        <div>
                            <p class="text-sm font-semibold text-slate-900">{{ $recommendation['title'] }}</p>
                            <p class="mt-0.5 text-xs text-slate-600">{{ $recommendation['text'] }}</p>
                        </div>
                    </li>
                @endforeach
            </ul>
        </x-ui.card>
    </div>

    {{-- Planned models --}}
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        @foreach ($models as $model)
            @php
                $tone = $tones[$model['tone']];
                $isReady = $model['ready'];
            @endphp

            <x-ui.card class="flex flex-col">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <span class="flex h-12 w-12 items-center justify-center rounded-xl {{ $tone['icon'] }}"><i class="fa-solid {{ $model['icon'] }} text-lg"></i></span>
                        <div>
                            <h2 class="text-base font-semibold text-slate-900">{{ $model['title'] }}</h2>
                            <p class="text-xs text-slate-400">{{ $model['algorithm'] }}</p>
                        </div>
                    </div>
                    <span @class([
                        'inline-flex shrink-0 items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold',
                        'bg-emerald-100 text-emerald-700' => $isReady,
                        'bg-amber-100 text-amber-700' => ! $isReady,
                    ])>
                        <i class="fa-solid {{ $isReady ? 'fa-circle-check' : 'fa-hourglass-half' }}"></i>
                        {{ $isReady ? 'Data siap dilatih' : 'Mengumpulkan data' }}
                    </span>
                </div>

                <p class="mt-4 text-sm text-slate-600">{{ $model['target'] }}</p>

                <div class="mt-4">
                    <p class="text-xs font-semibold tracking-wide text-slate-400 uppercase">Fitur input</p>
                    <div class="mt-2 flex flex-wrap gap-2">
                        @foreach ($model['features'] as $feature)
                            <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600">{{ $feature }}</span>
                        @endforeach
                    </div>
                </div>

                <div class="mt-5 space-y-3">
                    <p class="text-xs font-semibold tracking-wide text-slate-400 uppercase">Kesiapan dataset</p>
                    @foreach ($model['requirements'] as $requirement)
                        @php($percent = min(100, (int) round($requirement['current'] / max($requirement['required'], 1) * 100)))
                        <div>
                            <div class="mb-1 flex justify-between text-xs">
                                <span class="text-slate-600">{{ $requirement['label'] }}</span>
                                <span class="font-semibold text-slate-900">{{ number_format($requirement['current'], 0, ',', '.') }} / {{ number_format($requirement['required'], 0, ',', '.') }}</span>
                            </div>
                            <div class="h-2 overflow-hidden rounded-full bg-slate-100">
                                <div class="h-full rounded-full {{ $percent >= 100 ? 'bg-emerald-500' : $tone['bar'] }}" style="width: {{ $percent }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-5 flex items-center gap-3 rounded-xl border border-dashed border-slate-300 bg-slate-50 p-4 text-sm text-slate-500">
                    <i class="fa-solid fa-flask text-slate-400"></i>
                    Hasil prediksi akan tampil di sini setelah model dilatih dan diintegrasikan.
                </div>
            </x-ui.card>
        @endforeach
    </div>

    {{-- Roadmap --}}
    <x-ui.card>
        <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-base font-semibold text-slate-900">Tahapan Pengembangan</h2>
            <a href="{{ $downloadUrl }}" class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700">
                <i class="fa-solid fa-download"></i>
                Download dataset
            </a>
        </div>
        <ol class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($steps as $index => $step)
                <li class="rounded-xl bg-slate-50 p-4">
                    <div class="flex items-center gap-2">
                        <span class="flex h-7 w-7 items-center justify-center rounded-full bg-brand-600 text-xs font-bold text-white">{{ $index + 1 }}</span>
                        <p class="text-sm font-semibold text-slate-900">{{ $step['title'] }}</p>
                    </div>
                    <p class="mt-2 text-xs text-slate-500">{{ $step['text'] }}</p>
                </li>
            @endforeach
        </ol>
    </x-ui.card>
</div>

@push('scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
    <script>
        (() => {
            const hourly = @json($hourly);
            const trend = @json($trend);
            const DRY = @json($soilDryPercent);
            const future = trend.projection.map(() => null);

            new Chart(document.getElementById('prediction-chart'), {
                data: {
                    labels: [...hourly.map((h) => h.label), ...trend.projection.map((p) => p.label)],
                    datasets: [
                        {
                            type: 'line',
                            label: 'Kelembaban tanah (rata-rata/jam)',
                            data: [...hourly.map((h) => h.avg_soil), ...future],
                            borderColor: '#2563eb',
                            backgroundColor: 'rgba(37, 99, 235, 0.12)',
                            borderWidth: 2.5,
                            fill: true,
                            tension: 0.35,
                            pointRadius: 0,
                            pointHoverRadius: 5,
                            order: 1,
                        },
                        {
                            type: 'line',
                            label: 'Tren & proyeksi',
                            data: [...trend.line, ...trend.projection.map((p) => p.value)],
                            borderColor: '#7c3aed',
                            borderWidth: 2,
                            borderDash: [6, 4],
                            pointRadius: 0,
                            pointHoverRadius: 4,
                            order: 2,
                        },
                        {
                            type: 'line',
                            label: 'Batas kering (' + DRY + '%)',
                            data: [...hourly, ...trend.projection].map(() => DRY),
                            borderColor: '#f59e0b',
                            borderWidth: 1.5,
                            borderDash: [2, 4],
                            pointRadius: 0,
                            pointHoverRadius: 0,
                            order: 3,
                        },
                        {
                            type: 'bar',
                            label: 'Air habis (% data)',
                            data: [...hourly.map((h) => h.empty_percent || null), ...future],
                            backgroundColor: 'rgba(239, 68, 68, 0.25)',
                            borderRadius: 4,
                            order: 4,
                        },
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    scales: {
                        x: { grid: { display: false }, ticks: { color: '#94a3b8', maxTicksLimit: 9, maxRotation: 0 } },
                        y: { min: 0, max: 100, border: { display: false }, grid: { color: '#f1f5f9' }, ticks: { color: '#94a3b8', stepSize: 25, callback: (v) => v + '%' } },
                    },
                    plugins: {
                        legend: { position: 'bottom', labels: { usePointStyle: true, boxWidth: 8, color: '#475569' } },
                        tooltip: {
                            backgroundColor: '#0f172a',
                            padding: 12,
                            cornerRadius: 10,
                            filter: (item) => item.parsed.y !== null && item.datasetIndex !== 2,
                            callbacks: {
                                label: (item) => item.dataset.label + ': ' + item.parsed.y + '%',
                                afterBody: (items) => {
                                    const hour = hourly[items[0].dataIndex];
                                    if (!hour) return ['', 'Proyeksi tren'];
                                    return hour.total ? ['', hour.total + ' data · pompa aktif ' + hour.pump_percent + '%'] : ['', 'Tidak ada data'];
                                },
                            },
                        },
                    },
                },
            });
        })();
    </script>
@endpush
