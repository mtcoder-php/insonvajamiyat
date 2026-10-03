<?php

namespace App\Http\Controllers\Cabinet;

use App\Http\Controllers\Concerns\AiStudioActions;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Muallif kabineti → AI Studio (TZ 4.1.5, 4.1.6): imlo/uslub tekshiruvi, ilmiy tarjima, tahlil.
 * Natijani o'z maqolasiga biriktirish mumkin (?article={uuid} — oldindan tanlangan maqola).
 */
class AiStudioController extends Controller
{
    use AiStudioActions;

    public const TABS = ['proofreader', 'translator', 'analytics', 'history'];

    protected function aiRoutes(): string
    {
        return 'cabinet.ai';
    }

    public function index(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();

        return Inertia::render('cabinet/ai/Index', [
            ...$this->studioProps($request, $user, self::TABS, false),
            'articles' => fn (): array => $this->articleOptions($user),
            'settings' => null,
        ]);
    }
}
