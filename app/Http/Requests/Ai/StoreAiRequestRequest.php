<?php

namespace App\Http\Requests\Ai;

use App\Enums\AiRequestType;
use App\Services\Ai\PromptLibrary;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Yangi AI so'rovi: Proofreader / Translator / Analytics.
 */
class StoreAiRequestRequest extends FormRequest
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
            'type' => ['required', Rule::enum(AiRequestType::class)],
            'text' => ['required', 'string', 'max:200000'],
            'source_language' => ['required', Rule::in(PromptLibrary::LANGUAGES)],
            'target_language' => [
                Rule::requiredIf($this->input('type') === AiRequestType::Translation->value),
                'nullable',
                Rule::in(PromptLibrary::LANGUAGES),
                'different:source_language',
            ],
            'checks' => ['nullable', 'array'],
            'checks.*' => [Rule::in(array_keys(PromptLibrary::CHECKS))],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'text' => 'matn',
            'source_language' => 'manba til',
            'target_language' => 'tarjima tili',
        ];
    }

    public function type(): AiRequestType
    {
        return AiRequestType::from($this->string('type')->toString());
    }

    /**
     * @return array<int, string>
     */
    public function checks(): array
    {
        $checks = $this->input('checks', []);

        return is_array($checks) ? array_values(array_filter($checks, 'is_string')) : [];
    }
}
