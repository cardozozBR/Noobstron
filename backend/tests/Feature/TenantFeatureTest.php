<?php

namespace Tests\Feature;

use App\Enums\Feature;
use App\Models\Tenant;
use App\Models\TenantFeature;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantFeatureTest extends TestCase
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

    public function test_feature_can_be_assigned_to_tenant(): void
    {
        $tenant = $this->tenant(
            'tenant-feature-a'
        );

        $feature = TenantFeature::create([
            'tenant_id' => $tenant->id,
            'feature' => Feature::AUDIT,
            'enabled' => true,
        ]);

        $this->assertSame(
            Feature::AUDIT,
            $feature->feature
        );

        $this->assertTrue(
            $feature->enabled
        );

        $this->assertSame(
            $tenant->id,
            $feature->tenant->id
        );
    }

    public function test_tenant_has_feature_relation(): void
    {
        $tenant = $this->tenant(
            'tenant-feature-relation'
        );

        TenantFeature::create([
            'tenant_id' => $tenant->id,
            'feature' => Feature::USERS,
            'enabled' => true,
        ]);

        $this->assertCount(
            1,
            $tenant->features
        );

        $this->assertSame(
            Feature::USERS,
            $tenant->features->first()->feature
        );
    }

    public function test_same_feature_can_have_different_state_between_tenants(): void
    {
        $tenantA = $this->tenant(
            'tenant-feature-isolated-a'
        );

        $tenantB = $this->tenant(
            'tenant-feature-isolated-b'
        );

        TenantFeature::create([
            'tenant_id' => $tenantA->id,
            'feature' => Feature::BRANDING,
            'enabled' => true,
        ]);

        TenantFeature::create([
            'tenant_id' => $tenantB->id,
            'feature' => Feature::BRANDING,
            'enabled' => false,
        ]);

        $featureA = TenantFeature::where(
            'tenant_id',
            $tenantA->id
        )->firstOrFail();

        $featureB = TenantFeature::where(
            'tenant_id',
            $tenantB->id
        )->firstOrFail();

        $this->assertTrue(
            $featureA->enabled
        );

        $this->assertFalse(
            $featureB->enabled
        );
    }

    public function test_feature_value_is_cast_to_enum(): void
    {
        $tenant = $this->tenant(
            'tenant-feature-cast'
        );

        $feature = TenantFeature::create([
            'tenant_id' => $tenant->id,
            'feature' => 'users',
            'enabled' => true,
        ]);

        $feature->refresh();

        $this->assertSame(
            Feature::USERS,
            $feature->feature
        );
    }
}