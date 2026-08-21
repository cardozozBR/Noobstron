<?php

namespace Tests\Feature;

use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantTrialTest extends TestCase
{
    use RefreshDatabase;

    public function test_trial_dates_can_be_persisted(): void
    {
        $tenant = Tenant::query()->create([
            'name' => 'Trial Tenant',
            'slug' => 'trial-tenant',
            'status' => 'active',
            'trial_started_at' => '2026-08-17 12:00:00',
            'trial_ends_at' => '2026-08-31 12:00:00',
        ]);

        $tenant->refresh();

        $this->assertSame(
            '2026-08-17 12:00:00',
            $tenant->trial_started_at
                ->utc()
                ->format('Y-m-d H:i:s')
        );

        $this->assertSame(
            '2026-08-31 12:00:00',
            $tenant->trial_ends_at
                ->utc()
                ->format('Y-m-d H:i:s')
        );
    }

    public function test_trial_dates_are_optional(): void
    {
        $tenant = Tenant::query()->create([
            'name' => 'Regular Tenant',
            'slug' => 'regular-tenant',
            'status' => 'active',
        ]);

        $this->assertNull(
            $tenant->trial_started_at
        );

        $this->assertNull(
            $tenant->trial_ends_at
        );
    }
}