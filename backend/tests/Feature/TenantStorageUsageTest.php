<?php

namespace Tests\Feature;

use App\Enums\ImportStatus;
use App\Models\Import;
use App\Models\Tenant;
use App\Services\TenantStorageUsage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TenantStorageUsageTest extends TestCase
{
    use RefreshDatabase;

    public function test_empty_tenant_has_zero_storage_usage(): void
    {
        $tenant = $this->tenant('empty-storage');

        $usage = app(TenantStorageUsage::class)
            ->bytes($tenant);

        $this->assertSame(0, $usage);
    }

    public function test_import_sizes_are_summed_for_tenant(): void
    {
        $tenant = $this->tenant('storage-owner');

        $this->import($tenant, 100);
        $this->import($tenant, 250);

        $usage = app(TenantStorageUsage::class)
            ->bytes($tenant);

        $this->assertSame(350, $usage);
    }

    public function test_branding_logo_is_included_in_storage_usage(): void
    {
        Storage::fake('public');

        $tenant = $this->tenant('storage-branding');

        $path = 'tenant-branding/'
            . $tenant->id
            . '/logo.png';

        Storage::disk('public')->put(
            $path,
            str_repeat('x', 512)
        );

        $tenant->update([
            'logo_path' => $path,
        ]);

        $usage = app(TenantStorageUsage::class)
            ->bytes($tenant->fresh());

        $this->assertSame(512, $usage);
    }

    public function test_missing_branding_file_does_not_break_usage(): void
    {
        Storage::fake('public');

        $tenant = $this->tenant('storage-missing-logo');

        $tenant->update([
            'logo_path' => 'tenant-branding/'
                . $tenant->id
                . '/missing.png',
        ]);

        $usage = app(TenantStorageUsage::class)
            ->bytes($tenant->fresh());

        $this->assertSame(0, $usage);
    }
    public function test_storage_usage_is_isolated_between_tenants(): void
    {
        $first = $this->tenant('storage-first');
        $second = $this->tenant('storage-second');

        $this->import($first, 120);
        $this->import($first, 80);
        $this->import($second, 900);

        $service = app(TenantStorageUsage::class);

        $this->assertSame(
            200,
            $service->bytes($first)
        );

        $this->assertSame(
            900,
            $service->bytes($second)
        );
    }

    private function tenant(string $slug): Tenant
    {
        return Tenant::query()->create([
            'name' => ucfirst($slug),
            'slug' => $slug,
            'status' => 'active',
        ]);
    }

    private function import(
        Tenant $tenant,
        int $size
    ): Import {
        return Import::query()->create([
            'tenant_id' => $tenant->id,
            'user_id' => null,
            'target' => null,
            'original_name' => 'storage.csv',
            'stored_path' =>
                'tenant-imports/'
                . $tenant->id
                . '/storage.csv',
            'mime_type' => 'text/csv',
            'size' => $size,
            'status' => ImportStatus::UPLOADED,
            'delimiter' => ',',
            'encoding' => 'UTF-8',
        ]);
    }
}