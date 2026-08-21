<?php

namespace App\Http\Controllers;

use App\Models\Pipeline;
use App\Models\PipelineStage;
use App\Services\AuditService;
use App\Services\PipelineStageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PipelineStageController extends Controller
{
    public function store(
        Request $request,
        int $pipelineId,
        PipelineStageService $service,
        AuditService $audits
    ): RedirectResponse {
        $pipeline = Pipeline::query()
            ->findOrFail($pipelineId);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'position' => ['nullable', 'integer', 'min:1'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $stage = $service->create(
            $pipeline,
            $data
        );

        $audits->log(
            'pipeline_stage.created',
            'Etapa criada no pipeline '
                . $pipeline->name
                . ': '
                . $stage->name
                . '.'
        );

        return redirect()
            ->route(
                'pipelines.edit',
                $pipeline->id
            )
            ->with(
                'success',
                __('pipelines.stage_created')
            );
    }

    public function update(
        Request $request,
        int $pipelineId,
        int $stageId,
        PipelineStageService $service,
        AuditService $audits
    ): RedirectResponse {
        $pipeline = Pipeline::query()
            ->findOrFail($pipelineId);

        $stage = PipelineStage::query()
            ->where(
                'pipeline_id',
                $pipeline->id
            )
            ->findOrFail($stageId);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'position' => ['nullable', 'integer', 'min:1'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $stage = $service->update(
            $stage,
            $data
        );

        $audits->log(
            'pipeline_stage.updated',
            'Etapa atualizada no pipeline '
                . $pipeline->name
                . ': '
                . $stage->name
                . '.'
        );

        return redirect()
            ->route(
                'pipelines.edit',
                $pipeline->id
            )
            ->with(
                'success',
                __('pipelines.stage_updated')
            );
    }

    public function destroy(
        int $pipelineId,
        int $stageId,
        PipelineStageService $service,
        AuditService $audits
    ): RedirectResponse {
        $pipeline = Pipeline::query()
            ->findOrFail($pipelineId);

        $stage = PipelineStage::query()
            ->where(
                'pipeline_id',
                $pipeline->id
            )
            ->findOrFail($stageId);

        $name = $stage->name;

        $service->delete($stage);

        $audits->log(
            'pipeline_stage.deleted',
            'Etapa excluída do pipeline '
                . $pipeline->name
                . ': '
                . $name
                . '.'
        );

        return redirect()
            ->route(
                'pipelines.edit',
                $pipeline->id
            )
            ->with(
                'success',
                __('pipelines.stage_deleted')
            );
    }

    public function reorder(
        Request $request,
        int $pipelineId,
        PipelineStageService $service,
        AuditService $audits
    ): RedirectResponse {
        $pipeline = Pipeline::query()
            ->findOrFail($pipelineId);

        $data = $request->validate([
            'stage_ids' => ['required', 'array'],
            'stage_ids.*' => ['required', 'integer'],
        ]);

        $service->reorder(
            $pipeline,
            $data['stage_ids']
        );

        $audits->log(
            'pipeline_stage.reordered',
            'Etapas reordenadas no pipeline '
                . $pipeline->name
                . '.'
        );

        return redirect()
            ->route(
                'pipelines.edit',
                $pipeline->id
            )
            ->with(
                'success',
                __('pipelines.stages_reordered')
            );
    }
}
