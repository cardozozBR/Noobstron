<?php

namespace Tests\Feature;

use App\Enums\ImportStatus;
use App\Enums\SubscriptionStatus;
use App\Enums\UsageMetric;
use App\Exceptions\UsageBlockedException;
use App\Models\Import;
use App\Models\Plan;
use App\Models\PlanUsageLimit;
use App\Models\Tenant;
use App\Models\User;
use App\Services\ImportUploadService;
use App\Services\TenantContext;
use App\Services\TenantUsageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImportStorageUsageGuardTest extends TestCase
{
    use RefreshDatabase;

    public function test_import_is_allowed_when_projected_storage_fits_limit(): void
    {
        Storage::fake('local');

        [$tenant, $plan] =
            $this->subscribedTenant(
                'storage-import-allowed'
            );

        $content =
            "name,email\n"
            . "Maria,maria@example.test\n";

        $file = UploadedFile::fake()
            ->createWithContent(
                'allowed.csv',
                $content
            );

        $size = (int) $file->getSize();

        $this->storageLimit(
            $plan,
            $size
        );

        $this->authenticate(
            $tenant
        );

        $import = app(
            ImportUploadService::class
        )->store(
            $file
        );

        $this->assertSame(
            $tenant->id,
            $import->tenant_id
        );

        $this->assertSame(
            $size,
            $import->size
        );

        Storage::disk('local')
            ->assertExists(
                $import->stored_path
            );

        $this->assertSame(
            $size,
            app(TenantUsageService::class)
                ->value(
                    $tenant,
                    UsageMetric::STORAGE_BYTES
                )
        );
    }

    public function test_import_is_blocked_before_file_is_stored_when_limit_would_be_exceeded(): void
    {
        Storage::fake('local');

        [$tenant, $plan] =
            $this->subscribedTenant(
                'storage-import-blocked'
            );

        $content =
            "name,email\n"
            . "Maria,maria@example.test\n";

        $file = UploadedFile::fake()
            ->createWithContent(
                'blocked.csv',
                $content
            );

        $size = (int) $file->getSize();

        $this->storageLimit(
            $plan,
            max(0, $size - 1)
        );

        $this->authenticate(
            $tenant
        );

        try {
            app(
                ImportUploadService::class
            )->store(
                $file
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
                0,
                $exception->used
            );

            $this->assertSame(
                $size,
                $exception->requested
            );

            $this->assertTrue(
                $exception->upgradeSuggested
            );
        }

        $this->assertSame(
            [],
            Storage::disk('local')
                ->allFiles()
        );

        $this->assertSame(
            0,
            Import::query()
                ->withoutGlobalScopes()
                ->where(
                    'tenant_id',
                    $tenant->id
                )
                ->count()
        );
    }

    public function test_existing_storage_is_included_in_projected_limit(): void
    {
        Storage::fake('local');

        [$tenant, $plan] =
            $this->subscribedTenant(
                'storage-import-existing'
            );

        $this->existingImport(
            $tenant,
            100
        );

        $content =
            "name,email\n"
            . "A,a@example.test\n";

        $file = UploadedFile::fake()
            ->createWithContent(
                'projected.csv',
                $content
            );

        $size = (int) $file->getSize();

        $this->storageLimit(
            $plan,
            100 + $size - 1
        );

        $this->authenticate(
            $tenant
        );

        try {
            app(
                ImportUploadService::class
            )->store(
                $file
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
                $size,
                $exception->requested
            );

            $this->assertSame(
                $size - 1,
                $exception->remaining
            );
        }

        $this->assertSame(
            [],
            Storage::disk('local')
                ->allFiles()
        );
    }

    public function test_unlimited_storage_allows_import(): void
    {
        Storage::fake('local');

        [$tenant, $plan] =
            $this->subscribedTenant(
                'storage-import-unlimited'
            );

        $this->storageLimit(
            $plan,
            null
        );

        $this->authenticate(
            $tenant
        );

        $file = UploadedFile::fake()
            ->createWithContent(
                'enterprise.csv',
                "name,email\n"
                . "Enterprise,enterprise@example.test\n"
            );

        $import = app(
            ImportUploadService::class
        )->store(
            $file
        );

        Storage::disk('local')
            ->assertExists(
                $import->stored_path
            );

        $this->assertGreaterThan(
            0,
            $import->size
        );
    }

    public function test_zero_storage_limit_blocks_import(): void
    {
        Storage::fake('local');

        [$tenant, $plan] =
            $this->subscribedTenant(
                'storage-import-zero'
            );

        $this->storageLimit(
            $plan,
            0
        );

        $this->authenticate(
            $tenant
        );

        $file = UploadedFile::fake()
            ->createWithContent(
                'zero.csv',
                "name\nOne\n"
            );

        $this->expectException(
            UsageBlockedException::class
        );

        app(
            ImportUploadService::class
        )->store(
            $file
        );
    }

    public function test_storage_guard_is_isolated_between_tenants(): void
    {
        Storage::fake('local');

        [$blockedTenant, $blockedPlan] =
            $this->subscribedTenant(
                'storage-import-isolated-blocked'
            );

        [$allowedTenant, $allowedPlan] =
            $this->subscribedTenant(
                'storage-import-isolated-allowed'
            );

        $this->storageLimit(
            $blockedPlan,
            0
        );

        $this->storageLimit(
            $allowedPlan,
            100000
        );

        $this->authenticate(
            $allowedTenant
        );

        $allowed = app(
            ImportUploadService::class
        )->store(
            UploadedFile::fake()
                ->createWithContent(
                    'allowed-tenant.csv',
                    "name\nAllowed\n"
                )
        );

        $this->assertSame(
            $allowedTenant->id,
            $allowed->tenant_id
        );

        $this->authenticate(
            $blockedTenant
        );

        try {
            app(
                ImportUploadService::class
            )->store(
                UploadedFile::fake()
                    ->createWithContent(
                        'blocked-tenant.csv',
                        "name\nBlocked\n"
                    )
            );

            $this->fail(
                'Expected isolated storage blocking.'
            );
        } catch (
            UsageBlockedException $exception
        ) {
            $this->assertSame(
                'limit_exceeded',
                $exception->reason
            );
        }

        $this->assertSame(
            0,
            Import::query()
                ->withoutGlobalScopes()
                ->where(
                    'tenant_id',
                    $blockedTenant->id
                )
                ->count()
        );

        $this->assertSame(
            1,
            Import::query()
                ->withoutGlobalScopes()
                ->where(
                    'tenant_id',
                    $allowedTenant->id
                )
                ->count()
        );
    }

    public function test_legacy_tenant_without_subscription_remains_compatible(): void
    {
        Storage::fake('local');

        $tenant = $this->tenant(
            'storage-import-legacy'
        );

        $this->authenticate(
            $tenant
        );

        $import = app(
            ImportUploadService::class
        )->store(
            UploadedFile::fake()
                ->createWithContent(
                    'legacy.csv',
                    "name\nLegacy\n"
                )
        );

        $this->assertSame(
            $tenant->id,
            $import->tenant_id
        );

        Storage::disk('local')
            ->assertExists(
                $import->stored_path
            );
    }

    private function subscribedTenant(
        string $slug
    ): array {
        $tenant = $this->tenant(
            $slug
        );

        $plan = Plan::query()->create([
            'code' =>
                $slug . '-plan',
            'name' =>
                ucfirst($slug) . ' Plan',
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
                SubscriptionStatus::ACTIVE->value,
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

    private function authenticate(
        Tenant $tenant
    ): void {
        app(
            TenantContext::class
        )->set(
            $tenant
        );

        $user = User::query()->create([
            'name' =>
                'Storage User',
            'email' =>
                'storage-'
                . $tenant->slug
                . '@example.test',
            'password' =>
                Hash::make(
                    'TesteSenha123'
                ),
            'role' =>
                'user',
        ]);

        $this->actingAs(
            $user
        );
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
                    ImportStatus::UPLOADED,
                'delimiter' =>
                    ',',
                'encoding' =>
                    'UTF-8',
            ]);
    }
}