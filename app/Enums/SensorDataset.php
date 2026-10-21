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
    case Climate = 'climate';
    case Pump = 'pump';
    case LampD25 = 'lamp_d25';
    case LampD14 = 'lamp_d14';
    case Status = 'status';

    /**
     * Public name of the dataset (without ESP32 pins, safe for the visitor site).
     */
    public function label(): string
    {
        return match ($this) {
            self::Soil => 'Kelembaban Tanah',
            self::Water => 'Float Switch Air Tandon',
            self::Climate => 'Suhu & Kelembaban Udara (DHT)',
            self::Pump => 'Pompa Air',
            self::LampD25 => 'Lampu Indikator 1',
            self::LampD14 => 'Lampu Indikator 2',
            self::Status => 'Status Sistem',
        };
    }

    /**
     * ESP32 pin of the sensor/actuator, shown on the admin dashboard only.
     */
    public function pin(): ?string
    {
        return match ($this) {
            self::Soil => 'D34',
            self::Water => 'D15',
            self::Climate => 'D4',
            self::Pump => 'D23',
            self::LampD25 => 'D25',
            self::LampD14 => 'D14',
            self::Status => null,
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Soil => 'soil_percent, soil_raw, tanah_kering',
            self::Water => 'status_air, air_habis',
            self::Climate => 'suhu_c, kelembaban_udara',
            self::Pump => 'pompa_d23, mode_pompa',
            self::LampD25 => 'lampu_d25',
            self::LampD14 => 'lampu_d14',
            self::Status => 'status',
        };
    }

    /**
     * CSV columns of this dataset, keyed by header, each resolving its value from a reading.
     *
     * @return array<string, callable(SensorData): (int|float|string|null)>
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
                'status_air' => fn (SensorData $reading): string => $reading->is_water_empty ? 'Habis' : 'Ada',
                'air_habis' => fn (SensorData $reading): int => (int) $reading->is_water_empty,
            ],
            self::Climate => [
                'suhu_c' => fn (SensorData $reading): ?float => $reading->temperature,
                'kelembaban_udara' => fn (SensorData $reading): ?float => $reading->humidity,
            ],
            self::Pump => [
                'pompa_d23' => fn (SensorData $reading): int => (int) $reading->pump_status,
                'mode_pompa' => fn (SensorData $reading): ?string => $reading->pumpModeLabel(),
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
