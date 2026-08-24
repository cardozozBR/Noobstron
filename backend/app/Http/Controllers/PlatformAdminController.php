<?php

namespace App\Http\Controllers;

use App\Models\AiUsageRecord;
use App\Models\EmailMessage;
use App\Models\PaymentEventReceipt;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\WhatsAppMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PlatformAdminController extends Controller
{
    public function showLogin(): View
    {
        return view('platform.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $key = implode('|', [
            'platform-login',
            Str::lower($credentials['email']),
            $request->ip(),
        ]);

        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages([
                'email' => 'Credenciais inválidas.',
            ]);
        }

        $authenticated = Auth::guard('platform')->attempt([
            'email' => $credentials['email'],
            'password' => $credentials['password'],
            'is_active' => true,
        ]);

        if (! $authenticated) {
            RateLimiter::hit($key, 60);

            throw ValidationException::withMessages([
                'email' => 'Credenciais inválidas.',
            ]);
        }

        RateLimiter::clear($key);

        $request->session()->regenerate();

        return redirect()->route('platform.dashboard');
    }

    public function dashboard(): View
    {
        $latestIds = Subscription::query()
            ->selectRaw('MAX(id)')
            ->groupBy('tenant_id');

        $current = Subscription::query()
            ->whereIn('subscriptions.id', $latestIds);

        $subscriptionCounts = (clone $current)
            ->select(
                'status',
                DB::raw('COUNT(*) as aggregate')
            )
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $now = now();

        $trialActive = Tenant::query()
            ->whereNotNull('trial_ends_at')
            ->where('trial_ends_at', '>=', $now)
            ->count();

        $trialExpiring = Tenant::query()
            ->whereNotNull('trial_ends_at')
            ->whereBetween(
                'trial_ends_at',
                [
                    $now,
                    $now->copy()->addDays(7),
                ]
            )
            ->count();

        $mrr = (clone $current)
            ->where(
                'subscriptions.status',
                'active'
            )
            ->whereNotNull(
                'subscriptions.paid_at'
            )
            ->join(
                'tenants',
                'tenants.id',
                '=',
                'subscriptions.tenant_id'
            )
            ->leftJoin(
                'plan_prices',
                function ($join): void {
                    $join
                        ->on(
                            'plan_prices.plan_id',
                            '=',
                            'subscriptions.plan_id'
                        )
                        ->on(
                            'plan_prices.currency',
                            '=',
                            'tenants.currency'
                        );
                }
            )
            ->selectRaw(
                '
                COALESCE(
                    subscriptions.currency,
                    tenants.currency
                ) as billing_currency,
                SUM(
                    COALESCE(
                        subscriptions.amount_minor,
                        plan_prices.amount_minor,
                        0
                    )
                ) as aggregate
                '
            )
            ->groupByRaw(
                '
                COALESCE(
                    subscriptions.currency,
                    tenants.currency
                )
                '
            )
            ->pluck(
                'aggregate',
                'billing_currency'
            );

        $usage = [
            'users' => (int) DB::table('users')->count(),

            'messages' =>
                (int) EmailMessage::query()
                    ->withoutGlobalScopes()
                    ->count()
                + (int) WhatsAppMessage::query()
                    ->withoutGlobalScopes()
                    ->where('direction', 'outbound')
                    ->count(),

            'ai_tokens' =>
                (int) AiUsageRecord::query()
                    ->sum('total_tokens'),
        ];

        return view('platform.dashboard', [
            'tenantCount' => Tenant::query()->count(),
            'subscriptionCounts' => $subscriptionCounts,
            'trialActive' => $trialActive,
            'trialExpiring' => $trialExpiring,
            'mrr' => $mrr,
            'usage' => $usage,
        ]);
    }

    public function webhooks(): View
    {
        $receipts = PaymentEventReceipt::query()
            ->orderByDesc('processed_at')
            ->orderByDesc('id')
            ->paginate(50);

        return view('platform.webhooks', [
            'receipts' => $receipts,
        ]);
    }

    public function health(): View
    {
        $databaseOk = true;
        $queuePending = null;
        $queueFailed = null;

        try {
            DB::select('select 1');

            $queuePending =
                (int) DB::table('jobs')->count();

            $queueFailed =
                (int) DB::table('failed_jobs')->count();
        } catch (\Throwable) {
            $databaseOk = false;
        }

        return view('platform.health', [
            'checks' => [
                'database' => $databaseOk,
                'storage' =>
                    is_writable(storage_path()),
                'queue_pending' => $queuePending,
                'queue_failed' => $queueFailed,
                'mail_configured' =>
                    config('mail.default') !== 'log',
                'contact_recipient' =>
                    trim(
                        (string) config(
                            'marketing.contact_recipient'
                        )
                    ) !== '',
            ],
        ]);
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('platform')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('platform.login');
    }
}