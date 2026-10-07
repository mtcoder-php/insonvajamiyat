<?php

namespace App\Http\Requests\Admin\Settings;

use App\Support\Translations;
use Illuminate\Foundation\Http\FormRequest;

class RecommendedBookRequest extends FormRequest
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
            'author' => ['required', 'string', 'max:255'],
            'year' => ['nullable', 'integer', 'min:1800', 'max:'.(now()->year + 1)],
            'url' => ['nullable', 'string', 'max:500', 'regex:/^(https?:\/\/|\/)/'],
            'cover' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120', 'dimensions:min_width=150,min_height=200'],
            'remove_cover' => ['sometimes', 'boolean'],
            'is_active' => ['required', 'boolean'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:9999'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            ...Translations::attributes('title', 'Kitob nomi'),
            'author' => 'muallif',
            'year' => 'nashr yili',
            'url' => 'havola',
            'cover' => 'muqova',
            'sort_order' => 'tartib',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'url.regex' => 'Havola http(s):// yoki / bilan boshlanishi kerak.',
        ];
    }
}
