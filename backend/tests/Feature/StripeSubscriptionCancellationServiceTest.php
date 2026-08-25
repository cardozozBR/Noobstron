<?php

namespace Tests\Feature;

use App\Enums\SubscriptionStatus;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Services\StripeSubscriptionCancellationService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class StripeSubscriptionCancellationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_schedules_stripe_subscription_cancellation_at_period_end(): void
    {
        config([
            'services.stripe.secret_key' => 'sk_test_cancel',
            'services.stripe.base_url' => 'https://api.stripe.test',
        ]);

        $periodEnd = CarbonImmutable::parse(
            '2026-09-25 12:00:00 UTC'
        );

        Http::fake([
            'https://api.stripe.test/v1/subscriptions/sub_cancel_123' =>
                Http::response([
                    'id' => 'sub_cancel_123',
                    'status' => 'active',
                    'cancel_at_period_end' => true,
                    'cancel_at' => $periodEnd->timestamp,
                    'current_period_end' => $periodEnd->timestamp,
                ], 200),
        ]);

        $tenant = Tenant::query()->create([
    'name' => 'Stripe Cancellation Tenant',
    'slug' => 'stripe-cancellation-tenant',
    'status' => 'active',
    'country_code' => 'BR',
    'locale' => 'pt-BR',
    'timezone' => 'America/Fortaleza',
    'currency' => 'BRL',
]);

$plan = Plan::query()->create([
    'code' => 'stripe-cancellation-plan',
    'name' => 'Stripe Cancellation Plan',
    'active' => true,
]);

        $subscription = Subscription::query()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => SubscriptionStatus::ACTIVE,
            'payment_provider' => 'stripe',
            'external_reference' => 'sub_cancel_123',
            'current_period_start' =>
                CarbonImmutable::parse(
                    '2026-08-25 12:00:00 UTC'
                ),
            'current_period_end' => $periodEnd,
        ]);

        $result = app(
            StripeSubscriptionCancellationService::class
        )->cancelAtPeriodEnd($subscription);

        Http::assertSent(
            function ($request): bool {
                return $request->method() === 'POST'
                    && $request->url()
                        === 'https://api.stripe.test/v1/subscriptions/sub_cancel_123'
                    && (string) $request[
                        'cancel_at_period_end'
                    ] === 'true';
            }
        );

        $this->assertSame(
            SubscriptionStatus::ACTIVE,
            $result->status
        );

        $this->assertSame(
            '2026-09-25 12:00:00',
            $result->cancel_at
                ->utc()
                ->format('Y-m-d H:i:s')
        );

        $this->assertNull(
            $result->canceled_at
        );
    }
}
