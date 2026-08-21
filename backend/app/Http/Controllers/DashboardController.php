<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use App\Services\DashboardService;
use App\Services\TenantContext;

class DashboardController extends Controller
{
    public function index()
    {
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
        ]);
    }
}
