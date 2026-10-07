<?php

namespace App\Http\Requests\Admin\Settings;

use App\Support\Translations;
use Illuminate\Foundation\Http\FormRequest;

class BannerRequest extends FormRequest
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
        $creating = $this->route('banner') === null;

        return [
            ...Translations::rules('title', true, 200),
            ...Translations::rules('subtitle', false, 400),
            ...Translations::rules('button_text', false, 40),
            'link_url' => ['nullable', 'string', 'max:500', 'regex:/^(https?:\/\/|\/)/'],
            'image' => [$creating ? 'required' : 'nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120', 'dimensions:min_width=1200,min_height=400'],
            'is_active' => ['required', 'boolean'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:9999'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            ...Translations::attributes('title', 'Sarlavha'),
            ...Translations::attributes('subtitle', 'Izoh'),
            ...Translations::attributes('button_text', 'Tugma matni'),
            'link_url' => 'havola',
            'image' => 'rasm',
            'sort_order' => 'tartib',
            'starts_at' => 'boshlanish sanasi',
            'ends_at' => 'tugash sanasi',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'link_url.regex' => 'Havola http(s):// yoki / bilan boshlanishi kerak.',
        ];
    }
}
