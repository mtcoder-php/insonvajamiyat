<?php

namespace App\Http\Requests\Admin\Messages;

use App\Enums\BroadcastAudience;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BroadcastRequest extends FormRequest
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
            'subject' => ['required', 'string', 'min:3', 'max:200'],
            'body' => ['required', 'string', 'min:10', 'max:5000'],
            'audience' => ['required', Rule::enum(BroadcastAudience::class)],
            'send_email' => ['required', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'subject' => 'mavzu',
            'body' => 'matn',
            'audience' => 'qabul qiluvchilar',
        ];
    }
}
