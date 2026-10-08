<?php

namespace Tests;

use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Laravel\Fortify\Features;

abstract class TestCase extends BaseTestCase
{
    /**
     * RefreshDatabase ishlatilgan har bir testda rollar va ruxsatlar seed qilinadi.
     */
    protected bool $seed = true;

    protected string $seeder = RolesAndPermissionsSeeder::class;

    protected function setUp(): void
    {
        parent::setUp();

        // Testlar `npm run build` natijasiga (Vite manifest) bog'liq bo'lmasin
        $this->withoutVite();

        // SSR o'chiq: `npm run dev` ishlab turganda (public/hot) Inertia Vite serveriga SSR so'rovi
        // yubormasin — testlar tashqi jarayonga bog'liq bo'lmasin va Http::preventStrayRequests() buzilmasin
        config(['inertia.ssr.enabled' => false]);
    }

    protected function skipUnlessFortifyHas(string $feature, ?string $message = null): void
    {
        if (! Features::enabled($feature)) {
            $this->markTestSkipped($message ?? "Fortify feature [{$feature}] is not enabled.");
        }
    }
}
