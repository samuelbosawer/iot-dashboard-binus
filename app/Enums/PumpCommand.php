<?php

namespace App\Enums;

/**
 * Messages the ESP32 firmware understands on the pump command topic.
 */
enum PumpCommand: string
{
    case Auto = 'auto';
    case On = 'pump_on';
    case Off = 'pump_off';

    /**
     * Short name shown on the dashboard control buttons.
     */
    public function label(): string
    {
        return match ($this) {
            self::Auto => 'Otomatis',
            self::On => 'Manual ON',
            self::Off => 'Manual OFF',
        };
    }

    /**
     * Font Awesome icon for the control button.
     */
    public function icon(): string
    {
        return match ($this) {
            self::Auto => 'fa-robot',
            self::On => 'fa-power-off',
            self::Off => 'fa-ban',
        };
    }
}
