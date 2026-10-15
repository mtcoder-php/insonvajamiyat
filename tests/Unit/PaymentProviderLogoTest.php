<?php

namespace Tests\Unit;

use App\Enums\PaymentProvider;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * To'lov tizimlarining rasmiy logotiplari: public/images/payments/ dagi fayl nomi bo'yicha.
 */
class PaymentProviderLogoTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dir = public_path('images/payments');
    }

    public function test_logo_is_found_by_provider_key_in_file_name(): void
    {
        $existed = is_dir($this->dir);
        File::ensureDirectoryExists($this->dir);
        $click = $this->dir.'/Click-logo-test.png';
        file_put_contents($click, 'png');

        try {
            $this->assertStringEndsWith('/images/payments/Click-logo-test.png', (string) PaymentProvider::Click->logo());
            $this->assertNull(PaymentProvider::Manual->logo());
        } finally {
            @unlink($click);

            if (! $existed) {
                File::deleteDirectory($this->dir);
            }
        }
    }
}
