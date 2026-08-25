<?php

namespace App\Http\Controllers;

use App\Models\AiUsageRecord;
use App\Models\EmailMessage;
use App\Models\PaymentEventReceipt;
use App\Models\PlanUsageLimit;
use App\Models\Subscription;
use App\Models\SubscriptionInvoice;
use App\Models\Tenant;
use App\Models\TenantFeature;
use App\Models\WhatsAppMessage;
use App\Services\PlatformAdminAuditService;
use App\Services\TrialService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PlatformTenantController extends Controller
{
    public function index(
        Request $request
    ): View {
        $query = Tenant::query()
            ->orderBy('name')
            ->orderBy('id');

        $search = trim(
            (string) $request->query(
                'q',
                ''
            )
        );

        if ($search !== '') {
            $query->where(
                function ($query) use ($search): void {
                    $query
                        ->where(
                            'name',
                            'like',
                            '%'.$search.'%'
                        )
                        ->orWhere(
                            'slug',
                            'like',
                            '%'.$search.'%'
                        );
                }
            );
        }

        $status = trim(
            (string) $request->query(
                'status',
                ''
            )
        );

        if ($status !== '') {
            $query->where(
                'status',
                $status
            );
        }

        $tenants = $query
            ->paginate(25)
            ->withQueryString();

        $tenantIds = $tenants
            ->getCollection()
            ->pluck('id');

        $userCounts = $this->userCounts(
            $tenantIds
        );

        $subscriptions =
            $this->latestSubscriptions(
                $tenantIds
            );

        return view(
            'platform.tenants.index',
            [
                'tenants' => $tenants,
                'userCounts' => $userCounts,
                'subscriptions' => $subscriptions,
                'search' => $search,
                'status' => $status,
            ]
        );
    }

    public function show(
        Tenant $tenant
    ): View {
        $subscription =
            Subscription::query()
                ->where(
                    'tenant_id',
                    $tenant->id
                )
                ->with('plan')
                ->orderByDesc('id')
                ->first();

        $features =
            TenantFeature::query()
                ->where(
                    'tenant_id',
                    $tenant->id
                )
                ->orderBy('feature')
                ->get();

        $usageLimits = collect();

        if ($subscription?->plan_id) {
            $usageLimits =
                PlanUsageLimit::query()
                    ->where(
                        'plan_id',
                        $subscription->plan_id
                    )
                    ->orderBy('metric')
                    ->get();
        }

        $userCount = (int) DB::table(
            'users'
        )
            ->where(
                'tenant_id',
                $tenant->id
            )
            ->count();

        $subscriptionHistory =
            Subscription::query()
                ->where(
                    'tenant_id',
                    $tenant->id
                )
                ->with('plan')
                ->orderByDesc('id')
                ->limit(20)
                ->get();

        $subscriptionIds =
            $subscriptionHistory
                ->pluck('id');

        $invoices = $subscriptionIds->isEmpty()
            ? collect()
            : SubscriptionInvoice::query()
                ->whereIn(
                    'subscription_id',
                    $subscriptionIds
                )
                ->orderByDesc('id')
                ->limit(20)
                ->get();

        $emailFailures =
            EmailMessage::query()
                ->withoutGlobalScopes()
                ->where(
                    'tenant_id',
                    $tenant->id
                )
                ->where(
                    'status',
                    'failed'
                )
                ->orderByDesc('id')
                ->limit(10)
                ->get();

        $whatsAppFailures =
            WhatsAppMessage::query()
                ->withoutGlobalScopes()
                ->where(
                    'tenant_id',
                    $tenant->id
                )
                ->where(
                    'status',
                    'failed'
                )
                ->orderByDesc('id')
                ->limit(10)
                ->get();

        $usage = [
            'users' => $userCount,

            'email_messages' => (int) EmailMessage::query()
                ->withoutGlobalScopes()
                ->where(
                    'tenant_id',
                    $tenant->id
                )
                ->count(),

            'whatsapp_messages' => (int) WhatsAppMessage::query()
                ->withoutGlobalScopes()
                ->where(
                    'tenant_id',
                    $tenant->id
                )
                ->where(
                    'direction',
                    'outbound'
                )
                ->count(),

            'ai_tokens' => (int) AiUsageRecord::query()
                ->where(
                    'tenant_id',
                    $tenant->id
                )
                ->sum(
                    'total_tokens'
                ),
        ];

        $externalReferences =
            $subscriptionHistory
                ->pluck(
                    'external_reference'
                )
                ->filter()
                ->unique()
                ->values();

        $webhooks = $externalReferences->isEmpty()
            ? collect()
            : PaymentEventReceipt::query()
                ->whereIn(
                    'external_reference',
                    $externalReferences
                )
                ->orderByDesc('id')
                ->limit(20)
                ->get();

        return view(
            'platform.tenants.show',
            [
                'tenant' => $tenant,
                'subscription' => $subscription,
                'features' => $features,
                'usageLimits' => $usageLimits,
                'userCount' => $userCount,
                'subscriptionHistory' => $subscriptionHistory,
                'invoices' => $invoices,
                'usage' => $usage,
                'emailFailures' => $emailFailures,
                'whatsAppFailures' => $whatsAppFailures,
                'webhooks' => $webhooks,
            ]
        );
    }

    private function userCounts(
        Collection $tenantIds
    ): Collection {
        if ($tenantIds->isEmpty()) {
            return collect();
        }

        return DB::table('users')
            ->select(
                'tenant_id',
                DB::raw(
                    'COUNT(*) as aggregate'
                )
            )
            ->whereIn(
                'tenant_id',
                $tenantIds
            )
            ->groupBy('tenant_id')
            ->pluck(
                'aggregate',
                'tenant_id'
            );
    }

    private function latestSubscriptions(
        Collection $tenantIds
    ): Collection {
        if ($tenantIds->isEmpty()) {
            return collect();
        }

        return Subscription::query()
            ->whereIn(
                'tenant_id',
                $tenantIds
            )
            ->with('plan')
            ->orderByDesc('id')
            ->get()
            ->unique('tenant_id')
            ->keyBy('tenant_id');
    }

    public function extendTrial(
        Request $request,
        Tenant $tenant,
        TrialService $trialService,
        PlatformAdminAuditService $audit,
    ): RedirectResponse {
        $validated = $request->validate([
            'days' => [
                'required',
                'integer',
                'min:1',
                'max:90',
            ],
            'reason' => [
                'nullable',
                'string',
                'max:500',
            ],
        ]);

        $before = [
            'status' => $tenant->status,
            'trial_started_at' => $tenant->trial_started_at?->toISOString(),
            'trial_ends_at' => $tenant->trial_ends_at?->toISOString(),
        ];

        try {
            DB::transaction(
                function () use (
                    $tenant,
                    $trialService,
                    $audit,
                    $request,
                    $validated,
                    $before
                ): void {
                    $trialService->extend(
                        $tenant,
                        (int) $validated['days']
                    );

                    $tenant->refresh();

                    $after = [
                        'status' => $tenant->status,
                        'trial_started_at' => $tenant
                            ->trial_started_at
                            ?->toISOString(),
                        'trial_ends_at' => $tenant
                            ->trial_ends_at
                            ?->toISOString(),
                    ];

                    $audit->log(
                        action: 'tenant.trial_extended',
                        tenant: $tenant,
                        entityType: Tenant::class,
                        entityId: $tenant->id,
                        beforeState: $before,
                        afterState: $after,
                        reason: $validated['reason']
                            ?? null,
                        request: $request,
                    );
                }
            );

            return redirect()
                ->route(
                    'platform.tenants.show',
                    $tenant
                )
                ->with(
                    'success',
                    'Trial prorrogado por '
                    .$validated['days']
                    .' dia(s).'
                );
        } catch (\Throwable $exception) {
            try {
                $audit->log(
                    action: 'tenant.trial_extended',
                    tenant: $tenant,
                    entityType: Tenant::class,
                    entityId: $tenant->id,
                    beforeState: $before,
                    result: PlatformAdminAuditService::RESULT_FAILURE,
                    reason: $exception->getMessage(),
                    request: $request,
                );
            } catch (\Throwable) {
                // A falha principal não deve ser mascarada
                // por eventual indisponibilidade da auditoria.
            }

            return redirect()
                ->route(
                    'platform.tenants.show',
                    $tenant
                )
                ->withErrors([
                    'trial' => 'Não foi possível prorrogar o trial.',
                ]);
        }
    }
}
