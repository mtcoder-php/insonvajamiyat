<?php

namespace App\Http\Requests\Admin\Payments;

use App\Enums\PaymentProvider;
use App\Enums\PermissionName;
use App\Models\Payment;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * To'lovni qaytarish: sabab majburiy (muallif bilan kelishuv); qo'lda tasdiqlangan
 * to'lovda — pul qaytarilgan bank hujjati raqami ham.
 */
class RefundPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(PermissionName::PaymentsRefund->value) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $payment = $this->route('payment');
        $manual = $payment instanceof Payment && $payment->provider === PaymentProvider::Manual;

        return [
            'reason' => ['required', 'string', 'min:10', 'max:1000'],
            'reference' => [$manual ? 'required' : 'nullable', 'string', 'max:100'],
            'confirm' => ['accepted'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'reason' => __('Sabab'),
            'reference' => __('Bank hujjati raqami'),
            'confirm' => __('Tasdiq'),
        ];
    }
}
