<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\FilterSensorDataRequest;
use App\Http\Requests\StoreSensorDataRequest;
use App\Http\Resources\SensorDataResource;
use App\Models\SensorData;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Carbon;

class SensorController extends Controller
{
    private const DEFAULT_HISTORY_LIMIT = 50;

    private const MAX_HISTORY_LIMIT = 200;

    private const DEFAULT_PER_PAGE = 15;

    /**
     * Return a paginated, filterable list of readings (newest first) for the history table.
     */
    public function index(FilterSensorDataRequest $request): AnonymousResourceCollection
    {
        $status = $request->validated('status');

        $readings = SensorData::query()
            ->when($request->validated('date_from'), fn ($query, string $date) => $query->where('created_at', '>=', Carbon::parse($date)->startOfDay()))
            ->when($request->validated('date_to'), fn ($query, string $date) => $query->where('created_at', '<', Carbon::parse($date)->addDay()->startOfDay()))
            ->when($status === 'empty', fn ($query) => $query->where('is_water_empty', true))
            ->when($status === 'dry', fn ($query) => $query->where('is_water_empty', false)->where('is_soil_dry', true))
            ->when($status === 'ok', fn ($query) => $query->where('is_water_empty', false)->where('is_soil_dry', false))
            ->latest('id')
            ->paginate((int) $request->validated('per_page', self::DEFAULT_PER_PAGE))
            ->withQueryString();

        return SensorDataResource::collection($readings);
    }

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
     *
     * When `after_id` is given only readings newer than that id are returned,
     * so the dashboard can poll cheaply for just the new rows.
     */
    public function history(Request $request): AnonymousResourceCollection
    {
        $limit = min(
            max((int) $request->query('limit', self::DEFAULT_HISTORY_LIMIT), 1),
            self::MAX_HISTORY_LIMIT,
        );

        $afterId = max((int) $request->query('after_id', 0), 0);

        $history = SensorData::query()
            ->when($afterId > 0, fn ($query) => $query->where('id', '>', $afterId))
            ->latest('id')
            ->limit($limit)
            ->get()
            ->reverse()
            ->values();

        return SensorDataResource::collection($history);
    }
}
