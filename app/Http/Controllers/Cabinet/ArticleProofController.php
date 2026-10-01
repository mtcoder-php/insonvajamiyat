<?php

namespace App\Http\Controllers\Cabinet;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\User;
use App\Services\Production\ProductionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * Muallif kabineti — korrektura (yakuniy PDF): tasdiqlash yoki tuzatish so'rash.
 * Faqat maqolani yuborgan muallif javob beradi (ProductionService tekshiradi).
 */
class ArticleProofController extends Controller
{
    public function __construct(private readonly ProductionService $production) {}

    public function approve(Request $request, Article $article): RedirectResponse
    {
        $this->production->authorApprove($article, $this->user($request));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Korrektura tasdiqlandi. Rahmat!')]);

        return back();
    }

    public function changes(Request $request, Article $article): RedirectResponse
    {
        $validated = $request->validate(
            ['comment' => ['required', 'string', 'min:10', 'max:3000']],
            [],
            ['comment' => __('Tuzatishlar')],
        );

        $this->production->authorRequestChanges($article, $this->user($request), trim((string) $validated['comment']));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Tuzatishlar tahririyatga yuborildi.')]);

        return back();
    }

    private function user(Request $request): User
    {
        /** @var User $user */
        $user = $request->user();

        return $user;
    }
}
