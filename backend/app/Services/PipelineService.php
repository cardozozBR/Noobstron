<?php

namespace App\Services;

use App\Models\Pipeline;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PipelineService
{
    public function create(array $data): Pipeline
    {
        return DB::transaction(function () use ($data): Pipeline {
            $name = $this->normalizeName(
                $data['name'] ?? null
            );

            $hasPipelines = Pipeline::query()->exists();

            $makeDefault = ! $hasPipelines
                || (bool) ($data['is_default'] ?? false);

            if ($makeDefault) {
                $this->clearDefault();
            }

            return Pipeline::create([
                'name' => $name,
                'description' => $this->normalizeDescription(
                    $data['description'] ?? null
                ),
                'is_default' => $makeDefault,
                'is_active' => (bool) (
                    $data['is_active'] ?? true
                ),
            ]);
        });
    }

    public function update(
        Pipeline $pipeline,
        array $data
    ): Pipeline {
        return DB::transaction(function () use (
            $pipeline,
            $data
        ): Pipeline {
            $pipeline = $this->resolve($pipeline);

            $makeDefault = array_key_exists(
                'is_default',
                $data
            )
                ? (bool) $data['is_default']
                : $pipeline->is_default;

            if ($makeDefault && ! $pipeline->is_default) {
                $this->clearDefault(
                    $pipeline->id
                );
            }

            if (! $makeDefault && $pipeline->is_default) {
                $replacement = Pipeline::query()
                    ->whereKeyNot($pipeline->id)
                    ->orderBy('id')
                    ->first();

                if ($replacement === null) {
                    $makeDefault = true;
                } else {
                    $pipeline->update([
                        'is_default' => false,
                    ]);

                    $replacement->update([
                        'is_default' => true,
                    ]);
                }
            }

            $payload = [
                'is_default' => $makeDefault,
            ];

            if (array_key_exists('name', $data)) {
                $payload['name'] = $this->normalizeName(
                    $data['name']
                );
            }

            if (array_key_exists('description', $data)) {
                $payload['description'] =
                    $this->normalizeDescription(
                        $data['description']
                    );
            }

            if (array_key_exists('is_active', $data)) {
                $payload['is_active'] =
                    (bool) $data['is_active'];
            }

            $pipeline->update($payload);

            return $pipeline->fresh();
        });
    }

    public function setDefault(
        Pipeline $pipeline
    ): Pipeline {
        return DB::transaction(function () use ($pipeline): Pipeline {
            $pipeline = $this->resolve($pipeline);

            if ($pipeline->is_default) {
                return $pipeline;
            }

            $this->clearDefault(
                $pipeline->id
            );

            $pipeline->update([
                'is_default' => true,
            ]);

            return $pipeline->fresh();
        });
    }

    public function delete(
        Pipeline $pipeline
    ): void {
        DB::transaction(function () use ($pipeline): void {
            $pipeline = $this->resolve($pipeline);

            $replacement = null;

            if ($pipeline->is_default) {
                $replacement = Pipeline::query()
                    ->whereKeyNot($pipeline->id)
                    ->orderBy('id')
                    ->first();

                $pipeline->update([
                    'is_default' => false,
                ]);
            }

            $pipeline->delete();

            if ($replacement !== null) {
                $replacement->update([
                    'is_default' => true,
                ]);
            }
        });
    }

    private function resolve(
        Pipeline $pipeline
    ): Pipeline {
        $resolved = Pipeline::query()
            ->find($pipeline->getKey());

        if ($resolved === null) {
            throw (new ModelNotFoundException())
                ->setModel(
                    Pipeline::class,
                    [$pipeline->getKey()]
                );
        }

        return $resolved;
    }

    private function clearDefault(
        ?int $exceptId = null
    ): void {
        $query = Pipeline::query()
            ->where('is_default', true);

        if ($exceptId !== null) {
            $query->whereKeyNot($exceptId);
        }

        $query->update([
            'is_default' => false,
        ]);
    }

    private function normalizeName(
        mixed $name
    ): string {
        $name = trim((string) $name);

        if ($name === '') {
            throw new RuntimeException(
                'Pipeline name is required.'
            );
        }

        return $name;
    }

    private function normalizeDescription(
        mixed $description
    ): ?string {
        if ($description === null) {
            return null;
        }

        $description = trim(
            (string) $description
        );

        return $description === ''
            ? null
            : $description;
    }
}
