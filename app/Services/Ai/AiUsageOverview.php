<?php

namespace App\Services\Ai;

use App\Enums\AiRequestStatus;
use App\Models\AiRequest;
use App\Models\PromptTemplate;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * AI Studio → Sozlamalar: kalit/model, limitlar, prompt shablonlari va foydalanuvchilar sarfi.
 */
class AiUsageOverview
{
    public function __construct(
        private readonly AiSettings $settings,
        private readonly TokenBudget $budget,
        private readonly PromptLibrary $prompts,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function settings(string $search = ''): array
    {
        $this->prompts->ensureDefaults();

        return [
            'values' => [
                'enabled' => $this->settings->enabled(),
                'model' => $this->settings->model(),
                'maskedKey' => $this->settings->maskedKey(),
                'keySource' => $this->settings->keySource(),
                'authorLimit' => $this->settings->authorLimit(),
                'staffLimit' => $this->settings->staffLimit(),
                'maxInputChars' => $this->settings->maxInputChars(),
            ],
            'prompts' => PromptTemplate::query()
                ->with('editor:id,name')
                ->orderBy('id')
                ->get()
                ->map(fn (PromptTemplate $p): array => [
                    'key' => $p->key,
                    'name' => $p->name,
                    'type' => $p->type->value,
                    'studio' => $p->type->studioName(),
                    'systemPrompt' => $p->system_prompt,
                    'userPromptTemplate' => $p->user_prompt_template,
                    'model' => $p->model,
                    'temperature' => (float) $p->temperature,
                    'maxTokens' => $p->max_tokens,
                    'isActive' => $p->is_active,
                    'updatedAt' => $p->updated_at?->toIso8601String(),
                    'updatedBy' => $p->editor?->name,
                    'updateUrl' => route('admin.ai.prompts.update', $p->key),
                    'resetUrl' => route('admin.ai.prompts.reset', $p->key),
                ])
                ->all(),
            'usage' => $this->usage($search),
            'search' => $search,
            'urls' => [
                'update' => route('admin.ai.settings.update'),
                'forgetKey' => route('admin.ai.settings.key.destroy'),
            ],
        ];
    }

    /**
     * Joriy oy sarfi: eng ko'p ishlatganlar yoki qidiruv natijasi (20 ta).
     *
     * @return array<int, array<string, mixed>>
     */
    private function usage(string $search): array
    {
        $totals = AiRequest::query()
            ->where('created_at', '>=', now()->startOfMonth())
            ->where('status', '!=', AiRequestStatus::Failed->value)
            ->select('user_id', DB::raw('SUM(input_tokens + output_tokens) as tokens'), DB::raw('COUNT(*) as requests'))
            ->groupBy('user_id')
            ->orderByDesc('tokens')
            ->toBase()
            ->get()
            ->keyBy('user_id');

        $search = trim($search);

        $users = $search !== ''
            ? User::query()
                ->where(fn (Builder $q) => $q->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"))
                ->orderBy('name')
                ->limit(20)
                ->get()
            : User::query()
                ->whereIn('id', $totals->keys()->take(20)->all())
                ->get()
                ->sortByDesc(fn (User $u): int => (int) ($totals->get($u->id)->tokens ?? 0))
                ->values();

        return $users->map(function (User $user) use ($totals): array {
            $row = $totals->get($user->id);
            $limit = $this->budget->limit($user);
            $used = (int) ($row->tokens ?? 0);

            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'staff' => $user->isStaff(),
                'used' => $used,
                'requests' => (int) ($row->requests ?? 0),
                'limit' => $limit,
                'personalLimit' => $user->ai_monthly_token_limit,
                'percent' => $limit > 0 ? min(100, (int) round($used / $limit * 100)) : null,
                'updateUrl' => route('admin.ai.limits.update', $user->id),
            ];
        })->all();
    }
}
