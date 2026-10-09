<?php

namespace App\Services\Newsletter;

use App\Enums\AuditEvent;
use App\Enums\IssueStatus;
use App\Enums\Language;
use App\Jobs\SendNewsletterCampaign;
use App\Models\Article;
use App\Models\ArticleAuthor;
use App\Models\JournalIssue;
use App\Models\NewsletterCampaign;
use App\Models\NewsletterSubscriber;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Services\Settings\SettingsStore;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

/**
 * Admin → Obuna: obunachilar statistikasi va ro'yxati, CSV eksport, xat yuborish,
 * yangi son chop etilganda avtomatik xat (sozlama: newsletter.auto_issue, standart — yoqiq).
 */
class NewsletterCampaignService
{
    public const SETTINGS_GROUP = 'newsletter';

    public const AUTO_ISSUE = 'auto_issue';

    /** Yangi son xatida ko'rsatiladigan maqolalar soni */
    private const ISSUE_ARTICLES_LIMIT = 8;

    public function __construct(
        private readonly AuditLogger $audit,
        private readonly SettingsStore $settings,
    ) {}

    /**
     * @return array{confirmed: int, pending: int, unsubscribed: int, campaigns: int}
     */
    public function stats(): array
    {
        return [
            'confirmed' => NewsletterSubscriber::query()->confirmed()->count(),
            'pending' => NewsletterSubscriber::query()->whereNull('confirmed_at')->whereNull('unsubscribed_at')->count(),
            'unsubscribed' => NewsletterSubscriber::query()->whereNotNull('unsubscribed_at')->count(),
            'campaigns' => NewsletterCampaign::query()->where('status', NewsletterCampaign::SENT)->count(),
        ];
    }

    /**
     * Kimga: barcha tasdiqlangan obunachilar yoki bitta til.
     *
     * @return array<int, array{value: string, label: string, count: int}>
     */
    public function audiences(): array
    {
        $byLocale = NewsletterSubscriber::query()
            ->confirmed()
            ->selectRaw('locale, count(*) as total')
            ->groupBy('locale')
            ->pluck('total', 'locale');

        $audiences = [[
            'value' => 'all',
            'label' => __('Barcha obunachilar'),
            'count' => (int) $byLocale->sum(),
        ]];

        foreach (Language::cases() as $language) {
            $audiences[] = [
                'value' => $language->value,
                'label' => $language->label(),
                'count' => (int) ($byLocale[$language->value] ?? 0),
            ];
        }

        return $audiences;
    }

    public function autoIssueEnabled(): bool
    {
        return (bool) $this->settings->get(self::SETTINGS_GROUP, self::AUTO_ISSUE, true);
    }

    public function setAutoIssue(bool $enabled, User $by): void
    {
        $this->settings->set(self::SETTINGS_GROUP, self::AUTO_ISSUE, $enabled, $by, description: 'Yangi son chop etilganda obunachilarga avtomatik xat');
        $this->audit->log(AuditEvent::SettingsUpdated, null, ['newsletter.auto_issue' => $enabled], actor: $by);
    }

    /**
     * @param  array{subject: string, body: string, audience: string, button_label?: string|null, button_url?: string|null, journal_issue_id?: int|null}  $data
     */
    public function send(array $data, ?User $sender, string $kind = NewsletterCampaign::MANUAL): NewsletterCampaign
    {
        $campaign = new NewsletterCampaign([
            'kind' => $kind,
            'subject' => $data['subject'],
            'body' => $data['body'],
            'button_label' => filled($data['button_url'] ?? null) ? ($data['button_label'] ?? null) ?: __('Batafsil') : null,
            'button_url' => ($data['button_url'] ?? null) ?: null,
            'locale' => $data['audience'] === 'all' ? null : $data['audience'],
            'journal_issue_id' => $data['journal_issue_id'] ?? null,
            'sender_id' => $sender?->id,
        ]);

        $recipients = $campaign->recipients()->count();

        if ($recipients === 0) {
            throw ValidationException::withMessages([
                'audience' => __("Tanlangan guruhda tasdiqlangan obunachi yo'q."),
            ]);
        }

        $campaign->forceFill(['status' => NewsletterCampaign::QUEUED, 'recipients_count' => $recipients])->save();

        $this->audit->log(AuditEvent::NewsletterSent, $campaign, [
            'subject' => $campaign->subject,
            'kind' => $kind,
            'locale' => $campaign->locale ?? 'all',
            'recipients' => $recipients,
        ], actor: $sender);

        SendNewsletterCampaign::dispatch($campaign);

        return $campaign;
    }

