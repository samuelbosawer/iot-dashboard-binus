<?php

namespace App\Http\Controllers;

use App\Enums\PumpCommand;
use App\Models\SensorData;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Machine learning prediction menu (first version, still in development): the planned soil moisture, float switch
 * and soil temperature models with a design-only preview of their results, a chart of the real hourly sensor data
 * with a linear trend baseline, and rule-based recommendations. No trained model is used yet.
 */
class PredictionController extends Controller
{
    private const MIN_READINGS = 1000;

    private const MIN_DAYS = 7;

    private const MIN_EMPTY_EVENTS = 20;

    private const CHART_HOURS = 48;

    private const TREND_HOURS = 24;

    private const PROJECTION_HOURS = 6;

    /** Same threshold as the ESP32 firmware: below this the soil is dry and the pump waters. */
    private const SOIL_DRY_PERCENT = 55;

    private const OFFLINE_AFTER_MINUTES = 10;

    /**
     * The public prediction page linked from the visitor site.
     */
    public function visitor(): View
    {
        return view('prediction.visitor', $this->overview());
    }

    /**
     * The prediction page inside the admin dashboard.
     */
    public function dashboard(): View
    {
        return view('prediction.dashboard', $this->overview());
    }

    /**
     * @return array<string, mixed>
     */
    private function overview(): array
    {
        $dataset = $this->dataset();
        $hourly = $this->hourly();
        $trend = $this->trend($hourly);
        $models = $this->models($dataset);

        return [
            'dataset' => $dataset,
            'models' => $models,
            'hourly' => $hourly,
            'trend' => $trend,
            'soilDryPercent' => self::SOIL_DRY_PERCENT,
            'recommendations' => $this->recommendations($dataset, $trend, $models),
        ];
    }

    /**
     * @return array{total: int, days: int, dry: int, empty: int, soil_temperature: int, first_at: ?string, last_at: ?string}
     */
    private function dataset(): array
    {
        $row = SensorData::query()
            ->selectRaw('COUNT(*) as total, COUNT(soil_temperature) as soil_temperature_count, MIN(created_at) as first_at, MAX(created_at) as last_at')
            ->selectRaw('COUNT(DISTINCT DATE(created_at)) as days')
            ->selectRaw('SUM(CASE WHEN is_soil_dry = 1 THEN 1 ELSE 0 END) as dry_count')
            ->selectRaw('SUM(CASE WHEN is_water_empty = 1 THEN 1 ELSE 0 END) as empty_count')
            ->toBase()
            ->first();

        return [
            'total' => (int) $row->total,
            'days' => (int) $row->days,
            'dry' => (int) $row->dry_count,
            'empty' => (int) $row->empty_count,
            'soil_temperature' => (int) $row->soil_temperature_count,
            'first_at' => $row->first_at,
            'last_at' => $row->last_at,
        ];
    }

    /**
     * Hourly averages of the real readings for the last CHART_HOURS hours (hours without data are null).
     *
     * @return list<array{hour: string, label: string, total: int, avg_soil: ?float, empty_percent: ?int, pump_percent: ?int}>
     */
    private function hourly(): array
    {
        $from = now()->subHours(self::CHART_HOURS - 1)->startOfHour();
        $bucket = match (DB::connection()->getDriverName()) {
            'sqlite' => "strftime('%Y-%m-%d %H:00:00', created_at)",
            'pgsql' => "to_char(created_at, 'YYYY-MM-DD HH24:00:00')",
            default => "DATE_FORMAT(created_at, '%Y-%m-%d %H:00:00')",
        };

        $rows = SensorData::query()
            ->where('created_at', '>=', $from)
            ->selectRaw("{$bucket} as hour, COUNT(*) as total, AVG(soil_percent) as avg_soil")
            ->selectRaw('SUM(CASE WHEN is_water_empty = 1 THEN 1 ELSE 0 END) as empty_count')
            ->selectRaw('SUM(CASE WHEN pump_status = 1 THEN 1 ELSE 0 END) as pump_count')
            ->groupByRaw($bucket)
            ->toBase()
            ->get()
            ->keyBy('hour');

        return collect(range(0, self::CHART_HOURS - 1))
            ->map(function (int $offset) use ($from, $rows): array {
                $hour = $from->copy()->addHours($offset);
                $row = $rows->get($hour->format('Y-m-d H:00:00'));
                $total = (int) ($row->total ?? 0);

                return [
                    'hour' => $hour->format('Y-m-d H:00'),
                    'label' => $hour->format('d M H:00'),
                    'total' => $total,
                    'avg_soil' => $total > 0 ? round((float) $row->avg_soil, 1) : null,
                    'empty_percent' => $total > 0 ? (int) round($row->empty_count / $total * 100) : null,
                    'pump_percent' => $total > 0 ? (int) round($row->pump_count / $total * 100) : null,
                ];
            })
            ->all();
    }

