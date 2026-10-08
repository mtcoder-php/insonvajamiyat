<?php

namespace App\Services\Payments\Click;

use App\Enums\PaymentProvider;
use App\Enums\PaymentPurpose;
use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Services\Payments\OnlinePaymentService;
use App\Services\Payments\PaymentNotPayable;
use App\Services\Payments\PaymentSettlement;
use App\Services\Payments\WebhookResult;

/**
 * Click SHOP API (Prepare / Complete).
 *
 *  Prepare  (action=0): sign = md5(click_trans_id + service_id + SECRET_KEY + merchant_trans_id + amount + action + sign_time)
 *  Complete (action=1): sign = md5(click_trans_id + service_id + SECRET_KEY + merchant_trans_id + merchant_prepare_id + amount + action + sign_time)
 *
 * merchant_trans_id = payments.id; merchant_prepare_id / merchant_confirm_id = payments.id.
 */
class ClickMerchantService
{
    public const ACTION_PREPARE = 0;

    public const ACTION_COMPLETE = 1;

    public const SUCCESS = 0;

    public const SIGN_FAILED = -1;

    public const INCORRECT_AMOUNT = -2;

    public const ACTION_NOT_FOUND = -3;

    public const ALREADY_PAID = -4;

    public const ORDER_NOT_FOUND = -5;

    public const TRANSACTION_NOT_FOUND = -6;

    public const UPDATE_FAILED = -7;

    public const BAD_REQUEST = -8;

    public const CANCELLED = -9;

    private const MESSAGES = [
        self::SUCCESS => 'Success',
        self::SIGN_FAILED => 'SIGN CHECK FAILED!',
        self::INCORRECT_AMOUNT => 'Incorrect parameter amount',
        self::ACTION_NOT_FOUND => 'Action not found',
        self::ALREADY_PAID => 'Already paid',
        self::ORDER_NOT_FOUND => 'User does not exist',
        self::TRANSACTION_NOT_FOUND => 'Transaction does not exist',
        self::UPDATE_FAILED => 'Failed to update user',
        self::BAD_REQUEST => 'Error in request from click',
        self::CANCELLED => 'Transaction cancelled',
    ];

    private const REQUIRED = ['click_trans_id', 'service_id', 'merchant_trans_id', 'amount', 'action', 'sign_time', 'sign_string'];

