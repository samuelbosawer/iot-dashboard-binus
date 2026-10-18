@props(['dataset', 'models', 'downloadUrl'])

@php
    $tones = [
        'brand' => ['icon' => 'bg-brand-50 text-brand-600', 'bar' => 'bg-brand-500'],
        'sky' => ['icon' => 'bg-sky-50 text-sky-600', 'bar' => 'bg-sky-500'],
    ];

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

    {{-- Planned models --}}
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        @foreach ($models as $model)
            @php
                $tone = $tones[$model['tone']];
                $isReady = collect($model['requirements'])->every(fn (array $requirement): bool => $requirement['current'] >= $requirement['required']);
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