    /**
     * Least-squares line through the hourly soil averages of the last TREND_HOURS hours, projected
     * PROJECTION_HOURS ahead. A simple statistical baseline until a real model is trained.
     *
     * @param  list<array{label: string, avg_soil: ?float}>  $hourly
     * @return array{available: bool, slope: ?float, current: ?float, hours_until_dry: ?float, line: list<?float>, projection: list<array{label: string, value: float}>}
     */
    private function trend(array $hourly): array
    {
        $window = array_slice($hourly, -self::TREND_HOURS);
        $offset = count($hourly) - count($window);
        $points = [];

        foreach ($window as $index => $hour) {
            if ($hour['avg_soil'] !== null) {
                $points[] = [$offset + $index, $hour['avg_soil']];
            }
        }

        $empty = ['available' => false, 'slope' => null, 'current' => null, 'hours_until_dry' => null, 'line' => array_fill(0, count($hourly), null), 'projection' => []];

        if (count($points) < 3) {
            return $empty;
        }

        $n = count($points);
        $sumX = array_sum(array_column($points, 0));
        $sumY = array_sum(array_column($points, 1));
        $sumXY = array_sum(array_map(fn (array $p): float => $p[0] * $p[1], $points));
        $sumXX = array_sum(array_map(fn (array $p): float => $p[0] ** 2, $points));
        $denominator = $n * $sumXX - $sumX ** 2;

        if ($denominator == 0) {
            return $empty;
        }

        $slope = ($n * $sumXY - $sumX * $sumY) / $denominator;
        $intercept = ($sumY - $slope * $sumX) / $n;
        $at = fn (float $x): float => round(max(0, min(100, $intercept + $slope * $x)), 1);

        $lastX = end($points)[0];
        $current = $at($lastX);
        $lastHour = Carbon::parse(end($hourly)['hour']);

        $line = array_map(fn (int $x): ?float => $x >= $points[0][0] ? $at($x) : null, array_keys($hourly));

        $projection = array_map(fn (int $step): array => [
            'label' => $lastHour->copy()->addHours($step)->format('d M H:00'),
            'value' => $at(count($hourly) - 1 + $step),
        ], range(1, self::PROJECTION_HOURS));

        $hoursUntilDry = $slope < 0 && $current > self::SOIL_DRY_PERCENT
            ? round(($current - self::SOIL_DRY_PERCENT) / abs($slope), 1)
            : null;

        return [
            'available' => true,
            'slope' => round($slope, 2),
            'current' => $current,
            'hours_until_dry' => $hoursUntilDry,
            'line' => $line,
            'projection' => $projection,
        ];
    }

