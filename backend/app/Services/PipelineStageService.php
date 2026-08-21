<?php

namespace App\Services;

use App\Models\Pipeline;
use App\Models\PipelineStage;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PipelineStageService
{
    public function create(
        Pipeline $pipeline,
        array $data
    ): PipelineStage {
        return DB::transaction(function () use (
            $pipeline,
            $data
        ): PipelineStage {
            $pipeline = $this->resolvePipeline(
                $pipeline
            );

            $name = $this->normalizeName(
                $data['name'] ?? null
            );

            $position = array_key_exists(
                'position',
                $data
            )
                ? (int) $data['position']
                : $this->nextPosition(
                    $pipeline
                );

            if ($position < 1) {
                throw new RuntimeException(
                    'Stage position must be positive.'
                );
            }

            $count = $pipeline->stages()
                ->count();

            if ($position > $count + 1) {
                $position = $count + 1;
            }

            $this->shiftForward(
                $pipeline,
                $position
            );

            return PipelineStage::create([
                'pipeline_id' =>
                    $pipeline->id,
                'name' => $name,
                'position' => $position,
                'is_active' => (bool) (
                    $data['is_active']
                    ?? true
                ),
            ]);
        });
    }

    public function update(
        PipelineStage $stage,
        array $data
    ): PipelineStage {
        return DB::transaction(function () use (
            $stage,
            $data
        ): PipelineStage {
            $stage = $this->resolveStage(
                $stage
            );

            $payload = [];

            if (
                array_key_exists(
                    'name',
                    $data
                )
            ) {
                $payload['name'] =
                    $this->normalizeName(
                        $data['name']
                    );
            }

            if (
                array_key_exists(
                    'is_active',
                    $data
                )
            ) {
                $payload['is_active'] =
                    (bool) $data['is_active'];
            }

            if (
                array_key_exists(
                    'position',
                    $data
                )
            ) {
                $newPosition =
                    (int) $data['position'];

                $this->move(
                    $stage,
                    $newPosition
                );

                $stage = $stage->fresh();
            }

            if ($payload !== []) {
                $stage->update(
                    $payload
                );
            }

            return $stage->fresh();
        });
    }

    public function delete(
        PipelineStage $stage
    ): void {
        DB::transaction(function () use (
            $stage
        ): void {
            $stage = $this->resolveStage(
                $stage
            );

            $pipelineId =
                $stage->pipeline_id;

            $position =
                $stage->position;

            $stage->delete();

            PipelineStage::query()
                ->where(
                    'pipeline_id',
                    $pipelineId
                )
                ->where(
                    'position',
                    '>',
                    $position
                )
                ->orderBy('position')
                ->get()
                ->each(
                    function (
                        PipelineStage $item
                    ): void {
                        $item->update([
                            'position' =>
                                $item->position - 1,
                        ]);
                    }
                );
        });
    }

    public function reorder(
        Pipeline $pipeline,
        array $stageIds
    ): void {
        DB::transaction(function () use (
            $pipeline,
            $stageIds
        ): void {
            $pipeline =
                $this->resolvePipeline(
                    $pipeline
                );

            $stageIds = array_values(
                array_map(
                    'intval',
                    $stageIds
                )
            );

            if (
                count($stageIds)
                !== count(
                    array_unique(
                        $stageIds
                    )
                )
            ) {
                throw new RuntimeException(
                    'Stage list contains duplicates.'
                );
            }

            $stages = $pipeline->stages()
                ->get();

            $expectedIds = $stages
                ->pluck('id')
                ->map(
                    static fn ($id) =>
                        (int) $id
                )
                ->sort()
                ->values()
                ->all();

            $receivedIds = $stageIds;
            sort($receivedIds);

            if (
                $receivedIds
                !== $expectedIds
            ) {
                throw new RuntimeException(
                    'Stage list does not match pipeline stages.'
                );
            }

            $temporaryBase =
                1000000;

            foreach (
                $stageIds as $index => $id
            ) {
                PipelineStage::query()
                    ->whereKey($id)
                    ->update([
                        'position' =>
                            $temporaryBase
                            + $index
                            + 1,
                    ]);
            }

            foreach (
                $stageIds as $index => $id
            ) {
                PipelineStage::query()
                    ->whereKey($id)
                    ->update([
                        'position' =>
                            $index + 1,
                    ]);
            }
        });
    }

    private function move(
        PipelineStage $stage,
        int $newPosition
    ): void {
        if ($newPosition < 1) {
            throw new RuntimeException(
                'Stage position must be positive.'
            );
        }

        $count = PipelineStage::query()
            ->where(
                'pipeline_id',
                $stage->pipeline_id
            )
            ->count();

        if ($newPosition > $count) {
            $newPosition = $count;
        }

        $oldPosition =
            $stage->position;

        if (
            $newPosition
            === $oldPosition
        ) {
            return;
        }

        $temporaryPosition =
            1000000 + $stage->id;

        $stage->update([
            'position' =>
                $temporaryPosition,
        ]);

        if (
            $newPosition
            < $oldPosition
        ) {
            $affected = PipelineStage::query()
                ->where(
                    'pipeline_id',
                    $stage->pipeline_id
                )
                ->whereBetween(
                    'position',
                    [
                        $newPosition,
                        $oldPosition - 1,
                    ]
                )
                ->orderBy(
                    'position',
                    'desc'
                )
                ->get();

            foreach ($affected as $item) {
                $item->update([
                    'position' =>
                        $item->position + 1,
                ]);
            }
        } else {
            $affected = PipelineStage::query()
                ->where(
                    'pipeline_id',
                    $stage->pipeline_id
                )
                ->whereBetween(
                    'position',
                    [
                        $oldPosition + 1,
                        $newPosition,
                    ]
                )
                ->orderBy('position')
                ->get();

            foreach ($affected as $item) {
                $item->update([
                    'position' =>
                        $item->position - 1,
                ]);
            }
        }

        $stage->update([
            'position' =>
                $newPosition,
        ]);
    }

    private function shiftForward(
        Pipeline $pipeline,
        int $position
    ): void {
        $stages = $pipeline->stages()
            ->where(
                'position',
                '>=',
                $position
            )
            ->orderBy(
                'position',
                'desc'
            )
            ->get();

        foreach ($stages as $stage) {
            $stage->update([
                'position' =>
                    $stage->position + 1,
            ]);
        }
    }

    private function nextPosition(
        Pipeline $pipeline
    ): int {
        return (
            (int) $pipeline->stages()
                ->max('position')
        ) + 1;
    }

    private function resolvePipeline(
        Pipeline $pipeline
    ): Pipeline {
        $resolved = Pipeline::query()
            ->find(
                $pipeline->getKey()
            );

        if ($resolved === null) {
            throw (
                new ModelNotFoundException()
            )->setModel(
                Pipeline::class,
                [
                    $pipeline->getKey(),
                ]
            );
        }

        return $resolved;
    }

    private function resolveStage(
        PipelineStage $stage
    ): PipelineStage {
        $resolved =
            PipelineStage::query()
                ->find(
                    $stage->getKey()
                );

        if ($resolved === null) {
            throw (
                new ModelNotFoundException()
            )->setModel(
                PipelineStage::class,
                [
                    $stage->getKey(),
                ]
            );
        }

        return $resolved;
    }

    private function normalizeName(
        mixed $name
    ): string {
        $name = trim(
            (string) $name
        );

        if ($name === '') {
            throw new RuntimeException(
                'Stage name is required.'
            );
        }

        return $name;
    }
}
