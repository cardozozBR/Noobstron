<?php

namespace Tests\Feature;

use App\Enums\Feature;
use App\Models\Tenant;
use App\Support\TenantCapabilities;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantCapabilitiesTest extends TestCase
{
    use RefreshDatabase;

    private function tenant(
        string $slug
    ): Tenant {
        return Tenant::create([
            'name' => ucfirst($slug),
            'slug' => $slug,
            'status' => 'active',
            'country_code' => 'BR',
            'locale' => 'pt-BR',
            'timezone' => 'America/Fortaleza',
            'currency' => 'BRL',
        ]);
    }

    public function test_missing_feature_is_disabled_by_default(): void
    {
        $tenant = $this->tenant(
            'tenant-capabilities-default'
        );

        $capabilities = app(
            TenantCapabilities::class
        );

        $this->assertFalse(
            $capabilities->enabled(
                $tenant,
                Feature::AUDIT
            )
        );
    }

    public function test_feature_can_be_enabled(): void
    {
        $tenant = $this->tenant(
            'tenant-capabilities-enabled'
        );

        $capabilities = app(
            TenantCapabilities::class
        );

        $capabilities->set(
            $tenant,
            Feature::AUDIT,
            true
        );

        $this->assertTrue(
            $capabilities->enabled(
                $tenant,
                Feature::AUDIT
            )
        );
    }

    public function test_feature_can_be_disabled(): void
    {
        $tenant = $this->tenant(
            'tenant-capabilities-disabled'
        );

        $capabilities = app(
            TenantCapabilities::class
        );

        $capabilities->set(
            $tenant,
            Feature::USERS,
            true
        );

        $capabilities->set(
            $tenant,
            Feature::USERS,
            false
        );

        $this->assertFalse(
            $capabilities->enabled(
                $tenant,
                Feature::USERS
            )
        );
    }

    public function test_setting_feature_is_idempotent(): void
    {
        $tenant = $this->tenant(
            'tenant-capabilities-idempotent'
        );

        $capabilities = app(
            TenantCapabilities::class
        );

        $capabilities->set(
            $tenant,
            Feature::BRANDING,
            true
        );

        $capabilities->set(
            $tenant,
            Feature::BRANDING,
            true
        );

        $this->assertDatabaseCount(
            'tenant_features',
            1
        );

        $this->assertTrue(
            $capabilities->enabled(
                $tenant,
                Feature::BRANDING
            )
        );
    }

    public function test_capabilities_are_isolated_between_tenants(): void
    {
        $tenantA = $this->tenant(
            'tenant-capabilities-a'
        );

        $tenantB = $this->tenant(
            'tenant-capabilities-b'
        );

        $capabilities = app(
            TenantCapabilities::class
        );

        $capabilities->set(
            $tenantA,
            Feature::AUDIT,
            true
        );

        $this->assertTrue(
            $capabilities->enabled(
                $tenantA,
                Feature::AUDIT
            )
        );

        $this->assertFalse(
            $capabilities->enabled(
                $tenantB,
                Feature::AUDIT
            )
        );
    }
    public function test_missing_limit_is_null(): void
    {
        $tenant = $this->tenant(
            'tenant-capabilities-limit-null'
        );

        $capabilities = app(
            TenantCapabilities::class
        );

        $this->assertNull(
            $capabilities->limit(
                $tenant,
                Feature::USERS
            )
        );
    }

    public function test_limit_can_be_zero(): void
    {
        $tenant = $this->tenant(
            'tenant-capabilities-limit-zero'
        );

        $capabilities = app(
            TenantCapabilities::class
        );

        $capabilities->setLimit(
            $tenant,
            Feature::USERS,
            0
        );

        $this->assertSame(
            0,
            $capabilities->limit(
                $tenant,
                Feature::USERS
            )
        );
    }

    public function test_positive_limit_can_be_defined(): void
    {
        $tenant = $this->tenant(
            'tenant-capabilities-limit-positive'
        );

        $capabilities = app(
            TenantCapabilities::class
        );

        $capabilities->setLimit(
            $tenant,
            Feature::USERS,
            25
        );

        $this->assertSame(
            25,
            $capabilities->limit(
                $tenant,
                Feature::USERS
            )
        );
    }

    public function test_limit_can_be_removed(): void
    {
        $tenant = $this->tenant(
            'tenant-capabilities-limit-remove'
        );

        $capabilities = app(
            TenantCapabilities::class
        );

        $capabilities->setLimit(
            $tenant,
            Feature::USERS,
            10
        );

        $capabilities->setLimit(
            $tenant,
            Feature::USERS,
            null
        );

        $this->assertNull(
            $capabilities->limit(
                $tenant,
                Feature::USERS
            )
        );
    }

    public function test_negative_limit_is_rejected(): void
    {
        $tenant = $this->tenant(
            'tenant-capabilities-limit-negative'
        );

        $capabilities = app(
            TenantCapabilities::class
        );

        $this->expectException(
            \InvalidArgumentException::class
        );

        $capabilities->setLimit(
            $tenant,
            Feature::USERS,
            -1
        );
    }

    public function test_setting_limit_is_idempotent(): void
    {
        $tenant = $this->tenant(
            'tenant-capabilities-limit-idempotent'
        );

        $capabilities = app(
            TenantCapabilities::class
        );

        $capabilities->setLimit(
            $tenant,
            Feature::USERS,
            100
        );

        $capabilities->setLimit(
            $tenant,
            Feature::USERS,
            100
        );

        $this->assertDatabaseCount(
            'tenant_features',
            1
        );

        $this->assertSame(
            100,
            $capabilities->limit(
                $tenant,
                Feature::USERS
            )
        );
    }

    public function test_limits_are_isolated_between_tenants(): void
    {
        $tenantA = $this->tenant(
            'tenant-capabilities-limit-a'
        );

        $tenantB = $this->tenant(
            'tenant-capabilities-limit-b'
        );

        $capabilities = app(
            TenantCapabilities::class
        );

        $capabilities->setLimit(
            $tenantA,
            Feature::USERS,
            5
        );

        $capabilities->setLimit(
            $tenantB,
            Feature::USERS,
            50
        );

        $this->assertSame(
            5,
            $capabilities->limit(
                $tenantA,
                Feature::USERS
            )
        );

        $this->assertSame(
            50,
            $capabilities->limit(
                $tenantB,
                Feature::USERS
            )
        );
    }

    public function test_feature_can_be_configured_with_state_and_limit(): void
    {
        $tenant = $this->tenant(
            'tenant-capabilities-configure'
        );

        $capabilities = app(
            TenantCapabilities::class
        );

        $capabilities->configure(
            $tenant,
            Feature::USERS,
            true,
            15
        );

        $this->assertTrue(
            $capabilities->enabled(
                $tenant,
                Feature::USERS
            )
        );

        $this->assertSame(
            15,
            $capabilities->limit(
                $tenant,
                Feature::USERS
            )
        );
    }

    public function test_capability_profile_can_configure_multiple_features(): void
    {
        $tenant = $this->tenant(
            'tenant-capabilities-profile'
        );

        $capabilities = app(
            TenantCapabilities::class
        );

        $capabilities->applyProfile(
            $tenant,
            [
                [
                    'feature' => Feature::USERS,
                    'enabled' => true,
                    'limit' => 10,
                ],
                [
                    'feature' => Feature::AUDIT,
                    'enabled' => true,
                    'limit' => null,
                ],
                [
                    'feature' => Feature::BRANDING,
                    'enabled' => false,
                    'limit' => null,
                ],
            ]
        );

        $this->assertTrue(
            $capabilities->enabled(
                $tenant,
                Feature::USERS
            )
        );

        $this->assertSame(
            10,
            $capabilities->limit(
                $tenant,
                Feature::USERS
            )
        );

        $this->assertTrue(
            $capabilities->enabled(
                $tenant,
                Feature::AUDIT
            )
        );

        $this->assertFalse(
            $capabilities->enabled(
                $tenant,
                Feature::BRANDING
            )
        );
    }

    public function test_applying_profile_is_idempotent(): void
    {
        $tenant = $this->tenant(
            'tenant-capabilities-profile-idempotent'
        );

        $capabilities = app(
            TenantCapabilities::class
        );

        $profile = [
            [
                'feature' => Feature::USERS,
                'enabled' => true,
                'limit' => 20,
            ],
            [
                'feature' => Feature::AUDIT,
                'enabled' => true,
                'limit' => null,
            ],
        ];

        $capabilities->applyProfile(
            $tenant,
            $profile
        );

        $capabilities->applyProfile(
            $tenant,
            $profile
        );

        $this->assertSame(
            2,
            \App\Models\TenantFeature::query()
                ->where('tenant_id', $tenant->id)
                ->count()
        );
    }

    public function test_profile_application_is_isolated_between_tenants(): void
    {
        $tenantA = $this->tenant(
            'tenant-profile-a'
        );

        $tenantB = $this->tenant(
            'tenant-profile-b'
        );

        $capabilities = app(
            TenantCapabilities::class
        );

        $capabilities->applyProfile(
            $tenantA,
            [
                [
                    'feature' => Feature::USERS,
                    'enabled' => true,
                    'limit' => 5,
                ],
            ]
        );

        $capabilities->applyProfile(
            $tenantB,
            [
                [
                    'feature' => Feature::USERS,
                    'enabled' => true,
                    'limit' => 50,
                ],
            ]
        );

        $this->assertSame(
            5,
            $capabilities->limit(
                $tenantA,
                Feature::USERS
            )
        );

        $this->assertSame(
            50,
            $capabilities->limit(
                $tenantB,
                Feature::USERS
            )
        );
    }

    public function test_invalid_profile_is_rejected(): void
    {
        $tenant = $this->tenant(
            'tenant-profile-invalid'
        );

        $capabilities = app(
            TenantCapabilities::class
        );

        $this->expectException(
            \InvalidArgumentException::class
        );

        $capabilities->applyProfile(
            $tenant,
            [
                [
                    'feature' => 'users',
                    'enabled' => true,
                    'limit' => 10,
                ],
            ]
        );
    }
}