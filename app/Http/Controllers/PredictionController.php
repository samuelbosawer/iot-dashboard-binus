<?php

namespace App\Http\Controllers;

use App\Models\SensorData;
use Illuminate\Contracts\View\View;

/**
 * Machine learning prediction menu (first version): shows the planned soil and water models
 * and whether enough sensor data has been collected to train them. No predictions are made yet.
 */
class PredictionController extends Controller
{
    private const MIN_READINGS = 1000;

    private const MIN_DAYS = 7;

    private const MIN_EMPTY_EVENTS = 20;

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
     * @return array{dataset: array{total: int, days: int, dry: int, empty: int, first_at: ?string, last_at: ?string}, models: list<array{key: string, title: string, icon: string, tone: string, target: string, features: list<string>, algorithm: string, requirements: list<array{label: string, current: int, required: int}>}>}
     */
    private function overview(): array
    {
        $row = SensorData::query()
            ->selectRaw('COUNT(*) as total, MIN(created_at) as first_at, MAX(created_at) as last_at')
            ->selectRaw('COUNT(DISTINCT DATE(created_at)) as days')
            ->selectRaw('SUM(CASE WHEN is_soil_dry = 1 THEN 1 ELSE 0 END) as dry')
            ->selectRaw('SUM(CASE WHEN is_water_empty = 1 THEN 1 ELSE 0 END) as empty')
            ->toBase()
            ->first();

        $dataset = [
            'total' => (int) $row->total,
            'days' => (int) $row->days,
            'dry' => (int) $row->dry,
            'empty' => (int) $row->empty,
            'first_at' => $row->first_at,
            'last_at' => $row->last_at,
        ];

        $readings = ['label' => 'Jumlah data sensor', 'current' => $dataset['total'], 'required' => self::MIN_READINGS];
        $days = ['label' => 'Hari pengambilan data', 'current' => $dataset['days'], 'required' => self::MIN_DAYS];

        return [
            'dataset' => $dataset,
            'models' => [
                [
                    'key' => 'soil',
                    'title' => 'Prediksi Kelembaban Tanah',
                    'icon' => 'fa-seedling',
                    'tone' => 'brand',
                    'target' => 'Memperkirakan kelembaban tanah (%) 1 jam ke depan, sehingga penyiraman bisa dijadwalkan sebelum tanah kering.',
                    'features' => ['Riwayat soil_percent', 'Suhu & kelembaban udara (DHT)', 'Status pompa', 'Jam dalam sehari'],
                    'algorithm' => 'Regresi (Linear Regression / Random Forest)',
                    'requirements' => [$readings, $days],
                ],
                [
                    'key' => 'water',
                    'title' => 'Prediksi Air Tandon Habis',
                    'icon' => 'fa-water',
                    'tone' => 'sky',
                    'target' => 'Memperkirakan kapan float switch akan mendeteksi air tandon habis, sehingga tandon bisa diisi lebih awal.',
                    'features' => ['Durasi pompa menyala sejak tandon terisi', 'Frekuensi penyiraman', 'Suhu udara', 'Riwayat status float switch'],
                    'algorithm' => 'Klasifikasi (Logistic Regression / Decision Tree)',
                    'requirements' => [
                        $readings,
                        $days,
                        ['label' => 'Kejadian air habis', 'current' => $dataset['empty'], 'required' => self::MIN_EMPTY_EVENTS],
                    ],
                ],
            ],
        ];
    }
}
