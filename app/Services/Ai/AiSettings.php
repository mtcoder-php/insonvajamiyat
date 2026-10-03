<?php

namespace App\Services\Ai;

use App\Models\User;
use App\Services\Settings\SettingsStore;

/**
 * AI sozlamalari: avval settings jadvali ("ai" guruhi), bo'lmasa config/ai.php (.env).
 */
class AiSettings
{
    public const GROUP = 'ai';

    public function __construct(private readonly SettingsStore $store) {}

    public function apiKey(): ?string
    {
        $value = $this->store->get(self::GROUP, 'api_key', config('ai.api_key'));

        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }

    public function model(): ?string
    {
        $value = $this->store->get(self::GROUP, 'model', config('ai.model'));

        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }

    public function enabled(): bool
    {
        return (bool) $this->store->get(self::GROUP, 'enabled', (bool) config('ai.enabled', true));
    }

    /** Kalit va model bor, xizmat yoqilgan */
    public function ready(): bool
    {
        return $this->enabled() && $this->apiKey() !== null && $this->model() !== null;
    }

    public function authorLimit(): int
    {
        return $this->int('author_monthly_limit', 'ai.limits.author', 50_000);
    }

    public function staffLimit(): int
    {
        return $this->int('staff_monthly_limit', 'ai.limits.staff', 200_000);
    }

    public function maxInputChars(): int
    {
        return max(1_000, $this->int('max_input_chars', 'ai.max_input_chars', 30_000));
    }

    public function keySource(): string
    {
        return $this->store->has(self::GROUP, 'api_key') ? 'database' : (config('ai.api_key') ? 'env' : 'none');
    }

    /** sk-ant-...XyZ9 ko'rinishida (kalitning o'zi hech qachon frontendga chiqmaydi) */
    public function maskedKey(): ?string
    {
        $key = $this->apiKey();

        if ($key === null) {
            return null;
        }

        return mb_substr($key, 0, 7).'…'.mb_substr($key, -4);
    }

    /**
     * @param  array{api_key?: string|null, model?: string|null, enabled?: bool, author_monthly_limit?: int, staff_monthly_limit?: int, max_input_chars?: int}  $data
     */
    public function update(array $data, User $by): void
    {
        if (array_key_exists('api_key', $data) && is_string($data['api_key']) && trim($data['api_key']) !== '') {
            $this->store->set(self::GROUP, 'api_key', trim($data['api_key']), $by, encrypted: true, description: 'Anthropic API kaliti');
        }

        if (array_key_exists('model', $data)) {
            $this->store->set(self::GROUP, 'model', $data['model'] !== null ? trim($data['model']) : null, $by, description: 'Model identifikatori');
        }

        foreach (['enabled', 'author_monthly_limit', 'staff_monthly_limit', 'max_input_chars'] as $key) {
            if (array_key_exists($key, $data)) {
                $this->store->set(self::GROUP, $key, $data[$key], $by);
            }
        }
    }

    public function forgetApiKey(): void
    {
        $this->store->forget(self::GROUP, 'api_key');
    }

    private function int(string $key, string $configKey, int $fallback): int
    {
        $config = config($configKey, $fallback);
        $value = $this->store->get(self::GROUP, $key, is_numeric($config) ? (int) $config : $fallback);

        return is_numeric($value) ? max(0, (int) $value) : $fallback;
    }
}
