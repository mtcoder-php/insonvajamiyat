<?php

namespace App\Http\Controllers\Payments;

use App\Enums\PaymentProvider;
use App\Http\Controllers\Controller;
use App\Services\Payments\Payme\PaymeMerchantService;
use App\Services\Payments\PaymentLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Payme Merchant API — yagona JSON-RPC endpoint. Javob har doim HTTP 200 (Payme talabi).
 */
class PaymeController extends Controller
{
    public function __invoke(Request $request, PaymeMerchantService $payme, PaymentLogger $logger): JsonResponse
    {
        $startedAt = microtime(true);
        $raw = $request->getContent();

        $result = $payme->handle($request->header('Authorization'), $raw);

        $payload = json_decode($raw, true);
        $logger->log(
            PaymentProvider::Payme,
            $request,
            is_array($payload) ? $payload : ['raw' => mb_substr($raw, 0, 2000)],
            $result,
            $startedAt,
        );

        return response()->json($result->body);
    }
}
