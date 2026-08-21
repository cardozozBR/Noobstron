<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Opportunity;
use App\Models\Pipeline;
use App\Models\PipelineStage;
use App\Models\User;
use App\Services\AuditService;
use App\Services\OpportunityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OpportunityController extends Controller
{
    public function index(Request $request): View
    {
        $query = Opportunity::query()
            ->with([
                'customer',
                'pipeline',
                'stage',
                'responsible',
                'sales',
            ])
            ->orderByDesc('created_at');

        if ($request->filled('search')) {
            $search = trim(
                (string) $request->input('search')
            );

            $query->where(
                'name',
                'like',
                '%' . $search . '%'
            );
        }

        if ($request->filled('customer_id')) {
            $query->where(
                'customer_id',
                $request->integer('customer_id')
            );
        }

        if ($request->filled('pipeline_id')) {
            $query->where(
                'pipeline_id',
                $request->integer('pipeline_id')
            );
        }

        if ($request->filled('pipeline_stage_id')) {
            $query->where(
                'pipeline_stage_id',
                $request->integer('pipeline_stage_id')
            );
        }

        if ($request->filled('responsible_user_id')) {
            $query->where(
                'responsible_user_id',
                $request->integer('responsible_user_id')
            );
        }

        return view('opportunities.index', [
            'opportunities' => $query
                ->paginate(20)
                ->withQueryString(),
            ...$this->formOptions(),
        ]);
    }

    public function create(): View
    {
        return view(
            'opportunities.create',
            $this->formOptions()
        );
    }

    public function store(
        Request $request,
        OpportunityService $service,
        AuditService $audits
    ): RedirectResponse {
        $opportunity = $service->create(
            $this->validatedData($request)
        );

        $audits->log(
            'opportunity.created',
            'Oportunidade criada: '
                . $opportunity->name
                . '.'
        );

        return redirect()
            ->route(
                'opportunities.edit',
                $opportunity->id
            )
            ->with(
                'success',
                __('opportunities.messages.created')
            );
    }

    public function edit(int $id): View
    {
        $opportunity = Opportunity::query()
            ->with([
                'customer',
                'pipeline',
                'stage',
                'responsible',
                'sales',
            ])
            ->findOrFail($id);

        return view('opportunities.edit', [
            'opportunity' => $opportunity,
            ...$this->formOptions(),
        ]);
    }

    public function update(
        Request $request,
        int $id,
        OpportunityService $service,
        AuditService $audits
    ): RedirectResponse {
        $opportunity = Opportunity::query()
            ->findOrFail($id);

        $updated = $service->update(
            $opportunity,
            $this->validatedData(
                $request,
                true
            )
        );

        $audits->log(
            'opportunity.updated',
            'Oportunidade atualizada: '
                . $updated->name
                . '.'
        );

        return redirect()
            ->route(
                'opportunities.edit',
                $opportunity->id
            )
            ->with(
                'success',
                __('opportunities.messages.updated')
            );
    }

    public function moveStage(
        Request $request,
        int $id,
        OpportunityService $service,
        AuditService $audits
    ): RedirectResponse {
        $opportunity = Opportunity::query()
            ->findOrFail($id);

        $data = $request->validate([
            'pipeline_stage_id' => [
                'required',
                'integer',
            ],
        ]);

        $stage = PipelineStage::query()
            ->findOrFail(
                (int) $data['pipeline_stage_id']
            );

        $updated = $service->moveToStage(
            $opportunity,
            $stage
        );

        $audits->log(
            'opportunity.stage_changed',
            'Etapa da oportunidade alterada: '
                . $updated->name
                . ' -> '
                . $stage->name
                . '.'
        );

        return redirect()
            ->back()
            ->with(
                'success',
                __('opportunities.messages.stage_changed')
            );
    }

    public function destroy(
        int $id,
        AuditService $audits
    ): RedirectResponse {
        $opportunity = Opportunity::query()
            ->findOrFail($id);

        $name = $opportunity->name;

        $opportunity->delete();

        $audits->log(
            'opportunity.deleted',
            'Oportunidade excluida: '
                . $name
                . '.'
        );

        return redirect()
            ->route('opportunities.index')
            ->with(
                'success',
                __('opportunities.messages.deleted')
            );
    }

    private function validatedData(
        Request $request,
        bool $partial = false
    ): array {
        $required = $partial
            ? 'sometimes'
            : 'required';

        return $request->validate([
            'name' => [
                $required,
                'string',
                'max:255',
            ],
            'customer_id' => [
                $required,
                'integer',
            ],
            'pipeline_id' => [
                'nullable',
                'integer',
            ],
            'pipeline_stage_id' => [
                'nullable',
                'integer',
            ],
            'responsible_user_id' => [
                'nullable',
                'integer',
            ],
            'value_minor' => [
                $required,
                'integer',
                'min:0',
            ],
            'currency' => [
                $required,
                'string',
                'size:3',
            ],
            'probability' => [
                $required,
                'integer',
                'between:0,100',
            ],
            'expected_close_date' => [
                'nullable',
                'date',
            ],
            'notes' => [
                'nullable',
                'string',
                'max:10000',
            ],
        ]);
    }

    private function formOptions(): array
    {
        return [
            'customers' => Customer::query()
                ->orderBy('name')
                ->get(),
            'pipelines' => Pipeline::query()
                ->where('is_active', true)
                ->with([
                    'stages' => fn ($query) => $query
                        ->where('is_active', true)
                        ->orderBy('position'),
                ])
                ->orderByDesc('is_default')
                ->orderBy('name')
                ->get(),
            'responsibles' => User::query()
                ->orderBy('name')
                ->get([
                    'id',
                    'name',
                    'email',
                ]),
        ];
    }
}
