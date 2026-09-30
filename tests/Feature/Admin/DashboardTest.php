<?php

namespace Tests\Feature\Admin;

use App\Enums\ArticleStatus;
use App\Enums\PaymentProvider;
use App\Enums\RoleName;
use App\Models\AiRequest;
use App\Models\Article;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Super admin dashboard: statistika, grafiklar va o'ng panel ma'lumotlari.
 */
class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_shares_all_sections(): void
    {
        $admin = User::factory()->withRole(RoleName::SuperAdmin)->create();

        Article::factory()->count(2)->status(ArticleStatus::Submitted)->create(['submitted_at' => now()->subDay()]);
        Article::factory()->status(ArticleStatus::UnderReview)->create(['submitted_at' => now()->subDays(3)]);
        Article::factory()->published(now()->subDays(2))->create();
        Article::factory()->create(); // qoralama — hisobga kirmaydi

        Payment::factory()->paid(now()->startOfYear()->addDays(3))->create([
            'provider' => PaymentProvider::Click,
            'amount' => 150000,
        ]);
        Payment::factory()->paid(now()->startOfYear()->addDays(4))->create([
            'provider' => PaymentProvider::Payme,
            'amount' => 200000,
        ]);
        Payment::factory()->create(['amount' => 99000]); // kutilmoqda — tushumga kirmaydi

        AiRequest::factory()->count(3)->create();

        DB::table('notifications')->insert([
            'id' => (string) Str::uuid(),
            'type' => 'dashboard',
            'notifiable_type' => $admin->getMorphClass(),
            'notifiable_id' => $admin->id,
            'data' => json_encode(['kind' => 'article_submitted', 'title' => 'Yangi maqola yuborildi', 'message' => 'Test']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $admin->forceFill(['last_login_at' => now()])->save();

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/Dashboard')
                ->has('cards', 5)
                ->where('cards.0.key', 'total')
                ->where('cards.0.value', 4)
                ->where('statusBreakdown.total', 4)
                ->has('dynamics.submitted', 12)
                ->has('latestSubmissions', 4)
                ->where('payments.total', 350000)
                ->where('payments.click.'.(now()->startOfYear()->addDays(3)->month - 1), 150000)
                ->has('recentPayments', 3)
                ->where('aiUsage.total', 3)
                ->has('notificationsList', 1)
                ->where('notificationsList.0.kind', 'article_submitted')
                ->where('activeUsers.0.id', $admin->id)
                ->has('systemHealth', 4)
                ->where('systemHealth.1.key', 'database')
                ->where('systemHealth.1.state', 'up')
            );
    }

    public function test_dashboard_works_with_empty_database(): void
    {
        $admin = User::factory()->withRole(RoleName::SuperAdmin)->create();

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('statusBreakdown.total', 0)
                ->where('payments.total', 0)
                ->has('latestSubmissions', 0)
                ->where('aiUsage.total', 0)
            );
    }
}
