<?php

namespace App\Http\Controllers;

use App\Models\Pipeline;
use App\Services\AuditService;
use App\Services\PipelineService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PipelineController extends Controller
{
    public function index(): View
    {
        $pipelines = Pipeline::query()
            ->withCount('stages')
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get();

        return view(
            'pipelines.index',
            compact('pipelines')
        );
    }

    public function create(): View
    {
        return view('pipelines.create');
    }

    public function store(
        Request $request,
        PipelineService $service,
        AuditService $audits
    ): RedirectResponse {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_default' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $pipeline = $service->create($data);

        $audits->log(
            'pipeline.created',
            'Pipeline criado: ' . $pipeline->name . '.'
        );

        return redirect()
            ->route(
                'pipelines.edit',
                $pipeline->id
            )
            ->with(
                'success',
                __('pipelines.created')
            );
    }

    public function edit(int $id): View
    {
        $pipeline = Pipeline::query()
            ->with('stages')
            ->findOrFail($id);

        return view(
            'pipelines.edit',
            compact('pipeline')
        );
    }

    public function update(
        Request $request,
        int $id,
        PipelineService $service,
        AuditService $audits
    ): RedirectResponse {
        $pipeline = Pipeline::query()
            ->findOrFail($id);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_default' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $beforeDefault = $pipeline->is_default;

        $updated = $service->update(
            $pipeline,
            $data
        );

        $audits->log(
            'pipeline.updated',
            'Pipeline atualizado: '
                . $updated->name
                . '.'
        );

        if (
            $beforeDefault
            !== $updated->is_default
        ) {
            $audits->log(
                'pipeline.default_changed',
                'Pipeline padrão alterado: '
                    . $updated->name
                    . '.'
            );
        }

        return redirect()
            ->route(
                'pipelines.edit',
                $pipeline->id
            )
            ->with(
                'success',
                __('pipelines.updated')
            );
    }

    public function setDefault(
        int $id,
        PipelineService $service,
        AuditService $audits
    ): RedirectResponse {
        $pipeline = Pipeline::query()
            ->findOrFail($id);

        $wasDefault = $pipeline->is_default;

        $pipeline = $service->setDefault(
            $pipeline
        );

        if (! $wasDefault) {
            $audits->log(
                'pipeline.default_changed',
                'Pipeline padrão alterado: '
                    . $pipeline->name
                    . '.'
            );
        }

        return redirect()
            ->route('pipelines.index')
            ->with(
                'success',
                __('pipelines.default_changed')
            );
    }

    public function destroy(
        int $id,
        PipelineService $service,
        AuditService $audits
    ): RedirectResponse {
        $pipeline = Pipeline::query()
            ->findOrFail($id);

        $name = $pipeline->name;

        $service->delete(
            $pipeline
        );

        $audits->log(
            'pipeline.deleted',
            'Pipeline excluído: '
                . $name
                . '.'
        );

        return redirect()
            ->route('pipelines.index')
            ->with(
                'success',
                __('pipelines.deleted')
            );
    }
}
