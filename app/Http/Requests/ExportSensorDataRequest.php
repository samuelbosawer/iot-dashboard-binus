<?php

namespace App\Http\Requests;

use App\Enums\SensorDataset;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ExportSensorDataRequest extends FormRequest
{
    /**
     * Send validation errors back to the export form on the dashboard.
     */
    protected $errorBag = 'export';

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
            'status' => ['nullable', 'in:ok,dry,empty'],
            'datasets' => ['required', 'array', 'min:1'],
            'datasets.*' => [Rule::enum(SensorDataset::class)],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'datasets.required' => 'Pilih minimal satu jenis data sensor.',
            'date_to.after_or_equal' => 'Tanggal akhir tidak boleh sebelum tanggal awal.',
        ];
    }

    /**
     * The selected datasets, kept in the canonical column order.
     *
     * @return list<SensorDataset>
     */
    public function datasets(): array
    {
        $selected = $this->validated('datasets');

        return array_values(array_filter(
            SensorDataset::cases(),
            fn (SensorDataset $dataset): bool => in_array($dataset->value, $selected, true),
        ));
    }
}
