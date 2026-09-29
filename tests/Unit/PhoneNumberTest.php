<?php

namespace Tests\Unit;

use App\Support\PhoneNumber;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class PhoneNumberTest extends TestCase
{
    /**
     * @return array<string, array{0: string|null, 1: string|null}>
     */
    public static function normalizationCases(): array
    {
        return [
            'probel va defis bilan' => ['+998 90 123-45-67', '+998901234567'],
            'qavs bilan' => ['(+998) 90 1234567', '+998901234567'],
            'pliussiz xalqaro' => ['998901234567', '+998901234567'],
            'milliy 9 raqam' => ['901234567', '+998901234567'],
            'milliy probel bilan' => ['90 123 45 67', '+998901234567'],
            'xorijiy raqam' => ['+7 701 123 45 67', '+77011234567'],
            'bo‘sh satr' => ['   ', null],
            'null' => [null, null],
        ];
    }

    #[DataProvider('normalizationCases')]
    public function test_normalize(?string $input, ?string $expected): void
    {
        $this->assertSame($expected, PhoneNumber::normalize($input));
    }

    public function test_is_valid(): void
    {
        $this->assertTrue(PhoneNumber::isValid('+998901234567'));
        $this->assertTrue(PhoneNumber::isValid('+77011234567'));

        $this->assertFalse(PhoneNumber::isValid('998901234567'));
        $this->assertFalse(PhoneNumber::isValid('+12'));
        $this->assertFalse(PhoneNumber::isValid('+0901234567'));
        $this->assertFalse(PhoneNumber::isValid('abc'));
        $this->assertFalse(PhoneNumber::isValid(null));
    }
}
