<?php

namespace App\Http\Requests\Cabinet\Articles;

use Illuminate\Contracts\Validation\ValidationRule;

/**
 * 7-bosqich: muallif roziliklari va yuborish.
 */
class SubmitArticleRequest extends DraftArticleRequest
{
    /** Majburiy roziliklar (frontend bilan bir xil kalitlar) */
    public const CONSENTS = ['originality', 'exclusivity', 'rules'];

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [];

        foreach (self::CONSENTS as $consent) {
            $rules["consents.{$consent}"] = ['accepted'];
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'consents.*.accepted' => __('Yuborish uchun barcha shartlarga rozilik bildiring.'),
        ];
    }
}
