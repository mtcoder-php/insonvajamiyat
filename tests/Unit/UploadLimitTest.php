<?php

namespace Tests\Unit;

use App\Support\UploadLimit;
use PHPUnit\Framework\TestCase;

class UploadLimitTest extends TestCase
{
    public function test_php_ini_sizes_are_converted_to_bytes(): void
    {
        $this->assertSame(2 * 1024 * 1024, UploadLimit::toBytes('2M'));
        $this->assertSame(512 * 1024, UploadLimit::toBytes('512k'));
        $this->assertSame(1024 ** 3, UploadLimit::toBytes('1G'));
        $this->assertSame(1000, UploadLimit::toBytes('1000'));
        $this->assertSame(0, UploadLimit::toBytes('-1'));
        $this->assertSame(0, UploadLimit::toBytes(''));
    }
}
