<?php

namespace App\Enums;

use App\Models\SensorData;
use Illuminate\Database\Eloquent\Builder;

/**
 * The "Status" options of the history table and CSV export, grouped by what they look at.
 * Mirrors SensorStream.matchesStatus() in resources/views/components/sensor-stream.blade.php.
 */
enum SensorStatusFilter: string
{
    case Ok = 'ok';
    case Dry = 'dry';
    case Empty = 'empty';
    case Watering = 'watering';
    case Waiting = 'waiting';
    case ManualOn = 'manual_on';
    case ManualOff = 'manual_off';
    case Auto = 'auto';
    case Alarm = 'alarm';
    case NoSoilTemperature = 'no_soil_temperature';

    public function label(): string
    {
        return match ($this) {
            self::Ok => 'Aman - Basah',
            self::Dry => 'Tanah kering',
            self::Empty => 'Air habis',
            self::Watering => 'Menyiram (pompa ON)',
            self::Waiting => 'Kering - Tunggu Jeda',
            self::ManualOn => 'Manual ON',
            self::ManualOff => 'Manual OFF',
            self::Auto => 'Mode otomatis',
            self::Alarm => 'Alarm buzzer berbunyi',
            self::NoSoilTemperature => 'Suhu tanah tidak terbaca',
        };
    }

    public function group(): string
    {
        return match ($this) {
            self::Ok, self::Dry, self::Empty => 'Kondisi tanah & air',
            self::Watering, self::Waiting, self::ManualOn, self::ManualOff, self::Auto => 'Pompa',
            self::Alarm, self::NoSoilTemperature => 'Sensor',
        };
    }

    /**
     * Options grouped for a <select>, e.g. ['Pompa' => [SensorStatusFilter::Watering, ...]].
     *
     * @return array<string, list<self>>
     */
    public static function grouped(): array
    {
        $groups = [];

        foreach (self::cases() as $status) {
            $groups[$status->group()][] = $status;
        }

        return $groups;
    }

    /**
     * Limit the query to readings with this status. The pump statuses follow SensorData::statusLabel(),
     * so they match the "Status" badge shown in the table.
     *
     * @param  Builder<SensorData>  $query
     */
    public function apply(Builder $query): void
    {
        $isNotManual = fn (Builder $query) => $query->where(fn (Builder $query) => $query
            ->whereNull('pump_command')
            ->orWhereNotIn('pump_command', [PumpCommand::On->value, PumpCommand::Off->value]));

        match ($this) {
            self::Ok => $query->where('is_water_empty', false)->where('is_soil_dry', false),
            self::Dry => $query->where('is_water_empty', false)->where('is_soil_dry', true),
            self::Empty => $query->where('is_water_empty', true),
            self::Watering => $isNotManual($query)->where('pump_status', true),
            self::Waiting => $isNotManual($query)->where('pump_status', false)->where('is_water_empty', false)->where('is_soil_dry', true),
            self::ManualOn => $query->where('pump_command', PumpCommand::On->value),
            self::ManualOff => $query->where('pump_command', PumpCommand::Off->value),
            self::Auto => $query->where('pump_command', PumpCommand::Auto->value),
            self::Alarm => $query->where('buzzer_alarm', true),
            self::NoSoilTemperature => $query->whereNull('soil_temperature'),
        };
    }
}
