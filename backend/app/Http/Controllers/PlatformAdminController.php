<?php

namespace App\Http\Controllers;

use App\Models\AiUsageRecord;
use App\Models\EmailMessage;
use App\Models\PaymentEventReceipt;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\WhatsAppMessage;
use App\Services\EmailMessageService;
use App\Services\EmailQueueService;
use App\Services\PlatformAdminAuditService;
use App\Services\StripeSubscriptionWebhookService;
use App\Services\TenantContext;
use App\Services\WhatsAppMessageService;
use App\Services\WhatsAppQueueService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
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

        $newSubscriptions = Subscription::query()
            ->where(
                'created_at',
                '>=',
                $now->copy()->subDays(30)
            )
            ->count();

        $cancellations = Subscription::query()
            ->whereNotNull('canceled_at')
            ->where(
                'canceled_at',
                '>=',
                $now->copy()->subDays(30)
            )
            ->count();

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

            'messages' => (int) EmailMessage::query()
                ->withoutGlobalScopes()
                ->count()
                + (int) WhatsAppMessage::query()
                    ->withoutGlobalScopes()
                    ->where('direction', 'outbound')
                    ->count(),

            'ai_tokens' => (int) AiUsageRecord::query()
                ->sum('total_tokens'),
        ];

        $webhookCounts = PaymentEventReceipt::query()
            ->whereIn(
                'status',
                [
                    'failed',
                    'processing',
                ]
            )
            ->select(
                'status',
                DB::raw('COUNT(*) as aggregate')
            )
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $latestFailedWebhook = PaymentEventReceipt::query()
            ->where('status', 'failed')
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->first();

        $queueCounts = [
            'pending' => (int) DB::table('jobs')->count(),
            'failed' => (int) DB::table('failed_jobs')->count(),
        ];

        $messageFailureCounts = [
            'email' => (int) EmailMessage::query()
                ->withoutGlobalScopes()
                ->where('status', 'failed')
                ->count(),

            'whatsapp' => (int) WhatsAppMessage::query()
                ->withoutGlobalScopes()
                ->where('status', 'failed')
                ->count(),
        ];

        return view('platform.dashboard', [
            'tenantCount' => Tenant::query()->count(),
            'subscriptionCounts' => $subscriptionCounts,
            'newSubscriptions' => $newSubscriptions,
            'cancellations' => $cancellations,
            'trialActive' => $trialActive,
            'trialExpiring' => $trialExpiring,
            'mrr' => $mrr,
            'usage' => $usage,
            'webhookCounts' => $webhookCounts,
            'latestFailedWebhook' => $latestFailedWebhook,
            'queueCounts' => $queueCounts,
            'messageFailureCounts' => $messageFailureCounts,
        ]);
    }

    public function emailFailures(Request $request): View
    {
        $tenantId = $request->integer('tenant_id');

        $tenant = $tenantId > 0
            ? Tenant::query()
                ->withoutGlobalScopes()
                ->find($tenantId)
            : null;

        $messages = EmailMessage::query()
            ->withoutGlobalScopes()
            ->with('tenant')
            ->where('status', 'failed')
            ->when(
                $tenant !== null,
                fn ($query) => $query->where(
                    'tenant_id',
                    $tenant->id
                )
            )
            ->orderByDesc('failed_at')
            ->orderByDesc('id')
            ->paginate(50)
            ->withQueryString();

        return view('platform.email-failures', [
            'messages' => $messages,
            'tenant' => $tenant,
        ]);
    }

    public function retryEmailFailure(
        int $message,
        EmailMessageService $messages,
        EmailQueueService $queue,
        TenantContext $tenantContext,
        PlatformAdminAuditService $audit,
    ): RedirectResponse {
        $emailMessage = null;

        try {
            DB::transaction(function () use (
                $message,
                $messages,
                $queue,
                $tenantContext,
                &$emailMessage
            ): void {
                $emailMessage = EmailMessage::query()
                    ->withoutGlobalScopes()
                    ->lockForUpdate()
                    ->findOrFail($message);

                if ($emailMessage->tenant === null) {
                    throw new \RuntimeException(
                        'O tenant desta mensagem não está disponível.'
                    );
                }

                $tenantContext->set(
                    $emailMessage->tenant
                );

                $emailMessage = $messages->retry(
                    $emailMessage
                );

                $queue->dispatch(
                    $emailMessage
                );
            });
        } catch (\Throwable $exception) {
            report($exception);

            return redirect()
                ->route('platform.email-failures')
                ->with(
                    'error',
                    'O e-mail não pôde ser reprocessado.'
                );
        } finally {
            $tenantContext->clear();
        }

        if ($emailMessage !== null) {
            $emailMessage->refresh();

            $audit->log(
                action: 'email.reprocessed',
                tenant: $emailMessage->tenant,
                entityType: EmailMessage::class,
                entityId: $emailMessage->id,
                afterState: [
                    'status' => $emailMessage->status->value,
                    'to_email' => $emailMessage->to_email,
                    'subject' => $emailMessage->subject,
                ],
                result: PlatformAdminAuditService::RESULT_SUCCESS,
            );
        }

        return redirect()
            ->route('platform.email-failures')
            ->with(
                'success',
                'E-mail enviado para reprocessamento.'
            );
    }

    public function whatsappFailures(Request $request): View
    {
        $tenantId = $request->integer('tenant_id');

        $tenant = $tenantId > 0
            ? Tenant::query()
                ->withoutGlobalScopes()
                ->find($tenantId)
            : null;

        $messages = WhatsAppMessage::query()
            ->withoutGlobalScopes()
            ->with('tenant')
            ->where('status', 'failed')
            ->when(
                $tenant !== null,
                fn ($query) => $query->where(
                    'tenant_id',
                    $tenant->id
                )
            )
            ->orderByDesc('failed_at')
            ->orderByDesc('id')
            ->paginate(50)
            ->withQueryString();

        return view('platform.whatsapp-failures', [
            'messages' => $messages,
            'tenant' => $tenant,
        ]);
    }

    public function retryWhatsAppFailure(
        int $message,
        WhatsAppMessageService $messages,
        WhatsAppQueueService $queue,
        TenantContext $tenantContext,
        PlatformAdminAuditService $audit,
    ): RedirectResponse {
        $whatsAppMessage = null;

        try {
            DB::transaction(function () use (
                $message,
                $messages,
                $queue,
                $tenantContext,
                &$whatsAppMessage
            ): void {
                $whatsAppMessage = WhatsAppMessage::query()
                    ->withoutGlobalScopes()
                    ->with('tenant')
                    ->lockForUpdate()
                    ->findOrFail($message);

                if ($whatsAppMessage->tenant === null) {
                    throw new \RuntimeException(
                        'O tenant desta mensagem não está disponível.'
                    );
                }

                if (blank($whatsAppMessage->provider)) {
                    throw new \RuntimeException(
                        'O provider desta mensagem não está disponível.'
                    );
                }

                $tenantContext->set(
                    $whatsAppMessage->tenant
                );

                $whatsAppMessage = $messages->retry(
                    $whatsAppMessage
                );

                $queue->dispatch(
                    $whatsAppMessage,
                    $whatsAppMessage->provider
                );
            });
        } catch (\Throwable $exception) {
            report($exception);

            return redirect()
                ->route('platform.whatsapp-failures')
                ->with(
                    'error',
                    'A mensagem WhatsApp não pôde ser reprocessada.'
                );
        } finally {
            $tenantContext->clear();
        }

        if ($whatsAppMessage !== null) {
            $whatsAppMessage->refresh();

            $audit->log(
                action: 'whatsapp.reprocessed',
                tenant: $whatsAppMessage->tenant,
                entityType: WhatsAppMessage::class,
                entityId: $whatsAppMessage->id,
                afterState: [
                    'status' => $whatsAppMessage->status->value,
                    'phone' => $whatsAppMessage->phone,
                    'provider' => $whatsAppMessage->provider,
                ],
                result: PlatformAdminAuditService::RESULT_SUCCESS,
            );
        }

        return redirect()
            ->route('platform.whatsapp-failures')
            ->with(
                'success',
                'Mensagem WhatsApp enviada para reprocessamento.'
            );
    }

    public function jobs(): View
    {
        $pendingJobs = DB::table('jobs')
            ->orderByDesc('id')
            ->paginate(
                50,
                ['*'],
                'pending_page'
            );

        $failedJobs = DB::table('failed_jobs')
            ->orderByDesc('failed_at')
            ->orderByDesc('id')
            ->paginate(
                50,
                ['*'],
                'failed_page'
            );

        return view('platform.jobs', [
            'pendingJobs' => $pendingJobs,
            'failedJobs' => $failedJobs,
        ]);
    }

    public function retryFailedJob(
        string $uuid
    ): RedirectResponse {
        $failedJob = DB::table('failed_jobs')
            ->where('uuid', $uuid)
            ->first();

        if ($failedJob === null) {
            return redirect()
                ->route('platform.jobs')
                ->with(
                    'error',
                    'O job falho não foi encontrado.'
                );
        }

        try {
            $exitCode = Artisan::call(
                'queue:retry',
                [
                    'id' => [
                        $uuid,
                    ],
                ]
            );

            if ($exitCode !== 0) {
                $output = trim(
                    Artisan::output()
                );

                throw new \RuntimeException(
                    $output !== ''
                        ? $output
                        : 'O Laravel não conseguiu reprocessar o job.'
                );
            }

            return redirect()
                ->route('platform.jobs')
                ->with(
                    'success',
                    'Job enviado para reprocessamento.'
                );
        } catch (\Throwable $exception) {
            report($exception);

            return redirect()
                ->route('platform.jobs')
                ->with(
                    'error',
                    'O job não pôde ser reprocessado.'
                );
        }
    }

    public function forgetFailedJob(
        string $uuid
    ): RedirectResponse {
        $failedJob = DB::table('failed_jobs')
            ->where('uuid', $uuid)
            ->first();

        if ($failedJob === null) {
            return redirect()
                ->route('platform.jobs')
                ->with(
                    'error',
                    'O job falho não foi encontrado.'
                );
        }

        try {
            $exitCode = Artisan::call(
                'queue:forget',
                [
                    'id' => $uuid,
                ]
            );

            if ($exitCode !== 0) {
                $output = trim(
                    Artisan::output()
                );

                throw new \RuntimeException(
                    $output !== ''
                        ? $output
                        : 'O Laravel não conseguiu remover o job falho.'
                );
            }

            return redirect()
                ->route('platform.jobs')
                ->with(
                    'success',
                    'Job falho removido.'
                );
        } catch (\Throwable $exception) {
            report($exception);

            return redirect()
                ->route('platform.jobs')
                ->with(
                    'error',
                    'O job falho não pôde ser removido.'
                );
        }
    }
    public function webhooks(Request $request): View
    {
        $status = trim(
            (string) $request->query('status', '')
        );

        $allowedStatuses = [
            'processed',
            'processing',
            'failed',
        ];

        $tenantId = $request->integer('tenant_id');

        $tenant = $tenantId > 0
            ? Tenant::query()
                ->withoutGlobalScopes()
                ->find($tenantId)
            : null;

        $externalReferences = $tenant !== null
            ? Subscription::query()
                ->withoutGlobalScopes()
                ->where(
                    'tenant_id',
                    $tenant->id
                )
                ->whereNotNull(
                    'external_reference'
                )
                ->pluck(
                    'external_reference'
                )
                ->filter()
                ->unique()
                ->values()
            : collect();

        $receipts = PaymentEventReceipt::query()
            ->when(
                in_array(
                    $status,
                    $allowedStatuses,
                    true
                ),
                fn ($query) => $query->where(
                    'status',
                    $status
                )
            )
            ->when(
                $tenant !== null,
                fn ($query) => $query->whereIn(
                    'external_reference',
                    $externalReferences
                )
            )
            ->orderByDesc('processed_at')
            ->orderByDesc('id')
            ->paginate(50)
            ->withQueryString();

        return view('platform.webhooks', [
            'receipts' => $receipts,
            'status' => $status,
            'tenant' => $tenant,
        ]);
    }

    public function retryWebhook(
        PaymentEventReceipt $receipt,
        StripeSubscriptionWebhookService $webhook,
        PlatformAdminAuditService $audit,
    ): RedirectResponse {
        if (
            $receipt->provider !== 'stripe'
            || $receipt->status !== 'failed'
            || ! is_array($receipt->payload)
        ) {
            return redirect()
                ->route('platform.webhooks')
                ->with(
                    'error',
                    'Este webhook não pode ser reprocessado.'
                );
        }

        try {
            $processed = $webhook->retry($receipt);

            if (! $processed) {
                return redirect()
                    ->route(
                        'platform.webhooks',
                        ['status' => 'failed']
                    )
                    ->with(
                        'error',
                        'O webhook não pôde ser reprocessado.'
                    );
            }
        } catch (\Throwable $exception) {
            report($exception);

            return redirect()
                ->route(
                    'platform.webhooks',
                    ['status' => 'failed']
                )
                ->with(
                    'error',
                    'O webhook não pôde ser reprocessado.'
                );
        }

        $subscription = Subscription::query()
            ->withoutGlobalScopes()
            ->where(
                'external_reference',
                $receipt->external_reference
            )
            ->first();

        $receipt->refresh();

        $audit->log(
            action: 'webhook.reprocessed',
            tenant: $subscription?->tenant,
            entityType: PaymentEventReceipt::class,
            entityId: $receipt->id,
            afterState: [
                'status' => $receipt->status,
                'attempts' => $receipt->attempts,
                'event_id' => $receipt->event_id,
                'event_type' => $receipt->event_type,
                'provider' => $receipt->provider,
            ],
            result: PlatformAdminAuditService::RESULT_SUCCESS,
        );

        return redirect()
            ->route('platform.webhooks')
            ->with(
                'success',
                'Webhook reprocessado com sucesso.'
            );
    }

    public function health(): View
    {
        $databaseOk = true;
        $queuePending = null;
        $queueFailed = null;
        $whatsAppConfiguredTenants = null;
        $schedulerLastRunAt = null;
        $schedulerHealthy = false;

        $schedulerHeartbeat = cache()->get(
            'platform.scheduler.last_run_at'
        );
        $workerLastSeenAt = null;
        $workerHealthy = false;

        $workerHeartbeat = cache()->get(
            'platform.worker.last_seen_at'
        );

        if (is_string($workerHeartbeat)) {
            try {
                $workerLastSeenAt = Carbon::parse(
                    $workerHeartbeat
                );

                $workerHealthy =
                    $workerLastSeenAt->gte(
                        now()->subMinutes(5)
                    );
            } catch (\Throwable) {
                $workerLastSeenAt = null;
                $workerHealthy = false;
            }
        }

        if (is_string($schedulerHeartbeat)) {
            try {
                $schedulerLastRunAt = Carbon::parse(
                    $schedulerHeartbeat
                );

                $schedulerHealthy =
                    $schedulerLastRunAt->gte(
                        now()->subMinutes(5)
                    );
            } catch (\Throwable) {
                $schedulerLastRunAt = null;
                $schedulerHealthy = false;
            }
        }

        try {
            DB::select('select 1');

            $queuePending =
                (int) DB::table('jobs')->count();

            $queueFailed =
                (int) DB::table('failed_jobs')->count();

            $whatsAppConfiguredTenants =
                (int) DB::table(
                    'whatsapp_provider_configs'
                )
                    ->where(
                        'active',
                        true
                    )
                    ->whereNotNull(
                        'provider'
                    )
                    ->whereNotNull(
                        'sender_id'
                    )
                    ->distinct()
                    ->count(
                        'tenant_id'
                    );
        } catch (\Throwable) {
            $databaseOk = false;
        }

        return view('platform.health', [
            'checks' => [
                'database' => $databaseOk,
                'storage' => is_writable(
                    storage_path()
                ),
                'worker_healthy' => $workerHealthy,
                'worker_last_seen_at' => $workerLastSeenAt,
                'scheduler_healthy' => $schedulerHealthy,
                'scheduler_last_run_at' => $schedulerLastRunAt,
                'queue_pending' => $queuePending,
                'queue_failed' => $queueFailed,
                'mail_configured' => config('mail.default') !== 'log',
                'contact_recipient' => trim(
                    (string) config(
                        'marketing.contact_recipient'
                    )
                ) !== '',
                'stripe_configured' => filled(
                    config(
                        'services.stripe.secret_key'
                    )
                ),
                'whatsapp_configured_tenants' => $whatsAppConfiguredTenants,
                'checked_at' => now(),
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
