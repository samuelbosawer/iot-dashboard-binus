<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class VisitorReportRequest extends FormRequest
{
    /**
     * The report periods (in days) a visitor can pick.
     */
    public const PERIODS = [1, 7, 30];

    public const DEFAULT_PERIOD = 7;

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
            'days' => ['nullable', 'integer', 'in:'.implode(',', self::PERIODS)],
        ];
    }

    /**
     * The chosen report period in days.
     */
    public function days(): int
    {
        return (int) $this->validated('days', self::DEFAULT_PERIOD);
    }
}
