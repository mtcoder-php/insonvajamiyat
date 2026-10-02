<?php

namespace Tests\Feature\Admin;

use App\Enums\ArticleStatus;
use App\Enums\AuditEvent;
use App\Enums\PaymentProvider;
use App\Enums\ReviewStatus;
use App\Enums\RoleName;
use App\Models\Article;
use App\Models\ArticleAuthor;
use App\Models\AuditLog;
use App\Models\Payment;
use App\Models\Review;
use App\Models\Subject;
use App\Models\User;
use App\Services\Reports\AnalyticsService;
use App\Support\ReportPeriod;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Admin → Statistika va hisobotlar: ko'rsatkichlar, filtrlar, taqrizchilar, eksport va PDF hisobot.
 */
class ReportsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-09-20 12:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function admin(): User
    {
        return User::factory()->withRole(RoleName::SuperAdmin)->createOne();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function article(array $attributes = [], ?Subject $subject = null, string $country = 'UZ', string $organization = 'Toshkent davlat universiteti'): Article
    {
        $article = Article::factory()->createOne([
            'status' => ArticleStatus::Submitted,
            'submitted_at' => now()->subDays(3),
            'subject_id' => ($subject ?? Subject::factory()->createOne())->id,
            ...$attributes,
        ]);

        ArticleAuthor::factory()->for($article)->createOne([
            'last_name' => 'Karimov',
            'first_name' => 'Aziz',
            'country' => $country,
            'organization' => $organization,
            'sort_order' => 1,
        ]);

        return $article;
    }

    private function query(array $extra = []): array
    {
        return ['from' => '2026-09-01', 'to' => '2026-09-20', ...$extra];
    }

    public function test_only_users_with_reports_permission_can_open_page(): void
    {
        $this->actingAs(User::factory()->withRole(RoleName::Editor)->createOne())
            ->get(route('admin.reports.index'))
            ->assertForbidden();

        $this->actingAs(User::factory()->withRole(RoleName::ChiefEditor)->createOne())
            ->get(route('admin.reports.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('admin/reports/Index'));
    }

    public function test_overview_counts_period_metrics_and_compares_with_previous_period(): void
    {
        $history = Subject::factory()->createOne(['slug' => 'history']);

        $this->article(['accepted_at' => now()->subDay()], $history);
        $this->article(['status' => ArticleStatus::Rejected, 'rejected_at' => now()->subDays(2)], $history, 'KZ', 'Ilmiy tadqiqot markazi');
        $this->article();
        // Oldingi davr (2026-08-12 … 2026-08-31) — taqqoslash uchun
        $this->article(['submitted_at' => Carbon::parse('2026-08-20')]);
        // Davrdan tashqarida
        $this->article(['submitted_at' => Carbon::parse('2026-05-01')]);

        $this->actingAs($this->admin())
            ->get(route('admin.reports.index', $this->query()))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('filters.from', '2026-09-01')
                ->where('filters.to', '2026-09-20')
                ->where('filters.daily', true)
                ->where('kpis.0.key', 'submitted')
                ->where('kpis.0.value', 3)
                ->where('kpis.0.previous', 1)
                ->where('kpis.0.trend', 200)
                ->where('kpis.1.value', 1)
                ->where('kpis.2.value', 1)
                ->has('dynamics.labels', 20)
                ->where('subjectsChart.total', 3)
                ->where('subjectsChart.items.0.key', 'history')
                ->where('subjectsChart.items.0.value', 2)
                ->where('countries.total', 2)
                ->has('countries.items', 2)
                ->where('organizations.total', 2)
                ->where('articles.meta.total', 3)
                ->has('quick', 4)
                ->has('exports', 4)
                ->where('reviewers', null)
            );
    }

    public function test_subject_and_table_filters(): void
    {
        $history = Subject::factory()->createOne(['slug' => 'history']);

        $this->article(['title' => ['uz' => 'Amir Temur davlatchiligi']], $history);
        $this->article(['status' => ArticleStatus::Rejected, 'rejected_at' => now()->subDay()]);

        $admin = $this->admin();

        $this->actingAs($admin)
            ->get(route('admin.reports.index', $this->query(['subject' => 'history'])))
            ->assertInertia(fn (Assert $page) => $page
                ->where('filters.subject', 'history')
                ->where('kpis.0.value', 1)
                ->where('articles.meta.total', 1)
            );

        $this->actingAs($admin)
            ->get(route('admin.reports.index', $this->query(['status' => 'rejected'])))
            ->assertInertia(fn (Assert $page) => $page
                ->where('filters.status', 'rejected')
                ->where('articles.meta.total', 1)
                ->where('articles.data.0.status', 'rejected')
            );

        $this->actingAs($admin)
            ->get(route('admin.reports.index', $this->query(['q' => 'temur'])))
            ->assertInertia(fn (Assert $page) => $page->where('articles.meta.total', 1));
    }

    public function test_revenue_groups_paid_payments_by_provider(): void
    {
        Payment::factory()->paid(now()->subDays(2))->createOne(['provider' => PaymentProvider::Click, 'amount' => 150000]);
        Payment::factory()->paid(now()->subDays(1))->createOne(['provider' => PaymentProvider::Payme, 'amount' => 200000]);
        Payment::factory()->createOne(['provider' => PaymentProvider::Click, 'amount' => 999000]); // to'lanmagan

        $this->actingAs($this->admin())
            ->get(route('admin.reports.index', $this->query()))
            ->assertInertia(fn (Assert $page) => $page
                ->where('revenue.total', 350000)
                ->where('revenue.count', 2)
            );
    }

    public function test_reviewers_tab_shows_workload(): void
    {
        $reviewer = User::factory()->withRole(RoleName::Reviewer)->createOne();
        $article = $this->article();

        Review::factory()->for($article)->status(ReviewStatus::Completed)->createOne([
            'reviewer_id' => $reviewer->id,
            'created_at' => now()->subDays(10),
            'completed_at' => now()->subDays(4),
            'due_at' => now()->subDays(2),
        ]);
        Review::factory()->for($article)->createOne([
            'reviewer_id' => $reviewer->id,
            'created_at' => now()->subDays(5),
            'due_at' => now()->subDay(),
        ]);

        $this->actingAs($this->admin())
            ->get(route('admin.reports.index', $this->query(['tab' => 'reviewers'])))
            ->assertInertia(fn (Assert $page) => $page
                ->where('filters.tab', 'reviewers')
                ->where('dynamics', null)
                ->where('reviewers.totals.completed', 1)
                ->where('reviewers.totals.pending', 1)
                ->where('reviewers.totals.overdue', 1)
                ->where('reviewers.rows.0.id', $reviewer->id)
                ->where('reviewers.rows.0.avgDays', fn (mixed $days): bool => (float) $days === 6.0)
                ->where('reviewers.rows.0.onTime', 100)
            );
    }

    public function test_csv_export_streams_excel_friendly_file_and_is_audited(): void
    {
        $this->article(['title' => ['uz' => '=Xavfli sarlavha']]);

        $response = $this->actingAs($admin = $this->admin())
            ->get(route('admin.reports.export', ['type' => 'articles', ...$this->query()]));

        $response->assertOk();
        $this->assertStringContainsString('text/csv', (string) $response->headers->get('Content-Type'));

        $csv = $response->streamedContent();
        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv);
        $this->assertStringContainsString('Sarlavha;Mualliflar', $csv);
        $this->assertStringContainsString("'=Xavfli sarlavha", $csv);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'event' => AuditEvent::ReportExported->value,
        ]);

        $this->actingAs($admin)
            ->get('/admin/reports/export/unknown')
            ->assertNotFound();
    }

    public function test_print_summary_page(): void
    {
        $this->article();

        $this->actingAs($this->admin())
            ->get(route('admin.reports.print', $this->query()))
            ->assertOk()
            ->assertSee('Statistik hisobot')
            ->assertSee('01.09.2026 – 20.09.2026');

        $this->assertSame(1, AuditLog::query()->where('event', AuditEvent::ReportExported->value)->count());
    }

    public function test_report_period_defaults_and_buckets(): void
    {
        $default = ReportPeriod::make();
        $this->assertSame('2026-04-01', $default->from->toDateString());
        $this->assertSame('2026-09-20', $default->to->toDateString());
        $this->assertFalse($default->isDaily());
        $this->assertCount(6, $default->buckets());

        $week = ReportPeriod::make('2026-09-14', '2026-09-20');
        $this->assertSame(['2026-09-07', '2026-09-13'], [$week->previous()->from->toDateString(), $week->previous()->to->toDateString()]);
        $this->assertSame([0, 2, 0, 0, 0, 0, 1], $week->series([['2026-09-15 10:00', 1], ['2026-09-15', 1], ['2026-09-20 23:00', 1], ['2026-08-01', 5]]));

        // Kelajakdagi sana bugun bilan cheklanadi, noto'g'ri qiymat e'tiborsiz qoldiriladi
        $this->assertSame('2026-09-20', ReportPeriod::make('bad', '2030-01-01')->to->toDateString());
        $this->assertSame('other', AnalyticsService::organizationType('Beer-Fay LLC'));
        $this->assertSame('university', AnalyticsService::organizationType("O'zbekiston Milliy universiteti"));
    }
}
