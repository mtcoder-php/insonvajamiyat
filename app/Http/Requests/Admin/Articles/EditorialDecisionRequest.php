<?php

namespace App\Http\Requests\Admin\Articles;

use App\Enums\EditorialDecisionType;
use App\Models\Article;
use App\Services\Editorial\EditorialService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * Muharrir qarori: tuzatish talab qilish / qabul qilish / rad etish.
 */
class EditorialDecisionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $article = $this->route('article');

        return $article instanceof Article && Gate::allows('decide', $article);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $required = array_map(fn (EditorialDecisionType $t): string => $t->value, EditorialService::COMMENT_REQUIRED);

        return [
            'decision' => ['required', Rule::in(array_map(fn (EditorialDecisionType $t): string => $t->value, EditorialService::DECISIONS))],
            'comment_to_author' => [
                Rule::requiredIf(in_array($this->input('decision'), $required, true)),
                'nullable', 'string', 'max:5000',
            ],
            'internal_note' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function decision(): EditorialDecisionType
    {
        return EditorialDecisionType::from($this->string('decision')->toString());
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'decision' => __('Qaror'),
            'comment_to_author' => __('Muallifga izoh'),
            'internal_note' => __('Ichki izoh'),
        ];
    }
}
