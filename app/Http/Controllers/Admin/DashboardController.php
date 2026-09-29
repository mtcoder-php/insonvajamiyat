<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RoleName;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Admin panel — bosh sahifa (/admin).
 * Maqola, to'lov va AI statistikasi tegishli modullar bilan birga qo'shiladi.
 */
class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        return Inertia::render('admin/Dashboard', [
            'stats' => [
                'authors' => User::role(RoleName::Author)->count(),
                'staff' => User::staff()->count(),
                'blocked' => User::where('is_blocked', true)->count(),
            ],
        ]);
    }
}