    /**
     * Yangi son haqida xat matni (admin formasini to'ldirish va avtomatik xat uchun).
     *
     * @return array{subject: string, body: string, button_label: string, button_url: string}
     */
    public function issueTemplate(JournalIssue $issue): array
    {
        $journal = (string) config('journal.name', config('app.name'));
        $articles = $issue->articles()
            ->with('authors')
            ->limit(self::ISSUE_ARTICLES_LIMIT + 1)
            ->get();

        $lines = $articles->take(self::ISSUE_ARTICLES_LIMIT)->map(function (Article $article): string {
            $authors = $article->authors->map(fn (ArticleAuthor $a): string => $a->short_name)->take(3)->implode(', ');

            return '• '.Str::limit($article->title, 140).($authors !== '' ? ' — '.$authors : '');
        });

        $paragraphs = [
            __('«:journal» ilmiy jurnalining :issue soni chop etildi.', ['journal' => $journal, 'issue' => $issue->label]),
        ];

        if ($lines->isNotEmpty()) {
            $paragraphs[] = __('Ushbu sonda:')."\n".$lines->implode("\n").($articles->count() > self::ISSUE_ARTICLES_LIMIT ? "\n• ".__('va boshqa maqolalar') : '');
        }

        $paragraphs[] = __("Barcha maqolalar saytda ochiq — to'liq matn va PDF'ni yuklab olishingiz mumkin.");

        return [
            'subject' => __('Yangi son: «:journal» :issue', ['journal' => $journal, 'issue' => $issue->label]),
            'body' => implode("\n\n", $paragraphs),
            'button_label' => __("Sonni o'qish"),
            'button_url' => route('issues.show', $issue),
        ];
    }

