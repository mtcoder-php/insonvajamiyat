<?php

namespace Tests\Feature;

use App\Enums\ArticleStatus;
use App\Models\Article;
use App\Models\JournalIssue;
use App\Models\User;
use App\Notifications\ArticleUpdateNotification;
use App\Services\Issues\IssueWorkspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Ishga tushirishdan oldingi samaradorlik auditi (71-bosqich).
 */
class PerformanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_article_notification_mail_is_queued_but_bell_is_immediate(): void
    {
        // Production kabi: haqiqiy navbat (database)
        config(['queue.default' => 'database']);
        $author = User::factory()->author()->createOne();
        $article = Article::factory()->status(ArticleStatus::UnderReview)->createOne(['submitter_id' => $author->id]);

        $author->notify(new ArticleUpdateNotification($article, ArticleUpdateNotification::DECISION, 'Qaror qabul qilindi'));

        // Qo'ng'iroqcha uchun yozuv darhol bazada
        $this->assertSame(1, $author->notifications()->count());

        // Email — navbatda (SMTP sahifani kutdirmaydi)
        $jobs = DB::table('jobs')->pluck('payload');
        $this->assertCount(1, $jobs);
        $this->assertStringContainsString('SendQueuedNotifications', (string) $jobs->first());
        $this->assertStringContainsString('mail', (string) $jobs->first());
    }

    public function test_admin_issue_list_has_constant_query_count(): void
    {
        $workspace = app(IssueWorkspace::class);
        $count = function () use ($workspace): int {
            DB::flushQueryLog();
            DB::enableQueryLog();
            $workspace->list(null, null);
            $queries = count(DB::getQueryLog());
            DB::disableQueryLog();

            return $queries;
        };

        $issue = JournalIssue::factory()->createOne();
        $issue->articles()->attach(Article::factory()->status(ArticleStatus::Accepted)->createOne()->id, ['position' => 1, 'page_from' => 1, 'page_to' => 9]);
        $single = $count();

        foreach (range(1, 4) as $i) {
            $more = JournalIssue::factory()->createOne();
            $more->articles()->attach(Article::factory()->status(ArticleStatus::Accepted)->createOne()->id, ['position' => 1, 'page_from' => 1, 'page_to' => 5 + $i]);
        }

        $this->assertSame($single, $count(), 'Sonlar ko\'payganda so\'rovlar soni oshmasligi kerak (N+1)');

        $row = collect($workspace->list(null, null))->firstWhere('slug', $issue->slug);
        $this->assertSame(1, $row['articles'] ?? null);
        $this->assertSame(9, $row['pages'] ?? null);
    }

    public function test_performance_indexes_exist(): void
    {
        $this->assertTrue(Schema::hasIndex('articles', 'articles_status_published_at_index'));
        $this->assertTrue(Schema::hasIndex('articles', 'articles_submitted_at_index'));
        $this->assertTrue(Schema::hasIndex('notifications', 'notifications_notifiable_read_at_index'));
        $this->assertTrue(Schema::hasIndex('ai_requests', 'ai_requests_created_at_index'));
        $this->assertTrue(Schema::hasIndex('payments', 'payments_status_paid_at_index'));
    }
}
