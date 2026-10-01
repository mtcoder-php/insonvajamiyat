<?php

namespace App\Http\Controllers\Admin\Payments;

use App\Enums\PermissionName;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Payments\ConfirmPaymentRequest;
use App\Http\Requests\Admin\Payments\WaivePaymentRequest;
use App\Http\Resources\Admin\AwaitingPaymentResource;
use App\Http\Resources\Admin\PaymentListResource;
use App\Models\Article;
use App\Models\Payment;
use App\Models\User;
use App\Services\Admin\DashboardService;
use App\Services\Admin\PaymentsOverview;
use App\Services\Payments\ManualPaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Admin → To'lovlar (TZ 4.2.8): statistika, to'lov kutilayotgan maqolalar,
 * to'lovlar ro'yxati, qo'lda tasdiqlash va to'lovdan ozod qilish.
 */
class PaymentController extends Controller
{
    public function __construct(
        private readonly PaymentsOverview $overview,
        private readonly ManualPaymentService $manual,
    ) {}

    public function index(Request $request, DashboardService $dashboard): Response
    {
        $counts = $this->overview->counts();
        $requested = $request->string('tab')->toString();
        // Standart tab: to'lov kutilayotgan maqola bo'lsa — shu, aks holda barcha to'lovlar
        $tab = in_array($requested, PaymentsOverview::TABS, true)
            ? $requested
            : ($counts['awaiting'] > 0 ? 'awaiting' : 'all');
        $search = $request->string('search')->trim()->limit(100, '')->toString() ?: null;

        /** @var User $user */
        $user = $request->user();

        return Inertia::render('admin/payments/Index', [
            'filters' => ['tab' => $tab, 'search' => $search],
            'counts' => $counts,
            'stats' => fn () => $this->overview->stats(),
            'monthly' => fn () => $dashboard->paymentsMonthly(),
            'breakdown' => fn () => $this->overview->providerBreakdown(),
            'recent' => fn () => $dashboard->recentPayments(4),
            'awaiting' => $tab === 'awaiting'
                ? AwaitingPaymentResource::collection($this->overview->awaiting($search))
                : null,
            'payments' => $tab !== 'awaiting'
                ? PaymentListResource::collection($this->overview->payments($tab, $search))
                : null,
            'can' => [
                'confirm' => $user->can(PermissionName::PaymentsConfirmManually->value),
            ],
        ]);
    }

    public function confirm(ConfirmPaymentRequest $request, Article $article): RedirectResponse
    {
        /** @var User $admin */
        $admin = $request->user();
        $proof = $request->file('proof');

        $payment = $this->manual->confirm(
            $article,
            $admin,
            $request->payment(),
            $proof instanceof UploadedFile ? $proof : null,
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __("To'lov tasdiqlandi (:receipt). Maqola tahririyat navbatiga o'tdi.", ['receipt' => $payment->receipt_number]),
        ]);

        return back();
    }

    public function waive(WaivePaymentRequest $request, Article $article): RedirectResponse
    {
        /** @var User $admin */
        $admin = $request->user();

        $this->manual->waive($article, $admin, $request->string('reason')->trim()->toString());

        Inertia::flash('toast', ['type' => 'success', 'message' => __("Maqola to'lovdan ozod qilindi.")]);

        return back();
    }

    public function proof(Payment $payment): StreamedResponse
    {
        return $this->manual->downloadProof($payment);
    }
}
