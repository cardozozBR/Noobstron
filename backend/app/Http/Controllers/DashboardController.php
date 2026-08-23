<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use App\Enums\SubscriptionStatus;
use App\Services\DashboardService;
use App\Services\SubscriptionBillingService;
use App\Services\TenantContext;
use App\Services\TrialService;

class DashboardController extends Controller
{
    public function index(
        SubscriptionBillingService $billing,
        TrialService $trials,
    ) {
        $tenant = app(TenantContext::class)->get();

        $totalUsers = User::count();

        $totalAuditLogs = AuditLog::count();

        $totalActions = AuditLog::query()
            ->whereNotNull('action')
            ->distinct()
            ->count('action');

        $recentLogs = AuditLog::query()
            ->with('user')
            ->latest()
            ->limit(10)
            ->get();

        $dashboard = app(DashboardService::class);

        $subscription = $tenant
            ->subscriptions()
            ->with('plan')
            ->latest('id')
            ->first();

        $hasPaidSubscriptionHistory = $tenant
            ->subscriptions()
            ->whereNotNull('paid_at')
            ->exists();

        $isCurrentPaidSubscription =
            $subscription !== null
            && $subscription->status ===
                SubscriptionStatus::ACTIVE
            && $billing->isPaid($subscription);

        if ($isCurrentPaidSubscription) {
            $billingState = 'active';
        } elseif ($hasPaidSubscriptionHistory) {
            $billingState = 'inactive';
        } elseif (
            $trials->status($tenant)
            === TrialService::STATUS_ACTIVE
        ) {
            $billingState = 'trial';
        } else {
            $billingState = 'inactive';
        }

        $trialDaysRemaining = $billingState === 'trial'
            && $tenant->trial_ends_at !== null
                ? max(
                    0,
                    now()
                        ->startOfDay()
                        ->diffInDays(
                            $tenant->trial_ends_at
                                ->startOfDay(),
                            false
                        )
                )
                : null;

        return view('dashboard', [
            'tenant' => $tenant,
            'totalUsers' => $totalUsers,
            'totalAuditLogs' => $totalAuditLogs,
            'totalActions' => $totalActions,
            'recentLogs' => $recentLogs,
            'crmMetrics' => $dashboard->metrics(),
            'opportunitiesByStage' => $dashboard->opportunitiesByStage(),
            'opportunitiesByResponsible' =>
                $dashboard->opportunitiesByResponsible(),
            'upcomingActivities' => $dashboard->upcomingActivities(),
            'billingState' => $billingState,
            'billingPlanName' => $subscription?->plan?->name,
            'trialDaysRemaining' => $trialDaysRemaining,
        ]);
    }
}
