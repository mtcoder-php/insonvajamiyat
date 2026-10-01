<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * public/ ichidagi papka yoki fayl route bilan bir xil nomda bo'lmasligi kerak:
 * veb-server (php artisan serve, nginx try_files) mavjud papkani Laravel'dan
 * oldin ochadi va route 404 qaytaradi (masalan, public/dashboard → /dashboard).
 */
class PublicPathConflictTest extends TestCase
{
    public function test_no_public_path_shadows_a_route(): void
    {
        $conflicts = [];

        foreach (Route::getRoutes() as $route) {
            if (! in_array('GET', $route->methods(), true)) {
                continue;
            }

            $segment = explode('/', trim($route->uri(), '/'))[0];

            if ($segment === '' || str_contains($segment, '{') || $segment === 'storage') {
                continue;
            }

            if (file_exists(public_path($segment))) {
                $conflicts[] = "/{$segment} (public/{$segment})";
            }
        }

        $this->assertSame([], array_values(array_unique($conflicts)), "public/ dagi papka route'ni to'sib qo'ygan");
    }
}