    /**
     * The planned model of each sensor. "outputs" are the results the model will show; until a model is
     * trained the page only renders them as an empty design preview.
     *
     * @param  array{total: int, days: int, empty: int, soil_temperature: int}  $dataset
     * @return list<array{key: string, title: string, sensor: string, icon: string, tone: string, target: string, features: list<string>, algorithm: string, outputs: list<array{label: string, unit: ?string}>, requirements: list<array{label: string, current: int, required: int}>, ready: bool}>
     */
    private function models(array $dataset): array
    {
        $readings = ['label' => 'Jumlah data sensor', 'current' => $dataset['total'], 'required' => self::MIN_READINGS];
        $days = ['label' => 'Hari pengambilan data', 'current' => $dataset['days'], 'required' => self::MIN_DAYS];

        $models = [
            [
                'key' => 'soil',
                'title' => 'Prediksi Kelembaban Tanah',
                'sensor' => 'Sensor soil moisture',
                'icon' => 'fa-seedling',
                'tone' => 'brand',
                'target' => 'Memperkirakan kelembaban tanah (%) 1 jam ke depan, sehingga penyiraman bisa dijadwalkan sebelum tanah kering.',
                'features' => ['Riwayat kelembaban tanah', 'Suhu tanah (DS18B20)', 'Status pompa', 'Jam dalam sehari'],
                'algorithm' => 'Regresi (Linear Regression / Random Forest)',
                'outputs' => [
                    ['label' => 'Kelembaban 1 jam lagi', 'unit' => '%'],
                    ['label' => 'Tanah kering dalam', 'unit' => 'jam'],
                    ['label' => 'Saran penyiraman', 'unit' => null],
                ],
                'requirements' => [$readings, $days],
            ],
            [
                'key' => 'water',
                'title' => 'Prediksi Air Tandon Habis',
                'sensor' => 'Water float switch',
                'icon' => 'fa-water',
                'tone' => 'sky',
                'target' => 'Memperkirakan kapan float switch akan mendeteksi air tandon habis, sehingga tandon bisa diisi lebih awal.',
                'features' => ['Durasi pompa menyala sejak tandon terisi', 'Frekuensi penyiraman', 'Suhu tanah', 'Riwayat status float switch'],
                'algorithm' => 'Klasifikasi (Logistic Regression / Decision Tree)',
                'outputs' => [
                    ['label' => 'Peluang habis 24 jam', 'unit' => '%'],
                    ['label' => 'Perkiraan habis dalam', 'unit' => 'jam'],
                    ['label' => 'Saran isi tandon', 'unit' => null],
                ],
                'requirements' => [
                    $readings,
                    $days,
                    ['label' => 'Kejadian air habis', 'current' => $dataset['empty'], 'required' => self::MIN_EMPTY_EVENTS],
                ],
            ],
            [
                'key' => 'soil_temperature',
                'title' => 'Prediksi Suhu Tanah',
                'sensor' => 'Sensor suhu tanah DS18B20',
                'icon' => 'fa-temperature-half',
                'tone' => 'amber',
                'target' => 'Memperkirakan suhu tanah (°C) 1 jam ke depan dan peluang melewati 32 °C (batas alarm buzzer), sehingga tanaman bisa dinaungi atau disiram lebih awal.',
                'features' => ['Riwayat suhu tanah', 'Kelembaban tanah', 'Status pompa', 'Jam dalam sehari'],
                'algorithm' => 'Regresi deret waktu (Linear Regression / Random Forest)',
                'outputs' => [
                    ['label' => 'Suhu 1 jam lagi', 'unit' => '°C'],
                    ['label' => 'Peluang di atas 32 °C', 'unit' => '%'],
                    ['label' => 'Kategori suhu', 'unit' => null],
                ],
                'requirements' => [
                    $readings,
                    $days,
                    ['label' => 'Data suhu tanah terbaca', 'current' => $dataset['soil_temperature'], 'required' => self::MIN_READINGS],
                ],
            ],
        ];

        return array_map(fn (array $model): array => [
            ...$model,
            'ready' => collect($model['requirements'])->every(fn (array $requirement): bool => $requirement['current'] >= $requirement['required']),
        ], $models);
    }

