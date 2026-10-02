<?php

namespace App\Http\Controllers\Payments;

use App\Enums\PaymentProvider;
use App\Http\Controllers\Controller;
use App\Services\Payments\Click\ClickMerchantService;
use App\Services\Payments\PaymentLogger;
use App\Services\Payments\WebhookResult;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Click SHOP API: Prepare va Complete so'rovlari (application/x-www-form-urlencoded).
 * CSRF va sessiyasiz (routes/payments.php), har so'rov payment_logs ga yoziladi.
 */
class ClickController extends Controller
{
    public function __construct(
        private readonly ClickMerchantService $click,
        private readonly PaymentLogger $logger,
    ) {}

    public function prepare(Request $request): JsonResponse
    {
        return $this->respond($request, fn (array $data): WebhookResult => $this->click->prepare($data));
    }

    public function complete(Request $request): JsonResponse
    {
        return $this->respond($request, fn (array $data): WebhookResult => $this->click->complete($data));
    }

    /**
     * @param  \Closure(array<string, mixed>): WebhookResult  $handler
     */
    private function respond(Request $request, \Closure $handler): JsonResponse
    {
        $startedAt = microtime(true);
        /** @var array<string, mixed> $data */
        $data = $request->all();

        $result = $handler($data);
        $this->logger->log(PaymentProvider::Click, $request, $data, $result, $startedAt);

        return response()->json($result->body);
    }
}
