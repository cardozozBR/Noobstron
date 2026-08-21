<?php

namespace App\Services;

use App\Enums\TriggerType;
use App\Support\TriggerOccurrence;

use App\Models\Customer;
use App\Models\Opportunity;
use App\Models\Pipeline;
use App\Models\PipelineStage;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class OpportunityService
{
    public function __construct(
        private readonly TriggerDispatcher $triggers
    ) {
    }

    public function create(
        array $data
    ): Opportunity {
        return DB::transaction(function () use ($data): Opportunity {
            $customer = $this->resolveCustomer(
                $data['customer_id'] ?? null
            );

            $pipeline = $this->resolvePipelineForCreate(
                $data['pipeline_id'] ?? null
            );

            $stage = $this->resolveStageForPipeline(
                $pipeline,
                $data['pipeline_stage_id'] ?? null
            );

            $responsible = $this->resolveResponsible(
                $data['responsible_user_id'] ?? null
            );

            return Opportunity::create([
                'name' => $this->normalizeName(
                    $data['name'] ?? null
                ),
                'customer_id' => $customer->id,
                'pipeline_id' => $pipeline->id,
                'pipeline_stage_id' => $stage->id,
                'responsible_user_id' =>
                    $responsible?->id,
                'value_minor' => $this->normalizeValue(
                    $data['value_minor'] ?? 0
                ),
                'currency' => $this->normalizeCurrency(
                    $data['currency'] ?? null
                ),
                'probability' => $this->normalizeProbability(
                    $data['probability'] ?? 0
                ),
                'expected_close_date' =>
                    $data['expected_close_date'] ?? null,
                'notes' => $this->normalizeNullableText(
                    $data['notes'] ?? null
                ),
            ]);
        });
    }

    public function update(
        Opportunity $opportunity,
        array $data
    ): Opportunity {
        return DB::transaction(function () use (
            $opportunity,
            $data
        ): Opportunity {
            $opportunity = $this->resolveOpportunity(
                $opportunity
            );

            $previousStageId =
                (int) $opportunity->pipeline_stage_id;

            $pipeline = $opportunity->pipeline;

            if (array_key_exists('pipeline_id', $data)) {
                $pipeline = $this->resolvePipeline(
                    $data['pipeline_id']
                );
            }

            $stage = $opportunity->stage;

            if (
                array_key_exists('pipeline_stage_id', $data)
                || array_key_exists('pipeline_id', $data)
            ) {
                $stage = $this->resolveStageForPipeline(
                    $pipeline,
                    $data['pipeline_stage_id'] ?? null
                );
            }

            $payload = [
                'pipeline_id' => $pipeline->id,
                'pipeline_stage_id' => $stage->id,
            ];

            if (array_key_exists('name', $data)) {
                $payload['name'] =
                    $this->normalizeName(
                        $data['name']
                    );
            }

            if (array_key_exists('customer_id', $data)) {
                $payload['customer_id'] =
                    $this->resolveCustomer(
                        $data['customer_id']
                    )->id;
            }

            if (
                array_key_exists(
                    'responsible_user_id',
                    $data
                )
            ) {
                $payload['responsible_user_id'] =
                    $this->resolveResponsible(
                        $data['responsible_user_id']
                    )?->id;
            }

            if (array_key_exists('value_minor', $data)) {
                $payload['value_minor'] =
                    $this->normalizeValue(
                        $data['value_minor']
                    );
            }

            if (array_key_exists('currency', $data)) {
                $payload['currency'] =
                    $this->normalizeCurrency(
                        $data['currency']
                    );
            }

            if (array_key_exists('probability', $data)) {
                $payload['probability'] =
                    $this->normalizeProbability(
                        $data['probability']
                    );
            }

            if (
                array_key_exists(
                    'expected_close_date',
                    $data
                )
            ) {
                $payload['expected_close_date'] =
                    $data['expected_close_date'];
            }

            if (array_key_exists('notes', $data)) {
                $payload['notes'] =
                    $this->normalizeNullableText(
                        $data['notes']
                    );
            }

            $opportunity->update(
                $payload
            );

            $fresh = $opportunity->fresh();

            $this->dispatchStageChanged(
                $fresh,
                $previousStageId
            );

            return $fresh;
        });
    }

    public function moveToStage(
        Opportunity $opportunity,
        PipelineStage $stage
    ): Opportunity {
        return DB::transaction(function () use (
            $opportunity,
            $stage
        ): Opportunity {
            $opportunity =
                $this->resolveOpportunity(
                    $opportunity
                );

            $stage = PipelineStage::query()
                ->find($stage->getKey());

            if ($stage === null) {
                throw (
                    new ModelNotFoundException()
                )->setModel(
                    PipelineStage::class,
                    [$stage?->getKey()]
                );
            }

            $pipeline = Pipeline::query()
                ->findOrFail(
                    $stage->pipeline_id
                );

            $previousStageId =
                (int) $opportunity->pipeline_stage_id;

            $opportunity->update([
                'pipeline_id' => $pipeline->id,
                'pipeline_stage_id' => $stage->id,
            ]);

            $fresh = $opportunity->fresh();

            $this->dispatchStageChanged(
                $fresh,
                $previousStageId
            );

            return $fresh;
        });
    }

    private function dispatchStageChanged(
        Opportunity $opportunity,
        int $previousStageId
    ): void {
        $newStageId =
            (int) $opportunity->pipeline_stage_id;

        if ($previousStageId === $newStageId) {
            return;
        }

        $this->triggers->dispatch(
            TriggerOccurrence::forTenant(
                type:
                    TriggerType::OPPORTUNITY_STAGE_CHANGED,
                tenant: $opportunity->tenant,
                subjectType: 'opportunity',
                subjectId: $opportunity->id,
                payload: [
                    'opportunity_id' =>
                        $opportunity->id,
                    'previous_stage_id' =>
                        $previousStageId,
                    'new_stage_id' =>
                        $newStageId,
                    'pipeline_id' =>
                        (int) $opportunity->pipeline_id,
                    'customer_id' =>
                        (int) $opportunity->customer_id,
                    'responsible_user_id' =>
                        $opportunity->responsible_user_id,
                ]
            )
        );
    }

    private function resolveOpportunity(
        Opportunity $opportunity
    ): Opportunity {
        $resolved = Opportunity::query()
            ->find(
                $opportunity->getKey()
            );

        if ($resolved === null) {
            throw (
                new ModelNotFoundException()
            )->setModel(
                Opportunity::class,
                [$opportunity->getKey()]
            );
        }

        return $resolved;
    }

    private function resolveCustomer(
        mixed $id
    ): Customer {
        $customer = Customer::query()
            ->find((int) $id);

        if ($customer === null) {
            throw new RuntimeException(
                'Customer does not belong to current tenant.'
            );
        }

        return $customer;
    }

    private function resolvePipelineForCreate(
        mixed $id
    ): Pipeline {
        if ($id !== null && $id !== '') {
            return $this->resolvePipeline($id);
        }

        $pipeline = Pipeline::query()
            ->where('is_default', true)
            ->where('is_active', true)
            ->first();

        if ($pipeline === null) {
            throw new RuntimeException(
                'No active default pipeline is available.'
            );
        }

        return $pipeline;
    }

    private function resolvePipeline(
        mixed $id
    ): Pipeline {
        $pipeline = Pipeline::query()
            ->find((int) $id);

        if ($pipeline === null) {
            throw new RuntimeException(
                'Pipeline does not belong to current tenant.'
            );
        }

        if (! $pipeline->is_active) {
            throw new RuntimeException(
                'Pipeline is inactive.'
            );
        }

        return $pipeline;
    }

    private function resolveStageForPipeline(
        Pipeline $pipeline,
        mixed $stageId
    ): PipelineStage {
        if ($stageId === null || $stageId === '') {
            $stage = PipelineStage::query()
                ->where(
                    'pipeline_id',
                    $pipeline->id
                )
                ->where('is_active', true)
                ->orderBy('position')
                ->first();

            if ($stage === null) {
                throw new RuntimeException(
                    'Pipeline has no active stages.'
                );
            }

            return $stage;
        }

        $stage = PipelineStage::query()
            ->where(
                'pipeline_id',
                $pipeline->id
            )
            ->find((int) $stageId);

        if ($stage === null) {
            throw new RuntimeException(
                'Pipeline stage does not belong to selected pipeline.'
            );
        }

        if (! $stage->is_active) {
            throw new RuntimeException(
                'Pipeline stage is inactive.'
            );
        }

        return $stage;
    }

    private function resolveResponsible(
        mixed $id
    ): ?User {
        if ($id === null || $id === '') {
            return null;
        }

        $user = User::query()
            ->find((int) $id);

        if ($user === null) {
            throw new RuntimeException(
                'Responsible user does not belong to current tenant.'
            );
        }

        return $user;
    }

    private function normalizeName(
        mixed $name
    ): string {
        $name = trim(
            (string) $name
        );

        if ($name === '') {
            throw new RuntimeException(
                'Opportunity name is required.'
            );
        }

        return $name;
    }

    private function normalizeValue(
        mixed $value
    ): int {
        if (! is_numeric($value)) {
            throw new RuntimeException(
                'Opportunity value is invalid.'
            );
        }

        $value = (int) $value;

        if ($value < 0) {
            throw new RuntimeException(
                'Opportunity value cannot be negative.'
            );
        }

        return $value;
    }

    private function normalizeCurrency(
        mixed $currency
    ): string {
        $currency = strtoupper(
            trim(
                (string) $currency
            )
        );

        if (strlen($currency) !== 3) {
            throw new RuntimeException(
                'Opportunity currency is invalid.'
            );
        }

        return $currency;
    }

    private function normalizeProbability(
        mixed $probability
    ): int {
        if (! is_numeric($probability)) {
            throw new RuntimeException(
                'Opportunity probability is invalid.'
            );
        }

        $probability = (int) $probability;

        if (
            $probability < 0
            || $probability > 100
        ) {
            throw new RuntimeException(
                'Opportunity probability must be between 0 and 100.'
            );
        }

        return $probability;
    }

    private function normalizeNullableText(
        mixed $value
    ): ?string {
        if ($value === null) {
            return null;
        }

        $value = trim(
            (string) $value
        );

        return $value === ''
            ? null
            : $value;
    }
}
