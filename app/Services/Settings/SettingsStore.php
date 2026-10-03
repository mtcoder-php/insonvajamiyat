<?php

namespace App\Services\Settings;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;

/**
 * settings jadvali (group + key) bilan ishlash. Shifrlangan qiymatlar Crypt (APP_KEY) bilan saqlanadi.
 * So'rov davomida qiymatlar xotirada saqlanadi (bir sahifada qayta-qayta bazaga murojaat qilinmaydi).
 */
class SettingsStore
{
    /** @var array<string, array<string, Setting>> */
    private array $loaded = [];

    public function get(string $group, string $key, mixed $default = null): mixed
    {
        $setting = $this->group($group)[$key] ?? null;

        if ($setting === null || $setting->value === null || $setting->value === '') {
            return $default;
        }

        $value = $setting->value;

        if ($setting->is_encrypted) {
            try {
                $value = Crypt::decryptString($value);
            } catch (DecryptException) {
                return $default;
            }
        }

        return match ($setting->type) {
            'integer' => (int) $value,
            'boolean' => in_array($value, ['1', 'true'], true),
            'json' => json_decode($value, true),
            default => $value,
        };
    }

    public function has(string $group, string $key): bool
    {
        $setting = $this->group($group)[$key] ?? null;

        return $setting !== null && $setting->value !== null && $setting->value !== '';
    }

    public function set(string $group, string $key, mixed $value, ?User $by = null, bool $encrypted = false, ?string $description = null): void
    {
        [$type, $stored] = match (true) {
            is_bool($value) => ['boolean', $value ? '1' : '0'],
            is_int($value) => ['integer', (string) $value],
            is_array($value) => ['json', (string) json_encode($value, JSON_UNESCAPED_UNICODE)],
            $value === null => ['string', null],
            default => ['string', is_scalar($value) ? (string) $value : ''],
        };

        if ($encrypted && $stored !== null && $stored !== '') {
            $stored = Crypt::encryptString($stored);
        }

        $setting = Setting::query()->updateOrCreate(
            ['group' => $group, 'key' => $key],
            array_filter([
                'value' => $stored,
                'type' => $type,
                'is_encrypted' => $encrypted,
                'description' => $description,
                'updated_by' => $by?->id,
            ], fn (mixed $v, int|string $k): bool => $v !== null || $k === 'value', ARRAY_FILTER_USE_BOTH),
        );

        $this->loaded[$group][$key] = $setting;
    }

    public function forget(string $group, string $key): void
    {
        Setting::query()->where('group', $group)->where('key', $key)->delete();
        unset($this->loaded[$group][$key]);
    }

    /**
     * @return array<string, Setting>
     */
    private function group(string $group): array
    {
        return $this->loaded[$group] ??= Setting::query()
            ->where('group', $group)
            ->get()
            ->keyBy('key')
            ->all();
    }
}
