<?php

namespace App\Services\Articles;

use App\Enums\ArticleFileType;
use App\Enums\ArticleStatus;
use App\Enums\ArticleVersionType;
use App\Enums\EditorialDecisionType;
use App\Models\Article;
use App\Models\ArticleVersion;
use App\Models\EditorialDecision;
use App\Models\User;
use App\Notifications\ArticleUpdateNotification;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Tuzatish sikli: tahririyat "Tuzatish talab etiladi" qaroridan keyin muallif
 * tuzatilgan faylni va taqrizchilarga javobini yuboradi:
 *   article_versions (type=revision, version_number+1) + article_files (revision / ilovalar) →
 *   RevisionRequired → Resubmitted → mas'ul muharrirga bildirishnoma.
 *
 * Keyingi qadam muharrirda: qayta ko'rib chiqish yoki yangi taqriz raundi
 * (ReviewService::invite — holat InReview bo'lmagani uchun raund oshadi).
 */
class RevisionService
{
    public const MAX_SUPPLEMENTARY = 5;

    public function __construct(
        private readonly ArticleWorkflow $workflow,
        private readonly ArticleFileService $files,
    ) {}

    /**
     * Muallif uchun tuzatish talabi: muharrirning oxirgi izohi va raund.
     *
     * @return array{comment: string|null, requestedAt: string|null, round: int}|null
     */
    public function request(Article $article): ?array
    {
        if ($article->status !== ArticleStatus::RevisionRequired) {
            return null;
        }

        $decision = $article->decisions()
            ->where('decision', EditorialDecisionType::RequestRevision->value)
            ->latest('id')
            ->first();

        return [
            'comment' => $decision instanceof EditorialDecision ? $decision->comment_to_author : null,
            'requestedAt' => $decision instanceof EditorialDecision ? $decision->created_at->toIso8601String() : null,
            'round' => $article->review_round,
        ];
    }

    /**
     * @param  array<int, UploadedFile>  $supplementary
     */
    public function resubmit(
        Article $article,
        User $author,
        UploadedFile $manuscript,
        string $response,
        array $supplementary = [],
    ): ArticleVersion {
        if ($article->status !== ArticleStatus::RevisionRequired) {
            throw ValidationException::withMessages([
                'manuscript' => __("Maqolaning hozirgi holatida («:status») tuzatilgan versiya yuborib bo'lmaydi.", [
                    'status' => $article->status->label(),
                ]),
            ]);
        }

        $version = DB::transaction(function () use ($article, $author, $manuscript, $response, $supplementary): ArticleVersion {
            $number = (int) $article->versions()->max('version_number') + 1;

            $version = $article->versions()->create([
                'version_number' => $number,
                'type' => ArticleVersionType::Revision,
                'review_round' => $article->review_round,
                'language' => $article->language,
                'change_note' => $response,
                'created_by' => $author->id,
            ]);

            $this->files->store($article, $manuscript, ArticleFileType::Revision, $author, $version);

            foreach (array_slice($supplementary, 0, self::MAX_SUPPLEMENTARY) as $file) {
                $this->files->store($article, $file, ArticleFileType::Supplementary, $author, $version);
            }

            $this->workflow->transition(
                $article,
                ArticleStatus::Resubmitted,
                $author,
                __("Tuzatilgan versiya (v:number) yuborildi. Tahririyat qayta ko'rib chiqadi.", ['number' => $number]),
            );

            return $version;
        });

        $article->handlingEditor?->notify(new ArticleUpdateNotification(
            $article,
            ArticleUpdateNotification::RESUBMITTED,
            __('Tuzatilgan versiya yuborildi (v:number)', ['number' => $version->version_number]),
            $response,
            toStaff: true,
        ));

        return $version;
    }
}
