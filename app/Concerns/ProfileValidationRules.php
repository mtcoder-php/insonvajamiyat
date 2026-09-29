<?php

namespace App\Concerns;

use App\Models\User;
use App\Support\PhoneNumber;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

trait ProfileValidationRules
{
    /**
     * Get the validation rules used to validate user profiles.
     *
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    protected function profileRules(?int $userId = null): array
    {
        return [
            'name' => $this->nameRules(),
            'email' => $this->emailRules($userId),
        ];
    }

    /**
     * Get the validation rules used to validate user names.
     *
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function nameRules(): array
    {
        return ['required', 'string', 'max:255'];
    }

    /**
     * Familiya / ism: harflar, bo'shliq, defis, apostrof (oʻ, gʻ, O'zbek ismlari uchun).
     *
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function personNameRules(): array
    {
        return ['required', 'string', 'min:2', 'max:100', "regex:/^[\\p{L}\\p{M}' ʻʼ‘’`\\-]+$/u"];
    }

    /**
     * Telefon: PhoneNumber::normalize() dan keyin E.164 ko'rinishida bo'lishi kerak.
     *
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function phoneRules(): array
    {
        return ['required', 'string', 'max:20', 'regex:'.PhoneNumber::PATTERN];
    }

    /**
     * Get the validation rules used to validate user emails.
     *
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function emailRules(?int $userId = null): array
    {
        return [
            'required',
            'string',
            'email',
            'max:255',
            $userId === null
                ? Rule::unique(User::class)
                : Rule::unique(User::class)->ignore($userId),
        ];
    }
}
