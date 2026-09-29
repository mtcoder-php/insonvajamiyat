<?php

namespace Database\Factories;

use App\Models\Banner;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Banner>
 */
class BannerFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => ['uz' => 'Insonni anglash — jamiyatni anglashdir.'],
            'subtitle' => ['uz' => 'Understanding Humanity, Understanding Society.'],
            'image_path' => 'banners/'.fake()->uuid().'.webp',
            'button_text' => ['uz' => "Maqolalar ko'rish"],
            'sort_order' => 0,
            'is_active' => true,
        ];
    }
}
