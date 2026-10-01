<?php

namespace App\Http\Requests\Admin\Users;

use App\Concerns\UserProfileValidationRules;
use App\Enums\RoleName;
use App\Models\User;
use App\Support\PhoneNumber;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * Admin panel — foydalanuvchi ma'lumotlarini tahrirlash.
 */
class UpdateUserRequest extends FormRequest
{
    use UserProfileValidationRules;

    public function authorize(): bool
    {
        return Gate::allows('update', $this->targetUser());
    }

    public function targetUser(): User
    {
        $user = $this->route('user');

        abort_unless($user instanceof User, 404);

        return $user;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'phone' => PhoneNumber::normalize($this->string('phone')->toString() ?: null),
            'orcid' => $this->filled('orcid') ? strtoupper(trim($this->string('orcid')->toString())) : null,
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            ...$this->personalRules($this->targetUser()->id),
            ...$this->academicRules($this->targetUser()->authorProfile),
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['required', 'string', Rule::enum(RoleName::class)],
            'email_verified' => ['boolean'],
            'avatar' => ['nullable', ...$this->avatarRules()],
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
