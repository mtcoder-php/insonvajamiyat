<?php

namespace App\Http\Requests\Admin\Payments;

use App\Enums\PermissionName;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Maqolani nashr to'lovidan ozod qilish (sabab majburiy — muallifga ko'rinadi).
 */
class WaivePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(PermissionName::PaymentsConfirmManually->value) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return ['reason' => ['required', 'string', 'min:5', 'max:500']];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['reason' => __('Sabab')];
    }
}
