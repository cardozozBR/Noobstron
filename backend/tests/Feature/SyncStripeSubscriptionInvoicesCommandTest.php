<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SyncStripeSubscriptionInvoicesCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_syncs_stripe_subscription_invoices(): void
    {
        config()->set(
            'services.stripe.secret_key',
            'TEST_STRIPE_SECRET'
        );

        config()->set(
            'services.stripe.base_url',
            'https://api.stripe.test'
        );

        Http::fake([
            'https://api.stripe.test/v1/subscriptions/sub_command_123' =>
                Http::response([
                    'id' => 'sub_command_123',
                    'customer' => 'cus_command_123',
                ], 200),

            'https://api.stripe.test/v1/invoices*' =>
                Http::response([
                    'data' => [
                        [
                            'id' => 'in_command_123',
                            'subscription' =>
                                'sub_command_123',
                            'status' => 'paid',
                            'currency' => 'brl',
                            'amount_due' => 9900,
                            'amount_paid' => 9900,
                            'amount_remaining' => 0,
                            'billing_reason' =>
                                'subscription_cycle',
                            'status_transitions' => [
                                'paid_at' =>
                                    CarbonImmutable::parse(
                                        '2026-09-20 00:01:00 UTC'
                                    )->timestamp,
                            ],
                            'lines' => [
                                'data' => [
                                    [
                                        'period' => [
                                            'start' =>
                                                CarbonImmutable::parse(
                                                    '2026-09-20 00:00:00 UTC'
                                                )->timestamp,
                                            'end' =>
                                                CarbonImmutable::parse(
                                                    '2026-10-20 00:00:00 UTC'
                                                )->timestamp,
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ], 200),
        ]);

        $subscription = $this->subscription(
            'stripe',
            'sub_command_123'
        );

        $this->artisan(
            'subscriptions:sync-stripe-invoices'
        )
            ->expectsOutput(
                'Subscription #'
                . $subscription->id
                . ': 1 invoice(s) sincronizada(s).'
            )
            ->expectsOutput(
                'Assinaturas processadas: 1'
            )
            ->expectsOutput(
                'Invoices sincronizadas: 1'
            )
            ->assertSuccessful();

        $this->assertDatabaseHas(
            'subscription_invoices',
            [
                'subscription_id' =>
                    $subscription->id,
                'provider' => 'stripe',
                'external_invoice_id' =>
                    'in_command_123',
                'status' => 'paid',
                'amount_paid' => 9900,
            ]
        );
    }

    public function test_command_succeeds_when_there_are_no_stripe_subscriptions(): void
    {
        $this->subscription(
            'mercado_pago',
            'mp_command_123'
        );

        Http::fake();

        $this->artisan(
            'subscriptions:sync-stripe-invoices'
        )
            ->expectsOutput(
                'Nenhuma assinatura Stripe encontrada.'
            )
            ->assertSuccessful();

        Http::assertNothingSent();
    }

    public function test_command_returns_failure_when_stripe_sync_fails(): void
    {
        config()->set(
            'services.stripe.secret_key',
            'TEST_STRIPE_SECRET'
        );

        config()->set(
            'services.stripe.base_url',
            'https://api.stripe.test'
        );

        Http::fake([
            'https://api.stripe.test/v1/subscriptions/sub_command_fail' =>
                Http::response([
                    'error' => [
                        'message' => 'Failure',
                    ],
                ], 500),
        ]);

        $subscription = $this->subscription(
            'stripe',
            'sub_command_fail'
        );

        $this->artisan(
            'subscriptions:sync-stripe-invoices'
        )
            ->expectsOutput(
                'Subscription #'
                . $subscription->id
                . ': Stripe subscription lookup was rejected.'
            )
            ->expectsOutput(
                'Assinaturas processadas: 0'
            )
            ->expectsOutput(
                'Invoices sincronizadas: 0'
            )
            ->expectsOutput(
                'Falhas: 1'
            )
            ->assertFailed();
    }

    private function subscription(
        string $provider,
        string $externalReference
    ): Subscription {
        $tenant = Tenant::query()->create([
            'name' => 'Stripe Command Tenant',
            'slug' => uniqid(
                'stripe-command-',
                true
            ),
            'status' => 'active',
            'currency' => 'BRL',
        ]);

        $plan = Plan::query()->create([
            'code' => uniqid(
                'stripe-command-plan-',
                false
            ),
            'name' => 'Stripe Command Plan',
            'active' => true,
        ]);

        return Subscription::query()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'payment_provider' => $provider,
            'external_reference' =>
                $externalReference,
            'paid_at' =>
                CarbonImmutable::parse(
                    '2026-08-20 12:00:00 UTC'
                ),
            'current_period_start' =>
                CarbonImmutable::parse(
                    '2026-08-20 00:00:00 UTC'
                ),
            'current_period_end' =>
                CarbonImmutable::parse(
                    '2026-09-20 00:00:00 UTC'
                ),
        ]);
    }
}