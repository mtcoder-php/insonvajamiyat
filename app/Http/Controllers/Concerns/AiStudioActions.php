<?php

namespace App\Http\Controllers\Concerns;

use App\Enums\AiRequestType;
use App\Http\Requests\Ai\StoreAiRequestRequest;
use App\Models\AiRequest;
use App\Models\Article;
use App\Models\Translation;
use App\Models\User;
use App\Services\Ai\AiSettings;
use App\Services\Ai\AiStudioPresenter;
use App\Services\Ai\AiStudioService;
use App\Services\Ai\PromptLibrary;
use App\Services\Ai\TokenBudget;
use App\Services\Ai\TranslationService;
use App\Services\Editorial\EditorialWorkspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * AI Studio amallari — admin panel (admin.ai.*) va muallif kabineti (cabinet.ai.*) uchun umumiy.
 * Farq faqat route prefiksida (aiRoutes) va sahifa komponentida.
 */
trait AiStudioActions
{
    /** Route nomlari prefiksi: admin.ai yoki cabinet.ai */
    abstract protected function aiRoutes(): string;

    /**
     * Ikkala sahifa uchun umumiy prop'lar.
     *
     * @param  array<int, string>  $tabs
     * @return array<string, mixed>
     */
    protected function studioProps(Request $request, User $user, array $tabs, bool $canManage): array
    {
        $settings = app(AiSettings::class);
        $budget = app(TokenBudget::class);
        $presenter = AiStudioPresenter::for($this->aiRoutes());

        $tab = $request->string('tab')->toString();
        $tab = in_array($tab, $tabs, true) ? $tab : 'proofreader';

        $scopeAll = $canManage && $request->query('scope') === 'all';
        $typeFilter = $tab === 'history' ? $request->string('type')->toString() : self::typeFor($tab)?->value;
        $typeFilter = $typeFilter !== null && AiRequestType::tryFrom($typeFilter) !== null ? $typeFilter : null;

        $selected = null;
        $uuid = $request->string('request')->toString();

        if ($uuid !== '') {
            $selected = AiRequest::query()->where('uuid', $uuid)->with(['user:id,name', 'article'])->first();
            $selected = $selected !== null && Gate::allows('view', $selected) ? $selected : null;
        }

        $own = fn () => AiRequest::query()->where('user_id', $user->id);

        return [
            'tab' => $tab,
            'filters' => [
                'type' => $typeFilter,
                'scope' => $scopeAll ? 'all' : 'own',
                'article' => $request->string('article')->toString() ?: null,
            ],
            'ready' => $settings->ready(),
            'canManage' => $canManage,
            'languages' => array_map(fn (string $code): array => [
                'value' => $code,
                'label' => PromptLibrary::languageLabel($code),
            ], PromptLibrary::LANGUAGES),
            'checks' => array_map(
                fn (string $key, string $label): array => ['value' => $key, 'label' => ucfirst($label)],
                array_keys(PromptLibrary::CHECKS),
                PromptLibrary::CHECKS,
            ),
            'maxChars' => $settings->maxInputChars(),
            'budget' => fn (): array => $budget->summary($user),
            'current' => fn (): ?array => $selected !== null ? $presenter->detail($selected, $user) : null,
            'history' => function () use ($own, $scopeAll, $typeFilter, $presenter): array {
                $page = AiStudioPresenter::history($scopeAll ? AiRequest::query() : $own(), $typeFilter, 8);

                return [
                    'data' => array_map(fn (AiRequest $r): array => $presenter->item($r, $scopeAll), $page->items()),
                    'meta' => [
                        'currentPage' => $page->currentPage(),
                        'lastPage' => $page->lastPage(),
                        'total' => $page->total(),
                        'from' => $page->firstItem(),
                        'to' => $page->lastItem(),
                    ],
                ];
            },
            'stats' => fn (): array => AiStudioPresenter::stats($canManage ? AiRequest::query() : $own()),
            'activity' => fn (): array => ($canManage ? AiRequest::query() : $own())
                ->with(['user:id,name', 'article'])
                ->latest('id')
                ->limit(5)
                ->get()
                ->map(fn (AiRequest $r): array => $presenter->item($r, $canManage))
                ->all(),
            'urls' => [
                'store' => route($this->aiRoutes().'.requests.store'),
                'index' => route($this->aiRoutes().'.index'),
            ],
        ];
    }

