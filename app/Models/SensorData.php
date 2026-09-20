<?php

namespace App\Models;

use Database\Factories\SensorDataFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
}
