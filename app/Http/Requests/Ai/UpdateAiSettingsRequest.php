<?php

namespace App\Http\Requests\Ai;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAiSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'enabled' => ['required', 'boolean'],
            'api_key' => ['nullable', 'string', 'min:20', 'max:300'],
            'model' => ['required', 'string', 'max:100', 'regex:/^[A-Za-z0-9._:\-\/]+$/'],
            'author_monthly_limit' => ['required', 'integer', 'min:0', 'max:100000000'],
            'staff_monthly_limit' => ['required', 'integer', 'min:0', 'max:100000000'],
            'max_input_chars' => ['required', 'integer', 'min:1000', 'max:200000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'api_key' => 'API kaliti',
            'model' => 'model',
            'author_monthly_limit' => 'muallif limiti',
            'staff_monthly_limit' => 'xodim limiti',
            'max_input_chars' => 'matn uzunligi',
        ];
    }
}
