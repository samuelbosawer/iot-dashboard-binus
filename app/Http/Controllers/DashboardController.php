<?php

namespace App\Http\Controllers;

use App\Http\Resources\SensorDataResource;
use App\Models\SensorData;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    private const HISTORY_LIMIT = 30;

    /**
     * Display the realtime IoT dashboard with the initial server-rendered data.
     */
    public function index(): View
    {
        $history = SensorData::query()
            ->latest('id')
            ->limit(self::HISTORY_LIMIT)
            ->get()
            ->reverse()
            ->values();

        return view('dashboard', [
            'latest' => $history->isNotEmpty() ? SensorDataResource::make($history->last())->resolve() : null,
            'history' => SensorDataResource::collection($history)->resolve(),
            'historyLimit' => self::HISTORY_LIMIT,
        ]);
    }
}
