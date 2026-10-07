<?php

namespace App\Http\Requests\Admin\People;

use Illuminate\Foundation\Http\FormRequest;

class ReviewerSubjectsRequest extends FormRequest
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
            'subject_ids' => ['present', 'array', 'max:20'],
            'subject_ids.*' => ['integer', 'distinct', 'exists:subjects,id'],
        ];
    }

    /**
     * @return array<int, int>
     */
    public function subjectIds(): array
    {
        $ids = $this->input('subject_ids', []);

        return is_array($ids) ? array_values(array_unique(array_map('intval', array_filter($ids, 'is_numeric')))) : [];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['subject_ids' => "yo'nalishlar"];
    }
}
