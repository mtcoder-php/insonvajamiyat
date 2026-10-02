<?php

namespace App\Services\Editorial;

use App\Enums\ArticleStatus;
use App\Enums\AuditEvent;
use App\Enums\EditorialDecisionType;
use App\Enums\PermissionName;
use App\Models\Article;
use App\Models\ArticleNote;
use App\Models\EditorialDecision;
use App\Models\User;
use App\Notifications\ArticleUpdateNotification;
use App\Services\Articles\ArticleWorkflow;
use App\Services\Audit\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Muharrir ish joyi amallari: ko'rib chiqishni boshlash, mas'ul muharrirni biriktirish,
 * qaror (tuzatish / qabul / rad) va ichki izohlar.
 *
 * Holat o'zgarishlari faqat ArticleWorkflow orqali; muallifga ko'rinadigan izoh
 * holat tarixiga, qaror tafsilotlari editorial_decisions ga yoziladi.
 * "Taqrizga yuborish" (taqrizchi tayinlash bilan) — taqrizchilar bosqichida qo'shiladi.
 */
class EditorialService
{
    /** Hozircha muharrir ish joyidan beriladigan qarorlar */
    public const DECISIONS = [
        EditorialDecisionType::RequestRevision,
        EditorialDecisionType::Accept,
        EditorialDecisionType::Reject,
    ];

    /** Muallifga izoh majburiy bo'lgan qarorlar */
    public const COMMENT_REQUIRED = [
        EditorialDecisionType::RequestRevision,
        EditorialDecisionType::Reject,
    ];

    public function __construct(
        private readonly ArticleWorkflow $workflow,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * Yangi (yoki qayta yuborilgan) maqolani ko'rib chiqishga olish. Mas'ul muharrir
     * biriktirilmagan bo'lsa — amalni bajargan muharrir biriktiriladi.
     */
    public function startReview(Article $article, User $editor): void
    {
        if (! in_array($article->status, [ArticleStatus::Submitted, ArticleStatus::Resubmitted], true)) {
            throw ValidationException::withMessages([
                'article' => __("Faqat yangi yoki qayta yuborilgan maqolani ko'rib chiqishga olish mumkin."),
            ]);
        }

        DB::transaction(function () use ($article, $editor): void {
            if ($article->handling_editor_id === null) {
                $article->handling_editor_id = $editor->id;
            }

            $this->workflow->transition(
                $article,
                ArticleStatus::UnderReview,
                $editor,
                __("Maqolangiz muharrir tomonidan ko'rib chiqilmoqda."),
            );
        });
    }

    public function assignEditor(Article $article, ?User $editor): void
    {
        if ($editor !== null && ! self::canHandle($editor)) {
            throw ValidationException::withMessages([
                'editor_id' => __("Tanlangan foydalanuvchi maqola bo'yicha qaror qabul qila olmaydi."),
            ]);
        }

        $previous = $article->handling_editor_id;
        $article->forceFill(['handling_editor_id' => $editor?->id])->save();

        if ($previous !== $article->handling_editor_id) {
            $this->audit->log(AuditEvent::ArticleEditorAssigned, $article, ['editor' => $editor?->name]);
        }
    }

    /**
     * Muharrir qarori. Tuzatish va rad etishda muallifga izoh majburiy.
     */
    public function decide(
        Article $article,
        User $editor,
        EditorialDecisionType $decision,
        ?string $commentToAuthor,
        ?string $internalNote = null,
    ): EditorialDecision {
        if (! in_array($decision, self::DECISIONS, true)) {
            throw ValidationException::withMessages(['decision' => __('Bu qaror hozircha mavjud emas.')]);
        }

        if (in_array($decision, self::COMMENT_REQUIRED, true) && blank($commentToAuthor)) {
            throw ValidationException::withMessages([
                'comment_to_author' => __('Muallif uchun izoh yozing.'),
            ]);
        }

        if (! $article->status->canTransitionTo($decision->resultingStatus())) {
            throw ValidationException::withMessages([
                'decision' => __("Maqolaning hozirgi holatida («:status») bu qarorni berib bo'lmaydi.", [
                    'status' => $article->status->label(),
                ]),
            ]);
        }

        $record = DB::transaction(function () use ($article, $editor, $decision, $commentToAuthor, $internalNote): EditorialDecision {
            if ($article->handling_editor_id === null) {
                $article->handling_editor_id = $editor->id;
            }

            $record = $article->decisions()->create([
                'editor_id' => $editor->id,
                'round' => max(1, $article->review_round),
                'decision' => $decision,
                'comment_to_author' => $commentToAuthor,
                'internal_note' => $internalNote,
            ]);

            $this->workflow->transition(
                $article,
                $decision->resultingStatus(),
                $editor,
                $commentToAuthor ?? self::defaultComment($decision),
            );

            return $record;
        });

        // Muallifga: qaror va izoh (baza + email)
        $article->submitter->notify(new ArticleUpdateNotification(
            $article,
            ArticleUpdateNotification::DECISION,
            __('Tahririyat qarori: :decision', ['decision' => $decision->label()]),
            $commentToAuthor ?? self::defaultComment($decision),
        ));

        return $record;
    }

    public function addNote(Article $article, User $author, string $body): ArticleNote
    {
        return $article->notes()->create(['user_id' => $author->id, 'body' => $body]);
    }

    /** Maqola bo'yicha qaror qabul qila oladigan (mas'ul muharrir bo'la oladigan) foydalanuvchi */
    public static function canHandle(User $user): bool
    {
        return ! $user->is_blocked && $user->can(PermissionName::ArticlesDecide->value);
    }

    private static function defaultComment(EditorialDecisionType $decision): ?string
    {
        if ($decision !== EditorialDecisionType::Accept) {
            return null;
        }

        $text = __('Tabriklaymiz! Maqolangiz nashrga qabul qilindi.');

        return is_string($text) ? $text : null;
    }
}
