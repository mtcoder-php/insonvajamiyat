<?php

namespace App\Services\Payments;

use RuntimeException;

/**
 * Maqola endi to'lov kutmayapti: boshqa yo'l bilan to'langan, ozod qilingan yoki qaytarib olingan.
 */
final class PaymentNotPayable extends RuntimeException
{
    public function __construct(public readonly bool $alreadyPaid = false)
    {
        parent::__construct($alreadyPaid ? 'Article is already paid.' : 'Article is not awaiting payment.');
    }
}
