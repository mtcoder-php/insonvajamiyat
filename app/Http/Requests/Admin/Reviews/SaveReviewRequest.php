<?php

namespace App\Http\Requests\Admin\Reviews;

use App\Enums\ReviewCriterion;
use App\Enums\ReviewRecommendation;
use App\Models\Review;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Taqriz formasi: umumiy baho, mezonlar (1–5, 0.5 qadam), tavsiya, izohlar, fayl.
 * submit=1 — topshirish (barcha maydonlar majburiy), aks holda qoralama.
 */
class SaveReviewRequest extends FormRequest
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
        $submit = $this->boolean('submit');
        $score = ['numeric', 'min:1', 'max:5', 'multiple_of:0.5'];
        $rules = [
            'submit' => ['boolean'],
            'score' => ['nullable', ...$score],
            'criteria' => [$submit ? 'required' : 'nullable', 'array'],
            'recommendation' => [$submit ? 'required' : 'nullable', Rule::enum(ReviewRecommendation::class)],
            'comments_to_author' => $submit
                ? ['required', 'string', 'min:50', 'max:5000']
                : ['nullable', 'string', 'max:5000'],
            'comments_to_editor' => ['nullable', 'string', 'max:2000'],
            'attachment' => ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:10240'],
        ];

        foreach (ReviewCriterion::cases() as $criterion) {
            $rules["criteria.{$criterion->value}"] = [$submit ? 'required' : 'nullable', ...$score];
        }

        return $rules;
    }

    /**
     * @return array{score: float|null, criteria: array<string, float>, recommendation: ReviewRecommendation|null, comments_to_author: string|null, comments_to_editor: string|null}
     */
    public function review(): array
    {
        $criteria = [];

        foreach (ReviewCriterion::cases() as $criterion) {
            $value = $this->input("criteria.{$criterion->value}");

            if (is_numeric($value)) {
                $criteria[$criterion->value] = (float) $value;
            }
        }

        $score = $this->input('score');

        return [
            'score' => is_numeric($score) ? (float) $score : null,
            'criteria' => $criteria,
            'recommendation' => ReviewRecommendation::tryFrom($this->string('recommendation')->toString()),
            'comments_to_author' => $this->filled('comments_to_author') ? $this->string('comments_to_author')->trim()->toString() : null,
            'comments_to_editor' => $this->filled('comments_to_editor') ? $this->string('comments_to_editor')->trim()->toString() : null,
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        $attributes = [
            'score' => __('Umumiy baho'),
            'criteria' => __('Baholash mezonlari'),
            'recommendation' => __('Qaror'),
            'comments_to_author' => __('Taqriz matni'),
            'comments_to_editor' => __('Muharrir uchun izoh'),
            'attachment' => __('Taqriz fayli'),
        ];

        foreach (ReviewCriterion::cases() as $criterion) {
            $attributes["criteria.{$criterion->value}"] = $criterion->label();
        }

        return $attributes;
    }
}
