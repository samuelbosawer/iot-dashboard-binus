<?php

namespace App\Enums;

use App\Models\SensorData;

/**
 * The groups of columns a user can pick when downloading the sensor dataset as CSV.
 * The cases are ordered so the columns follow the published dataset structure:
 * timestamp, soil_percent, soil_raw, soil_temperature, is_water_empty, pump_status, pump_command, status, alarm.
 * DHT11 air temperature / humidity and the indicator lamps are still stored but intentionally not offered here.
 */
enum SensorDataset: string
{
    case Soil = 'soil';
    case SoilTemperature = 'soil_temperature';
    case Water = 'water';
    case Pump = 'pump';
    case Status = 'status';
    case Buzzer = 'buzzer';

    /**
     * Public name of the dataset (without ESP32 pins, safe for the visitor site).
     */
    public function label(): string
    {
        return match ($this) {
            self::Soil => 'Kelembaban Tanah',
            self::SoilTemperature => 'Suhu Tanah',
            self::Water => 'Float Switch Air Tandon',
            self::Pump => 'Pompa Air',
            self::Status => 'Status Otomasi',
            self::Buzzer => 'Alarm Buzzer',
        };
    }

    /**
     * ESP32 pin of the sensor/actuator, shown on the admin dashboard only.
     */
    public function pin(): ?string
    {
        return match ($this) {
            self::Soil => 'D34',
            self::SoilTemperature => 'D26',
            self::Water => 'D15',
            self::Pump => 'D23',
            self::Buzzer => 'D13',
            self::Status => null,
        };
    }

    public function description(): string
    {
        return implode(', ', array_keys($this->columns()));
    }

    /**
     * CSV columns of this dataset, keyed by header, each resolving its value from a reading
     * and the reading stored just before it (needed to tell "MULAI AUTO" / "SELESAI" apart).
     *
     * @return array<string, callable(SensorData, ?SensorData): (int|float|string|null)>
     */
    public function columns(): array
    {
        return match ($this) {
            self::Soil => [
                'soil_percent' => fn (SensorData $reading): int => $reading->soil_percent,
                'soil_raw' => fn (SensorData $reading): int => $reading->soil_raw,
            ],
            self::SoilTemperature => [
                'soil_temperature' => fn (SensorData $reading): ?float => $reading->soil_temperature,
            ],
            self::Water => [
                'is_water_empty' => fn (SensorData $reading): string => self::boolean($reading->is_water_empty),
            ],
            self::Pump => [
                'pump_status' => fn (SensorData $reading): string => $reading->pump_status ? 'ON' : 'OFF',
                'pump_command' => fn (SensorData $reading): ?string => $reading->pump_command,
            ],
            self::Status => [
                'status' => fn (SensorData $reading, ?SensorData $previous): string => $reading->automationStatus($previous),
            ],
            self::Buzzer => [
                'alarm' => fn (SensorData $reading): ?string => $reading->buzzer_alarm === null ? null : self::boolean($reading->buzzer_alarm),
            ],
        };
    }

    /**
     * Booleans are written as "true" / "false", like the InfluxDB export.
     */
    private static function boolean(bool $value): string
    {
        return $value ? 'true' : 'false';
    }
}
