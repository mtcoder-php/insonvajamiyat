<?php

namespace App\Http\Requests\Admin\System;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MailSettingsRequest extends FormRequest
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
        $smtp = $this->input('mailer') === 'smtp';

        return [
            'mailer' => ['required', Rule::in(['smtp', 'log'])],
            'host' => [$smtp ? 'required' : 'nullable', 'string', 'max:255', 'regex:/^[A-Za-z0-9.\-]+$/'],
            'port' => [$smtp ? 'required' : 'nullable', 'integer', 'min:1', 'max:65535'],
            'scheme' => ['required', Rule::in(['smtp', 'smtps'])],
            'username' => ['nullable', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'max:255'],
            'from_address' => ['required', 'email', 'max:255'],
            'from_name' => ['required', 'string', 'max:120'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'host' => 'SMTP server',
            'port' => 'port',
            'username' => 'login',
            'password' => 'parol',
            'from_address' => 'yuboruvchi email',
            'from_name' => 'yuboruvchi nomi',
        ];
    }
}
