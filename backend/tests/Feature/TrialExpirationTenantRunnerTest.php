<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Services\TenantContext;
use App\Services\TrialExpirationTenantRunner;
use App\Services\TrialService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class TrialExpirationTenantRunnerTest extends TestCase
{
    use RefreshDatabase;

    public function test_runner_blocks_expired_active_tenants(): void
    {
        $tenant = $this->startedTenant(
            'expired-active'
        );

        $count = app(
            TrialExpirationTenantRunner::class
        )->dispatch(
            $this->expirationMoment()
        );

        $this->assertSame(
            1,
            $count
        );

        $this->assertSame(
            'blocked',
            $tenant->refresh()->status
        );
    }

    public function test_runner_ignores_active_trial(): void
    {
        $tenant = $this->startedTenant(
            'active-trial'
        );

        $count = app(
            TrialExpirationTenantRunner::class
        )->dispatch(
            CarbonImmutable::parse(
                '2026-08-20 12:00:00',
                'UTC'
            )
        );

        $this->assertSame(
            0,
            $count
        );

        $this->assertSame(
            'active',
            $tenant->refresh()->status
        );
    }

    public function test_runner_ignores_tenant_without_trial(): void
    {
        $tenant = $this->tenant(
            'without-trial'
        );

        $count = app(
            TrialExpirationTenantRunner::class
        )->dispatch(
            $this->expirationMoment()
        );

        $this->assertSame(
            0,
            $count
        );

        $this->assertSame(
            'active',
            $tenant->refresh()->status
        );
    }

    public function test_runner_preserves_non_active_status(): void
    {
        $tenant = $this->startedTenant(
            'suspended-trial'
        );

        $tenant->forceFill([
            'status' => 'suspended',
        ])->save();

        $count = app(
            TrialExpirationTenantRunner::class
        )->dispatch(
            $this->expirationMoment()
        );

        $this->assertSame(
            0,
            $count
        );

        $this->assertSame(
            'suspended',
            $tenant->refresh()->status
        );
    }

    public function test_runner_processes_multiple_expired_tenants(): void
    {
        $first = $this->startedTenant(
            'expired-a'
        );

        $second = $this->startedTenant(
            'expired-b'
        );

        $count = app(
            TrialExpirationTenantRunner::class
        )->dispatch(
            $this->expirationMoment()
        );

        $this->assertSame(
            2,
            $count
        );

        $this->assertSame(
            'blocked',
            $first->refresh()->status
        );

        $this->assertSame(
            'blocked',
            $second->refresh()->status
        );
    }

    public function test_runner_is_idempotent(): void
    {
        $tenant = $this->startedTenant(
            'idempotent-trial'
        );

        $runner = app(
            TrialExpirationTenantRunner::class
        );

        $this->assertSame(
            1,
            $runner->dispatch(
                $this->expirationMoment()
            )
        );

        $this->assertSame(
            0,
            $runner->dispatch(
                $this->expirationMoment()
            )
        );

        $this->assertSame(
            'blocked',
            $tenant->refresh()->status
        );
    }

    public function test_runner_preserves_trial_dates(): void
    {
        $tenant = $this->startedTenant(
            'preserve-dates'
        );

        $startedAt =
            $tenant->trial_started_at;

        $endsAt =
            $tenant->trial_ends_at;

        app(
            TrialExpirationTenantRunner::class
        )->dispatch(
            $this->expirationMoment()
        );

        $tenant->refresh();

        $this->assertTrue(
            $tenant->trial_started_at
                ->equalTo($startedAt)
        );

        $this->assertTrue(
            $tenant->trial_ends_at
                ->equalTo($endsAt)
        );
    }

    public function test_runner_clears_tenant_context(): void
    {
        $this->startedTenant(
            'context-clear'
        );

        app(
            TrialExpirationTenantRunner::class
        )->dispatch(
            $this->expirationMoment()
        );

        $this->expectException(
            RuntimeException::class
        );

        app(
            TenantContext::class
        )->get();
    }

    public function test_artisan_command_executes_successfully(): void
    {
        $tenant = $this->startedTenant(
            'artisan-expired'
        );

        CarbonImmutable::setTestNow(
            $this->expirationMoment()
        );

        try {
            $this->artisan(
                'trials:block-expired'
            )
                ->expectsOutput(
                    'Expired trials blocked: 1'
                )
                ->assertExitCode(0);
        } finally {
            CarbonImmutable::setTestNow();
        }

        $this->assertSame(
            'blocked',
            $tenant->refresh()->status
        );
    }

    private function tenant(
        string $slug
    ): Tenant {
        return Tenant::query()->create([
            'name' => 'Trial Runner Tenant',
            'slug' => $slug,
            'status' => 'active',
        ]);
    }

    private function startedTenant(
        string $slug
    ): Tenant {
        return app(
            TrialService::class
        )->start(
            $this->tenant($slug),
            CarbonImmutable::parse(
                '2026-08-17 12:00:00',
                'UTC'
            )
        );
    }

    private function expirationMoment(): CarbonImmutable
    {
        return CarbonImmutable::parse(
            '2026-08-31 12:00:00',
            'UTC'
        );
    }
}