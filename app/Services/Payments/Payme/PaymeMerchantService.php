<?php

namespace App\Services\Payments\Payme;

use App\Enums\PaymentProvider;
use App\Enums\PaymentPurpose;
use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Models\PaymentItem;
use App\Services\Payments\OnlinePaymentService;
use App\Services\Payments\PaymentNotPayable;
use App\Services\Payments\PaymentSettlement;
use App\Services\Payments\WebhookResult;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Payme Merchant API (JSON-RPC 2.0): CheckPerformTransaction, CreateTransaction, PerformTransaction,
 * CancelTransaction, CheckTransaction, GetStatement.
 *
 * Avtorizatsiya: Basic "Paycom:{KEY}". Summalar tiyinda, vaqtlar millisekundda.
 * Tranzaksiya holatlari (payments.provider_state): 1 yaratilgan, 2 bajarilgan, -1 bekor (bajarilmasdan), -2 bekor (bajarilgandan keyin).
 * Bajarilgan to'lov bekor qilinmaydi (-31007): maqola allaqachon tahririyat navbatida, qaytarish qo'lda hal qilinadi.
 */
class PaymeMerchantService
{
    public const STATE_CREATED = 1;

    public const STATE_PERFORMED = 2;

    public const STATE_CANCELLED = -1;

    public const STATE_CANCELLED_AFTER_PERFORM = -2;

    /** Muddati o'tgani uchun bekor qilish sababi (Payme ro'yxati bo'yicha) */
    public const REASON_TIMEOUT = 4;

    private const METHODS = [
        'CheckPerformTransaction', 'CreateTransaction', 'PerformTransaction',
        'CancelTransaction', 'CheckTransaction', 'GetStatement',
    ];

    private ?int $paymentId = null;

    public function __construct(private readonly PaymentSettlement $settlement) {}

    public function handle(?string $authorization, string $raw): WebhookResult
    {
        $this->paymentId = null;
        $request = json_decode($raw, true);
        $id = is_array($request) ? ($request['id'] ?? null) : null;
        $method = is_array($request) && is_string($request['method'] ?? null) ? $request['method'] : 'unknown';

        try {
            if (! $this->authorized($authorization)) {
                throw new PaymeException(PaymeException::INSUFFICIENT_PRIVILEGE);
            }

            if (! is_array($request)) {
                throw new PaymeException(PaymeException::PARSE_ERROR);
            }

            $params = $request['params'] ?? null;

            if (! in_array($method, self::METHODS, true)) {
                throw new PaymeException(PaymeException::METHOD_NOT_FOUND, $method);
            }

            if (! is_array($params)) {
                throw new PaymeException(PaymeException::INVALID_REQUEST);
            }

            /** @var array<string, mixed> $params */
            $result = match ($method) {
                'CheckPerformTransaction' => $this->checkPerformTransaction($params),
                'CreateTransaction' => $this->createTransaction($params),
                'PerformTransaction' => $this->performTransaction($params),
                'CancelTransaction' => $this->cancelTransaction($params),
                'CheckTransaction' => $this->checkTransaction($params),
                default => $this->getStatement($params),
            };

            return new WebhookResult(
                body: ['result' => $result, 'id' => $id],
                action: $method,
                paymentId: $this->paymentId,
                signatureValid: true,
            );
        } catch (PaymeException $e) {
            return new WebhookResult(
                body: ['error' => $e->toError(), 'id' => $id],
                action: $method,
                paymentId: $this->paymentId,
                errorCode: (int) $e->getCode(),
                signatureValid: $e->getCode() !== PaymeException::INSUFFICIENT_PRIVILEGE,
            );
        } catch (Throwable $e) {
            report($e);
            $error = new PaymeException(PaymeException::INTERNAL);

            return new WebhookResult(
                body: ['error' => $error->toError(), 'id' => $id],
                action: $method,
                paymentId: $this->paymentId,
                errorCode: PaymeException::INTERNAL,
                signatureValid: true,
            );
        }
    }

