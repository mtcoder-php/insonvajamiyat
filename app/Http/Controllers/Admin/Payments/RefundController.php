<?php

namespace App\Http\Controllers\Admin\Payments;

use App\Enums\PaymentProvider;
use App\Enums\RefundStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Payments\RefundPaymentRequest;
use App\Models\Payment;
use App\Models\Refund;
use App\Models\User;
use App\Services\Payments\RefundService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * Admin → To'lovlar → "Qaytarish" (TZ 4.1.4, 4.2.8): so'rov ochish va ochiq so'rovni bekor qilish.
 */
class RefundController extends Controller
{
    public function __construct(private readonly RefundService $refunds) {}

    public function store(RefundPaymentRequest $request, Payment $payment): RedirectResponse
    {
        /** @var User $admin */
        $admin = $request->user();
        $reference = $request->string('reference')->trim()->toString();

        $refund = $this->refunds->request(
            $payment,
            $admin,
            $request->string('reason')->trim()->toString(),
            $reference !== '' ? $reference : null,
        );

        Inertia::flash('toast', match (true) {
            $refund->status === RefundStatus::Completed => [
                'type' => 'success',
                'message' => __("To'lov qaytarildi. Muallifga xabar yuborildi."),
            ],
            $refund->status === RefundStatus::Failed => [
                'type' => 'error',
                'message' => __("Click to'lovni qaytarmadi: :error", ['error' => (string) $refund->error_message]),
            ],
            $payment->provider === PaymentProvider::Payme => [
                'type' => 'info',
                'message' => __("So'rov ochildi. Endi Payme biznes kabinetida shu tranzaksiyani bekor qiling — holat avtomatik yangilanadi."),
            ],
            default => ['type' => 'success', 'message' => __("Qaytarish so'rovi saqlandi.")],
        });

        return back();
    }

    public function cancel(Request $request, Refund $refund): RedirectResponse
    {
        /** @var User $admin */
        $admin = $request->user();

        $this->refunds->cancel($refund, $admin);

        Inertia::flash('toast', ['type' => 'success', 'message' => __("Qaytarish so'rovi bekor qilindi.")]);

        return back();
    }
}
