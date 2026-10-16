<?php

namespace App\Http\Requests\Admin\Library;

use App\Support\Translations;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Kutubxona → kitob qo'shish / tahrirlash (muqova, PDF, ma'lumotlar).
 */
class BookRequest extends FormRequest
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
            'subject_id' => ['nullable', 'integer', Rule::exists('subjects', 'id')],
            'author' => ['required', 'string', 'max:255'],
            'publisher' => ['nullable', 'string', 'max:255'],
            'year' => ['nullable', 'integer', 'min:1800', 'max:'.(now()->year + 1)],
            'pages' => ['nullable', 'integer', 'min:1', 'max:20000'],
            'isbn' => ['nullable', 'string', 'max:20', 'regex:/^[0-9Xx\- ]{10,20}$/'],
            'language' => ['required', Rule::in(['uz', 'ru', 'en'])],
            'url' => ['nullable', 'string', 'max:500', 'regex:/^(https?:\/\/|\/)/'],
            'cover' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120', 'dimensions:min_width=150,min_height=200'],
            'remove_cover' => ['sometimes', 'boolean'],
            'file' => ['nullable', 'file', 'mimes:pdf', 'max:102400'],
            'remove_file' => ['sometimes', 'boolean'],
            'is_featured' => ['required', 'boolean'],
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
            ...Translations::attributes('description', 'Annotatsiya'),
            'subject_id' => "yo'nalish",
            'author' => 'muallif',
            'publisher' => 'nashriyot',
            'year' => 'nashr yili',
            'pages' => 'betlar soni',
            'isbn' => 'ISBN',
            'language' => 'til',
            'url' => 'havola',
            'cover' => 'muqova',
            'file' => 'PDF fayl',
            'sort_order' => 'tartib',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'url.regex' => __('Havola http(s):// yoki / bilan boshlanishi kerak.'),
            'isbn.regex' => __("ISBN 10 yoki 13 ta raqamdan iborat bo'lishi kerak."),
        ];
    }
}
