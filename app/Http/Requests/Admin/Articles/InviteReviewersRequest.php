<?php

namespace App\Http\Requests\Admin\Articles;

use App\Enums\PermissionName;
use App\Services\Reviews\ReviewService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Maqolaga taqrizchilarni taklif qilish (muddat bilan).
 */
class InviteReviewersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(PermissionName::ArticlesAssignReviewer->value) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'reviewer_ids' => ['required', 'array', 'min:1', 'max:'.ReviewService::MAX_REVIEWERS],
            'reviewer_ids.*' => ['integer', 'distinct', 'exists:users,id'],
            'due_days' => ['required', 'integer', 'min:3', 'max:60'],
        ];
    }

    /**
     * @return array<int, int>
     */
    public function reviewerIds(): array
    {
        $ids = $this->input('reviewer_ids', []);

        return is_array($ids) ? array_values(array_map('intval', array_filter($ids, 'is_numeric'))) : [];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'reviewer_ids' => __('Taqrizchilar'),
            'due_days' => __('Taqriz muddati'),
        ];
    }
}
