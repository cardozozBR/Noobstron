<?php

namespace Tests\Feature;

use App\Enums\ImportStatus;
use App\Models\Import;
use App\Models\Tenant;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImportModelTest extends TestCase
{
    use RefreshDatabase;

    private function tenant(
        string $slug
    ): Tenant {
        return Tenant::create([
            'name' => $slug,
            'slug' => $slug,
            'status' => 'active',
            'country_code' => 'BR',
            'locale' => 'pt-BR',
            'timezone' => 'America/Fortaleza',
            'currency' => 'BRL',
        ]);
    }

    public function test_import_is_created_in_current_tenant(): void
    {
        $tenant = $this->tenant(
            'import-model'
        );

        app(TenantContext::class)->set(
            $tenant
        );

        $import = Import::create([
            'original_name' => 'leads.csv',
            'stored_path' =>
                'tenant-imports/1/leads.csv',
            'status' => ImportStatus::UPLOADED,
        ]);

        $this->assertSame(
            $tenant->id,
            $import->tenant_id
        );
    }

    public function test_import_status_is_cast_to_enum(): void
    {
        $tenant = $this->tenant(
            'import-status'
        );

        app(TenantContext::class)->set(
            $tenant
        );

        $import = Import::create([
            'original_name' => 'leads.csv',
            'stored_path' =>
                'tenant-imports/1/leads.csv',
            'status' => ImportStatus::PARSED,
        ]);

        $this->assertSame(
            ImportStatus::PARSED,
            $import->status
        );
    }

    public function test_header_is_cast_to_array(): void
    {
        $tenant = $this->tenant(
            'import-header'
        );

        app(TenantContext::class)->set(
            $tenant
        );

        $import = Import::create([
            'original_name' => 'leads.csv',
            'stored_path' =>
                'tenant-imports/1/leads.csv',
            'header' => [
                'name',
                'email',
            ],
        ]);

        $this->assertSame(
            [
                'name',
                'email',
            ],
            $import->header
        );
    }

    public function test_import_queries_are_isolated_by_tenant(): void
    {
        $tenantA = $this->tenant(
            'import-a'
        );

        $tenantB = $this->tenant(
            'import-b'
        );

        app(TenantContext::class)->set(
            $tenantA
        );

        Import::create([
            'original_name' => 'a.csv',
            'stored_path' =>
                'tenant-imports/a.csv',
        ]);

        app(TenantContext::class)->set(
            $tenantB
        );

        Import::create([
            'original_name' => 'b.csv',
            'stored_path' =>
                'tenant-imports/b.csv',
        ]);

        app(TenantContext::class)->set(
            $tenantA
        );

        $this->assertSame(
            [
                'a.csv',
            ],
            Import::query()
                ->pluck('original_name')
                ->all()
        );
    }

    public function test_import_from_another_tenant_cannot_be_found(): void
    {
        $tenantA = $this->tenant(
            'import-find-a'
        );

        $tenantB = $this->tenant(
            'import-find-b'
        );

        app(TenantContext::class)->set(
            $tenantB
        );

        $foreign = Import::create([
            'original_name' => 'b.csv',
            'stored_path' =>
                'tenant-imports/b.csv',
        ]);

        app(TenantContext::class)->set(
            $tenantA
        );

        $this->assertNull(
            Import::query()->find(
                $foreign->id
            )
        );
    }

    public function test_tenant_has_imports_relation(): void
    {
        $tenant = $this->tenant(
            'import-relation'
        );

        app(TenantContext::class)->set(
            $tenant
        );

        Import::create([
            'original_name' => 'data.csv',
            'stored_path' =>
                'tenant-imports/data.csv',
        ]);

        $this->assertCount(
            1,
            $tenant->imports
        );
    }
}
