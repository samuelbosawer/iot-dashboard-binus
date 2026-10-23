<?php

namespace App\Models;

use App\Enums\PumpCommand;
use Database\Factories\SensorDataFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

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
        'soil_temperature',
        'water_raw',
        'temperature',
        'humidity',
        'is_water_empty',
        'is_soil_dry',
        'pump_status',
        'pump_command',
        'lampu1_d25',
        'lampu2_d14',
        'buzzer_alarm',
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
            'soil_temperature' => 'float',
            'water_raw' => 'integer',
            'temperature' => 'float',
            'humidity' => 'float',
            'is_water_empty' => 'boolean',
            'is_soil_dry' => 'boolean',
            'pump_status' => 'boolean',
            'lampu1_d25' => 'boolean',
            'lampu2_d14' => 'boolean',
            'buzzer_alarm' => 'boolean',
        ];
    }

    /**
     * Limit readings to a date range (inclusive, Y-m-d), a system status (ok, dry, empty)
     * and/or a free-text search (see search()).
     *
     * @param  Builder<self>  $query
     * @param  array{date_from?: ?string, date_to?: ?string, status?: ?string, q?: ?string}  $filters
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
            ->when($status === 'ok', fn (Builder $query) => $query->where('is_water_empty', false)->where('is_soil_dry', false))
            ->when(trim((string) ($filters['q'] ?? '')), fn (Builder $query, string $term) => $query->search($term));
    }

    /**
     * Free-text search of the history table. A reading matches when any of these match:
     * - its time ("2026-10-21", "14:05"),
     * - a number: soil %, soil raw value or soil temperature ("35", "27.5"),
     * - a keyword such as "kering", "habis", "menyiram", "manual off" or "alarm".
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function search(Builder $query, string $term): void
    {
        $keyword = Str::lower(trim($term));

        $query->where(function (Builder $query) use ($term, $keyword): void {
            $query->where('created_at', 'like', '%'.trim($term).'%');

            if (is_numeric($keyword)) {
                $number = (float) $keyword;
                // "27" matches 27.0–27.99 °C, "27.5" matches 27.45–27.54 °C (a range works on SQLite and MySQL alike).
                [$from, $to] = str_contains($keyword, '.') ? [$number - 0.05, $number + 0.05] : [$number, $number + 1];

                $query->orWhere('soil_percent', $number)
                    ->orWhere('soil_raw', $number)
                    ->orWhere(fn (Builder $query) => $query->where('soil_temperature', '>=', $from)->where('soil_temperature', '<', $to));
            }

            if (mb_strlen($keyword) < 3) {
                return;
            }

            foreach (self::searchKeywords() as $word => $condition) {
                if (str_contains($word, $keyword) || str_contains($keyword, $word)) {
                    $query->orWhere($condition);
                }
            }
        });
    }

    /**
     * Words a visitor may type, each mapped to the readings it describes.
     *
     * @return array<string, callable(Builder<self>): void>
     */
    private static function searchKeywords(): array
    {
        return [
            'kering' => fn (Builder $query) => $query->where('is_soil_dry', true),
            'basah' => fn (Builder $query) => $query->where('is_soil_dry', false),
            'aman' => fn (Builder $query) => $query->where('is_water_empty', false)->where('is_soil_dry', false),
            'air habis' => fn (Builder $query) => $query->where('is_water_empty', true),
            'menyiram' => fn (Builder $query) => $query->where('pump_status', true),
            'pompa on' => fn (Builder $query) => $query->where('pump_status', true),
            'pompa off' => fn (Builder $query) => $query->where('pump_status', false),
            'otomatis' => fn (Builder $query) => $query->where('pump_command', PumpCommand::Auto->value),
            'auto' => fn (Builder $query) => $query->where('pump_command', PumpCommand::Auto->value),
            'manual on' => fn (Builder $query) => $query->where('pump_command', PumpCommand::On->value),
            'manual off' => fn (Builder $query) => $query->where('pump_command', PumpCommand::Off->value),
            'alarm buzzer' => fn (Builder $query) => $query->where('buzzer_alarm', true),
        ];
    }

    /**
     * What the pump was really doing, matching the ESP32 firmware. A dry soil alone does not mean
     * watering: in auto mode the pump runs 8 s at most once every 2 hours, so between runs it waits.
     * Mirrors SensorStream.pumpState() in resources/views/components/sensor-stream.blade.php.
     */
    public function statusLabel(): string
    {
        return match (true) {
            $this->pump_command === PumpCommand::On->value => 'Manual ON',
            $this->pump_command === PumpCommand::Off->value => 'Manual OFF',
            $this->pump_status => 'Menyiram',
            $this->is_water_empty => 'Air Habis',
            $this->is_soil_dry => 'Kering - Tunggu Jeda',
            default => 'Aman - Basah',
        };
    }

    /**
     * Pump mode reported by the firmware, e.g. "Otomatis"; null for readings stored before MQTT.
     */
    public function pumpModeLabel(): ?string
    {
        return $this->pump_command === null ? null : (PumpCommand::tryFrom($this->pump_command)?->label() ?? $this->pump_command);
    }
}
