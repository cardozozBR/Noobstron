<?php

namespace App\Services;

use App\Enums\ImportRowStatus;
use App\Enums\ImportStatus;
use App\Enums\ImportTarget;
use App\Models\Customer;
use App\Models\CustomerEmail;
use App\Models\CustomerPhone;
use App\Models\Import;
use App\Models\ImportRow;
use App\Models\Lead;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class ImportExecutionService
{
    public function __construct(
        private readonly CsvImportParser $parser,
        private readonly CsvImportMapping $mapping,
        private readonly CsvImportRowReader $rows,
        private readonly ImportRowNormalizer $normalizer,
        private readonly ImportRowValidator $validator,
        private readonly AuditService $audits
    ) {
    }

    public function execute(
        Import $import,
        ?ImportTarget $target = null
    ): Import {
        $target ??= $import->target;

        if ($target === null) {
            throw new RuntimeException(
                'Import target is required.'
            );
        }

        if (
            $import->status === ImportStatus::COMPLETED
            || $import->status ===
                ImportStatus::COMPLETED_WITH_ERRORS
        ) {
            return $import->fresh();
        }

        $path = Storage::disk(
            'local'
        )->path(
            $import->stored_path
        );

        $inspection = $this->parser
            ->inspect(
                $path,
                $import->delimiter
            );

        $mapping = $this->mapping
            ->build(
                $inspection['header'],
                $target
            );

        $rows = $this->rows
            ->rows(
                $path,
                $mapping['mapping'],
                $inspection['delimiter']
            );

        $import->update([
            'target' => $target,
            'status' => ImportStatus::PROCESSING,
            'mapping' => $mapping['mapping'],
            'row_count' => count($rows),
            'error_message' => null,
            'started_at' =>
                $import->started_at ?? now(),
        ]);

        foreach ($rows as $row) {
            $this->processRow(
                $import,
                $target,
                $row
            );
        }

        $this->refreshCounters(
            $import
        );

        $import->refresh();

        $finalStatus =
            $import->failure_count > 0
                ? ImportStatus::COMPLETED_WITH_ERRORS
                : ImportStatus::COMPLETED;

        $import->update([
            'status' => $finalStatus,
            'completed_at' => now(),
        ]);

        $this->audits->log(
            'import.completed',
            'Importação concluída: '
                . $import->original_name
                . '.'
        );


return $import->fresh();
    }

    private function processRow(
        Import $import,
        ImportTarget $target,
        array $row
    ): void {
        if (
            ImportRow::query()
                ->where(
                    'import_id',
                    $import->id
                )
                ->where(
                    'line',
                    $row['line']
                )
                ->exists()
        ) {
            return;
        }

        $normalized = $this->normalizer
            ->normalize(
                $row['data'],
                $target
            );

        $validation = $this->validator
            ->validate(
                $normalized,
                $target
            );

        if (! $validation['valid']) {
            ImportRow::create([
                'tenant_id' =>
                    $import->tenant_id,
                'import_id' =>
                    $import->id,
                'line' => $row['line'],
                'status' =>
                    ImportRowStatus::FAILED,
                'data' => $normalized,
                'errors' =>
                    $validation['errors'],
            ]);

            return;
        }

        try {
            DB::transaction(
                function () use (
                    $import,
                    $target,
                    $row,
                    $validation
                ): void {
                    $entity = $this->persist(
                        $target,
                        $validation['data']
                    );

                    ImportRow::create([
                        'tenant_id' =>
                            $import->tenant_id,
                        'import_id' =>
                            $import->id,
                        'line' => $row['line'],
                        'status' =>
                            ImportRowStatus::SUCCESS,
                        'data' =>
                            $validation['data'],
                        'errors' => [],
                        'entity_type' =>
                            $entity::class,
                        'entity_id' =>
                            $entity->getKey(),
                    ]);
                }
            );
        } catch (Throwable $exception) {
            ImportRow::create([
                'tenant_id' =>
                    $import->tenant_id,
                'import_id' =>
                    $import->id,
                'line' => $row['line'],
                'status' =>
                    ImportRowStatus::FAILED,
                'data' =>
                    $validation['data'],
                'errors' => [
                    '_row' => [
                        $exception->getMessage(),
                    ],
                ],
            ]);
        }
    }

    private function persist(
        ImportTarget $target,
        array $data
    ): Model {
        return match ($target) {
            ImportTarget::LEADS =>
                $this->persistLead(
                    $data
                ),

            ImportTarget::CUSTOMERS =>
                $this->persistCustomer(
                    $data
                ),
        };
    }

    private function persistLead(
        array $data
    ): Lead {
        $payload = [
            'name' => $data['name'],
        ];

        foreach ([
            'email',
            'phone',
            'status',
            'source',
            'tags',
            'notes',
        ] as $field) {
            if (
                array_key_exists(
                    $field,
                    $data
                )
                && $data[$field] !== null
            ) {
                $payload[$field] =
                    $data[$field];
            }
        }

        return Lead::create(
            $payload
        );
    }

    private function persistCustomer(
        array $data
    ): Customer {
        $payload = [
            'type' => $data['type'],
            'name' => $data['name'],
        ];

        foreach ([
            'legal_name',
            'tax_country_code',
            'tax_identifier_type',
            'tax_identifier',
            'tags',
            'notes',
        ] as $field) {
            if (
                array_key_exists(
                    $field,
                    $data
                )
                && $data[$field] !== null
            ) {
                $payload[$field] =
                    $data[$field];
            }
        }

        $customer = Customer::create(
            $payload
        );

        if (
            isset($data['email'])
            && $data['email'] !== null
        ) {
            CustomerEmail::create([
                'customer_id' =>
                    $customer->id,
                'label' => 'Import',
                'email' => $data['email'],
                'is_primary' => true,
            ]);
        }

        if (
            isset($data['phone'])
            && $data['phone'] !== null
        ) {
            $tenant = app(
                TenantContext::class
            )->get();

            CustomerPhone::create([
                'customer_id' =>
                    $customer->id,
                'label' => 'Import',
                'country_code' =>
                    $tenant->country_code,
                'national_number' =>
                    $data['phone'],
                'is_primary' => true,
            ]);
        }

        return $customer;
    }

    private function refreshCounters(
        Import $import
    ): void {
        $success = ImportRow::query()
            ->where(
                'import_id',
                $import->id
            )
            ->where(
                'status',
                ImportRowStatus::SUCCESS
                    ->value
            )
            ->count();

        $failed = ImportRow::query()
            ->where(
                'import_id',
                $import->id
            )
            ->where(
                'status',
                ImportRowStatus::FAILED
                    ->value
            )
            ->count();

        $import->update([
            'processed_count' =>
                $success + $failed,
            'success_count' => $success,
            'failure_count' => $failed,
        ]);
    }
}
