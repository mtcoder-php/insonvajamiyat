<?php

namespace App\Http\Controllers\Cabinet;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Muallif kabineti — bosh sahifa (/cabinet).
 * Maqolalar statistikasi maqola moduli bilan birga qo'shiladi.
 */
class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user()->load('authorProfile');

        return Inertia::render('cabinet/Dashboard', [
            'profile' => $user->authorProfile?->only([
                'last_name', 'first_name', 'middle_name', 'organization', 'position',
                'academic_degree', 'orcid',
            ]),
            'profileCompleted' => $user->authorProfile?->onboarding_completed_at !== null,
        ]);
    }
}
