<?php

namespace App\Http\Requests;

use App\Concerns\UserProfileValidationRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Profil rasmini yuklash (o'z profili yoki admin tomonidan).
 * Ruxsat route middleware / controller'da tekshiriladi.
 */
class AvatarRequest extends FormRequest
{
    use UserProfileValidationRules;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'avatar' => ['required', ...$this->avatarRules()],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['avatar' => __('Rasm')];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->profileMessages();
    }
}
