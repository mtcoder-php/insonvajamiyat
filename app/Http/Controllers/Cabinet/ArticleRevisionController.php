<?php

namespace App\Http\Controllers\Cabinet;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cabinet\Articles\ResubmitArticleRequest;
use App\Models\Article;
use App\Models\User;
use App\Services\Articles\RevisionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Inertia\Inertia;
use RuntimeException;

/**
 * Muallif kabineti — tuzatilgan versiyani yuborish (RevisionRequired → Resubmitted).
 */
class ArticleRevisionController extends Controller
{
    public function store(ResubmitArticleRequest $request, Article $article, RevisionService $revisions): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $manuscript = $request->file('manuscript');

        if (! $manuscript instanceof UploadedFile) {
            throw new RuntimeException('Manuscript is required.');
        }

        $version = $revisions->resubmit(
            $article,
            $user,
            $manuscript,
            $request->string('response')->trim()->toString(),
            $request->supplementary(),
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Tuzatilgan versiya (v:number) tahririyatga yuborildi.', ['number' => $version->version_number]),
        ]);

        return to_route('cabinet.articles.show', $article->uuid);
    }
}
