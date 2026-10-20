<?php

namespace App\Services;

use App\Models\SensorData;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use JsonException;

/**
 * Turns an MQTT message published by the ESP32 into a stored reading.
 */
class SensorReadingIngestor
{
    /**
     * Decode, normalise, validate and store one sensor message.
     *
     * @throws JsonException when the message is not valid JSON
     * @throws ValidationException when the reading is incomplete or out of range
     */
    public function ingest(string $message): SensorData
    {
        $payload = json_decode($message, true, 16, JSON_THROW_ON_ERROR);

        if (! is_array($payload)) {
            throw ValidationException::withMessages(['payload' => 'The message must be a JSON object.']);
        }

        $validated = Validator::make($this->normalize($payload), $this->rules())->validate();

        return SensorData::create($validated);
    }

    /**
     * Map the firmware payload onto the sensor_data columns:
     * - relay_d23 is the real pump state ("pump_status" is sent as "ON"/"OFF"),
     * - lamp D25 lights while the pump runs and lamp D14 when the tank is empty,
     * - a failed DHT read is sent as 0 °C / 0 % and stored as "no reading".
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function normalize(array $payload): array
    {
        if ((float) ($payload['temperature'] ?? 0) === 0.0 && (float) ($payload['humidity'] ?? 0) === 0.0) {
            $payload['temperature'] = null;
            $payload['humidity'] = null;
        }

        $payload['pump_status'] = array_key_exists('relay_d23', $payload)
            ? $payload['relay_d23']
            : $this->toBoolean($payload['pump_status'] ?? null);

        $payload['lampu1_d25'] ??= $this->toBoolean($payload['pump_status']);
        $payload['lampu2_d14'] ??= $this->toBoolean($payload['is_water_empty'] ?? null);

        return $payload;
    }

    /**
     * @return array<string, array<int, string>>
     */
    private function rules(): array
    {
        return [
            'soil_percent' => ['required', 'integer', 'between:0,100'],
            'soil_raw' => ['required', 'integer', 'between:0,65535'],
            'water_raw' => ['nullable', 'integer', 'between:0,65535'],
            'temperature' => ['nullable', 'numeric', 'between:-40,80'],
            'humidity' => ['nullable', 'numeric', 'between:0,100'],
            'is_water_empty' => ['required', 'boolean'],
            'is_soil_dry' => ['required', 'boolean'],
            'pump_status' => ['required', 'boolean'],
            'lampu1_d25' => ['required', 'boolean'],
            'lampu2_d14' => ['required', 'boolean'],
            'pump_command' => ['nullable', 'string', 'max:20'],
        ];
    }

    /**
     * Accept the "ON"/"OFF" strings the firmware uses next to real booleans.
     */
    private function toBoolean(mixed $value): ?bool
    {
        return match ($value) {
            'ON' => true,
            'OFF' => false,
            default => filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE),
        };
    }
}
