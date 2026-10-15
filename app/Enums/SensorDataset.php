<?php

namespace App\Enums;

use App\Models\SensorData;

/**
 * The groups of columns a user can pick when downloading the sensor dataset as CSV.
 */
enum SensorDataset: string
{
    case Soil = 'soil';
    case Water = 'water';
    case Pump = 'pump';
    case LampD25 = 'lamp_d25';
    case LampD14 = 'lamp_d14';
    case Status = 'status';

    public function label(): string
    {
        return match ($this) {
            self::Soil => 'Kelembaban Tanah (D34)',
            self::Water => 'Level Air (D33)',
            self::Pump => 'Pompa Air (D23)',
            self::LampD25 => 'Lampu Indikator 1 (D25)',
            self::LampD14 => 'Lampu Indikator 2 (D14)',
            self::Status => 'Status Sistem',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Soil => 'soil_percent, soil_raw, tanah_kering',
            self::Water => 'water_raw, water_percent, air_habis',
            self::Pump => 'pompa_d23',
            self::LampD25 => 'lampu_d25',
            self::LampD14 => 'lampu_d14',
            self::Status => 'status',
        };
    }

    /**
     * CSV columns of this dataset, keyed by header, each resolving its value from a reading.
     *
     * @return array<string, callable(SensorData): (int|string)>
     */
    public function columns(): array
    {
        return match ($this) {
            self::Soil => [
                'soil_percent' => fn (SensorData $reading): int => $reading->soil_percent,
                'soil_raw' => fn (SensorData $reading): int => $reading->soil_raw,
                'tanah_kering' => fn (SensorData $reading): int => (int) $reading->is_soil_dry,
            ],
            self::Water => [
                'water_raw' => fn (SensorData $reading): int => $reading->water_raw,
                'water_percent' => fn (SensorData $reading): int => (int) round(min($reading->water_raw, 4095) / 4095 * 100),
                'air_habis' => fn (SensorData $reading): int => (int) $reading->is_water_empty,
            ],
            self::Pump => [
                'pompa_d23' => fn (SensorData $reading): int => (int) $reading->pump_status,
            ],
            self::LampD25 => [
                'lampu_d25' => fn (SensorData $reading): int => (int) $reading->lampu1_d25,
            ],
            self::LampD14 => [
                'lampu_d14' => fn (SensorData $reading): int => (int) $reading->lampu2_d14,
            ],
            self::Status => [
                'status' => fn (SensorData $reading): string => $reading->statusLabel(),
            ],
        };
    }
}