    /**
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    private function checkPerformTransaction(array $params): array
    {
        $payment = $this->findPayable($params);
        $result = ['allow' => true];

        $detail = $this->receiptDetail($payment);

        if ($detail !== null) {
            $result['detail'] = $detail;
        }

        return $result;
    }

    /**
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    private function createTransaction(array $params): array
    {
        $transactionId = self::requireString($params, 'id');
        $existing = $this->findTransaction($transactionId);

        if ($existing !== null) {
            if ($existing->provider_state !== self::STATE_CREATED) {
                throw new PaymeException(PaymeException::CANNOT_PERFORM);
            }

            if ($this->expired($existing)) {
                $this->cancelTransactionRecord($existing, self::REASON_TIMEOUT);

                throw new PaymeException(PaymeException::CANNOT_PERFORM);
            }

            return $this->createdResponse($existing);
        }

        return DB::transaction(function () use ($params, $transactionId): array {
            $payment = $this->findPayable($params, lock: true);

            if ($payment->provider_transaction_id !== null && $payment->provider_state === self::STATE_CREATED) {
                if (! $this->expired($payment)) {
                    throw new PaymeException(PaymeException::ORDER_BUSY);
                }

                $this->cancelTransactionRecord($payment, self::REASON_TIMEOUT);

                throw new PaymeException(PaymeException::ORDER_UNAVAILABLE);
            }

            if ($payment->provider_transaction_id !== null) {
                // Bu buyurtma bo'yicha avvalgi tranzaksiya yakunlangan (bekor qilingan) — yangisi qabul qilinmaydi
                throw new PaymeException(PaymeException::ORDER_UNAVAILABLE);
            }

            $payment->forceFill([
                'status' => PaymentStatus::Processing,
                'provider_transaction_id' => $transactionId,
                'provider_state' => self::STATE_CREATED,
                'provider_create_time' => self::nowMs(),
                'meta' => [...($payment->meta ?? []), 'payme_time' => self::int($params['time'] ?? null)],
            ])->save();

            $this->settlement->markArticlePending($payment);

            return $this->createdResponse($payment);
        });
    }

    /**
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    private function performTransaction(array $params): array
    {
        $payment = $this->requireTransaction($params);

        if ($payment->provider_state === self::STATE_PERFORMED) {
            return $this->performedResponse($payment);
        }

        if ($payment->provider_state !== self::STATE_CREATED) {
            throw new PaymeException(PaymeException::CANNOT_PERFORM);
        }

        if ($this->expired($payment)) {
            $this->cancelTransactionRecord($payment, self::REASON_TIMEOUT);

            throw new PaymeException(PaymeException::CANNOT_PERFORM);
        }

        try {
            $this->settlement->markPaid($payment, [
                'provider_state' => self::STATE_PERFORMED,
                'provider_perform_time' => self::nowMs(),
            ]);
        } catch (PaymentNotPayable) {
            throw new PaymeException(PaymeException::CANNOT_PERFORM);
        }

        return $this->performedResponse($payment);
    }

    /**
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    private function cancelTransaction(array $params): array
    {
        $payment = $this->requireTransaction($params);

        if ($payment->provider_state === self::STATE_CREATED) {
            $reason = self::int($params['reason'] ?? null);
            $this->cancelTransactionRecord($payment, $reason > 0 ? $reason : null);
        } elseif ($payment->provider_state === self::STATE_PERFORMED) {
            throw new PaymeException(PaymeException::CANNOT_CANCEL);
        }

        return [
            'transaction' => (string) $payment->id,
            'cancel_time' => (int) $payment->provider_cancel_time,
            'state' => (int) $payment->provider_state,
        ];
    }

    /**
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    private function checkTransaction(array $params): array
    {
        $payment = $this->requireTransaction($params);

        return [
            'create_time' => (int) $payment->provider_create_time,
            'perform_time' => (int) $payment->provider_perform_time,
            'cancel_time' => (int) $payment->provider_cancel_time,
            'transaction' => (string) $payment->id,
            'state' => (int) $payment->provider_state,
            'reason' => $payment->cancel_reason,
        ];
    }

    /**
     * @param  array<string, mixed>  $params
     * @return array{transactions: array<int, array<string, mixed>>}
     */
    private function getStatement(array $params): array
    {
        $from = self::int($params['from'] ?? null);
        $to = self::int($params['to'] ?? null);

        $transactions = Payment::query()
            ->where('provider', PaymentProvider::Payme->value)
            ->whereNotNull('provider_transaction_id')
            ->whereBetween('provider_create_time', [$from, $to])
            ->orderBy('provider_create_time')
            ->get()
            ->map(fn (Payment $payment): array => [
                'id' => $payment->provider_transaction_id,
                'time' => self::int($payment->meta['payme_time'] ?? null) ?: (int) $payment->provider_create_time,
                'amount' => OnlinePaymentService::tiyin($payment),
                'account' => [self::accountKey() => (string) $payment->id],
                'create_time' => (int) $payment->provider_create_time,
                'perform_time' => (int) $payment->provider_perform_time,
                'cancel_time' => (int) $payment->provider_cancel_time,
                'transaction' => (string) $payment->id,
                'state' => (int) $payment->provider_state,
                'reason' => $payment->cancel_reason,
            ])
            ->values()
            ->all();

        return ['transactions' => $transactions];
    }

