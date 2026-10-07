<?php

namespace App\Http\Requests\Admin\Settings;

use App\Support\Translations;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SubjectRequest extends FormRequest
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
            'code' => ['nullable', 'string', 'max:30'],
            'parent_id' => ['nullable', 'integer', Rule::exists('subjects', 'id')->whereNull('parent_id')],
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
            'code' => 'shifr',
            'parent_id' => "yuqori yo'nalish",
            'sort_order' => 'tartib',
        ];
    }
}
