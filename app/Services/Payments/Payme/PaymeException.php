<?php

namespace App\Services\Payments\Payme;

use RuntimeException;

/**
 * Payme Merchant API xatosi: kod + uch tildagi xabar (ru, uz, en).
 */
final class PaymeException extends RuntimeException
{
    public const INTERNAL = -32400;

    public const INSUFFICIENT_PRIVILEGE = -32504;

    public const PARSE_ERROR = -32700;

    public const INVALID_REQUEST = -32600;

    public const METHOD_NOT_FOUND = -32601;

    public const WRONG_AMOUNT = -31001;

    public const TRANSACTION_NOT_FOUND = -31003;

    public const CANNOT_CANCEL = -31007;

    public const CANNOT_PERFORM = -31008;

    public const ORDER_NOT_FOUND = -31050;

    public const ORDER_UNAVAILABLE = -31051;

    public const ORDER_BUSY = -31052;

    private const MESSAGES = [
        self::INTERNAL => ['Внутренняя ошибка', 'Ichki xatolik', 'Internal error'],
        self::INSUFFICIENT_PRIVILEGE => ['Недостаточно привилегий', 'Ruxsat yetarli emas', 'Insufficient privilege'],
        self::PARSE_ERROR => ['Ошибка разбора JSON', "JSON ni o'qib bo'lmadi", 'Parse error'],
        self::INVALID_REQUEST => ['Неверный запрос', "Noto'g'ri so'rov", 'Invalid request'],
        self::METHOD_NOT_FOUND => ['Метод не найден', 'Metod topilmadi', 'Method not found'],
        self::WRONG_AMOUNT => ['Неверная сумма', "Summa noto'g'ri", 'Wrong amount'],
        self::TRANSACTION_NOT_FOUND => ['Транзакция не найдена', 'Tranzaksiya topilmadi', 'Transaction not found'],
        self::CANNOT_CANCEL => ['Заказ выполнен, отмена невозможна', "Buyurtma bajarilgan, bekor qilib bo'lmaydi", 'Order completed, cannot cancel'],
        self::CANNOT_PERFORM => ['Невозможно выполнить операцию', "Amalni bajarib bo'lmaydi", 'Unable to perform operation'],
        self::ORDER_NOT_FOUND => ['Заказ не найден', 'Buyurtma topilmadi', 'Order not found'],
        self::ORDER_UNAVAILABLE => ['Заказ уже оплачен или недоступен', "Buyurtma to'langan yoki mavjud emas", 'Order already paid or unavailable'],
        self::ORDER_BUSY => ['Заказ ожидает оплаты в другой транзакции', "Buyurtma boshqa tranzaksiyada to'lanmoqda", 'Order is pending in another transaction'],
    ];

    public function __construct(int $code, public readonly ?string $data = null)
    {
        parent::__construct(self::MESSAGES[$code][2] ?? 'Error', $code);
    }

    /**
     * @return array{code: int, message: array{ru: string, uz: string, en: string}, data?: string}
     */
    public function toError(): array
    {
        [$ru, $uz, $en] = self::MESSAGES[$this->getCode()] ?? self::MESSAGES[self::INTERNAL];

        $error = ['code' => (int) $this->getCode(), 'message' => ['ru' => $ru, 'uz' => $uz, 'en' => $en]];

        if ($this->data !== null) {
            $error['data'] = $this->data;
        }

        return $error;
    }
}
