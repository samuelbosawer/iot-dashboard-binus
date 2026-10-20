<?php

namespace App\Http\Controllers;

use App\Enums\SensorDataset;
use App\Http\Requests\VisitorReportRequest;
use App\Http\Resources\SensorDataResource;
use App\Models\SensorData;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;

class VisitorController extends Controller
{
    private const RECENT_LIMIT = 30;

    /**
     * Display the public visitor page with a general report of the chosen period.
     */
    public function index(VisitorReportRequest $request): View
    {
        $days = $request->days();
        $from = now()->subDays($days - 1)->startOfDay();

        $recent = SensorData::query()
            ->latest('id')
            ->limit(self::RECENT_LIMIT)
            ->get()
            ->reverse()
            ->values();

        $summary = $this->summary($from);

        return view('visitor', [
            'days' => $days,
            'periods' => VisitorReportRequest::PERIODS,
            'from' => $from,
            'summary' => $summary,
            'conclusions' => $this->conclusions($summary),
            'daily' => $this->daily($from, $days),
            'latest' => $recent->isNotEmpty() ? SensorDataResource::make($recent->last())->resolve() : null,
            'recent' => SensorDataResource::collection($recent)->resolve(),
            'datasets' => SensorDataset::cases(),
        ]);
    }

    /**
     * Aggregate every reading since the given moment into one general report.
     *
     * @return array{total: int, avg_soil: ?float, avg_temperature: ?float, min_temperature: ?float, max_temperature: ?float, avg_humidity: ?float, min_humidity: ?float, max_humidity: ?float, pump_percent: int, ok_percent: int, dry_percent: int, empty_percent: int, ok: int, dry: int, empty: int}
     */
    private function summary(Carbon $from): array
    {
        $row = SensorData::query()
            ->where('created_at', '>=', $from)
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('AVG(soil_percent) as avg_soil')
            ->selectRaw('AVG(temperature) as avg_temperature, MIN(temperature) as min_temperature, MAX(temperature) as max_temperature')
            ->selectRaw('AVG(humidity) as avg_humidity, MIN(humidity) as min_humidity, MAX(humidity) as max_humidity')
            ->selectRaw('SUM(CASE WHEN pump_status = 1 THEN 1 ELSE 0 END) as pump_on')
            ->selectRaw('SUM(CASE WHEN is_water_empty = 1 THEN 1 ELSE 0 END) as empty_count')
            ->selectRaw('SUM(CASE WHEN is_water_empty = 0 AND is_soil_dry = 1 THEN 1 ELSE 0 END) as dry_count')
            ->toBase()
            ->first();

        $total = (int) $row->total;
        $empty = (int) $row->empty_count;
        $dry = (int) $row->dry_count;
        $ok = $total - $empty - $dry;
        $percent = fn (int $count): int => $total > 0 ? (int) round($count / $total * 100) : 0;
        $round = fn (mixed $value): ?float => $value === null ? null : round((float) $value, 1);

        return [
            'total' => $total,
            'avg_soil' => $round($row->avg_soil),
            'avg_temperature' => $round($row->avg_temperature),
            'min_temperature' => $round($row->min_temperature),
            'max_temperature' => $round($row->max_temperature),
            'avg_humidity' => $round($row->avg_humidity),
            'min_humidity' => $round($row->min_humidity),
            'max_humidity' => $round($row->max_humidity),
            'pump_percent' => $percent((int) $row->pump_on),
            'ok_percent' => $percent($ok),
            'dry_percent' => $percent($dry),
            'empty_percent' => $percent($empty),
            'ok' => $ok,
            'dry' => $dry,
            'empty' => $empty,
        ];
    }

