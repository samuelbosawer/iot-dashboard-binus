<?php

namespace App\Http\Requests;

use App\Enums\PumpCommand;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SendPumpCommandRequest extends FormRequest
{
    /**
     * Every signed-in dashboard user may control the pump (the route requires auth).
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
            'command' => ['required', Rule::enum(PumpCommand::class)],
        ];
    }

    public function command(): PumpCommand
    {
        return PumpCommand::from($this->validated('command'));
    }
}
