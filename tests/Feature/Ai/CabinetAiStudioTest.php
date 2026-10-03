<?php

namespace Tests\Feature\Ai;

use App\Enums\AiRequestStatus;
use App\Enums\ArticleStatus;
use App\Models\AiRequest;
use App\Models\Article;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Muallif kabineti → AI Studio: o'z maqolasiga biriktirish, natijani Word sifatida yuklab olish.
 */
class CabinetAiStudioTest extends TestCase
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

        Http::fake(['api.anthropic.com/*' => Http::response([
            'model' => 'test-model',
            'content' => [['type' => 'text', 'text' => (string) json_encode([
                'issues' => [['original' => 'talim', 'suggestion' => "ta'lim", 'type' => 'spelling', 'reason' => 'Imlo']],
                'score' => 90,
                'summary' => 'Yaxshi',
            ], JSON_UNESCAPED_UNICODE)]],
            'usage' => ['input_tokens' => 100, 'output_tokens' => 50],
            'stop_reason' => 'end_turn',
        ])]);
    }

    private function article(User $author): Article
    {
        return Article::factory()->status(ArticleStatus::Submitted)->createOne(['submitter_id' => $author->id]);
    }

    public function test_author_uses_studio_with_own_articles(): void
    {
        $author = User::factory()->author()->createOne();
        $own = $this->article($author);
        $foreign = $this->article(User::factory()->author()->createOne());

        $this->actingAs($author)
            ->get(route('cabinet.ai.index', ['article' => $own->uuid]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('cabinet/ai/Index')
                ->where('canManage', false)
                ->where('settings', null)
                ->where('filters.article', $own->uuid)
                ->where('budget.limit', 50_000)
                ->has('articles', 1)
                ->where('articles.0.value', $own->uuid)
            );

        // Begona maqolaga biriktirib bo'lmaydi
        $this->actingAs($author)
            ->post(route('cabinet.ai.requests.store'), [
                'type' => 'spell_check', 'text' => self::TEXT, 'source_language' => 'uz', 'article' => $foreign->uuid,
            ])
            ->assertSessionHasErrors('article');

        $this->actingAs($author)
            ->post(route('cabinet.ai.requests.store'), [
                'type' => 'spell_check', 'text' => self::TEXT, 'source_language' => 'uz', 'article' => $own->uuid,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirectContains('/cabinet/ai');

        $request = AiRequest::query()->latest('id')->firstOrFail();
        $this->assertSame($own->id, $request->article_id);
        $this->assertSame(AiRequestStatus::Completed, $request->status);

        // Tahrirlangan matn Word sifatida (qabul qilingan takliflar bilan)
        $issue = $request->result['issues'][0]['id'] ?? '';
        $this->actingAs($author)
            ->put(route('cabinet.ai.requests.proofread', $request->uuid), ['decisions' => [$issue => 'accepted']])
            ->assertSessionHasNoErrors();

        $this->actingAs($author)
            ->get(route('cabinet.ai.requests.download', $request->uuid))
            ->assertOk()
            ->assertDownload();

        // Maqola sahifasida AI bloki
        $this->actingAs($author)
            ->get(route('cabinet.articles.show', $own->uuid))
            ->assertInertia(fn (Assert $page) => $page
                ->where('ai.url', route('cabinet.ai.index', ['article' => $own->uuid]))
                ->has('ai.requests', 1)
                ->where('ai.requests.0.uuid', $request->uuid)
            );

        // Boshqa muallif natijani ko'ra olmaydi, admin bo'limi yopiq
        $other = User::factory()->author()->createOne();
        $this->actingAs($other)->get(route('cabinet.ai.requests.download', $request->uuid))->assertForbidden();
        $this->actingAs($author)->get(route('admin.ai.index'))->assertForbidden();
    }
}
