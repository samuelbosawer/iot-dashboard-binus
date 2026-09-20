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
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'soil_percent' => ['required', 'integer', 'between:0,100'],
            'soil_raw' => ['required', 'integer', 'between:0,65535'],
            'water_raw' => ['required', 'integer', 'between:0,65535'],
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