    public function store(StoreAiRequestRequest $request, AiStudioService $studio): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $type = $request->type();
        $article = $this->articleFor($request, $user);

        $aiRequest = $studio->submit(
            $user,
            $type,
            $request->string('text')->toString(),
            $request->string('source_language')->toString(),
            $request->filled('target_language') ? $request->string('target_language')->toString() : null,
            $request->checks(),
            $article?->id,
        );

        return to_route($this->aiRoutes().'.index', array_filter([
            'tab' => AiStudioPresenter::tab($type),
            'request' => $aiRequest->uuid,
            'article' => $article?->uuid,
        ]));
    }

    public function proofread(Request $request, AiRequest $aiRequest, AiStudioService $studio): RedirectResponse
    {
        Gate::authorize('update', $aiRequest);
        abort_unless($aiRequest->type === AiRequestType::SpellCheck && $aiRequest->status->isFinished(), 422);

        $validated = $request->validate([
            'decisions' => ['required', 'array', 'max:2000'],
            'decisions.*' => ['string', 'in:accepted,rejected,pending'],
        ]);

        /** @var array<string, string> $decisions */
        $decisions = $validated['decisions'];
        $studio->saveProofread($aiRequest, $decisions);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Yangi versiya saqlandi.')]);

        return back();
    }

    /** Proofreader: tahrirlangan matnni Word (.docx) sifatida yuklab olish */
    public function downloadProofread(AiRequest $aiRequest, AiStudioService $studio): BinaryFileResponse
    {
        Gate::authorize('view', $aiRequest);
        abort_unless($aiRequest->type === AiRequestType::SpellCheck && $aiRequest->status->isFinished(), 404);

        return $studio->proofreadDocx($aiRequest);
    }

    public function storeVersion(Request $request, Translation $translation, TranslationService $translations): RedirectResponse
    {
        Gate::authorize('update', $translation);

        $request->validate(['content' => ['required', 'string', 'max:300000']]);

        /** @var User $user */
        $user = $request->user();
        $version = $translations->addVersion($translation, $user, $request->string('content')->toString());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Tarjima :v-versiya sifatida saqlandi.', ['v' => $version->version])]);

        return back();
    }

    public function download(Request $request, Translation $translation, TranslationService $translations): BinaryFileResponse
    {
        Gate::authorize('view', $translation);

        $version = $request->integer('version');

        return $translations->download($translation, $version > 0 ? $version : null);
    }

    /**
     * Muallifning maqolalari (so'rovni maqolaga biriktirish uchun).
     *
     * @return array<int, array{value: string, label: string}>
     */
    protected function articleOptions(User $user): array
    {
        return Article::query()
            ->ownedBy($user)
            ->latest('id')
            ->limit(50)
            ->get()
            ->map(fn (Article $article): array => [
                'value' => $article->uuid,
                'label' => EditorialWorkspace::code($article).' — '.mb_strimwidth((string) $article->title, 0, 70, '…'),
            ])
            ->all();
    }

    private function articleFor(StoreAiRequestRequest $request, User $user): ?Article
    {
        $uuid = $request->string('article')->toString();

        if ($uuid === '') {
            return null;
        }

        $article = Article::query()->where('uuid', $uuid)->ownedBy($user)->first();

        if ($article === null) {
            throw ValidationException::withMessages(['article' => __('Maqola topilmadi yoki sizga tegishli emas.')]);
        }

        return $article;
    }

    private static function typeFor(string $tab): ?AiRequestType
    {
        return match ($tab) {
            'proofreader' => AiRequestType::SpellCheck,
            'translator' => AiRequestType::Translation,
            'analytics' => AiRequestType::Analysis,
            default => null,
        };
    }
}