    /**
     * Rule-based recommendations from the real data, ordered by urgency.
     *
     * @param  array{total: int}  $dataset
     * @param  array{available: bool, slope: ?float, current: ?float, hours_until_dry: ?float}  $trend
     * @param  list<array{title: string, ready: bool, requirements: list<array{current: int, required: int}>}>  $models
     * @return list<array{icon: string, tone: string, title: string, text: string}>
     */
    private function recommendations(array $dataset, array $trend, array $models): array
    {
        if ($dataset['total'] === 0) {
            return [['icon' => 'fa-plug', 'tone' => 'slate', 'title' => 'Belum ada data', 'text' => 'Nyalakan ESP32 dan pastikan terhubung ke WiFi agar data sensor mulai terkumpul.']];
        }

        $latest = SensorData::query()->latest('id')->first();
        $day = SensorData::query()
            ->where('created_at', '>=', now()->subDay())
            ->selectRaw('COUNT(*) as total, AVG(soil_temperature) as avg_soil_temperature, COUNT(soil_temperature) as soil_temperature_count')
            ->selectRaw('SUM(CASE WHEN pump_status = 1 THEN 1 ELSE 0 END) as pump_count')
            ->toBase()
            ->first();
        $week = SensorData::query()
            ->where('created_at', '>=', now()->subDays(7))
            ->selectRaw('COUNT(*) as total, SUM(CASE WHEN is_water_empty = 1 THEN 1 ELSE 0 END) as empty_count')
            ->toBase()
            ->first();

        $number = fn (float $value): string => number_format($value, 1, ',', '.');
        $items = [];

        if ($latest->is_water_empty) {
            $items[] = ['icon' => 'fa-triangle-exclamation', 'tone' => 'red', 'title' => 'Isi tandon sekarang', 'text' => 'Float switch mendeteksi air habis. Pompa dikunci mati sampai tandon terisi, sehingga tanaman tidak akan disiram.'];
        }

        if ($latest->created_at->lt(now()->subMinutes(self::OFFLINE_AFTER_MINUTES))) {
            $items[] = ['icon' => 'fa-wifi', 'tone' => 'amber', 'title' => 'Perangkat tidak mengirim data', 'text' => 'Data terakhir diterima '.$latest->created_at->diffForHumans().'. Periksa daya dan koneksi WiFi ESP32.'];
        }

        if ($latest->pump_command === PumpCommand::On->value) {
            $items[] = $latest->is_water_empty
                ? ['icon' => 'fa-triangle-exclamation', 'tone' => 'red', 'title' => 'Pompa menyala manual saat air habis', 'text' => 'Pompa bisa rusak karena berjalan tanpa air. Kembalikan ke mode Otomatis atau Manual OFF dari dashboard.']
                : ['icon' => 'fa-hand-pointer', 'tone' => 'amber', 'title' => 'Pompa menyala manual', 'text' => 'Pompa terus menyala sampai dikirim perintah Otomatis atau Manual OFF dari dashboard.'];
        } elseif ($latest->pump_command === PumpCommand::Off->value && $latest->is_soil_dry) {
            $items[] = ['icon' => 'fa-ban', 'tone' => 'amber', 'title' => 'Penyiraman otomatis dimatikan', 'text' => "Tanah kering ({$latest->soil_percent}%) tetapi pompa di mode Manual OFF. Kembalikan ke mode Otomatis agar tanaman disiram."];
        } elseif ($latest->pump_status) {
            $items[] = ['icon' => 'fa-faucet-drip', 'tone' => 'brand', 'title' => 'Penyiraman sedang berjalan', 'text' => "Kelembaban tanah {$latest->soil_percent}%. Pastikan selang tidak tersumbat dan air mengalir ke tanaman."];
        } elseif ($latest->is_soil_dry && ! $latest->is_water_empty) {
            $items[] = ['icon' => 'fa-hourglass-half', 'tone' => 'brand', 'title' => 'Menunggu jadwal siram', 'text' => "Tanah kering ({$latest->soil_percent}%, di bawah ".self::SOIL_DRY_PERCENT.'%). Mode otomatis menyiram 8 detik paling sering sekali tiap 30 menit.'];
        }

        if ($trend['hours_until_dry'] !== null && $trend['hours_until_dry'] <= 12) {
            $items[] = ['icon' => 'fa-chart-line', 'tone' => 'amber', 'title' => 'Tanah segera mengering', 'text' => 'Berdasarkan tren 24 jam terakhir (turun '.$number(abs($trend['slope'])).'% per jam), tanah diperkirakan mencapai '.self::SOIL_DRY_PERCENT.'% dalam ±'.$number($trend['hours_until_dry']).' jam. Pastikan tandon cukup terisi.'];
        } elseif ($trend['available'] && $trend['slope'] > 0.5) {
            $items[] = ['icon' => 'fa-arrow-trend-up', 'tone' => 'emerald', 'title' => 'Kelembaban tanah naik', 'text' => 'Tren 24 jam terakhir naik '.$number($trend['slope']).'% per jam, penyiraman bekerja dengan baik.'];
        }

        $emptyShare = (int) $week->total > 0 ? (int) round($week->empty_count / $week->total * 100) : 0;
        if ($emptyShare >= 10) {
            $items[] = ['icon' => 'fa-droplet-slash', 'tone' => 'amber', 'title' => 'Air tandon sering habis', 'text' => "Air habis pada {$emptyShare}% data 7 hari terakhir. Pertimbangkan tandon lebih besar atau jadwal isi ulang rutin."];
        }

        $pumpShare = (int) $day->total > 0 ? (int) round($day->pump_count / $day->total * 100) : 0;
        if ($pumpShare >= 50) {
            $items[] = ['icon' => 'fa-gauge-high', 'tone' => 'amber', 'title' => 'Pompa terlalu sering menyala', 'text' => "Pompa aktif pada {$pumpShare}% data 24 jam terakhir. Cek kebocoran, posisi sensor tanah, atau kalibrasi nilai kering/basah sensor."];
        }

        if ((int) $day->total > 0 && (int) $day->soil_temperature_count === 0) {
            $items[] = ['icon' => 'fa-temperature-half', 'tone' => 'slate', 'title' => 'Data suhu tanah belum masuk', 'text' => 'Sensor DS18B20 tidak terbaca 24 jam terakhir. Periksa kabel di pin D26 dan resistor pull-up 4,7 kΩ.'];
        } elseif ($day->avg_soil_temperature !== null && (float) $day->avg_soil_temperature > 32) {
            $items[] = ['icon' => 'fa-sun', 'tone' => 'amber', 'title' => 'Suhu tanah tinggi', 'text' => 'Rata-rata suhu tanah 24 jam terakhir '.$number((float) $day->avg_soil_temperature).' °C. Tanah lebih cepat kering; mulsa dan penyiraman pagi atau sore lebih efektif.'];
        }

        $pending = collect($models)->reject(fn (array $model): bool => $model['ready']);
        if ($pending->isNotEmpty()) {
            $missing = max(0, self::MIN_READINGS - $dataset['total']);
            $items[] = ['icon' => 'fa-database', 'tone' => 'slate', 'title' => 'Lanjutkan pengumpulan data', 'text' => $missing > 0
                ? 'Model ML membutuhkan '.number_format($missing, 0, ',', '.').' data lagi. Biarkan ESP32 tetap menyala dan mengirim data.'
                : 'Jumlah data sudah cukup, tunggu hingga rentang hari dan kejadian air habis memenuhi syarat pelatihan.'];
        }

        if (collect($items)->doesntContain(fn (array $item): bool => in_array($item['tone'], ['red', 'amber'], true))) {
            array_unshift($items, ['icon' => 'fa-circle-check', 'tone' => 'emerald', 'title' => 'Kondisi stabil', 'text' => 'Tidak ada masalah terdeteksi. Air tandon tersedia dan kelembaban tanah terjaga.']);
        }

        return $items;
    }
}
