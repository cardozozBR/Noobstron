<?php

namespace App\Http\Controllers;

use App\Enums\ActivityStatus;
use App\Enums\ActivityType;
use App\Models\Activity;
use App\Models\Customer;
use App\Models\Opportunity;
use App\Models\User;
use App\Services\ActivityService;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ActivityController extends Controller
{
    public function index(Request $request): View
    {
        $query = Activity::query()
            ->with([
                'customer',
                'opportunity',
                'responsible',
            ])
            ->orderByDesc('created_at');

        if ($request->filled('search')) {
            $search = trim(
                (string) $request->input('search')
            );

            $query->where(
                'title',
                'like',
                '%' . $search . '%'
            );
        }

        if ($request->filled('type')) {
            $query->where(
                'type',
                $request->input('type')
            );
        }

        if ($request->filled('status')) {
            $query->where(
                'status',
                $request->input('status')
            );
        }

        if ($request->filled('customer_id')) {
            $query->where(
                'customer_id',
                $request->integer('customer_id')
            );
        }

        if ($request->filled('opportunity_id')) {
            $query->where(
                'opportunity_id',
                $request->integer('opportunity_id')
            );
        }

        if ($request->filled('responsible_user_id')) {
            $query->where(
                'responsible_user_id',
                $request->integer('responsible_user_id')
            );
        }

        return view('activities.index', [
            'activities' => $query
                ->paginate(20)
                ->withQueryString(),
            ...$this->formOptions(),
        ]);
    }

    public function create(): View
    {
        return view(
            'activities.create',
            $this->formOptions()
        );
    }

    public function store(
        Request $request,
        ActivityService $service,
        AuditService $audits
    ): RedirectResponse {
        $activity = $service->create(
            $this->validatedData($request)
        );

        $audits->log(
            'activity.created',
            'Atividade criada: '
                . $activity->title
                . '.'
        );

        return redirect()
            ->route(
                'activities.edit',
                $activity->id
            )
            ->with(
                'success',
                __('activities.messages.created')
            );
    }

    public function edit(int $id): View
    {
        $activity = Activity::query()
            ->with([
                'customer',
                'opportunity',
                'responsible',
            ])
            ->findOrFail($id);

        return view('activities.edit', [
            'activity' => $activity,
            ...$this->formOptions(),
        ]);
    }

    public function update(
        Request $request,
        int $id,
        ActivityService $service,
        AuditService $audits
    ): RedirectResponse {
        $activity = Activity::query()
            ->findOrFail($id);

        $updated = $service->update(
            $activity,
            $this->validatedData(
                $request,
                true
            )
        );

        $audits->log(
            'activity.updated',
            'Atividade atualizada: '
                . $updated->title
                . '.'
        );

        return redirect()
            ->route(
                'activities.edit',
                $updated->id
            )
            ->with(
                'success',
                __('activities.messages.updated')
            );
    }

    public function complete(
        int $id,
        ActivityService $service,
        AuditService $audits
    ): RedirectResponse {
        $activity = Activity::query()
            ->findOrFail($id);

        $updated = $service->complete(
            $activity
        );

        $audits->log(
            'activity.completed',
            'Atividade concluida: '
                . $updated->title
                . '.'
        );

        return redirect()
            ->back()
            ->with(
                'success',
                __('activities.messages.completed')
            );
    }

    public function reopen(
        int $id,
        ActivityService $service,
        AuditService $audits
    ): RedirectResponse {
        $activity = Activity::query()
            ->findOrFail($id);

        $updated = $service->reopen(
            $activity
        );

        $audits->log(
            'activity.reopened',
            'Atividade reaberta: '
                . $updated->title
                . '.'
        );

        return redirect()
            ->back()
            ->with(
                'success',
                __('activities.messages.reopened')
            );
    }

    public function cancel(
        int $id,
        ActivityService $service,
        AuditService $audits
    ): RedirectResponse {
        $activity = Activity::query()
            ->findOrFail($id);

        $updated = $service->cancel(
            $activity
        );

        $audits->log(
            'activity.cancelled',
            'Atividade cancelada: '
                . $updated->title
                . '.'
        );

        return redirect()
            ->back()
            ->with(
                'success',
                __('activities.messages.cancelled')
            );
    }

    public function destroy(
        int $id,
        AuditService $audits
    ): RedirectResponse {
        $activity = Activity::query()
            ->findOrFail($id);

        $title = $activity->title;

        $activity->delete();

        $audits->log(
            'activity.deleted',
            'Atividade excluida: '
                . $title
                . '.'
        );

        return redirect()
            ->route('activities.index')
            ->with(
                'success',
                __('activities.messages.deleted')
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
            'type' => [
                $required,
                \Illuminate\Validation\Rule::enum(
                    ActivityType::class
                ),
            ],
            'status' => [
                'nullable',
                \Illuminate\Validation\Rule::enum(
                    ActivityStatus::class
                ),
            ],
            'title' => [
                $required,
                'string',
                'max:255',
            ],
            'description' => [
                'nullable',
                'string',
                'max:10000',
            ],
            'customer_id' => [
                'nullable',
                'integer',
            ],
            'opportunity_id' => [
                'nullable',
                'integer',
            ],
            'responsible_user_id' => [
                'nullable',
                'integer',
            ],
            'due_at' => [
                'nullable',
                'date',
            ],
        ]);
    }

    private function formOptions(): array
    {
        return [
            'types' => ActivityType::cases(),
            'statuses' => ActivityStatus::cases(),

            'customers' => Customer::query()
                ->orderBy('name')
                ->get(),

            'opportunities' => Opportunity::query()
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
