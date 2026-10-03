<?php

namespace App\Jobs;

use App\Models\AiRequest;
use App\Services\Ai\AiStudioService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

/**
 * AI so'rovini navbatda bajarish (TZ 6: asinxron, progress-indikator bilan).
 * Bir marta uriniladi: xato bo'lsa so'rov "Xato" holatiga o'tadi, foydalanuvchi qayta yuboradi.
 */
class ProcessAiRequest implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 900;

    public function __construct(public readonly int $requestId) {}

    public function handle(AiStudioService $studio): void
    {
        $request = AiRequest::query()->find($this->requestId);

        if ($request !== null) {
            $studio->process($request);
        }
    }

    public function failed(?Throwable $exception): void
    {
        $request = AiRequest::query()->find($this->requestId);

        if ($request !== null && ! $request->status->isFinished()) {
            $message = __("So'rov bajarilmadi (vaqt tugadi yoki ichki xato). Qayta urinib ko'ring.");

            app(AiStudioService::class)->fail($request, is_string($message) ? $message : "So'rov bajarilmadi.");
        }
    }
}
