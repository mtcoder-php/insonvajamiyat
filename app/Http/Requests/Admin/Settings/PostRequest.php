<?php

namespace App\Http\Requests\Admin\Settings;

use App\Enums\PostType;
use App\Support\Translations;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PostRequest extends FormRequest
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
            'type' => ['required', Rule::enum(PostType::class)],
            ...Translations::rules('title', true, 255),
            ...Translations::rules('excerpt', false, 500),
            // HTML (matn muharriri): teglar bilan birga
            ...Translations::rules('body', false, 60000),
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120', 'dimensions:min_width=600,min_height=300'],
            'remove_image' => ['sometimes', 'boolean'],
            'is_published' => ['required', 'boolean'],
            'is_pinned' => ['required', 'boolean'],
            'published_at' => ['nullable', 'date'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'type' => 'turi',
            ...Translations::attributes('title', 'Sarlavha'),
            ...Translations::attributes('excerpt', 'Qisqa mazmun'),
            ...Translations::attributes('body', 'Matn'),
            'image' => 'rasm',
            'published_at' => 'nashr sanasi',
        ];
    }
}
