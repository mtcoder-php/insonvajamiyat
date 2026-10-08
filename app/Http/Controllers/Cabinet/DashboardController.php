<?php

namespace App\Http\Controllers\Cabinet;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Cabinet\AuthorDashboardService;
use App\Services\Content\JournalDocumentService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Muallif kabineti — bosh sahifa (dizayn: "Muallif kabineti").
 */
class DashboardController extends Controller
{
    public function __invoke(Request $request, AuthorDashboardService $dashboard): Response
    {
        /** @var User $user */
        $user = $request->user();

        $user->loadMissing('authorProfile');

        return Inertia::render('cabinet/Dashboard', [
            'profileCompleted' => $user->authorProfile?->onboarding_completed_at !== null,
            'cards' => fn () => $dashboard->cards($user),
            'articles' => fn () => $dashboard->latest($user),
            'focus' => fn () => $dashboard->focus($user),
            'messages' => fn () => $dashboard->messages($user),
            'chart' => fn () => $dashboard->chart($user),
            'links' => fn () => self::links(),
        ]);
    }

    /**
     * Foydali havolalar: maqola shabloni (public/ dagi fayl bo'lsa), yo'riqnoma.
     *
     * @return array{template: string|null, guidelines: string}
     */
    public static function links(): array
    {
        return [
            'template' => app(JournalDocumentService::class)->templateUrl(),
            'guidelines' => route('guidelines'),
        ];
    }
}
