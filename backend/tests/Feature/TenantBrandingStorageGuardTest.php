<?php

namespace Tests\Feature;

use App\Enums\SubscriptionStatus;
use App\Enums\UsageMetric;
use App\Exceptions\UsageBlockedException;
use App\Models\Import;
use App\Models\Plan;
use App\Models\PlanUsageLimit;
use App\Models\Tenant;
use App\Services\TenantBrandingStorageGuard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TenantBrandingStorageGuardTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_logo_is_allowed_when_it_fits_storage_limit(): void
    {
        Storage::fake('public');

        [$tenant, $plan] =
            $this->subscribedTenant(
                'branding-storage-allowed'
            );

        $logo = UploadedFile::fake()
            ->create(
                'logo.png',
                100,
                'image/png'
            );

        $this->storageLimit(
            $plan,
            (int) $logo->getSize()
        );

        app(
            TenantBrandingStorageGuard::class
        )->assertCanStoreLogo(
            $tenant,
            $logo
        );

        $this->addToAssertionCount(1);
    }

    public function test_new_logo_is_blocked_when_projected_storage_exceeds_limit(): void
    {
        Storage::fake('public');

        [$tenant, $plan] =
            $this->subscribedTenant(
                'branding-storage-blocked'
            );

        $logo = UploadedFile::fake()
            ->create(
                'blocked.png',
                100,
                'image/png'
            );

        $size = (int) $logo->getSize();

        $this->storageLimit(
            $plan,
            max(
                0,
                $size - 1
            )
        );

        try {
            app(
                TenantBrandingStorageGuard::class
            )->assertCanStoreLogo(
                $tenant,
                $logo
            );

            $this->fail(
                'Expected UsageBlockedException.'
            );
        } catch (
            UsageBlockedException $exception
        ) {
            $this->assertSame(
                UsageMetric::STORAGE_BYTES,
                $exception->metric
            );

            $this->assertSame(
                'limit_exceeded',
                $exception->reason
            );

            $this->assertSame(
                $size,
                $exception->requested
            );

            $this->assertTrue(
                $exception->upgradeSuggested
            );
        }
    }

    public function test_existing_import_storage_is_included_when_adding_logo(): void
    {
        Storage::fake('public');

        [$tenant, $plan] =
            $this->subscribedTenant(
                'branding-storage-import'
            );

        $this->existingImport(
            $tenant,
            100
        );

        $logo = UploadedFile::fake()
            ->create(
                'logo.png',
                50,
                'image/png'
            );

        $logoSize = (int) $logo->getSize();

        $this->storageLimit(
            $plan,
            100 + $logoSize - 1
        );

        try {
            app(
                TenantBrandingStorageGuard::class
            )->assertCanStoreLogo(
                $tenant,
                $logo
            );

            $this->fail(
                'Expected projected storage blocking.'
            );
        } catch (
            UsageBlockedException $exception
        ) {
            $this->assertSame(
                100,
                $exception->used
            );

            $this->assertSame(
                $logoSize,
                $exception->requested
            );

            $this->assertSame(
                $logoSize - 1,
                $exception->remaining
            );
        }
    }

    public function test_replacing_logo_only_consumes_positive_size_delta(): void
    {
        Storage::fake('public');

        [$tenant, $plan] =
            $this->subscribedTenant(
                'branding-storage-delta'
            );

        $oldPath =
            'tenant-branding/'
            . $tenant->id
            . '/old.png';

        Storage::disk('public')
            ->put(
                $oldPath,
                str_repeat(
                    'A',
                    1000
                )
            );

        $tenant->logo_path =
            $oldPath;

        $tenant->save();

        $newLogo = UploadedFile::fake()
            ->createWithContent(
                'new.png',
                str_repeat(
                    'B',
                    1200
                )
            );

        $newSize =
            (int) $newLogo->getSize();

        $oldSize =
            Storage::disk('public')
                ->size(
                    $oldPath
                );

        $delta = max(
            0,
            $newSize - $oldSize
        );

        $this->storageLimit(
            $plan,
            $oldSize + $delta
        );

        app(
            TenantBrandingStorageGuard::class
        )->assertCanStoreLogo(
            $tenant,
            $newLogo
        );

        $this->assertSame(
            200,
            $delta
        );
    }

    public function test_replacing_logo_is_blocked_when_positive_delta_does_not_fit(): void
    {
        Storage::fake('public');

        [$tenant, $plan] =
            $this->subscribedTenant(
                'branding-storage-delta-blocked'
            );

        $oldPath =
            'tenant-branding/'
            . $tenant->id
            . '/old.png';

        Storage::disk('public')
            ->put(
                $oldPath,
                str_repeat(
                    'A',
                    1000
                )
            );

        $tenant->logo_path =
            $oldPath;

        $tenant->save();

        $newLogo = UploadedFile::fake()
            ->createWithContent(
                'new.png',
                str_repeat(
                    'B',
                    1200
                )
            );

        $this->storageLimit(
            $plan,
            1199
        );

        try {
            app(
                TenantBrandingStorageGuard::class
            )->assertCanStoreLogo(
                $tenant,
                $newLogo
            );

            $this->fail(
                'Expected delta storage blocking.'
            );
        } catch (
            UsageBlockedException $exception
        ) {
            $this->assertSame(
                1000,
                $exception->used
            );

            $this->assertSame(
                200,
                $exception->requested
            );

            $this->assertSame(
                199,
                $exception->remaining
            );
        }
    }

    public function test_replacing_with_smaller_logo_does_not_require_extra_quota(): void
    {
        Storage::fake('public');

        [$tenant, $plan] =
            $this->subscribedTenant(
                'branding-storage-smaller'
            );

        $oldPath =
            'tenant-branding/'
            . $tenant->id
            . '/old.png';

        Storage::disk('public')
            ->put(
                $oldPath,
                str_repeat(
                    'A',
                    2000
                )
            );

        $tenant->logo_path =
            $oldPath;

        $tenant->save();

        $this->storageLimit(
            $plan,
            0
        );

        $smallerLogo =
            UploadedFile::fake()
                ->createWithContent(
                    'smaller.png',
                    str_repeat(
                        'B',
                        1000
                    )
                );

        app(
            TenantBrandingStorageGuard::class
        )->assertCanStoreLogo(
            $tenant,
            $smallerLogo
        );

        $this->addToAssertionCount(1);
    }

    public function test_missing_old_logo_file_is_treated_as_zero_existing_branding_bytes(): void
    {
        Storage::fake('public');

        [$tenant, $plan] =
            $this->subscribedTenant(
                'branding-storage-missing-old'
            );

        $tenant->logo_path =
            'tenant-branding/'
            . $tenant->id
            . '/missing.png';

        $tenant->save();

        $newLogo = UploadedFile::fake()
            ->create(
                'replacement.png',
                10,
                'image/png'
            );

        $size =
            (int) $newLogo->getSize();

        $this->storageLimit(
            $plan,
            $size
        );

        app(
            TenantBrandingStorageGuard::class
        )->assertCanStoreLogo(
            $tenant,
            $newLogo
        );

        $this->addToAssertionCount(1);
    }

    public function test_unlimited_storage_allows_logo(): void
    {
        Storage::fake('public');

        [$tenant, $plan] =
            $this->subscribedTenant(
                'branding-storage-unlimited'
            );

        $this->storageLimit(
            $plan,
            null
        );

        app(
            TenantBrandingStorageGuard::class
        )->assertCanStoreLogo(
            $tenant,
            UploadedFile::fake()
                ->create(
                    'enterprise.png',
                    2048,
                    'image/png'
                )
        );

        $this->addToAssertionCount(1);
    }

    public function test_zero_limit_blocks_new_logo(): void
    {
        Storage::fake('public');

        [$tenant, $plan] =
            $this->subscribedTenant(
                'branding-storage-zero'
            );

        $this->storageLimit(
            $plan,
            0
        );

        $this->expectException(
            UsageBlockedException::class
        );

        app(
            TenantBrandingStorageGuard::class
        )->assertCanStoreLogo(
            $tenant,
            UploadedFile::fake()
                ->create(
                    'zero.png',
                    10,
                    'image/png'
                )
        );
    }

    public function test_branding_storage_is_isolated_between_tenants(): void
    {
        Storage::fake('public');

        [$blockedTenant, $blockedPlan] =
            $this->subscribedTenant(
                'branding-storage-blocked-tenant'
            );

        [$allowedTenant, $allowedPlan] =
            $this->subscribedTenant(
                'branding-storage-allowed-tenant'
            );

        $this->storageLimit(
            $blockedPlan,
            0
        );

        $this->storageLimit(
            $allowedPlan,
            100000
        );

        $logo = UploadedFile::fake()
            ->create(
                'isolated.png',
                10,
                'image/png'
            );

        app(
            TenantBrandingStorageGuard::class
        )->assertCanStoreLogo(
            $allowedTenant,
            $logo
        );

        try {
            app(
                TenantBrandingStorageGuard::class
            )->assertCanStoreLogo(
                $blockedTenant,
                $logo
            );

            $this->fail(
                'Expected isolated branding blocking.'
            );
        } catch (
            UsageBlockedException $exception
        ) {
            $this->assertSame(
                'limit_exceeded',
                $exception->reason
            );
        }
    }

    public function test_legacy_tenant_without_subscription_remains_compatible(): void
    {
        Storage::fake('public');

        $tenant = $this->tenant(
            'branding-storage-legacy'
        );

        app(
            TenantBrandingStorageGuard::class
        )->assertCanStoreLogo(
            $tenant,
            UploadedFile::fake()
                ->create(
                    'legacy.png',
                    10,
                    'image/png'
                )
        );

        $this->addToAssertionCount(1);
    }

    private function subscribedTenant(
        string $slug
    ): array {
        $tenant =
            $this->tenant(
                $slug
            );

        $plan =
            Plan::query()->create([
                'code' =>
                    $slug . '-plan',
                'name' =>
                    ucfirst($slug)
                    . ' Plan',
                'active' =>
                    true,
            ]);

        DB::table(
            'subscriptions'
        )->insert([
            'tenant_id' =>
                $tenant->id,
            'plan_id' =>
                $plan->id,
            'status' =>
                SubscriptionStatus::ACTIVE
                    ->value,
            'current_period_start' =>
                '2026-08-18 00:00:00',
            'current_period_end' =>
                '2026-09-18 00:00:00',
            'created_at' =>
                now(),
            'updated_at' =>
                now(),
        ]);

        return [
            $tenant,
            $plan,
        ];
    }

    private function tenant(
        string $slug
    ): Tenant {
        return Tenant::query()->create([
            'name' =>
                ucfirst($slug),
            'slug' =>
                $slug,
            'status' =>
                'active',
            'country_code' =>
                'BR',
            'locale' =>
                'pt-BR',
            'timezone' =>
                'America/Fortaleza',
            'currency' =>
                'BRL',
        ]);
    }

    private function storageLimit(
        Plan $plan,
        ?int $limit
    ): void {
        PlanUsageLimit::query()->create([
            'plan_id' =>
                $plan->id,
            'metric' =>
                UsageMetric::STORAGE_BYTES,
            'limit_value' =>
                $limit,
        ]);
    }

    private function existingImport(
        Tenant $tenant,
        int $size
    ): void {
        Import::query()
            ->withoutGlobalScopes()
            ->create([
                'tenant_id' =>
                    $tenant->id,
                'user_id' =>
                    null,
                'target' =>
                    null,
                'original_name' =>
                    'existing.csv',
                'stored_path' =>
                    'existing/'
                    . $tenant->id
                    . '.csv',
                'mime_type' =>
                    'text/csv',
                'size' =>
                    $size,
                'status' =>
                    'uploaded',
                'delimiter' =>
                    ',',
                'encoding' =>
                    'UTF-8',
            ]);
    }
}