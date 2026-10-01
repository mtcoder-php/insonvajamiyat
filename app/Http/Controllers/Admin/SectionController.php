<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AdminSection;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Hali ishlab chiqilmagan admin bo'limlari uchun vaqtinchalik sahifa:
 * bo'lim nomi, vazifasi va keyingi bosqichda quriladigan imkoniyatlar.
 * Bo'lim CRUD'i tayyor bo'lgach, route o'z controller'iga o'tkaziladi.
 */
class SectionController extends Controller
{
    public function __invoke(Request $request): Response
    {
        // routes/admin.php: ->defaults('section', ...)
        $key = $request->route('section');
        $section = is_string($key) ? AdminSection::tryFrom($key) : null;

        abort_if($section === null, 404);

        return Inertia::render('admin/Section', [
            'section' => [
                'key' => $section->value,
                'title' => $section->title(),
                'description' => $section->description(),
                'features' => $section->features(),
            ],
        ]);
    }
}
