<?php

namespace App\Http\Controllers;

use App\Enums\SubscriptionStatus;
use App\Models\Plan;
use App\Services\StripeCustomerPortalService;
use App\Services\StripeSubscriptionPlanChangeService;
use App\Services\SubscriptionBillingService;
use App\Services\SubscriptionCheckoutService;
use App\Services\SubscriptionService;
use App\Services\TenantContext;
use App\Support\SubscriptionPeriod;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class SubscriptionBillingController extends Controller
{
    public function index(
        TenantContext $tenantContext,
        SubscriptionBillingService $billing,
    ): View {
        $tenant = $tenantContext->get();
        $user = auth()->user();

        abort_unless(
            $user
            && $user->tenant_id === $tenant->id,
            403
        );

       $subscription = $tenant
    ->subscriptions()
    ->with([
        'plan.prices',
        'invoices' => function ($query): void {
            $query
                ->latest('period_end')
                ->latest('id');
        },
    ])
    ->latest('id')
    ->first();

        $isPaid = $subscription !== null
            && $billing->isPaid($subscription);

            $isActive = $subscription !== null
    && $subscription->status === SubscriptionStatus::ACTIVE;

            $availablePlans = Plan::query()
    ->where('active', true)
    ->with([
        'prices',
    ])
    ->orderBy('id')
    ->get()
    ->filter(
        function (Plan $plan) use ($tenant): bool {
            return $plan
                ->prices
                ->firstWhere(
                    'currency',
                    $tenant->currency
                ) !== null;
        }
    )
    ->values();

        return view(
            'billing.index',
           [
    'tenant' => $tenant,
    'subscription' => $subscription,
    'isPaid' => $isPaid,
    'isActive' => $isActive,
    'availablePlans' => $availablePlans,
]
        );
    }

    public function checkout(
        Request $request,
        TenantContext $tenantContext,
        SubscriptionCheckoutService $checkout,
        SubscriptionService $subscriptions,
    ): RedirectResponse {
        $tenant = $tenantContext->get();
        $user = auth()->user();

        abort_unless(
            $user
            && $user->tenant_id === $tenant->id,
            403
        );

        $providerCode = strtolower(
            trim(
                (string) $request->input(
                    'provider',
                    ''
                )
            )
        );

        if (
            ! in_array(
                $providerCode,
                [
                    'stripe',
                    'mercado_pago',
                ],
                true
            )
        ) {
            return redirect()
                ->route('billing.index')
                ->with(
                    'error',
                    'Provedor de pagamento inválido.'
                );
        }

        $subscription = $tenant
            ->subscriptions()
            ->latest('id')
            ->first();

        if ($subscription === null) {
            return redirect()
                ->route('billing.index')
                ->with(
                    'error',
                    'Nenhuma assinatura foi encontrada.'
                );
        }

        if (
            $subscription->status ===
                SubscriptionStatus::CANCELLED
            || $subscription->status ===
                SubscriptionStatus::EXPIRED
        ) {
            $subscription->loadMissing('plan');

            $periodStart =
                CarbonImmutable::now('UTC');

            $subscription = $subscriptions->create(
                $tenant,
                $subscription->plan,
                new SubscriptionPeriod(
                    $periodStart,
                    $periodStart->addMonthNoOverflow(),
                ),
            );
        }

        if (
            $subscription->status !==
            SubscriptionStatus::ACTIVE
        ) {
            return redirect()
                ->route('billing.index')
                ->with(
                    'error',
                    'A assinatura atual não pode iniciar um novo pagamento.'
                );
        }

        $result = $checkout->checkout(
            $subscription,
            $providerCode
        );

        if (
            ! $result->successful
            || $result->checkoutUrl === null
        ) {
            return redirect()
                ->route('billing.index')
                ->with(
                    'error',
                    $result->failureReason
                        ?? 'Não foi possível iniciar o pagamento.'
                );
        }

        return redirect()->away(
            $result->checkoutUrl
        );
    }

    public function changePlan(
        Request $request,
        TenantContext $tenantContext,
        StripeSubscriptionPlanChangeService $stripePlanChange,
    ): RedirectResponse {
        $tenant = $tenantContext->get();
        $user = auth()->user();

        abort_unless(
            $user
            && $user->tenant_id === $tenant->id,
            403
        );

        $planId = $request->integer(
            'plan_id'
        );

        if ($planId <= 0) {
            return redirect()
                ->route('billing.index')
                ->with(
                    'error',
                    'Plano inválido.'
                );
        }

        $subscription = $tenant
            ->subscriptions()
            ->latest('id')
            ->first();

        if ($subscription === null) {
            return redirect()
                ->route('billing.index')
                ->with(
                    'error',
                    'Nenhuma assinatura foi encontrada.'
                );
        }

        if (
            $subscription->status !==
            SubscriptionStatus::ACTIVE
        ) {
            return redirect()
                ->route('billing.index')
                ->with(
                    'error',
                    'A assinatura atual não pode trocar de plano.'
                );
        }

        if (
            $subscription->payment_provider
            !== 'stripe'
        ) {
            return redirect()
                ->route('billing.index')
                ->with(
                    'error',
                    'A troca de plano automática está disponível apenas para assinaturas Stripe.'
                );
        }

        $targetPlan = Plan::query()
            ->whereKey($planId)
            ->where('active', true)
            ->with('prices')
            ->first();

        if ($targetPlan === null) {
            return redirect()
                ->route('billing.index')
                ->with(
                    'error',
                    'Plano de destino não encontrado.'
                );
        }

        if (
            $subscription->plan_id
            === $targetPlan->id
        ) {
            return redirect()
                ->route('billing.index')
                ->with(
                    'success',
                    'Você já está neste plano.'
                );
        }

        try {
            $stripePlanChange->change(
                $subscription,
                $targetPlan
            );
        } catch (RuntimeException $exception) {
            return redirect()
                ->route('billing.index')
                ->with(
                    'error',
                    $exception->getMessage()
                );
        }

        return redirect()
            ->route('billing.index')
            ->with(
                'success',
                'Plano alterado com sucesso.'
            );
    }

    public function portal(
        TenantContext $tenantContext,
        StripeCustomerPortalService $portal,
    ): RedirectResponse {
        $tenant = $tenantContext->get();
        $user = auth()->user();

        abort_unless(
            $user
            && $user->tenant_id === $tenant->id,
            403
        );

        $subscription = $tenant
            ->subscriptions()
            ->latest('id')
            ->first();

        if (
            $subscription === null
            || $subscription->status !== SubscriptionStatus::ACTIVE
            || $subscription->payment_provider !== 'stripe'
            || $subscription->paid_at === null
        ) {
            return redirect()
                ->route('billing.index')
                ->with(
                    'error',
                    'Não existe uma assinatura Stripe paga para gerenciar.'
                );
        }

        try {
            $portalUrl = $portal->createSession(
                $subscription
            );
        } catch (RuntimeException $exception) {
            return redirect()
                ->route('billing.index')
                ->with(
                    'error',
                    $exception->getMessage()
                );
        }

        return redirect()->away(
            $portalUrl
        );
    }
}