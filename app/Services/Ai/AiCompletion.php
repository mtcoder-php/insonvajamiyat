<?php

namespace App\Services\Ai;

/**
 * Model javobi: matn va token sarfi.
 */
final readonly class AiCompletion
{
    public function __construct(
        public string $text,
        public int $inputTokens,
        public int $outputTokens,
        public string $model,
        public ?string $stopReason = null,
    ) {}
}