    /**
     * Chop etilgan son uchun avtomatik xat (PublishService chaqiradi). Xatolik nashrni
     * buzmaydi; sozlama o'chiq, obunachi yo'q yoki shu songa xat yuborilgan bo'lsa — hech narsa.
     */
    public function announceIssue(JournalIssue $issue, ?User $publisher): ?NewsletterCampaign
    {
        try {
            if (! $this->autoIssueEnabled()
                || NewsletterSubscriber::query()->confirmed()->doesntExist()
                || NewsletterCampaign::query()->where('journal_issue_id', $issue->id)->where('kind', NewsletterCampaign::ISSUE)->exists()) {
                return null;
            }

            return $this->send([
                ...$this->issueTemplate($issue),
                'audience' => 'all',
                'journal_issue_id' => $issue->id,
            ], $publisher, NewsletterCampaign::ISSUE);
        } catch (Throwable $e) {
            Log::warning('Yangi son haqida obuna xati yuborilmadi', ['issue' => $issue->id, 'error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * Admin formasidagi "Yangi son haqida" tanlovi uchun: so'nggi chop etilgan sonlar
     * va har biri uchun tayyor xat matni (tanlanganda forma shu bilan to'ladi).
     *
     * @return array<int, array{id: int, label: string, announced: bool, template: array{subject: string, body: string, button_label: string, button_url: string}}>
     */
    public function issueOptions(int $limit = 6): array
    {
        $announced = NewsletterCampaign::query()->whereNotNull('journal_issue_id')->pluck('journal_issue_id')->all();

        return JournalIssue::query()
            ->where('status', IssueStatus::Published->value)
            ->latest('published_at')
            ->limit($limit)
            ->get()
            ->map(fn (JournalIssue $issue): array => [
                'id' => $issue->id,
                'label' => $issue->label,
                'announced' => in_array($issue->id, $announced, true),
                'template' => $this->issueTemplate($issue),
            ])
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function history(int $limit = 30): array
    {
        return NewsletterCampaign::query()
            ->with('sender:id,name')
            ->latest('id')
            ->limit($limit)
            ->get()
            ->map(fn (NewsletterCampaign $c): array => [
                'uuid' => $c->uuid,
                'kind' => $c->kind,
                'subject' => $c->subject,
                'body' => $c->body,
                'buttonUrl' => $c->button_url,
                'locale' => $c->locale,
                'status' => $c->status,
                'recipients' => $c->recipients_count,
                'sent' => $c->sent_count,
                'sender' => $c->sender?->name,
                'createdAt' => $c->created_at?->toIso8601String(),
                'error' => $c->error,
            ])
            ->all();
    }

    /**
     * @param  array{q?: string|null, status?: string|null}  $filters
     * @return LengthAwarePaginator<int, NewsletterSubscriber>
     */
    public function subscribers(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        return $this->filtered($filters)
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @param  array{q?: string|null, status?: string|null}  $filters
     */
    public function export(array $filters, User $by): StreamedResponse
    {
        $query = $this->filtered($filters);

        $this->audit->log(AuditEvent::ReportExported, null, ['report' => 'newsletter_subscribers', 'filters' => array_filter($filters)], actor: $by);

        return response()->streamDownload(function () use ($query): void {
            $out = fopen('php://output', 'w');

            if ($out === false) {
                return;
            }

            fwrite($out, "\xEF\xBB\xBF"); // Excel UTF-8 ni to'g'ri ochishi uchun
            fputcsv($out, ['email', 'til', 'holat', 'obuna sanasi', 'tasdiqlangan', 'chiqqan'], escape: '');

            $query->orderBy('id')->chunkById(500, function ($subscribers) use ($out): void {
                foreach ($subscribers as $s) {
                    /** @var NewsletterSubscriber $s */
                    fputcsv($out, [
                        $s->email,
                        $s->locale,
                        $this->state($s),
                        $s->created_at?->format('Y-m-d H:i'),
                        $s->confirmed_at?->format('Y-m-d H:i'),
                        $s->unsubscribed_at?->format('Y-m-d H:i'),
                    ], escape: '');
                }
            });

            fclose($out);
        }, 'obunachilar-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function delete(NewsletterSubscriber $subscriber, User $by): void
    {
        $this->audit->log(AuditEvent::ContentDeleted, null, ['type' => 'newsletter_subscriber', 'email' => $subscriber->email], actor: $by);
        $subscriber->delete();
    }

    /** confirmed | pending | unsubscribed */
    public function state(NewsletterSubscriber $subscriber): string
    {
        return match (true) {
            $subscriber->unsubscribed_at !== null => 'unsubscribed',
            $subscriber->confirmed_at !== null => 'confirmed',
            default => 'pending',
        };
    }

    /**
     * @param  array{q?: string|null, status?: string|null}  $filters
     * @return Builder<NewsletterSubscriber>
     */
    private function filtered(array $filters): Builder
    {
        $q = trim((string) ($filters['q'] ?? ''));

        return NewsletterSubscriber::query()
            ->when($q !== '', fn (Builder $query) => $query->where('email', 'like', '%'.addcslashes(Str::lower($q), '%_\\').'%'))
            ->when(($filters['status'] ?? null) === 'confirmed', fn (Builder $query) => $query->confirmed())
            ->when(($filters['status'] ?? null) === 'pending', fn (Builder $query) => $query->whereNull('confirmed_at')->whereNull('unsubscribed_at'))
            ->when(($filters['status'] ?? null) === 'unsubscribed', fn (Builder $query) => $query->whereNotNull('unsubscribed_at'));
    }
}
