<?php

namespace App\Http\Resources;

use App\Models\SensorData;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin SensorData
 */
class SensorDataResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'soil_percent' => $this->soil_percent,
            'soil_raw' => $this->soil_raw,
            'water_raw' => $this->water_raw,
            'is_water_empty' => $this->is_water_empty,
            'is_soil_dry' => $this->is_soil_dry,
            'pump_status' => $this->pump_status,
            'lampu1_d25' => $this->lampu1_d25,
            'lampu2_d14' => $this->lampu2_d14,
            'created_at' => $this->created_at?->toIso8601String(),
            'created_at_label' => $this->created_at?->format('d M Y H:i:s'),
            'time_label' => $this->created_at?->format('H:i:s'),
        ];
    }
}
