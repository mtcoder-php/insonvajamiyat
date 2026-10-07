<?php

namespace App\Http\Requests\Admin\System;

use Illuminate\Foundation\Http\FormRequest;

class JournalSettingsRequest extends FormRequest
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
        $url = ['nullable', 'string', 'max:255', 'regex:/^https?:\/\//'];

        return [
            'name' => ['required', 'string', 'max:120'],
            'subtitle' => ['nullable', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:500'],
            'issn' => ['nullable', 'string', 'regex:/^\d{4}-\d{3}[\dXx]$/'],
            'eissn' => ['nullable', 'string', 'regex:/^\d{4}-\d{3}[\dXx]$/'],
            'doi_prefix' => ['nullable', 'string', 'max:60', 'regex:/^10\.\d{4,9}(\/[\w.\-]+)?$/'],
            'frequency' => ['nullable', 'string', 'max:120'],
            'plagiarism_max' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'contact_email' => ['nullable', 'email', 'max:120'],
            'contact_phone' => ['nullable', 'string', 'max:40'],
            'contact_address' => ['nullable', 'string', 'max:255'],
            'social_telegram' => $url,
            'social_facebook' => $url,
            'social_instagram' => $url,
            'social_youtube' => $url,
            'social_linkedin' => $url,
            'payment_recipient' => ['nullable', 'string', 'max:255'],
            'payment_bank' => ['nullable', 'string', 'max:255'],
            'payment_account' => ['nullable', 'string', 'regex:/^[\d ]{16,34}$/'],
            'payment_mfo' => ['nullable', 'string', 'regex:/^\d{5}$/'],
            'payment_inn' => ['nullable', 'string', 'regex:/^\d{9}$/'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'jurnal nomi',
            'subtitle' => 'qo\'shimcha nom',
            'description' => 'tavsif',
            'issn' => 'ISSN',
            'eissn' => 'e-ISSN',
            'doi_prefix' => 'DOI prefiksi',
            'frequency' => 'davriylik',
            'plagiarism_max' => 'plagiat chegarasi',
            'contact_email' => 'email',
            'contact_phone' => 'telefon',
            'contact_address' => 'manzil',
            'payment_recipient' => 'qabul qiluvchi',
            'payment_bank' => 'bank',
            'payment_account' => 'hisob raqami',
            'payment_mfo' => 'MFO',
            'payment_inn' => 'STIR (INN)',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'issn.regex' => 'ISSN 1234-5678 ko\'rinishida bo\'lishi kerak.',
            'eissn.regex' => 'e-ISSN 1234-5678 ko\'rinishida bo\'lishi kerak.',
            'doi_prefix.regex' => 'DOI prefiksi 10.xxxx yoki 10.xxxx/nom ko\'rinishida bo\'lishi kerak.',
            'social_*.regex' => 'Havola http:// yoki https:// bilan boshlanishi kerak.',
            'payment_account.regex' => 'Hisob raqami 16–34 ta raqamdan iborat bo\'lishi kerak (odatda 20 ta).',
            'payment_mfo.regex' => 'MFO 5 ta raqam bo\'lishi kerak.',
            'payment_inn.regex' => 'STIR 9 ta raqam bo\'lishi kerak.',
        ];
    }
}
