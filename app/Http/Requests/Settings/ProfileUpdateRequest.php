<?php

namespace App\Http\Requests\Settings;

use App\Concerns\UserProfileValidationRules;
use App\Models\User;
use App\Support\PhoneNumber;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Shaxsiy profil: familiya, ism, otasining ismi, email, telefon, til va ilmiy ma'lumotlar.
 */
class ProfileUpdateRequest extends FormRequest
{
    use UserProfileValidationRules;

    protected function prepareForValidation(): void
    {
        $this->merge([
            'phone' => PhoneNumber::normalize($this->string('phone')->toString() ?: null),
            'orcid' => $this->filled('orcid') ? strtoupper(trim($this->string('orcid')->toString())) : null,
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var User $user */
        $user = $this->user();

        return [
            ...$this->personalRules($user->id),
            ...$this->academicRules($user->authorProfile),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return $this->profileAttributeNames();
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->profileMessages();
    }
}
