<?php

namespace App\Enums;

use App\Models\SensorData;

/**
 * The groups of columns a user can pick when downloading the sensor dataset as CSV.
 * DHT11 air temperature / humidity are still stored but intentionally not offered here.
 */
enum SensorDataset: string
{
    case Soil = 'soil';
    case Water = 'water';
    case SoilTemperature = 'soil_temperature';
    case Pump = 'pump';
    case LampD25 = 'lamp_d25';
    case LampD14 = 'lamp_d14';
    case Buzzer = 'buzzer';
    case Status = 'status';

    /**
     * Public name of the dataset (without ESP32 pins, safe for the visitor site).
     */
    public function label(): string
    {
        return match ($this) {
            self::Soil => 'Kelembaban Tanah',
            self::Water => 'Float Switch Air Tandon',
            self::SoilTemperature => 'Suhu Tanah',
            self::Pump => 'Pompa Air',
            self::LampD25 => 'Lampu Indikator 1',
            self::LampD14 => 'Lampu Indikator 2',
            self::Buzzer => 'Alarm Buzzer',
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
            self::SoilTemperature => 'D26',
            self::Pump => 'D23',
            self::LampD25 => 'D25',
            self::LampD14 => 'D14',
            self::Buzzer => 'D13',
            self::Status => null,
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Soil => 'soil_percent, soil_raw, tanah_kering',
            self::Water => 'status_air, air_habis',
            self::SoilTemperature => 'suhu_tanah_c',
            self::Pump => 'pompa_d23, mode_pompa',
            self::LampD25 => 'lampu_d25',
            self::LampD14 => 'lampu_d14',
            self::Buzzer => 'alarm_buzzer',
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
            self::SoilTemperature => [
                'suhu_tanah_c' => fn (SensorData $reading): ?float => $reading->soil_temperature,
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
            self::Buzzer => [
                'alarm_buzzer' => fn (SensorData $reading): ?int => $reading->buzzer_alarm === null ? null : (int) $reading->buzzer_alarm,
            ],
            self::Status => [
                'status' => fn (SensorData $reading): string => $reading->statusLabel(),
            ],
        };
    }
}