    /**
     * account[payment_id] bo'yicha to'lanadigan buyurtma va summani tekshirish.
     *
     * @param  array<string, mixed>  $params
     */
    private function findPayable(array $params, bool $lock = false): Payment
    {
        $account = $params['account'] ?? null;
        $value = is_array($account) ? ($account[self::accountKey()] ?? null) : null;
        $id = is_scalar($value) ? (string) $value : '';

        if (! ctype_digit($id)) {
            throw new PaymeException(PaymeException::ORDER_NOT_FOUND, self::accountKey());
        }

        $query = Payment::query()
            ->whereKey((int) $id)
            ->where('provider', PaymentProvider::Payme->value)
            ->where('purpose', PaymentPurpose::Publication->value);

        $payment = ($lock ? $query->lockForUpdate() : $query)->first();

        if ($payment === null) {
            throw new PaymeException(PaymeException::ORDER_NOT_FOUND, self::accountKey());
        }

        $this->paymentId = $payment->id;

        if (! $payment->status->is(PaymentStatus::Pending, PaymentStatus::Processing)
            || ! PaymentSettlement::isPayable($payment->article)) {
            throw new PaymeException(PaymeException::ORDER_UNAVAILABLE, self::accountKey());
        }

        if (self::int($params['amount'] ?? null) !== OnlinePaymentService::tiyin($payment)) {
            throw new PaymeException(PaymeException::WRONG_AMOUNT);
        }

        return $payment;
    }

    /**
     * @param  array<string, mixed>  $params
     */
    private function requireTransaction(array $params): Payment
    {
        $payment = $this->findTransaction(self::requireString($params, 'id'));

        if ($payment === null) {
            throw new PaymeException(PaymeException::TRANSACTION_NOT_FOUND);
        }

        return $payment;
    }

    private function findTransaction(string $transactionId): ?Payment
    {
        $payment = Payment::query()
            ->where('provider', PaymentProvider::Payme->value)
            ->where('provider_transaction_id', $transactionId)
            ->first();

        $this->paymentId = $payment?->id ?? $this->paymentId;

        return $payment;
    }

    private function cancelTransactionRecord(Payment $payment, ?int $reason): void
    {
        $this->settlement->cancel($payment, [
            'provider_state' => self::STATE_CANCELLED,
            'provider_cancel_time' => self::nowMs(),
            'cancel_reason' => $reason,
        ]);
    }

    private function expired(Payment $payment): bool
    {
        $timeout = (int) config('payments.payme.timeout_ms', 43_200_000);

        return $payment->provider_create_time !== null
            && self::nowMs() - $payment->provider_create_time > $timeout;
    }

    /**
     * @return array<string, mixed>
     */
    private function createdResponse(Payment $payment): array
    {
        return [
            'create_time' => (int) $payment->provider_create_time,
            'transaction' => (string) $payment->id,
            'state' => self::STATE_CREATED,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function performedResponse(Payment $payment): array
    {
        return [
            'transaction' => (string) $payment->id,
            'perform_time' => (int) $payment->provider_perform_time,
            'state' => self::STATE_PERFORMED,
        ];
    }

    /**
     * Elektron chek (fiskalizatsiya) — MXIK kodi sozlangan bo'lsa.
     *
     * @return array<string, mixed>|null
     */
    private function receiptDetail(Payment $payment): ?array
    {
        $ikpu = config('payments.payme.fiscal.ikpu');
        $package = config('payments.payme.fiscal.package_code');

        if (! is_string($ikpu) || $ikpu === '' || ! is_string($package) || $package === '') {
            return null;
        }

        $items = $payment->items()->get()->map(fn (PaymentItem $item): array => [
            'title' => mb_substr((string) $item->name, 0, 128),
            'price' => (int) round((float) $item->unit_price * 100),
            'count' => $item->quantity,
            'code' => $ikpu,
            'package_code' => $package,
            'vat_percent' => (int) config('payments.payme.fiscal.vat_percent', 0),
        ])->values()->all();

        return ['receipt_type' => 0, 'items' => $items];
    }

    private function authorized(?string $header): bool
    {
        $key = config('payments.payme.key');

        if (! is_string($key) || $key === '' || $header === null || ! str_starts_with($header, 'Basic ')) {
            return false;
        }

        $decoded = base64_decode(substr($header, 6), true);

        $login = config('payments.payme.login');
        $expected = (is_string($login) && $login !== '' ? $login : 'Paycom').':'.$key;

        return is_string($decoded) && hash_equals($expected, $decoded);
    }

    /**
     * @param  array<string, mixed>  $params
     */
    private static function requireString(array $params, string $key): string
    {
        $value = $params[$key] ?? null;

        if (! is_string($value) || $value === '' || strlen($value) > 191) {
            throw new PaymeException(PaymeException::INVALID_REQUEST);
        }

        return $value;
    }

    private static function accountKey(): string
    {
        $key = config('payments.payme.account_key');

        return is_string($key) && $key !== '' ? $key : 'payment_id';
    }

    private static function int(mixed $value): int
    {
        return is_numeric($value) ? (int) $value : 0;
    }

    private static function nowMs(): int
    {
        return (int) now()->getTimestampMs();
    }
}
