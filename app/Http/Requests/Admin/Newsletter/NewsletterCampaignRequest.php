<?php

namespace App\Http\Requests\Admin\Newsletter;

use App\Enums\IssueStatus;
use App\Enums\Language;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Obunachilarga xat: kimga (barcha / til), mavzu, matn, ixtiyoriy tugma (matn + https havola),
 * ixtiyoriy bog'langan son.
 */
class NewsletterCampaignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'audience' => ['required', 'string', Rule::in(['all', ...array_column(Language::cases(), 'value')])],
            'subject' => ['required', 'string', 'min:3', 'max:200'],
            'body' => ['required', 'string', 'min:10', 'max:10000'],
            'button_label' => ['nullable', 'string', 'max:60'],
            'button_url' => ['nullable', 'string', 'max:500', 'regex:/^https?:\/\//'],
            'journal_issue_id' => ['nullable', 'integer', Rule::exists('journal_issues', 'id')->where('status', IssueStatus::Published->value)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'audience' => __('kimga'),
            'subject' => __('mavzu'),
            'body' => __('matn'),
            'button_label' => __('tugma matni'),
            'button_url' => __('tugma havolasi'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'button_url.regex' => __('Havola http:// yoki https:// bilan boshlanishi kerak.'),
        ];
    }
}
