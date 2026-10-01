<?php

namespace App\Http\Requests\Admin\Issues;

use App\Enums\PermissionName;
use App\Models\JournalIssue;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Jurnal soni: yil, jild, raqam (yil ichida takrorlanmaydi), DOI, nom va tavsif.
 */
class IssueRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can(PermissionName::IssuesManage->value);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $issue = $this->route('issue');
        $ignore = $issue instanceof JournalIssue ? $issue->id : null;

        return [
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'volume' => ['nullable', 'integer', 'min:1', 'max:999'],
            'number' => [
                'required', 'integer', 'min:1', 'max:999',
                Rule::unique('journal_issues', 'number')
                    ->where('year', $this->integer('year'))
                    ->whereNull('deleted_at')
                    ->ignore($ignore),
            ],
            'doi' => [
                'nullable', 'string', 'max:100', 'regex:/^10\.\d{4,9}\/\S+$/',
                Rule::unique('journal_issues', 'doi')->ignore($ignore),
            ],
            'title' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array{year: int, volume: int|null, number: int, doi: string|null, title: string|null, description: string|null}
     */
    public function issue(): array
    {
        return [
            'year' => $this->integer('year'),
            'volume' => $this->filled('volume') ? $this->integer('volume') : null,
            'number' => $this->integer('number'),
            'doi' => $this->filled('doi') ? $this->string('doi')->trim()->toString() : null,
            'title' => $this->filled('title') ? $this->string('title')->trim()->toString() : null,
            'description' => $this->filled('description') ? $this->string('description')->trim()->toString() : null,
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'year' => __('Yil'),
            'volume' => __('Jild'),
            'number' => __('Son raqami'),
            'doi' => 'DOI',
            'title' => __('Nomi'),
            'description' => __('Tavsif'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'number.unique' => __('Bu yilda shu raqamli son allaqachon mavjud.'),
            'doi.regex' => __('DOI formati: 10.xxxx/nom'),
        ];
    }
}
