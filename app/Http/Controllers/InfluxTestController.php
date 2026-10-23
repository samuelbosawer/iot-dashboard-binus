<?php

namespace App\Http\Controllers;

use App\Services\InfluxService;
use Illuminate\Http\JsonResponse;
use Throwable;

class InfluxTestController extends Controller
{
    private const TEST_DEVICE = 'test-influx';

    /**
     * Check the InfluxDB connection: ping, write one test point, then read it back.
     */
    public function __invoke(InfluxService $influx): JsonResponse
    {
        $ping = $influx->ping();

        if (! $ping['ok']) {
            return response()->json(['ok' => false, 'ping' => $ping], 503);
        }

        try {
            $influx->writeSensorData(self::TEST_DEVICE, [
                'soil_percent' => random_int(20, 80),
                'soil_temperature' => round(random_int(2400, 3000) / 100, 2),
                'pump_status' => false,
                'source' => 'route /test-influx',
            ]);

            $rows = $influx->querySensorData(self::TEST_DEVICE, '-5m', 5);
        } catch (Throwable $exception) {
            return response()->json(['ok' => false, 'ping' => $ping, 'error' => $exception->getMessage()], 500);
        }

        return response()->json([
            'ok' => true,
            'ping' => $ping,
            'org' => config('influxdb.org'),
            'bucket' => config('influxdb.bucket'),
            'written_and_read_back' => count($rows) > 0,
            'latest_test_points' => $rows,
        ]);
    }
}
