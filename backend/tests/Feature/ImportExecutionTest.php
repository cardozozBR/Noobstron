<?php

namespace Tests\Feature;

use App\Enums\ImportStatus;
use App\Enums\ImportTarget;
use App\Models\Customer;
use App\Models\Import;
use App\Models\Lead;
use App\Models\Tenant;
use App\Services\ImportExecutionService;
use App\Services\ImportUploadService;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImportExecutionTest extends TestCase
{
    use RefreshDatabase;

    private function tenant(
        string $slug = 'import-execution'
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

    private function upload(
        string $content,
        ImportTarget $target,
        string $name = 'data.csv'
    ): Import {
        $file = UploadedFile::fake()
            ->createWithContent(
                $name,
                $content
            );

        return app(
            ImportUploadService::class
        )->store(
            $file,
            ',',
            $target
        );
    }

    public function test_valid_leads_are_imported(): void
    {
        Storage::fake('local');

        $tenant = $this->tenant();

        $import = $this->upload(
            "name,email,status,source\n"
            . "Maria,MARIA@EXAMPLE.COM,qualified,website\n"
            . "Joao,joao@example.com,new,referral\n",
            ImportTarget::LEADS
        );

        $result = app(
            ImportExecutionService::class
        )->execute(
            $import
        );

        $this->assertSame(
            ImportStatus::COMPLETED,
            $result->status
        );

        $this->assertSame(
            2,
            $result->processed_count
        );

        $this->assertSame(
            2,
            $result->success_count
        );

        $this->assertSame(
            0,
            $result->failure_count
        );

        $this->assertSame(
            2,
            Lead::query()->count()
        );

        $this->assertDatabaseHas(
            'leads',
            [
                'tenant_id' => $tenant->id,
                'name' => 'Maria',
                'email' =>
                    'maria@example.com',
            ]
        );
    }

    public function test_invalid_rows_do_not_stop_valid_rows(): void
    {
        Storage::fake('local');

        $this->tenant(
            'import-partial'
        );

        $import = $this->upload(
            "name,email,status\n"
            . "Maria,maria@example.com,new\n"
            . ",invalid,invalid\n"
            . "Joao,joao@example.com,qualified\n",
            ImportTarget::LEADS
        );

        $result = app(
            ImportExecutionService::class
        )->execute(
            $import
        );

        $this->assertSame(
            ImportStatus::COMPLETED_WITH_ERRORS,
            $result->status
        );

        $this->assertSame(
            3,
            $result->processed_count
        );

        $this->assertSame(
            2,
            $result->success_count
        );

        $this->assertSame(
            1,
            $result->failure_count
        );

        $this->assertSame(
            2,
            Lead::query()->count()
        );

        $failed = $result->rows()
            ->where(
                'status',
                'failed'
            )
            ->firstOrFail();

        $this->assertSame(
            3,
            $failed->line
        );

        $this->assertArrayHasKey(
            'name',
            $failed->errors
        );
    }

    public function test_customers_and_primary_contacts_are_imported(): void
    {
        Storage::fake('local');

        $tenant = $this->tenant(
            'import-customers'
        );

        $import = $this->upload(
            "type,name,email,phone,tax_identifier_type,tax_identifier\n"
            . "individual,Maria,MARIA@EXAMPLE.COM,(85)99999-9999,CPF,529.982.247-25\n",
            ImportTarget::CUSTOMERS
        );

        $result = app(
            ImportExecutionService::class
        )->execute(
            $import
        );

        $this->assertSame(
            ImportStatus::COMPLETED,
            $result->status
        );

        $customer = Customer::query()
            ->firstOrFail();

        $this->assertSame(
            $tenant->id,
            $customer->tenant_id
        );

        $this->assertDatabaseHas(
            'customer_emails',
            [
                'customer_id' =>
                    $customer->id,
                'email' =>
                    'maria@example.com',
                'is_primary' => true,
            ]
        );

        $this->assertDatabaseHas(
            'customer_phones',
            [
                'customer_id' =>
                    $customer->id,
                'national_number' =>
                    '85999999999',
                'is_primary' => true,
            ]
        );
    }

    public function test_reexecuting_completed_import_is_idempotent(): void
    {
        Storage::fake('local');

        $this->tenant(
            'import-idempotent'
        );

        $import = $this->upload(
            "name,email\n"
            . "Maria,maria@example.com\n",
            ImportTarget::LEADS
        );

        $service = app(
            ImportExecutionService::class
        );

        $first = $service->execute(
            $import
        );

        $second = $service->execute(
            $first
        );

        $this->assertSame(
            1,
            Lead::query()->count()
        );

        $this->assertSame(
            1,
            $second->rows()->count()
        );

        $this->assertSame(
            1,
            $second->success_count
        );
    }

    public function test_existing_processed_rows_are_not_processed_again(): void
    {
        Storage::fake('local');

        $this->tenant(
            'import-row-idempotent'
        );

        $import = $this->upload(
            "name,email\n"
            . "Maria,maria@example.com\n"
            . "Joao,joao@example.com\n",
            ImportTarget::LEADS
        );

        $service = app(
            ImportExecutionService::class
        );

        $result = $service->execute(
            $import
        );

        $result->update([
            'status' =>
                ImportStatus::PROCESSING,
            'completed_at' => null,
        ]);

        $service->execute(
            $result->fresh()
        );

        $this->assertSame(
            2,
            Lead::query()->count()
        );

        $this->assertSame(
            2,
            $result->rows()->count()
        );
    }

    public function test_import_is_isolated_by_tenant(): void
    {
        Storage::fake('local');

        $tenantA = $this->tenant(
            'import-tenant-a'
        );

        $import = $this->upload(
            "name\nMaria\n",
            ImportTarget::LEADS
        );

        $tenantB = $this->tenant(
            'import-tenant-b'
        );

        app(TenantContext::class)->set(
            $tenantB
        );

        $this->assertNull(
            Import::query()->find(
                $import->id
            )
        );

        app(TenantContext::class)->set(
            $tenantA
        );

        $this->assertNotNull(
            Import::query()->find(
                $import->id
            )
        );
    }

    public function test_target_is_required(): void
    {
        Storage::fake('local');

        $this->tenant(
            'import-no-target'
        );

        $import = app(
            ImportUploadService::class
        )->store(
            UploadedFile::fake()
                ->createWithContent(
                    'data.csv',
                    "name\nMaria\n"
                )
        );

        $this->expectException(
            \RuntimeException::class
        );

        app(
            ImportExecutionService::class
        )->execute(
            $import
        );
    }
}
