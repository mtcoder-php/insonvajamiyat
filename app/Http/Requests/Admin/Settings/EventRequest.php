<?php

namespace App\Http\Requests\Admin\Settings;

use App\Support\Translations;
use Illuminate\Foundation\Http\FormRequest;

class EventRequest extends FormRequest
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
            ...Translations::rules('title', true, 255),
            ...Translations::rules('description', false, 5000),
            ...Translations::rules('location', false, 255),
            'starts_at' => ['required', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'registration_url' => ['nullable', 'string', 'max:500', 'regex:/^https?:\/\//'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120', 'dimensions:min_width=600,min_height=300'],
            'remove_image' => ['sometimes', 'boolean'],
            'is_published' => ['required', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            ...Translations::attributes('title', 'Nomi'),
            ...Translations::attributes('description', 'Tavsif'),
            ...Translations::attributes('location', "O'tkaziladigan joy"),
            'starts_at' => 'boshlanish vaqti',
            'ends_at' => 'tugash vaqti',
            'registration_url' => "ro'yxatdan o'tish havolasi",
            'image' => 'rasm',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'registration_url.regex' => 'Havola http:// yoki https:// bilan boshlanishi kerak.',
        ];
    }
}
