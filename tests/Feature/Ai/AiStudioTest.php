<?php

namespace Tests\Feature\Ai;

use App\Enums\AiRequestStatus;
use App\Enums\AiRequestType;
use App\Enums\RoleName;
use App\Models\AiRequest;
use App\Models\AuditLog;
use App\Models\PromptTemplate;
use App\Models\Setting;
use App\Models\Translation;
use App\Models\User;
use App\Services\Ai\AiSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * AI Studio: Proofreader, Translator, Analytics, limitlar va sozlamalar.
 * Anthropic API Http::fake bilan almashtiriladi; navbat testda sinxron (QUEUE_CONNECTION=sync).
 */
class AiStudioTest extends TestCase
{
    use RefreshDatabase;

    private const TEXT = "Mazkur maqolada zamonaviy talim texnologiyalari tahlil qilinadi.\n\nTadqiqotchilar bu sohada ishlarni olib bormoqdalar.";

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'ai.api_key' => 'sk-ant-test-key-0000000000000',
            'ai.model' => 'test-model',
            'ai.enabled' => true,
        ]);
    }

    /**
     * @param  array<string, mixed>|string  $text
     */
    private function fakeAnthropic(array|string $text, int $status = 200): void
    {
        $body = $status === 200
            ? [
                'model' => 'test-model',
                'content' => [['type' => 'text', 'text' => is_string($text) ? $text : (string) json_encode($text, JSON_UNESCAPED_UNICODE)]],
                'usage' => ['input_tokens' => 120, 'output_tokens' => 80],
                'stop_reason' => 'end_turn',
            ]
            : ['type' => 'error', 'error' => ['type' => 'authentication_error', 'message' => 'invalid x-api-key']];

        Http::fake(['api.anthropic.com/*' => Http::response($body, $status)]);
    }

    private function editor(): User
    {
        return User::factory()->withRole(RoleName::Editor)->createOne();
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    private function submit(User $user, string $type, array $extra = []): AiRequest
    {
        $this->actingAs($user)
            ->post(route('admin.ai.requests.store'), [
                'type' => $type,
                'text' => self::TEXT,
                'source_language' => 'uz',
                ...$extra,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        return AiRequest::query()->latest('id')->firstOrFail();
    }

    public function test_staff_open_studio_and_only_manager_sees_settings(): void
    {
        $editor = $this->editor();

        $this->actingAs($editor)
            ->get(route('admin.ai.index', ['tab' => 'settings']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/ai/Index')
                ->where('tab', 'proofreader')
                ->where('ready', true)
                ->where('canManage', false)
                ->where('settings', null)
                ->where('budget.limit', 200_000)
            );

        $this->actingAs($editor)->put(route('admin.ai.settings.update'), [])->assertForbidden();

        $admin = User::factory()->withRole(RoleName::SuperAdmin)->createOne();

        $this->actingAs($admin)
            ->get(route('admin.ai.index', ['tab' => 'settings']))
            ->assertInertia(fn (Assert $page) => $page
                ->where('tab', 'settings')
                ->has('settings.prompts', 3)
                ->where('settings.values.model', 'test-model')
                ->where('settings.values.keySource', 'env')
            );

        $this->actingAs(User::factory()->author()->createOne())
            ->get(route('admin.ai.index'))
            ->assertForbidden();
    }

    public function test_proofreader_finds_issues_and_applies_only_accepted(): void
    {
        $this->fakeAnthropic([
            'issues' => [
                ['original' => 'talim', 'suggestion' => "ta'lim", 'type' => 'spelling', 'reason' => 'Imlo xatosi'],
                ['original' => 'olib bormoqdalar', 'suggestion' => 'olib borishmoqda', 'type' => 'grammar', 'reason' => 'Moslashuv'],
                ['original' => 'matnda yo\'q bo\'lak', 'suggestion' => 'x', 'type' => 'style', 'reason' => ''],
            ],
            'score' => 84,
            'summary' => 'Matn yaxshi.',
        ]);

        $editor = $this->editor();
        $request = $this->submit($editor, 'spell_check', ['checks' => ['spelling', 'style']]);

        $this->assertSame(AiRequestStatus::Completed, $request->status);
        $this->assertSame(240, $request->totalTokens());
        $this->assertCount(2, $request->result['issues'] ?? []);

        Http::assertSent(fn (HttpRequest $http): bool => $http->hasHeader('x-api-key', 'sk-ant-test-key-0000000000000')
            && $http['model'] === 'test-model'
            && str_contains((string) $http['system'], 'imlo va grammatik xatolar; uslub va ifoda')
            && str_contains((string) $http['messages'][0]['content'], 'olib bormoqdalar'));

        $this->actingAs($editor)
            ->get(route('admin.ai.index', ['request' => $request->uuid]))
            ->assertInertia(fn (Assert $page) => $page
                ->where('current.status', 'completed')
                ->where('current.proofread.score', 84)
                ->where('current.proofread.counts.errors', 2)
                ->has('current.proofread.segments', 5)
            );

        $issues = $request->result['issues'];

        $this->actingAs($editor)
            ->put(route('admin.ai.requests.proofread', $request->uuid), [
                'decisions' => [$issues[0]['id'] => 'accepted', $issues[1]['id'] => 'rejected'],
            ])
            ->assertSessionHasNoErrors();

        $request->refresh();
        $this->assertStringContainsString("zamonaviy ta'lim texnologiyalari", (string) $request->output_text);
        $this->assertStringContainsString('olib bormoqdalar', (string) $request->output_text);

        // Boshqa foydalanuvchi natijani o'zgartira olmaydi
        $this->actingAs($this->editor())
            ->put(route('admin.ai.requests.proofread', $request->uuid), ['decisions' => [$issues[1]['id'] => 'accepted']])
            ->assertForbidden();
    }

    public function test_translation_creates_versions_and_downloads_docx(): void
    {
        $this->fakeAnthropic("In this article, modern educational technologies are analysed.\n\nResearchers are working in this field.");

        $editor = $this->editor();
        $request = $this->submit($editor, 'translation', ['target_language' => 'en']);

        $this->assertSame(AiRequestStatus::Completed, $request->status);
        $translation = Translation::query()->where('uuid', $request->result['translation'] ?? '')->firstOrFail();
        $this->assertSame(1, $translation->versions()->count());

        Http::assertSent(fn (HttpRequest $http): bool => str_contains((string) $http['system'], "o'zbek tilidan ingliz tiliga"));

        $this->actingAs($editor)
            ->post(route('admin.ai.translations.versions', $translation->uuid), ['content' => 'Edited translation text.'])
            ->assertSessionHasNoErrors();

        $this->assertSame(2, $translation->versions()->count());
        $this->assertSame('Edited translation text.', $translation->latestVersion()->firstOrFail()->content);

        $this->actingAs($editor)
            ->get(route('admin.ai.translations.download', [$translation->uuid, 'version' => 1]))
            ->assertOk()
            ->assertDownload();

        $this->actingAs($this->editor())
            ->get(route('admin.ai.translations.download', $translation->uuid))
            ->assertForbidden();

        // Bir xil til — rad etiladi
        $this->actingAs($editor)
            ->post(route('admin.ai.requests.store'), [
                'type' => 'translation', 'text' => self::TEXT, 'source_language' => 'uz', 'target_language' => 'uz',
            ])
            ->assertSessionHasErrors('target_language');
    }

    public function test_analysis_stores_metrics(): void
    {
        $this->fakeAnthropic('Natija: {"score":78,"metrics":{"academic_style":80,"clarity":75,"structure":70,"terminology":85,"coherence":72},"strengths":["Aniq maqsad"],"weaknesses":["Manbalar kam"],"recommendations":["Xulosa qo\'shing"],"summary":"Yaxshi"}');

        $request = $this->submit($this->editor(), 'analysis');

        $this->assertSame(AiRequestStatus::Completed, $request->status);
        $this->assertSame(78, $request->result['score'] ?? null);
        $this->assertSame(85, $request->result['metrics']['terminology'] ?? null);
        $this->assertSame(['Aniq maqsad'], $request->result['strengths'] ?? null);
    }

    public function test_monthly_limit_blocks_and_api_errors_are_reported(): void
    {
        $editor = $this->editor();
        $editor->forceFill(['ai_monthly_token_limit' => 100])->save();

        $this->actingAs($editor)
            ->post(route('admin.ai.requests.store'), ['type' => 'analysis', 'text' => self::TEXT, 'source_language' => 'uz'])
            ->assertSessionHasErrors('text');

        $this->assertSame(0, AiRequest::query()->count());

        // 0 — cheklanmagan; API kaliti noto'g'ri bo'lsa so'rov "Xato" bo'ladi
        $editor->forceFill(['ai_monthly_token_limit' => 0])->save();
        $this->fakeAnthropic('', 401);

        $request = $this->submit($editor, 'analysis');

        $this->assertSame(AiRequestStatus::Failed, $request->status);
        $this->assertStringContainsString('API kaliti', (string) $request->error_message);
    }

    public function test_service_must_be_configured(): void
    {
        config(['ai.api_key' => null]);

        $this->actingAs($this->editor())
            ->post(route('admin.ai.requests.store'), ['type' => 'analysis', 'text' => self::TEXT, 'source_language' => 'uz'])
            ->assertSessionHasErrors('text');
    }

    public function test_super_admin_manages_key_prompts_and_limits(): void
    {
        $admin = User::factory()->withRole(RoleName::SuperAdmin)->createOne();
        $plain = 'sk-ant-api03-new-secret-key-1234567890';

        $this->actingAs($admin)
            ->put(route('admin.ai.settings.update'), [
                'enabled' => true,
                'api_key' => $plain,
                'model' => 'claude-new-model',
                'author_monthly_limit' => 10_000,
                'staff_monthly_limit' => 0,
                'max_input_chars' => 20_000,
            ])
            ->assertSessionHasNoErrors();

        $stored = Setting::query()->where('group', 'ai')->where('key', 'api_key')->firstOrFail();
        $this->assertTrue($stored->is_encrypted);
        $this->assertNotSame($plain, $stored->value);
        $this->assertSame($plain, app(AiSettings::class)->apiKey());
        $this->assertDatabaseHas('audit_logs', ['event' => 'ai.settings']);
        $this->assertFalse(AuditLog::query()->where('properties', 'like', '%'.$plain.'%')->exists());

        $this->actingAs($admin)
            ->get(route('admin.ai.index', ['tab' => 'settings']))
            ->assertInertia(fn (Assert $page) => $page
                ->where('settings.values.keySource', 'database')
                ->where('settings.values.maskedKey', fn (string $masked): bool => ! str_contains($masked, 'secret'))
                ->where('settings.values.model', 'claude-new-model')
                ->where('settings.values.authorLimit', 10_000)
            );

        $this->actingAs($admin)
            ->put(route('admin.ai.prompts.update', 'translator'), [
                'system_prompt' => str_repeat('Siz tarjimonsiz. {source_language} → {target_language}. ', 3),
                'user_prompt_template' => '{text}',
                'model' => null,
                'temperature' => 0.4,
                'max_tokens' => 4096,
                'is_active' => true,
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(4096, PromptTemplate::query()->where('key', 'translator')->value('max_tokens'));

        $this->actingAs($admin)->post(route('admin.ai.prompts.reset', 'translator'))->assertSessionHasNoErrors();
        $this->assertSame(8192, PromptTemplate::query()->where('key', 'translator')->value('max_tokens'));

        $author = User::factory()->author()->createOne();
        $this->actingAs($admin)->put(route('admin.ai.limits.update', $author->id), ['limit' => 5000])->assertSessionHasNoErrors();
        $this->assertSame(5000, $author->refresh()->ai_monthly_token_limit);

        $this->actingAs($admin)->put(route('admin.ai.limits.update', $author->id), ['limit' => null])->assertSessionHasNoErrors();
        $this->assertNull($author->refresh()->ai_monthly_token_limit);
    }

    public function test_history_is_scoped_to_owner(): void
    {
        $editor = $this->editor();
        AiRequest::factory()->count(2)->create(['user_id' => $editor->id, 'type' => AiRequestType::Analysis]);
        $foreign = AiRequest::factory()->createOne(['type' => AiRequestType::Analysis]);

        $this->actingAs($editor)
            ->get(route('admin.ai.index', ['tab' => 'history', 'scope' => 'all']))
            ->assertInertia(fn (Assert $page) => $page
                ->where('filters.scope', 'own')
                ->has('history.data', 2)
            );

        $this->actingAs($editor)
            ->get(route('admin.ai.index', ['tab' => 'analytics', 'request' => $foreign->uuid]))
            ->assertInertia(fn (Assert $page) => $page->where('current', null));
    }
}
