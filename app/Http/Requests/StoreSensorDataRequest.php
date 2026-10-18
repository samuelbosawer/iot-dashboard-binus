<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreSensorDataRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * Access is guarded by the ApiKey middleware.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Fill in the fields the ESP32 sketch does not send, using the same logic as the firmware:
     * the D23 relay is the real pump state, D25 lights while watering and D14 lights when the tank is empty.
     * A failed DHT read (sent as 0 °C and 0 % by older firmware) is stored as "no reading" instead of fake zeros.
     */
    protected function prepareForValidation(): void
    {
        if ((float) $this->input('temperature') === 0.0 && (float) $this->input('humidity') === 0.0) {
            $this->merge(['temperature' => null, 'humidity' => null]);
        }

        $isWaterEmpty = $this->boolean('is_water_empty');
        $isSoilDry = $this->boolean('is_soil_dry');

        if ($this->has('relay_d23')) {
            $this->merge(['pump_status' => $this->input('relay_d23')]);
        }

        if ($this->missing('lampu1_d25')) {
            $this->merge(['lampu1_d25' => $isSoilDry && ! $isWaterEmpty]);
        }

        if ($this->missing('lampu2_d14')) {
            $this->merge(['lampu2_d14' => $isWaterEmpty]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
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
        ];
    }

    /**
     * Always answer devices with JSON, even without an Accept header.
     */
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'message' => 'Validation failed.',
            'errors' => $validator->errors(),
        ], 422));
    }
}
