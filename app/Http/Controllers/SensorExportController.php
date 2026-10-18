<?php

namespace App\Http\Controllers;

use App\Enums\SensorDataset;
use App\Http\Requests\ExportSensorDataRequest;
use App\Models\SensorData;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SensorExportController extends Controller
{
    /**
     * Stream the selected sensor datasets for the chosen dates as a CSV download (oldest first).
     */
    public function __invoke(ExportSensorDataRequest $request): StreamedResponse
    {
        $columns = collect($request->datasets())
            ->flatMap(fn (SensorDataset $dataset): array => $dataset->columns())
            ->all();

        $query = SensorData::query()->filter($request->validated());

        return response()->streamDownload(function () use ($query, $columns): void {
            $handle = fopen('php://output', 'w');

            // UTF-8 BOM so Excel opens the file with the right encoding.
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, ['id', 'waktu', ...array_keys($columns)]);

            $query->lazyById(1000)->each(function (SensorData $reading) use ($handle, $columns): void {
                fputcsv($handle, [
                    $reading->id,
                    $reading->created_at?->format('Y-m-d H:i:s'),
                    ...array_map(fn (callable $value): int|float|string|null => $value($reading), array_values($columns)),
                ]);
            });

            fclose($handle);
        }, $this->filename($request), ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Build a descriptive file name such as dataset-sensor_2026-10-01_sd_2026-10-15.csv.
     */
    private function filename(ExportSensorDataRequest $request): string
    {
        $from = $request->validated('date_from') ?? 'awal';
        $to = $request->validated('date_to') ?? now()->format('Y-m-d');

        return "dataset-sensor_{$from}_sd_{$to}.csv";
    }
}
