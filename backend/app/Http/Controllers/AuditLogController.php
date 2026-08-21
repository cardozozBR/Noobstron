<?php

namespace App\Http\Controllers;

use App\Support\TenantDateTime;

use App\Models\AuditLog;
use App\Models\User;
use App\Services\TenantContext;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $tenant = app(TenantContext::class)->get();

        $filters = $request->validate([
            'action' => ['nullable', 'string', 'max:100'],
            'user_id' => ['nullable', 'integer'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'search' => ['nullable', 'string', 'max:255'],
            'origin' => ['nullable', 'in:all,user,system'],
        ]);

        $action = $filters['action'] ?? null;
        $userId = $filters['user_id'] ?? null;
        $dateFrom = $filters['date_from'] ?? null;
        $dateTo = $filters['date_to'] ?? null;
        $search = $filters['search'] ?? null;
        $origin = $filters['origin'] ?? 'all';

        $query = AuditLog::query()
            ->with('user')
            ->latest();

        if ($action) {
            $query->where('action', $action);
        }

        if ($userId) {
            $query->where('user_id', $userId);
        }

        if ($dateFrom) {
            [$start] = app(TenantDateTime::class)
                ->localDayUtcBounds($dateFrom);

            $query->where(
                'created_at',
                '>=',
                $start->format('Y-m-d H:i:s')
            );
        }

        if ($dateTo) {
            [, $end] = app(TenantDateTime::class)
                ->localDayUtcBounds($dateTo);

            $query->where(
                'created_at',
                '<',
                $end->format('Y-m-d H:i:s')
            );
        }

        if ($search) {
            $query->where('description', 'like', '%' . $search . '%');
        }

        if ($origin === 'user') {
            $query->whereNotNull('user_id');
        } elseif ($origin === 'system') {
            $query->whereNull('user_id');
        }

        $logs = $query
            ->paginate(15)
            ->withQueryString();

        $actions = AuditLog::query()
            ->whereNotNull('action')
            ->distinct()
            ->orderBy('action')
            ->pluck('action');

        $users = User::query()
            ->whereIn('id', function ($query) {
                $query->select('user_id')
                    ->from('audit_logs')
                    ->whereNotNull('user_id');
            })
            ->orderBy('name')
            ->get();

        $metrics = AuditLog::query()
            ->selectRaw('
                COUNT(*) AS total_logs,
                COUNT(DISTINCT user_id) AS total_users,
                COUNT(DISTINCT action) AS total_actions
            ')
            ->first();

        $totalLogs = (int) $metrics->total_logs;
        $totalUsers = (int) $metrics->total_users;
        $totalActions = (int) $metrics->total_actions;

        return view('audit.index', [
            'logs' => $logs,
            'tenant' => $tenant,
            'actions' => $actions,
            'selectedAction' => $action,
            'users' => $users,
            'selectedUser' => $userId,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'search' => $search,
            'origin' => $origin,
            'totalLogs' => $totalLogs,
            'totalUsers' => $totalUsers,
            'totalActions' => $totalActions,
        ]);
    }
}