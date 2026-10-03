<?php

namespace App\Services\Ai;

use App\Enums\AiRequestStatus;
use App\Enums\AiRequestType;
use App\Models\AiRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Oylik token limiti: shaxsiy (users.ai_monthly_token_limit) yoki rol bo'yicha standart.
 * 0 — cheklanmagan. Sarf — joriy oyda yakunlangan va jarayondagi so'rovlar tokenlari.
 */
class TokenBudget
{
    /** Taxminiy hisob: 1 token ≈ 3 belgi; chiquvchi matn hajmi turga qarab */
    private const OUTPUT_FACTOR = [
        'spell_check' => 0.6,
        'translation' => 1.4,
        'analysis' => 0.25,
    ];

    /** Har bir bo'lakdagi ko'rsatma (system prompt) uchun taxminiy token */
    private const PROMPT_OVERHEAD = 600;

    public function __construct(private readonly AiSettings $settings) {}

    public function limit(User $user): int
    {
        if ($user->ai_monthly_token_limit !== null) {
            return max(0, $user->ai_monthly_token_limit);
        }

        return $user->isStaff() ? $this->settings->staffLimit() : $this->settings->authorLimit();
    }

    public function isPersonal(User $user): bool
    {
        return $user->ai_monthly_token_limit !== null;
    }

    public function used(User $user): int
    {
        return (int) AiRequest::query()
            ->where('user_id', $user->id)
            ->where('created_at', '>=', now()->startOfMonth())
            ->where('status', '!=', AiRequestStatus::Failed->value)
            ->sum(DB::raw('input_tokens + output_tokens'));
    }

    /** null — cheklanmagan */
    public function remaining(User $user): ?int
    {
        $limit = $this->limit($user);

        return $limit === 0 ? null : max(0, $limit - $this->used($user));
    }

    public function estimate(string $text, AiRequestType $type, int $chunks = 1): int
    {
        $input = (int) ceil(mb_strlen($text) / 3);

        return $input + (int) ceil($input * (self::OUTPUT_FACTOR[$type->value] ?? 1.0)) + self::PROMPT_OVERHEAD * max(1, $chunks);
    }

    /**
     * @throws ValidationException
     */
    public function ensureCanSpend(User $user, string $text, AiRequestType $type, int $chunks = 1): void
    {
        $remaining = $this->remaining($user);

        if ($remaining === null) {
            return;
        }

        $needed = $this->estimate($text, $type, $chunks);

        if ($needed > $remaining) {
            throw ValidationException::withMessages([
                'text' => $remaining === 0
                    ? self::t('Bu oy uchun AI token limitingiz tugagan. Keyingi oy yangilanadi yoki administratorga murojaat qiling.')
                    : self::t('Token yetarli emas: taxminan :needed token kerak, qolgani :remaining. Matnni qisqartiring.', [
                        'needed' => number_format($needed, 0, '.', ' '),
                        'remaining' => number_format($remaining, 0, '.', ' '),
                    ]),
            ]);
        }
    }

    /**
     * @return array{limit: int, used: int, remaining: int|null, personal: bool, resetsAt: string}
     */
    public function summary(User $user): array
    {
        $limit = $this->limit($user);
        $used = $this->used($user);

        return [
            'limit' => $limit,
            'used' => $used,
            'remaining' => $limit === 0 ? null : max(0, $limit - $used),
            'personal' => $this->isPersonal($user),
            'resetsAt' => now()->addMonthNoOverflow()->startOfMonth()->toIso8601String(),
        ];
    }

    /**
     * @param  array<string, string>  $replace
     */
    private static function t(string $key, array $replace = []): string
    {
        $value = __($key, $replace);

        return is_string($value) ? $value : $key;
    }
}
