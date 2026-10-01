<?php

namespace App\Http\Requests\Admin\Payments;

use App\Enums\PaymentProvider;
use App\Enums\PermissionName;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Nashr to'lovini qo'lda tasdiqlash: summa, to'lov sanasi, to'lov hujjati raqami,
 * izoh va kvitansiya (ixtiyoriy).
 */
class ConfirmPaymentRequest extends FormRequest
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
        return [
            'amount' => ['required', 'numeric', 'min:1', 'max:100000000'],
            'paid_at' => ['required', 'date', 'before_or_equal:now'],
            'reference' => [
                'nullable', 'string', 'max:191',
                Rule::unique('payments', 'provider_transaction_id')->where('provider', PaymentProvider::Manual->value),
            ],
            'note' => ['nullable', 'string', 'max:1000'],
            'proof' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ];
    }

    /**
     * @return array{amount: float, paid_at: CarbonInterface, reference: string|null, note: string|null}
     */
    public function payment(): array
    {
        return [
            'amount' => (float) $this->string('amount')->toString(),
            'paid_at' => CarbonImmutable::parse($this->string('paid_at')->toString()),
            'reference' => $this->filled('reference') ? $this->string('reference')->trim()->toString() : null,
            'note' => $this->filled('note') ? $this->string('note')->trim()->toString() : null,
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'amount' => __('Summa'),
            'paid_at' => __("To'lov sanasi"),
            'reference' => __("To'lov hujjati raqami"),
            'note' => __('Izoh'),
            'proof' => __('Kvitansiya'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'reference.unique' => __("Bu to'lov hujjati raqami allaqachon ro'yxatdan o'tgan."),
            'paid_at.before_or_equal' => __("To'lov sanasi kelajakda bo'lishi mumkin emas."),
        ];
    }
}
