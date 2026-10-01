<?php

namespace App\Http\Controllers\Cabinet;

use App\Enums\ArticleStatus;
use App\Enums\SubmissionStep;
use App\Http\Controllers\Controller;
use App\Http\Requests\Cabinet\Articles\ArticleAbstractRequest;
use App\Http\Requests\Cabinet\Articles\ArticleAuthorsRequest;
use App\Http\Requests\Cabinet\Articles\ArticleDetailsRequest;
use App\Http\Requests\Cabinet\Articles\ArticleKeywordsRequest;
use App\Http\Requests\Cabinet\Articles\SubmitArticleRequest;
use App\Models\Article;
use App\Models\User;
use App\Services\Articles\ArticleSubmissionService;
use App\Services\Cabinet\SubmissionWizardData;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Yangi maqola yuborish — 7 bosqichli forma (cabinet/articles/Wizard).
 *
 * 1-bosqichdan keyin maqola qoralama sifatida saqlanadi; har bir bosqich alohida
 * saqlanadi ("Saqlash va davom etish" — keyingi bosqichga, "Qoralama sifatida saqlash" — shu bosqichda qoladi).
 */
class ArticleSubmissionController extends Controller
{
    public function __construct(
        private readonly ArticleSubmissionService $submission,
        private readonly SubmissionWizardData $wizard,
    ) {}

    public function create(Request $request): Response
    {
        return Inertia::render('cabinet/articles/Wizard', $this->wizard->props(
            $this->user($request), null, SubmissionStep::Details,
        ));
    }

    public function store(ArticleDetailsRequest $request): RedirectResponse
    {
        $article = $this->submission->createDraft($this->user($request), $request->details());

        return $this->saved($request, $article, SubmissionStep::Details);
    }

    public function edit(Request $request, Article $article): Response|RedirectResponse
    {
        if ($article->status !== ArticleStatus::Draft) {
            return to_route('cabinet.articles.show', $article->uuid);
        }

        Gate::authorize('editDraft', $article);

        $step = SubmissionStep::tryFrom($request->integer('step', 1)) ?? SubmissionStep::Details;

        return Inertia::render('cabinet/articles/Wizard', $this->wizard->props($this->user($request), $article, $step));
    }

    public function updateDetails(ArticleDetailsRequest $request, Article $article): RedirectResponse
    {
        $this->submission->updateDetails($article, $request->details());

        return $this->saved($request, $article, SubmissionStep::Details);
    }

    public function updateAuthors(ArticleAuthorsRequest $request, Article $article): RedirectResponse
    {
        $this->submission->syncAuthors(
            $article,
            $this->user($request),
            $request->authors(),
            $request->integer('corresponding'),
        );

        return $this->saved($request, $article, SubmissionStep::Authors);
    }

    public function updateAbstract(ArticleAbstractRequest $request, Article $article): RedirectResponse
    {
        $references = $request->input('references');

        $this->submission->updateAbstract(
            $article,
            $request->titles(),
            $request->abstracts(),
            is_string($references) ? $references : null,
        );

        return $this->saved($request, $article, SubmissionStep::Abstract);
    }

    public function updateKeywords(ArticleKeywordsRequest $request, Article $article): RedirectResponse
    {
        $this->submission->updateKeywords($article, $request->keywords());

        return $this->saved($request, $article, SubmissionStep::Keywords);
    }

    public function submit(SubmitArticleRequest $request, Article $article): RedirectResponse
    {
        $this->submission->submit($article, $this->user($request));
        $article->refresh();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $article->status === ArticleStatus::AwaitingPayment
                ? __("Maqolangiz yuborildi. To'lov tasdiqlangach tahririyat ko'rib chiqadi.")
                : __("Maqolangiz yuborildi va tahririyat navbatiga qo'shildi."),
        ]);

        return to_route('cabinet.articles.show', $article->uuid);
    }

    public function destroy(Article $article): RedirectResponse
    {
        Gate::authorize('delete', $article);
        abort_unless($article->status === ArticleStatus::Draft, 403);

        $this->submission->deleteDraft($article);

        Inertia::flash('toast', ['type' => 'success', 'message' => __("Qoralama o'chirildi.")]);

        return to_route('cabinet.articles.index');
    }

    /**
     * Saqlangandan keyin: "stay" bo'lsa shu bosqichda qoladi, aks holda keyingisiga o'tadi.
     */
    private function saved(Request $request, Article $article, SubmissionStep $step): RedirectResponse
    {
        $stay = $request->boolean('stay');

        if ($stay) {
            Inertia::flash('toast', ['type' => 'success', 'message' => __('Qoralama saqlandi.')]);
        }

        return to_route('cabinet.articles.edit', [
            'article' => $article->uuid,
            'step' => $stay ? $step->value : $step->next()->value,
        ]);
    }

    private function user(Request $request): User
    {
        /** @var User $user */
        $user = $request->user();

        return $user;
    }
}
