<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Admin\DashboardService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Admin panel — bosh sahifa (/admin), dizayn: super admin dashboard.png.
 */
class DashboardController extends Controller
{
    public function __invoke(Request $request, DashboardService $dashboard): Response
    {
        /** @var User $user */
        $user = $request->user();

        return Inertia::render('admin/Dashboard', [
            'cards' => fn () => $dashboard->statusCards(),
            'dynamics' => fn () => $dashboard->monthlyDynamics(),
            'statusBreakdown' => fn () => $dashboard->statusBreakdown(),
            'latestSubmissions' => fn () => $dashboard->latestSubmissions(),
            'payments' => fn () => $dashboard->paymentsMonthly(),
            'recentPayments' => fn () => $dashboard->recentPayments(),
            'aiUsage' => fn () => $dashboard->aiUsage(),
            'notificationsList' => fn () => $dashboard->notifications($user),
            'activeUsers' => fn () => $dashboard->activeUsers(),
            'systemHealth' => fn () => $dashboard->systemHealth(),
        ]);
    }
}
