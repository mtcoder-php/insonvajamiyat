<?php

namespace App\Concerns;

use App\Enums\Language;
use App\Models\AuthorProfile;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

/**
 * Foydalanuvchi profili (shaxsiy va ilmiy ma'lumotlar) uchun umumiy qoidalar:
 * admin paneldagi foydalanuvchi formasi va shaxsiy profil sahifasi ishlatadi.
 */
trait UserProfileValidationRules
{
    use ProfileValidationRules;

    /** ORCID: 0000-0002-1825-0097 (oxirgi belgi X bo'lishi mumkin) */
    public const ORCID_PATTERN = '/^\d{4}-\d{4}-\d{4}-\d{3}[\dX]$/';

    /**
     * Familiya, ism, otasining ismi, email, telefon, til.
     *
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    protected function personalRules(?int $userId = null, bool $phoneRequired = false): array
    {
        return [
            'last_name' => $this->personNameRules(),
            'first_name' => $this->personNameRules(),
            'middle_name' => ['nullable', 'string', 'max:100', "regex:/^[\\p{L}\\p{M}' ʻʼ‘’`\\-]+$/u"],
            'email' => $this->emailRules($userId),
            'phone' => $phoneRequired
                ? $this->phoneRules()
                : ['nullable', ...array_values(array_filter($this->phoneRules(), fn ($rule) => $rule !== 'required'))],
            'locale' => ['required', Rule::enum(Language::class)],
        ];
    }

    /**
     * Ilmiy ma'lumotlar (ixtiyoriy).
     *
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    protected function academicRules(?AuthorProfile $profile = null): array
    {
        return [
            'position' => ['nullable', 'string', 'max:255'],
            'organization' => ['nullable', 'string', 'max:255'],
            'department' => ['nullable', 'string', 'max:255'],
            'academic_degree' => ['nullable', 'string', 'max:100'],
            'academic_title' => ['nullable', 'string', 'max:100'],
            'orcid' => [
                'nullable',
                'string',
                'regex:'.self::ORCID_PATTERN,
                $profile === null
                    ? Rule::unique(AuthorProfile::class, 'orcid')
                    : Rule::unique(AuthorProfile::class, 'orcid')->ignore($profile->id),
            ],
            'city' => ['nullable', 'string', 'max:100'],
            'bio' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * Avatar: JPG/PNG/WEBP, 2 MB gacha, kamida 96×96.
     *
     * @return array<int, string>
     */
    protected function avatarRules(): array
    {
        return ['image', 'mimes:jpg,jpeg,png,webp', 'max:2048', 'dimensions:min_width=96,min_height=96'];
    }

    /**
     * Xato xabarlaridagi maydon nomlari.
     *
     * @return array<string, string>
     */
    protected function profileAttributeNames(): array
    {
        return [
            'last_name' => __('Familiya'),
            'first_name' => __('Ism'),
            'middle_name' => __('Otasining ismi'),
            'email' => __('Elektron pochta'),
            'phone' => __('Telefon raqam'),
            'locale' => __('Til'),
            'position' => __('Lavozim'),
            'organization' => __('Tashkilot'),
            'department' => __("Kafedra / bo'lim"),
            'academic_degree' => __('Ilmiy daraja'),
            'academic_title' => __('Ilmiy unvon'),
            'orcid' => 'ORCID',
            'city' => __('Shahar'),
            'bio' => __("Qisqacha ma'lumot"),
            'avatar' => __('Rasm'),
            'password' => __('Parol'),
            'roles' => __('Rollar'),
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function profileMessages(): array
    {
        return [
            'phone.regex' => __("Telefon raqamini to'g'ri kiriting, masalan: +998 90 123 45 67"),
            'orcid.regex' => __('ORCID formati: 0000-0000-0000-0000'),
            'avatar.dimensions' => __("Rasm kamida 96×96 piksel bo'lishi kerak."),
        ];
    }
}
