<?php

namespace App\Http\Requests\Admin\People;

class AddReviewerRequest extends ReviewerSubjectsRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'subject_ids' => ['nullable', 'array', 'max:20'],
            'subject_ids.*' => ['integer', 'distinct', 'exists:subjects,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['user_id' => 'foydalanuvchi', 'subject_ids' => "yo'nalishlar"];
    }
}
