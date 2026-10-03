<?php

namespace App\Services\Ai;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * Anthropic Messages API (POST /v1/messages) — rasmiy SDK'siz, Laravel HTTP klienti orqali.
 * Vaqtinchalik xatolarda (429, 5xx, 529) ikki marta qayta urinadi.
 */
class AnthropicClient
{
    private const RETRY_STATUSES = [429, 500, 502, 503, 504, 529];

    public function __construct(private readonly AiSettings $settings) {}

    /**
     * @throws AiException
     */
    public function complete(string $system, string $prompt, ?string $model = null, int $maxTokens = 4096, ?float $temperature = null): AiCompletion
    {
        $key = $this->settings->apiKey();
        $model ??= $this->settings->model();

        if ($key === null || $model === null) {
            throw new AiException(self::t("AI xizmati sozlanmagan: API kaliti yoki model ko'rsatilmagan."));
        }

        $payload = [
            'model' => $model,
            'max_tokens' => $maxTokens,
            'system' => $system,
            'messages' => [['role' => 'user', 'content' => $prompt]],
        ];

        if ($temperature !== null) {
            $payload['temperature'] = $temperature;
        }

        try {
            $response = Http::withHeaders([
                'x-api-key' => $key,
                'anthropic-version' => self::str(config('ai.api_version', '2023-06-01')),
            ])
                ->acceptJson()
                ->asJson()
                ->timeout((int) config('ai.timeout', 120))
                ->retry(3, 1500, fn (\Throwable $e): bool => $e instanceof RequestException
                    && in_array($e->response->status(), self::RETRY_STATUSES, true), throw: false)
                ->post(self::str(config('ai.api_url', 'https://api.anthropic.com/v1/messages')), $payload);
        } catch (ConnectionException) {
            throw new AiException(self::t("AI serveriga ulanib bo'lmadi. Birozdan keyin qayta urinib ko'ring."));
        }

        if ($response->failed()) {
            throw new AiException($this->errorMessage($response));
        }

        $text = '';

        foreach ((array) $response->json('content', []) as $block) {
            if (is_array($block) && ($block['type'] ?? null) === 'text' && is_string($block['text'] ?? null)) {
                $text .= $block['text'];
            }
        }

        return new AiCompletion(
            text: $text,
            inputTokens: (int) $response->json('usage.input_tokens', 0),
            outputTokens: (int) $response->json('usage.output_tokens', 0),
            model: self::str($response->json('model', $model)),
            stopReason: self::str($response->json('stop_reason')) ?: null,
        );
    }

    private function errorMessage(Response $response): string
    {
        return match (true) {
            in_array($response->status(), [401, 403], true) => self::t("AI API kaliti noto'g'ri yoki ruxsat yo'q. Sozlamalarni tekshiring."),
            $response->status() === 404 => self::t("Ko'rsatilgan model topilmadi. Sozlamalarda model identifikatorini tekshiring."),
            $response->status() === 429 => self::t("AI xizmatiga so'rovlar chegarasi oshdi. Birozdan keyin qayta urinib ko'ring."),
            $response->status() === 400 => self::t("AI so'rovi qabul qilinmadi: :reason", ['reason' => mb_substr(self::str($response->json('error.message', '')), 0, 200)]),
            default => self::t("AI xizmati vaqtincha ishlamayapti (:code). Birozdan keyin qayta urinib ko'ring.", ['code' => (string) $response->status()]),
        };
    }

    /**
     * @param  array<string, string>  $replace
     */
    private static function t(string $key, array $replace = []): string
    {
        $value = __($key, $replace);

        return is_string($value) ? $value : $key;
    }

    private static function str(mixed $value): string
    {
        return is_string($value) ? $value : '';
    }
}
