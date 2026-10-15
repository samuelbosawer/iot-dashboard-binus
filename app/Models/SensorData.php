<?php

namespace App\Models;

use Database\Factories\SensorDataFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class SensorData extends Model
{
    /** @use HasFactory<SensorDataFactory> */
    use HasFactory;

    protected $table = 'sensor_data';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'soil_percent',
        'soil_raw',
        'water_raw',
        'is_water_empty',
        'is_soil_dry',
        'pump_status',
        'lampu1_d25',
        'lampu2_d14',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'soil_percent' => 'integer',
            'soil_raw' => 'integer',
            'water_raw' => 'integer',
            'is_water_empty' => 'boolean',
            'is_soil_dry' => 'boolean',
            'pump_status' => 'boolean',
            'lampu1_d25' => 'boolean',
            'lampu2_d14' => 'boolean',
        ];
    }

    /**
     * Limit readings to a date range (inclusive, Y-m-d) and/or a system status (ok, dry, empty).
     *
     * @param  Builder<self>  $query
     * @param  array{date_from?: ?string, date_to?: ?string, status?: ?string}  $filters
     */
    #[Scope]
    protected function filter(Builder $query, array $filters): void
    {
        $status = $filters['status'] ?? null;

        $query
            ->when($filters['date_from'] ?? null, fn (Builder $query, string $date) => $query->where('created_at', '>=', Carbon::parse($date)->startOfDay()))
            ->when($filters['date_to'] ?? null, fn (Builder $query, string $date) => $query->where('created_at', '<', Carbon::parse($date)->addDay()->startOfDay()))
            ->when($status === 'empty', fn (Builder $query) => $query->where('is_water_empty', true))
            ->when($status === 'dry', fn (Builder $query) => $query->where('is_water_empty', false)->where('is_soil_dry', true))
            ->when($status === 'ok', fn (Builder $query) => $query->where('is_water_empty', false)->where('is_soil_dry', false));
    }

    /**
     * Human readable system status, matching the dashboard.
     */
    public function statusLabel(): string
    {
        return match (true) {
            $this->is_water_empty => 'Air Habis',
            $this->is_soil_dry => 'Tanah Kering',
            default => 'Aman',
        };
    }
}