    public function __construct(private readonly PaymentSettlement $settlement) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function prepare(array $data): WebhookResult
    {
        return $this->handle($data, self::ACTION_PREPARE);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function complete(array $data): WebhookResult
    {
        return $this->handle($data, self::ACTION_COMPLETE);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function handle(array $data, int $action): WebhookResult
    {
        $name = $action === self::ACTION_PREPARE ? 'prepare' : 'complete';
        $required = $action === self::ACTION_COMPLETE ? [...self::REQUIRED, 'merchant_prepare_id'] : self::REQUIRED;

        foreach ($required as $key) {
            if (! isset($data[$key]) || ! is_scalar($data[$key]) || (string) $data[$key] === '') {
                return $this->reply($data, $name, self::BAD_REQUEST);
            }
        }

        if ((int) $data['action'] !== $action) {
            return $this->reply($data, $name, self::ACTION_NOT_FOUND);
        }

        if (! $this->validSignature($data, $action)) {
            return $this->reply($data, $name, self::SIGN_FAILED);
        }

        $payment = $this->findPayment(self::s($data['merchant_trans_id']));

        if ($payment === null) {
            return $this->reply($data, $name, self::ORDER_NOT_FOUND);
        }

        if (abs((float) self::s($data['amount']) - (float) $payment->amount) > 0.009) {
            return $this->reply($data, $name, self::INCORRECT_AMOUNT, $payment);
        }

        return $action === self::ACTION_PREPARE
            ? $this->handlePrepare($data, $payment)
            : $this->handleComplete($data, $payment);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function handlePrepare(array $data, Payment $payment): WebhookResult
    {
        if ($payment->status === PaymentStatus::Paid) {
            return $this->reply($data, 'prepare', self::ALREADY_PAID, $payment);
        }

        if ($payment->status->is(PaymentStatus::Cancelled, PaymentStatus::Failed, PaymentStatus::Refunded)) {
            return $this->reply($data, 'prepare', self::CANCELLED, $payment);
        }

        $article = $payment->article;

        if (! PaymentSettlement::isPayable($article)) {
            $paid = $article !== null && $article->payment_status->isSettled();

            return $this->reply($data, 'prepare', $paid ? self::ALREADY_PAID : self::CANCELLED, $payment);
        }

        $payment->forceFill([
            'status' => PaymentStatus::Processing,
            'provider_transaction_id' => self::s($data['click_trans_id']),
            'provider_paydoc_id' => isset($data['click_paydoc_id']) ? self::s($data['click_paydoc_id']) : null,
            'meta' => [...($payment->meta ?? []), 'click_prepared_at' => now()->toIso8601String()],
        ])->save();

        $this->settlement->markArticlePending($payment);

        return $this->reply($data, 'prepare', self::SUCCESS, $payment, ['merchant_prepare_id' => $payment->id]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function handleComplete(array $data, Payment $payment): WebhookResult
    {
        if ((string) $payment->id !== self::s($data['merchant_prepare_id'])
            || $payment->provider_transaction_id !== self::s($data['click_trans_id'])) {
            return $this->reply($data, 'complete', self::TRANSACTION_NOT_FOUND, $payment);
        }

        if ($payment->status === PaymentStatus::Paid) {
            return $this->reply($data, 'complete', self::ALREADY_PAID, $payment, ['merchant_confirm_id' => $payment->id]);
        }

        if ($payment->status !== PaymentStatus::Processing) {
            return $this->reply($data, 'complete', self::CANCELLED, $payment);
        }

        // Click tomonida xato (masalan, kartada mablag' yetarli emas) — tranzaksiya bekor qilinadi
        $clickError = isset($data['error']) && is_numeric($data['error']) ? (int) $data['error'] : 0;

        if ($clickError < 0) {
            $this->settlement->cancel($payment, [
                'meta' => [...($payment->meta ?? []), 'click_error' => $clickError, 'click_error_note' => $data['error_note'] ?? null],
            ]);

            return $this->reply($data, 'complete', self::CANCELLED, $payment);
        }

        try {
            $this->settlement->markPaid($payment);
        } catch (PaymentNotPayable $e) {
            $this->settlement->cancel($payment);

            return $this->reply($data, 'complete', $e->alreadyPaid ? self::ALREADY_PAID : self::CANCELLED, $payment);
        }

        return $this->reply($data, 'complete', self::SUCCESS, $payment, ['merchant_confirm_id' => $payment->id]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function validSignature(array $data, int $action): bool
    {
        // O'chirilgan yoki to'liq sozlanmagan Click — hech qanday so'rov qabul qilinmaydi
        // (bo'sh maxfiy kalit bilan imzoni istalgan odam hisoblay olardi)
        if (! config('payments.click.enabled')
            || self::s(config('payments.click.secret_key')) === ''
            || self::s(config('payments.click.service_id')) === '') {
            return false;
        }

        if (self::s($data['service_id']) !== self::s(config('payments.click.service_id'))) {
            return false;
        }

        $parts = [
            self::s($data['click_trans_id']),
            self::s($data['service_id']),
            self::s(config('payments.click.secret_key')),
            self::s($data['merchant_trans_id']),
        ];

        if ($action === self::ACTION_COMPLETE) {
            $parts[] = self::s($data['merchant_prepare_id']);
        }

        array_push($parts, self::s($data['amount']), self::s($data['action']), self::s($data['sign_time']));

        return hash_equals(md5(implode('', $parts)), strtolower(self::s($data['sign_string'])));
    }

    private function findPayment(string $id): ?Payment
    {
        if (! ctype_digit($id)) {
            return null;
        }

        return Payment::query()
            ->whereKey((int) $id)
            ->where('provider', PaymentProvider::Click->value)
            ->where('purpose', PaymentPurpose::Publication->value)
            ->first();
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $extra
     */
    private function reply(array $data, string $action, int $error, ?Payment $payment = null, array $extra = []): WebhookResult
    {
        $body = [
            'click_trans_id' => isset($data['click_trans_id']) ? (int) self::s($data['click_trans_id']) : null,
            'merchant_trans_id' => isset($data['merchant_trans_id']) ? self::s($data['merchant_trans_id']) : null,
            ...$extra,
            'error' => $error,
            'error_note' => self::MESSAGES[$error],
        ];

        return new WebhookResult(
            body: $body,
            action: $action,
            paymentId: $payment?->id,
            errorCode: $error,
            signatureValid: match ($error) {
                self::SIGN_FAILED => false,
                self::BAD_REQUEST, self::ACTION_NOT_FOUND => null,
                default => true,
            },
        );
    }

    private static function s(mixed $value): string
    {
        return is_scalar($value) ? (string) $value : '';
    }

    /** Test va hujjatlar uchun: imzoni hisoblash */
    public static function sign(string $clickTransId, string $merchantTransId, string $amount, int $action, string $signTime, ?string $prepareId = null): string
    {
        return md5($clickTransId
            .self::s(config('payments.click.service_id'))
            .self::s(config('payments.click.secret_key'))
            .$merchantTransId
            .($prepareId ?? '')
            .$amount
            .$action
            .$signTime);
    }

    /** OnlinePaymentService bilan bir xil format */
    public static function amount(Payment $payment): string
    {
        return OnlinePaymentService::clickAmount($payment);
    }
}
