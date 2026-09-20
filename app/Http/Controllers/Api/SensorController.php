<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSensorDataRequest;
use App\Http\Resources\SensorDataResource;
use App\Models\SensorData;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SensorController extends Controller
{
    private const DEFAULT_HISTORY_LIMIT = 50;

    private const MAX_HISTORY_LIMIT = 200;

    /**
     * Store a reading sent by the ESP32.
     */
    public function store(StoreSensorDataRequest $request): JsonResponse
    {
        $sensorData = SensorData::create($request->validated());

        return SensorDataResource::make($sensorData)
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Return the most recent reading.
     */
    public function latest(): JsonResponse
    {
        $sensorData = SensorData::query()->latest('id')->first();

        if ($sensorData === null) {
            return response()->json(['message' => 'No sensor data yet.'], 404);
        }

        return SensorDataResource::make($sensorData)->response();
    }

    /**
     * Return the latest readings ordered oldest to newest, ready for charting.
     */
    public function history(Request $request): AnonymousResourceCollection
    {
        $limit = min(
            max((int) $request->query('limit', self::DEFAULT_HISTORY_LIMIT), 1),
            self::MAX_HISTORY_LIMIT,
        );

        $history = SensorData::query()
            ->latest('id')
            ->limit($limit)
            ->get()
            ->reverse()
            ->values();

        return SensorDataResource::collection($history);
    }
}
