<?php

namespace App\Http\Requests\Admin\Settings;

use App\Support\Translations;
use Illuminate\Foundation\Http\FormRequest;

class ArticleTypeRequest extends FormRequest
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
            ...Translations::rules('name', true, 150),
            ...Translations::rules('description', false, 1000),
            'price' => ['required', 'numeric', 'min:0', 'max:100000000'],
            'review_days' => ['nullable', 'integer', 'min:1', 'max:365'],
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
            ...Translations::attributes('name', 'Nomi'),
            ...Translations::attributes('description', 'Tavsif'),
            'price' => 'narx',
            'review_days' => "ko'rib chiqish muddati",
            'sort_order' => 'tartib',
        ];
    }
}
