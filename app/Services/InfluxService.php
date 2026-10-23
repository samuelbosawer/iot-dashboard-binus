<?php

namespace App\Services;

use InfluxDB2\Client;
use InfluxDB2\FluxRecord;
use InfluxDB2\Model\WritePrecision;
use InfluxDB2\Point;
use InfluxDB2\WriteType;
use InvalidArgumentException;
use Throwable;

/**
 * Writes and reads sensor readings in InfluxDB 2.x (config/influxdb.php).
 */
class InfluxService
{
    /**
     * Write one reading as a point: measurement "sensor_data", tag device_id, one field per value.
     * Numbers and booleans become fields; null values and nested arrays are skipped.
     *
     * @param  array<string, mixed>  $data  e.g. ['soil_percent' => 42, 'soil_temperature' => 26.5, 'pump_status' => true]
     */
    public function writeSensorData(string $deviceId, array $data): void
    {
        $point = Point::measurement(config('influxdb.measurement'))
            ->addTag('device_id', $deviceId)
            ->time(now()->getTimestamp(), WritePrecision::S);

        $fields = 0;
        foreach ($data as $key => $value) {
            if ($key === 'device_id' || $value === null || is_array($value) || is_object($value)) {
                continue;
            }

            $point->addField((string) $key, is_int($value) || is_float($value) || is_bool($value) ? $value : (string) $value);
            $fields++;
        }

        if ($fields === 0) {
            throw new InvalidArgumentException('Sensor data needs at least one field.');
        }

        $this->withClient(function (Client $client) use ($point): void {
            $writeApi = $client->createWriteApi(['writeType' => WriteType::SYNCHRONOUS]);
            $writeApi->write($point, WritePrecision::S);
            $writeApi->close();
        });
    }

    /**
     * Readings of one device within a Flux range such as "-1h", "30m", "7d" (newest first),
     * one row per timestamp with every field as a column.
     *
     * @return list<array<string, mixed>>
     */
    public function querySensorData(string $deviceId, string $range = '-1h', int $limit = 500): array
    {
        $flux = sprintf(
            'from(bucket: %s)
              |> range(start: %s)
              |> filter(fn: (r) => r._measurement == %s and r.device_id == %s)
              |> pivot(rowKey: ["_time"], columnKey: ["_field"], valueColumn: "_value")
              |> sort(columns: ["_time"], desc: true)
              |> limit(n: %d)',
            $this->fluxString(config('influxdb.bucket')),
            $this->fluxRange($range),
            $this->fluxString(config('influxdb.measurement')),
            $this->fluxString($deviceId),
            max(1, min($limit, 10000)),
        );

        $tables = $this->withClient(fn (Client $client) => $client->createQueryApi()->query($flux)) ?? [];

        $rows = [];
        foreach ($tables as $table) {
            foreach ($table->records as $record) {
                $rows[] = $this->recordToArray($record);
            }
        }

        return $rows;
    }

    /**
     * Check that InfluxDB answers and the token is accepted.
     *
     * @return array{ok: bool, url: string, version: ?string, error: ?string}
     */
    public function ping(): array
    {
        try {
            $headers = $this->withClient(fn (Client $client) => $client->ping());
            $version = $headers['X-Influxdb-Version'][0] ?? $headers['x-influxdb-version'][0] ?? null;

            return ['ok' => true, 'url' => config('influxdb.url'), 'version' => $version, 'error' => null];
        } catch (Throwable $exception) {
            return ['ok' => false, 'url' => config('influxdb.url'), 'version' => null, 'error' => $exception->getMessage()];
        }
    }

    /**
     * @template T
     *
     * @param  callable(Client): T  $callback
     * @return T
     */
    private function withClient(callable $callback): mixed
    {
        $client = new Client([
            'url' => config('influxdb.url'),
            'token' => config('influxdb.token'),
            'org' => config('influxdb.org'),
            'bucket' => config('influxdb.bucket'),
            'precision' => WritePrecision::S,
            'timeout' => config('influxdb.timeout'),
            'verifySSL' => config('influxdb.verify_ssl'),
        ]);

        try {
            return $callback($client);
        } finally {
            $client->close();
        }
    }

    /**
     * Quote a value as a Flux string literal (escapes backslashes, quotes and interpolation).
     */
    private function fluxString(string $value): string
    {
        return '"'.addcslashes($value, '"\\$').'"';
    }

    /**
     * Accept "-1h", "1h", "30m", "7d", "2w", "1mo"… and always look back in time.
     */
    private function fluxRange(string $range): string
    {
        if (! preg_match('/^-?(\d+)(s|m|h|d|w|mo|y)$/', trim($range), $matches)) {
            throw new InvalidArgumentException("Invalid range [{$range}]. Use e.g. -1h, 30m or 7d.");
        }

        return '-'.$matches[1].$matches[2];
    }

    /**
     * @return array<string, mixed>
     */
    private function recordToArray(FluxRecord $record): array
    {
        return collect($record->values)
            ->except(['result', 'table', '_start', '_stop', '_measurement'])
            ->mapWithKeys(fn ($value, $key) => [$key === '_time' ? 'time' : $key => $value])
            ->all();
    }
}