    /**
     * Plain-language findings of the report, so visitors do not have to read the numbers themselves.
     *
     * @param  array{total: int, avg_soil: ?float, avg_temperature: ?float, min_temperature: ?float, max_temperature: ?float, avg_humidity: ?float, pump_percent: int, ok_percent: int, dry_percent: int, empty_percent: int}  $summary
     * @return list<array{icon: string, tone: string, text: string}>
     */
    private function conclusions(array $summary): array
    {
        if ($summary['total'] === 0) {
            return [['icon' => 'fa-hourglass-half', 'tone' => 'slate', 'text' => 'Belum ada data sensor pada periode ini.']];
        }

        $number = fn (float $value): string => number_format($value, 1, ',', '.');
        $conclusions = [];

        $soil = $summary['avg_soil'];
        $conclusions[] = match (true) {
            $soil < 35 => ['icon' => 'fa-seedling', 'tone' => 'amber', 'text' => "Tanah cenderung kering dengan kelembaban rata-rata {$number($soil)}%."],
            $soil <= 60 => ['icon' => 'fa-seedling', 'tone' => 'emerald', 'text' => "Kelembaban tanah cukup, rata-rata {$number($soil)}%."],
            default => ['icon' => 'fa-seedling', 'tone' => 'brand', 'text' => "Tanah terjaga lembab dengan rata-rata {$number($soil)}%."],
        };

        if ($summary['avg_temperature'] !== null) {
            $temperature = $summary['avg_temperature'];
            $range = "{$number($summary['min_temperature'])}–{$number($summary['max_temperature'])} °C";
            $conclusions[] = match (true) {
                $temperature < 20 => ['icon' => 'fa-temperature-low', 'tone' => 'brand', 'text' => "Udara sejuk, suhu rata-rata {$number($temperature)} °C (rentang {$range})."],
                $temperature <= 30 => ['icon' => 'fa-temperature-half', 'tone' => 'emerald', 'text' => "Suhu udara normal, rata-rata {$number($temperature)} °C (rentang {$range})."],
                $temperature <= 35 => ['icon' => 'fa-temperature-three-quarters', 'tone' => 'amber', 'text' => "Udara cukup hangat, rata-rata {$number($temperature)} °C (rentang {$range})."],
                default => ['icon' => 'fa-temperature-full', 'tone' => 'red', 'text' => "Udara panas, rata-rata {$number($temperature)} °C (rentang {$range}). Tanaman butuh penyiraman lebih sering."],
            };

            $humidity = $summary['avg_humidity'];
            $conclusions[] = match (true) {
                $humidity < 40 => ['icon' => 'fa-droplet-slash', 'tone' => 'amber', 'text' => "Udara kering, kelembaban rata-rata {$number($humidity)}%."],
                $humidity <= 70 => ['icon' => 'fa-droplet', 'tone' => 'emerald', 'text' => "Kelembaban udara ideal, rata-rata {$number($humidity)}%."],
                default => ['icon' => 'fa-cloud-rain', 'tone' => 'brand', 'text' => "Udara lembab, kelembaban rata-rata {$number($humidity)}%."],
            };
        } else {
            $conclusions[] = ['icon' => 'fa-temperature-half', 'tone' => 'slate', 'text' => 'Sensor DHT belum mengirim data suhu dan kelembaban udara pada periode ini.'];
        }

        $conclusions[] = ['icon' => 'fa-faucet-drip', 'tone' => 'emerald', 'text' => "Pompa menyiram pada {$summary['pump_percent']}% dari seluruh pembacaan."];

        $conclusions[] = $summary['empty_percent'] > 0
            ? ['icon' => 'fa-triangle-exclamation', 'tone' => 'red', 'text' => "Air tandon sempat habis pada {$summary['empty_percent']}% pembacaan. Sistem dalam kondisi aman {$summary['ok_percent']}% dari waktu."]
            : ['icon' => 'fa-circle-check', 'tone' => 'emerald', 'text' => "Air tandon selalu tersedia. Sistem dalam kondisi aman {$summary['ok_percent']}% dari waktu."];

        return $conclusions;
    }

    /**
     * Daily averages for every day of the period, including days without readings.
     *
     * @return list<array{date: string, label: string, total: int, avg_soil: ?float, avg_temperature: ?float, avg_humidity: ?float}>
     */
    private function daily(Carbon $from, int $days): array
    {
        $rows = SensorData::query()
            ->where('created_at', '>=', $from)
            ->selectRaw('DATE(created_at) as day, COUNT(*) as total')
            ->selectRaw('AVG(soil_percent) as avg_soil, AVG(temperature) as avg_temperature, AVG(humidity) as avg_humidity')
            ->groupByRaw('DATE(created_at)')
            ->toBase()
            ->get()
            ->keyBy('day');

        return collect(range(0, $days - 1))
            ->map(function (int $offset) use ($from, $rows): array {
                $date = $from->copy()->addDays($offset);
                $row = $rows->get($date->format('Y-m-d'));

                return [
                    'date' => $date->format('Y-m-d'),
                    'label' => $date->format('d M'),
                    'total' => (int) ($row->total ?? 0),
                    'avg_soil' => isset($row->avg_soil) ? round((float) $row->avg_soil, 1) : null,
                    'avg_temperature' => isset($row->avg_temperature) ? round((float) $row->avg_temperature, 1) : null,
                    'avg_humidity' => isset($row->avg_humidity) ? round((float) $row->avg_humidity, 1) : null,
                ];
            })
            ->all();
    }
}
