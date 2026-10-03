<?php

namespace App\Http\Controllers\Admin\Ai;

use App\Enums\AiRequestType;
use App\Enums\PermissionName;
use App\Http\Controllers\Controller;
use App\Http\Requests\Ai\StoreAiRequestRequest;
use App\Models\AiRequest;
use App\Models\Translation;
use App\Models\User;
use App\Services\Ai\AiSettings;
use App\Services\Ai\AiStudioPresenter;
use App\Services\Ai\AiStudioService;
use App\Services\Ai\AiUsageOverview;
use App\Services\Ai\PromptLibrary;
use App\Services\Ai\TokenBudget;
use App\Services\Ai\TranslationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Admin → AI Studio (super admin ai page.png):
 *   ?tab=proofreader|translator|analytics|history|settings, ?request={uuid} — tanlangan natija.
 * Natija tayyorlanayotganda frontend faqat "current" ni qayta so'raydi (usePoll).
 */
class AiStudioController extends Controller
{
    public const TABS = ['proofreader', 'translator', 'analytics', 'history', 'settings'];

    public function index(
        Request $request,
        AiSettings $settings,
        TokenBudget $budget,
        AiUsageOverview $usage,
    ): Response {
        /** @var User $user */
        $user = $request->user();
        $canManage = $user->can(PermissionName::AiSettingsManage->value);
        $presenter = AiStudioPresenter::for('admin.ai');

        $tab = $request->string('tab')->toString();
        $tab = in_array($tab, self::TABS, true) && ($tab !== 'settings' || $canManage) ? $tab : 'proofreader';

        $scopeAll = $canManage && $request->query('scope') === 'all';
        $typeFilter = $tab === 'history' ? $request->string('type')->toString() : self::typeFor($tab)?->value;
        $typeFilter = $typeFilter !== null && AiRequestType::tryFrom($typeFilter) !== null ? $typeFilter : null;

        $selected = null;
        $uuid = $request->string('request')->toString();

        if ($uuid !== '') {
            $selected = AiRequest::query()->where('uuid', $uuid)->with('user:id,name')->first();
            $selected = $selected !== null && Gate::allows('view', $selected) ? $selected : null;
        }

        return Inertia::render('admin/ai/Index', [
            'tab' => $tab,
            'filters' => ['type' => $typeFilter, 'scope' => $scopeAll ? 'all' : 'own'],
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
            'history' => function () use ($user, $scopeAll, $typeFilter, $presenter): array {
                $scope = $scopeAll ? AiRequest::query() : AiRequest::query()->where('user_id', $user->id);
                $page = AiStudioPresenter::history($scope, $typeFilter, 8);

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
            'stats' => fn (): array => AiStudioPresenter::stats($canManage ? AiRequest::query() : AiRequest::query()->where('user_id', $user->id)),
            'activity' => fn (): array => ($canManage ? AiRequest::query() : AiRequest::query()->where('user_id', $user->id))
                ->with('user:id,name')
                ->latest('id')
                ->limit(5)
                ->get()
                ->map(fn (AiRequest $r): array => $presenter->item($r, $canManage))
                ->all(),
            'settings' => $tab === 'settings' && $canManage ? fn (): array => $usage->settings($request->string('uq')->toString()) : null,
            'urls' => [
                'store' => route('admin.ai.requests.store'),
                'index' => route('admin.ai.index'),
            ],
        ]);
    }

    public function store(StoreAiRequestRequest $request, AiStudioService $studio): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $type = $request->type();

        $aiRequest = $studio->submit(
            $user,
            $type,
            $request->string('text')->toString(),
            $request->string('source_language')->toString(),
            $request->filled('target_language') ? $request->string('target_language')->toString() : null,
            $request->checks(),
        );

        return to_route('admin.ai.index', ['tab' => AiStudioPresenter::tab($type), 'request' => $aiRequest->uuid]);
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
