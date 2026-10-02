<?php

namespace Tests\Feature\Admin;

use App\Enums\ArticleStatus;
use App\Enums\AuditEvent;
use App\Enums\RoleName;
use App\Models\Article;
use App\Models\User;
use App\Notifications\ArticleUpdateNotification;
use App\Services\Production\ProductionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Korrektura muddati: muddat belgilanishi, eslatmalar, muallifsiz tasdiqlash va kabinetdagi holat.
 */
class ProofDeadlineTest extends TestCase
{
    use RefreshDatabase;

    private User $layout;

    private User $chief;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        config(['journal.proof.deadline_days' => 5, 'journal.proof.reminder_hours' => 24]);
        $this->layout = User::factory()->withRole(RoleName::LayoutEditor)->createOne();
        $this->chief = User::factory()->withRole(RoleName::ChiefEditor)->createOne();
    }

    private function articleWithProof(): Article
    {
        $article = Article::factory()->withAuthors()->status(ArticleStatus::InProduction)->createOne([
            'submitter_id' => User::factory()->author()->createOne()->id,
            'submitted_at' => now()->subMonth(),
            'layout_editor_id' => $this->layout->id,
        ]);

        $this->actingAs($this->layout)
            ->post(route('admin.production.final-pdf', $article->uuid), [
                'pdf' => UploadedFile::fake()->create('final.pdf', 400, 'application/pdf'),
            ])
            ->assertSessionHasNoErrors();

        return $article->refresh();
    }

    private function state(Article $article): string
    {
        return app(ProductionService::class)->proofState($article->refresh())['state'];
    }

    public function test_final_pdf_sets_deadline_and_author_sees_it(): void
    {
        $this->travelTo(now()->setDateTime(2026, 9, 1, 10, 0));
        $article = $this->articleWithProof();

        $this->assertSame('2026-09-06 10:00', app(ProductionService::class)->proofDueAt($article)?->format('Y-m-d H:i'));
        $this->assertSame('pending', $this->state($article));

        $this->actingAs($article->submitter)
            ->get(route('cabinet.articles.show', $article->uuid))
            ->assertInertia(fn (Assert $page) => $page
                ->where('production.state', 'pending')
                ->where('production.deadlineDays', 5)
                ->where('production.dueAt', fn (?string $due): bool => str_starts_with((string) $due, '2026-09-06T10:00'))
            );
    }

    public function test_reminders_are_sent_once_before_and_after_deadline(): void
    {
        Notification::fake();
        $article = $this->articleWithProof();
        $service = app(ProductionService::class);

        // Muddatgacha 2 kun — hali eslatma yo'q
        $this->travel(3)->days();
        $this->assertSame(['reminded' => 0, 'overdue' => 0], $service->sendProofReminders());

        // 24 soatdan kam qoldi — muallifga eslatma, qayta yuborilmaydi
        $this->travel(1)->days();
        $this->travel(2)->hours();
        $this->assertSame(['reminded' => 1, 'overdue' => 0], $service->sendProofReminders());
        $this->assertSame(['reminded' => 0, 'overdue' => 0], $service->sendProofReminders());

        // Muddat o'tdi — maketchi va bosh muharrirga bir marta
        $this->travel(1)->days();
        $this->artisan('app:proof-reminders')->assertSuccessful();
        $this->assertSame('overdue', $this->state($article));
        $this->assertSame(['reminded' => 0, 'overdue' => 0], $service->sendProofReminders());

        Notification::assertSentToTimes($article->submitter, ArticleUpdateNotification::class, 2); // PDF tayyor + eslatma
        Notification::assertSentTo($this->chief, ArticleUpdateNotification::class, fn (ArticleUpdateNotification $n): bool => $n->toStaff);
        Notification::assertSentTo($this->layout, ArticleUpdateNotification::class, fn (ArticleUpdateNotification $n): bool => $n->toStaff);
    }

    public function test_chief_can_waive_only_after_deadline_with_reason(): void
    {
        Notification::fake();
        $article = $this->articleWithProof();

        // Muddat o'tmagan
        $this->actingAs($this->chief)
            ->post(route('admin.production.waive-proof', $article->uuid), ['reason' => 'Muallif javob bermayapti, son kechikmoqda.'])
            ->assertSessionHasErrors('reason');

        $this->travel(6)->days();

        // Sabab majburiy, maketchida huquq yo'q
        $this->actingAs($this->chief)
            ->post(route('admin.production.waive-proof', $article->uuid), ['reason' => 'qisqa'])
            ->assertSessionHasErrors('reason');
        $this->actingAs($this->layout)
            ->post(route('admin.production.waive-proof', $article->uuid), ['reason' => 'Muallif javob bermayapti, son kechikmoqda.'])
            ->assertForbidden();

        $this->actingAs($this->chief)
            ->get(route('admin.production.show', $article->uuid))
            ->assertInertia(fn (Assert $page) => $page
                ->where('article.production.proof.state', 'overdue')
                ->where('article.can.waiveProof', true)
            );

        $this->actingAs($this->chief)
            ->post(route('admin.production.waive-proof', $article->uuid), ['reason' => 'Muallif javob bermayapti, son kechikmoqda.'])
            ->assertSessionHasNoErrors();

        $this->assertSame('waived', $this->state($article));
        $this->assertTrue(app(ProductionService::class)->proofResolved($article));
        $this->assertDatabaseHas('audit_logs', [
            'event' => AuditEvent::ProofApprovalWaived->value,
            'user_id' => $this->chief->id,
            'subject_id' => $article->id,
        ]);
        Notification::assertSentTo($article->submitter, ArticleUpdateNotification::class, fn (ArticleUpdateNotification $n): bool => $n->body === 'Muallif javob bermayapti, son kechikmoqda.');

        $check = collect(app(ProductionService::class)->checks($article))->firstWhere('key', 'author');
        $this->assertTrue($check['ok'] ?? false);

        // Muallif keyin xato topsa — tuzatish so'rovi qarorni bekor qiladi
        $this->actingAs($article->submitter)
            ->post(route('cabinet.articles.proof.changes', $article->uuid), ['comment' => '3-betda familiyam xato yozilgan.'])
            ->assertSessionHasNoErrors();

        $this->assertSame('changes', $this->state($article));

        // Yangi PDF — yangi muddat
        $this->actingAs($this->layout)
            ->post(route('admin.production.final-pdf', $article->uuid), [
                'pdf' => UploadedFile::fake()->create('final-v2.pdf', 400, 'application/pdf'),
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame('pending', $this->state($article));
    }

    public function test_author_can_still_approve_after_deadline(): void
    {
        $article = $this->articleWithProof();
        $this->travel(7)->days();

        $this->actingAs($article->submitter)
            ->post(route('cabinet.articles.proof.approve', $article->uuid))
            ->assertSessionHasNoErrors();

        $this->assertSame('approved', $this->state($article));
        $this->assertFalse(app(ProductionService::class)->canWaiveAuthor($article->refresh()));
    }
}
