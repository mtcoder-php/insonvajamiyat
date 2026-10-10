<?php

namespace App\Http\Controllers\Admin\Payments;

use App\Enums\PermissionName;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Payments\ConfirmPaymentRequest;
use App\Http\Requests\Admin\Payments\WaivePaymentRequest;
use App\Http\Resources\Admin\AwaitingPaymentResource;
use App\Http\Resources\Admin\PaymentListResource;
use App\Http\Resources\Admin\PaymentLogResource;
use App\Http\Resources\Admin\RefundResource;
use App\Models\Article;
use App\Models\Payment;
use App\Models\User;
use App\Services\Admin\DashboardService;
use App\Services\Admin\PaymentsOverview;
use App\Services\Payments\ManualPaymentService;
use App\Services\Payments\PaymentReminderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Admin → To'lovlar (TZ 4.2.8): statistika, to'lov kutilayotgan maqolalar,
 * to'lovlar ro'yxati, qo'lda tasdiqlash, to'lovdan ozod qilish va mualliflarga eslatma.
 */
class PaymentController extends Controller
{
    /** Admin ko'radigan vaqt (tahririyat Toshkentda) */
    private const TIMEZONE = 'Asia/Tashkent';

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
        $logFilter = in_array($request->string('log')->toString(), PaymentsOverview::LOG_FILTERS, true)
            ? $request->string('log')->toString()
            : 'all';

        /** @var User $user */
        $user = $request->user();

        return Inertia::render('admin/payments/Index', [
            'filters' => ['tab' => $tab, 'search' => $search, 'log' => $logFilter],
            'counts' => $counts,
            'stats' => fn () => $this->overview->stats(),
            'monthly' => fn () => $dashboard->paymentsMonthly(),
            'breakdown' => fn () => $this->overview->providerBreakdown(),
            'recent' => fn () => $dashboard->recentPayments(4),
            'awaiting' => $tab === 'awaiting'
                ? AwaitingPaymentResource::collection($this->overview->awaiting($search))
                : null,
            'payments' => ! in_array($tab, ['awaiting', 'refunds', 'logs'], true)
                ? PaymentListResource::collection($this->overview->payments($tab, $search))
                : null,
            'refunds' => $tab === 'refunds'
                ? RefundResource::collection($this->overview->refunds($search))
                : null,
            'logs' => $tab === 'logs'
                ? PaymentLogResource::collection($this->overview->logs($search, $logFilter))
                : null,
            'can' => [
                'confirm' => $user->can(PermissionName::PaymentsConfirmManually->value),
                'refund' => $user->can(PermissionName::PaymentsRefund->value),
            ],
            'remindAllUrl' => route('admin.payments.remind-all'),
            'reminderDays' => PaymentReminderService::days(),
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

    public function remind(Request $request, Article $article, PaymentReminderService $reminders): RedirectResponse
    {
        /** @var User $admin */
        $admin = $request->user();

        if (! PaymentReminderService::isAwaiting($article)) {
            Inertia::flash('toast', ['type' => 'error', 'message' => __("Maqola to'lov kutilayotganlar ro'yxatida emas.")]);

            return back();
        }

        $next = PaymentReminderService::availableAt($article);

        if ($next !== null || ! $reminders->remind($article, $admin)) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => __('Eslatma yaqinda yuborilgan. Keyingisini :time dan keyin yuborish mumkin.', [
                    'time' => ($next ?? now())->timezone(self::TIMEZONE)->format('d.m.Y H:i'),
                ]),
            ]);

            return back();
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Muallifga eslatma yuborildi.')]);

        return back();
    }

    public function remindAll(Request $request, PaymentReminderService $reminders): RedirectResponse
    {
        /** @var User $admin */
        $admin = $request->user();
        $sent = $reminders->remindAll($admin);

        Inertia::flash('toast', $sent > 0
            ? ['type' => 'success', 'message' => __('Eslatma yuborildi: :count ta muallif.', ['count' => $sent])]
            : ['type' => 'info', 'message' => __("Barcha mualliflarga so'nggi kunda eslatma yuborilgan — hozircha yangi eslatma kerak emas.")]);

        return back();
    }

    public function proof(Payment $payment): StreamedResponse
    {
        return $this->manual->downloadProof($payment);
    }
}
