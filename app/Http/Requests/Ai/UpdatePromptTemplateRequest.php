<?php

namespace App\Http\Requests\Ai;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePromptTemplateRequest extends FormRequest
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
            'system_prompt' => ['required', 'string', 'min:50', 'max:20000'],
            'user_prompt_template' => ['nullable', 'string', 'max:2000'],
            'model' => ['nullable', 'string', 'max:100', 'regex:/^[A-Za-z0-9._:\-\/]+$/'],
            'temperature' => ['required', 'numeric', 'min:0', 'max:1'],
            'max_tokens' => ['required', 'integer', 'min:256', 'max:64000'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'system_prompt' => "ko'rsatma",
            'user_prompt_template' => 'foydalanuvchi shabloni',
            'max_tokens' => 'maksimal token',
        ];
    }
}
