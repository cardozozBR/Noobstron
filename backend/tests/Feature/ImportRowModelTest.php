<?php

namespace Tests\Feature;

use App\Enums\ImportRowStatus;
use App\Models\Import;
use App\Models\ImportRow;
use App\Models\Tenant;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImportRowModelTest extends TestCase
{
    use RefreshDatabase;

    private function tenant(
        string $slug
    ): Tenant {
        $tenant = Tenant::create([
            'name' => $slug,
            'slug' => $slug,
            'status' => 'active',
            'country_code' => 'BR',
            'locale' => 'pt-BR',
            'timezone' =>
                'America/Fortaleza',
            'currency' => 'BRL',
        ]);

        app(TenantContext::class)->set(
            $tenant
        );

        return $tenant;
    }

    public function test_import_can_have_rows(): void
    {
        $tenant = $this->tenant(
            'import-row'
        );

        $import = Import::create([
            'original_name' => 'data.csv',
            'stored_path' => 'data.csv',
        ]);

        ImportRow::create([
            'tenant_id' => $tenant->id,
            'import_id' => $import->id,
            'line' => 2,
            'status' =>
                ImportRowStatus::SUCCESS,
            'data' => [
                'name' => 'Maria',
            ],
            'errors' => [],
        ]);

        $this->assertCount(
            1,
            $import->rows
        );

        $this->assertSame(
            ImportRowStatus::SUCCESS,
            $import->rows->first()->status
        );
    }

    public function test_import_line_is_unique(): void
    {
        $tenant = $this->tenant(
            'import-row-unique'
        );

        $import = Import::create([
            'original_name' => 'data.csv',
            'stored_path' => 'data.csv',
        ]);

        ImportRow::create([
            'tenant_id' => $tenant->id,
            'import_id' => $import->id,
            'line' => 2,
            'status' =>
                ImportRowStatus::SUCCESS,
        ]);

        $this->expectException(
            \Illuminate\Database\QueryException::class
        );

        ImportRow::create([
            'tenant_id' => $tenant->id,
            'import_id' => $import->id,
            'line' => 2,
            'status' =>
                ImportRowStatus::FAILED,
        ]);
    }
}
