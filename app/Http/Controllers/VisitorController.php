<?php

namespace App\Http\Controllers;

use App\Enums\SensorDataset;
use App\Http\Requests\VisitorReportRequest;
use App\Http\Resources\SensorDataResource;
use App\Models\SensorData;
use App\Services\SensorHistoryTable;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;

class VisitorController extends Controller
{
    private const RECENT_LIMIT = 30;

    private const TABLE_PER_PAGE = 10;

    /**
     * Display the public visitor page with a general report of the chosen period.
     */
    public function index(VisitorReportRequest $request, SensorHistoryTable $table): View
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
            'table' => $table->forRequest($request, self::TABLE_PER_PAGE, 'tabel'),
        ]);
    }

    /**
     * Aggregate every reading since the given moment into one general report.
     *
     * @return array{total: int, avg_soil: ?float, avg_soil_temperature: ?float, min_soil_temperature: ?float, max_soil_temperature: ?float, pump_percent: int, buzzer_percent: int, ok_percent: int, dry_percent: int, empty_percent: int, ok: int, dry: int, empty: int}
     */
    private function summary(Carbon $from): array
    {
        $row = SensorData::query()
            ->where('created_at', '>=', $from)
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('AVG(soil_percent) as avg_soil')
            ->selectRaw('AVG(soil_temperature) as avg_soil_temperature, MIN(soil_temperature) as min_soil_temperature, MAX(soil_temperature) as max_soil_temperature')
            ->selectRaw('SUM(CASE WHEN pump_status = 1 THEN 1 ELSE 0 END) as pump_on')
            ->selectRaw('SUM(CASE WHEN buzzer_alarm = 1 THEN 1 ELSE 0 END) as buzzer_on')
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
            'avg_soil_temperature' => $round($row->avg_soil_temperature),
            'min_soil_temperature' => $round($row->min_soil_temperature),
            'max_soil_temperature' => $round($row->max_soil_temperature),
            'pump_percent' => $percent((int) $row->pump_on),
            'buzzer_percent' => $percent((int) $row->buzzer_on),
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
     * @param  array{total: int, avg_soil: ?float, avg_soil_temperature: ?float, min_soil_temperature: ?float, max_soil_temperature: ?float, pump_percent: int, buzzer_percent: int, ok_percent: int, dry_percent: int, empty_percent: int}  $summary
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

        if ($summary['avg_soil_temperature'] !== null) {
            $temperature = $summary['avg_soil_temperature'];
            $range = "{$number($summary['min_soil_temperature'])}–{$number($summary['max_soil_temperature'])} °C";
            $conclusions[] = match (true) {
                $temperature < 20 => ['icon' => 'fa-temperature-low', 'tone' => 'brand', 'text' => "Suhu tanah dingin, rata-rata {$number($temperature)} °C (rentang {$range}). Pertumbuhan umbi bisa melambat."],
                $temperature <= 30 => ['icon' => 'fa-temperature-half', 'tone' => 'emerald', 'text' => "Suhu tanah ideal, rata-rata {$number($temperature)} °C (rentang {$range})."],
                $temperature <= 35 => ['icon' => 'fa-temperature-three-quarters', 'tone' => 'amber', 'text' => "Suhu tanah cukup hangat, rata-rata {$number($temperature)} °C (rentang {$range}). Air tanah lebih cepat menguap."],
                default => ['icon' => 'fa-temperature-full', 'tone' => 'red', 'text' => "Suhu tanah panas, rata-rata {$number($temperature)} °C (rentang {$range}). Pertimbangkan mulsa atau naungan."],
            };
        } else {
            $conclusions[] = ['icon' => 'fa-temperature-half', 'tone' => 'slate', 'text' => 'Sensor suhu tanah (DS18B20) belum mengirim data pada periode ini.'];
        }

        if ($summary['buzzer_percent'] > 0) {
            $conclusions[] = ['icon' => 'fa-bell', 'tone' => 'amber', 'text' => "Alarm buzzer berbunyi pada {$summary['buzzer_percent']}% pembacaan (air habis atau tanah kering)."];
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
     * @return list<array{date: string, label: string, total: int, avg_soil: ?float, avg_soil_temperature: ?float}>
     */
    private function daily(Carbon $from, int $days): array
    {
        $rows = SensorData::query()
            ->where('created_at', '>=', $from)
            ->selectRaw('DATE(created_at) as day, COUNT(*) as total')
            ->selectRaw('AVG(soil_percent) as avg_soil, AVG(soil_temperature) as avg_soil_temperature')
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
                    'avg_soil_temperature' => isset($row->avg_soil_temperature) ? round((float) $row->avg_soil_temperature, 1) : null,
                ];
            })
            ->all();
    }
}
