<?php

namespace App\Models;

use App\Enums\PumpCommand;
use App\Enums\SensorStatusFilter;
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
     * Limit readings to a date range (inclusive, Y-m-d), a status (see SensorStatusFilter)
     * and/or a free-text search (see search()).
     *
     * @param  Builder<self>  $query
     * @param  array{date_from?: ?string, date_to?: ?string, status?: ?string, q?: ?string}  $filters
     */
    #[Scope]
    protected function filter(Builder $query, array $filters): void
    {
        $query
            ->when($filters['date_from'] ?? null, fn (Builder $query, string $date) => $query->where('created_at', '>=', Carbon::parse($date)->startOfDay()))
            ->when($filters['date_to'] ?? null, fn (Builder $query, string $date) => $query->where('created_at', '<', Carbon::parse($date)->addDay()->startOfDay()))
            ->when(SensorStatusFilter::tryFrom((string) ($filters['status'] ?? '')), fn (Builder $query, SensorStatusFilter $status) => $status->apply($query))
            ->when(trim((string) ($filters['q'] ?? '')), fn (Builder $query, string $term) => $query->search($term));
    }

    /**
     * Free-text search of the history table. A reading matches when any of these match:
     * - its time as stored or as shown in the table ("2026-10-21", "14:05", "21 Oct 2026", "21/10/2026"),
     * - a number: soil %, soil raw value or soil temperature, also written as shown ("35", "35%", "27,5 °C"),
     * - a keyword such as "kering", "tunggu jeda", "air ada", "manual off", "alarm", "diam" or "ds18b20 error".
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function search(Builder $query, string $term): void
    {
        $term = trim($term);
        $keyword = Str::lower($term);
        $number = self::searchNumber($keyword);
        $day = self::searchDay($term);

        $query->where(function (Builder $query) use ($term, $keyword, $number, $day): void {
            $query->where('created_at', 'like', '%'.$term.'%');

            if ($day) {
                $query->orWhere(fn (Builder $query) => $query->where('created_at', '>=', $day)->where('created_at', '<', $day->copy()->addDay()));
            }

            if ($number !== null) {
                $value = (float) $number;
                // "27" matches 27.0–27.99 °C, "27.5" matches 27.45–27.54 °C (a range works on SQLite and MySQL alike).
                [$from, $to] = str_contains($number, '.') ? [$value - 0.05, $value + 0.05] : [$value, $value + 1];

                $query->orWhere('soil_percent', $value)
                    ->orWhere('soil_raw', $value)
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
     * A number typed as the table shows it ("27,5 °C", "35%"), normalised to "27.5" / "35".
     */
    private static function searchNumber(string $keyword): ?string
    {
        if (! preg_match('/^(-?\d+(?:[.,]\d+)?)\s*(?:°\s*c?|%)?$/u', $keyword, $matches)) {
            return null;
        }

        return str_replace(',', '.', $matches[1]);
    }

    /**
     * A day typed as the table shows it ("21 Oct 2026") or in the local format ("21/10/2026", "21-10-2026").
     */
    private static function searchDay(string $term): ?Carbon
    {
        foreach (['d M Y', 'j M Y', 'd/m/Y', 'j/n/Y', 'd-m-Y', 'j-n-Y'] as $format) {
            if (Carbon::canBeCreatedFromFormat($term, $format)) {
                return Carbon::createFromFormat('!'.$format, $term);
            }
        }

        return null;
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
            'air ada' => fn (Builder $query) => $query->where('is_water_empty', false),
            'tersedia' => fn (Builder $query) => $query->where('is_water_empty', false),
            'menyiram' => fn (Builder $query) => $query->where('pump_status', true),
            'tunggu jeda' => fn (Builder $query) => SensorStatusFilter::Waiting->apply($query),
            'pompa on' => fn (Builder $query) => $query->where('pump_status', true),
            'pompa off' => fn (Builder $query) => $query->where('pump_status', false),
            'otomatis' => fn (Builder $query) => $query->where('pump_command', PumpCommand::Auto->value),
            'auto' => fn (Builder $query) => $query->where('pump_command', PumpCommand::Auto->value),
            'manual on' => fn (Builder $query) => $query->where('pump_command', PumpCommand::On->value),
            'manual off' => fn (Builder $query) => $query->where('pump_command', PumpCommand::Off->value),
            'alarm buzzer' => fn (Builder $query) => $query->where('buzzer_alarm', true),
            'diam' => fn (Builder $query) => $query->where('buzzer_alarm', false),
            'ds18b20 error' => fn (Builder $query) => $query->whereNull('soil_temperature'),
        ];
    }

    /**
     * What the pump was really doing, matching the ESP32 firmware. A dry soil alone does not mean
     * watering: in auto mode the pump runs 8 s at most once every 30 minutes, so between runs it waits.
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
     * The automation step as the ESP32 firmware names it ("stat" in iot_penyiram_binus.ino), e.g. "MULAI AUTO"
     * or "SELESAI". The firmware does not publish it, so it is rebuilt from this reading and the one before it:
     * an auto watering run starts when the pump switches on and is finished when it switches off again.
     */
    public function automationStatus(?self $previous = null): string
    {
        $wasPumping = (bool) $previous?->pump_status;

        return match (true) {
            $this->pump_command === PumpCommand::On->value => 'MANUAL ON',
            $this->pump_command === PumpCommand::Off->value => 'MANUAL OFF',
            $this->pump_status => $wasPumping ? 'AUTO SIRAM' : 'MULAI AUTO',
            $wasPumping => 'SELESAI',
            $this->is_water_empty => 'AIR HABIS',
            $this->is_soil_dry => 'TUNGGU',
            default => 'LEMBAB AMAN',
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
