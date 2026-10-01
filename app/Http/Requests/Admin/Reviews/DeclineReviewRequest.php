<?php

namespace App\Http\Requests\Admin\Reviews;

use App\Models\Review;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Taqriz taklifini rad etish (sabab ixtiyoriy — faqat muharrirga ko'rinadi).
 */
class DeclineReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        $review = $this->route('review');

        return $review instanceof Review && $review->reviewer_id === $this->user()?->id;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return ['reason' => ['nullable', 'string', 'max:1000']];
    }
}
