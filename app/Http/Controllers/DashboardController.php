<?php

namespace App\Http\Controllers;

use App\Enums\PumpCommand;
use App\Enums\SensorDataset;
use App\Http\Resources\SensorDataResource;
use App\Models\SensorData;
use App\Services\SensorHistoryTable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /** The chart starts at 30 readings; 120 are loaded so the 30/60/120 buttons work without a request. */
    private const HISTORY_LIMIT = 30;

    private const HISTORY_MAX = 120;

    private const TABLE_PER_PAGE = 15;

    /**
     * Display the realtime IoT dashboard with the initial server-rendered data.
     */
    public function index(Request $request, SensorHistoryTable $table): View
    {
        $history = SensorData::query()
            ->latest('id')
            ->limit(self::HISTORY_MAX)
            ->get()
            ->reverse()
            ->values();

        return view('dashboard', [
            'latest' => $history->isNotEmpty() ? SensorDataResource::make($history->last())->resolve() : null,
            'history' => SensorDataResource::collection($history)->resolve(),
            'historyLimit' => self::HISTORY_LIMIT,
            'historyMax' => self::HISTORY_MAX,
            'table' => $table->forRequest($request, self::TABLE_PER_PAGE, 'riwayat'),
            'datasets' => SensorDataset::cases(),
            'pumpCommands' => PumpCommand::cases(),
        ]);
    }
}
