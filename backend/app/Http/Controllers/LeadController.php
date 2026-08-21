<?php

namespace App\Http\Controllers;

use App\Enums\CustomerType;

use App\Enums\LeadSource;
use App\Enums\LeadStatus;
use App\Models\Lead;
use App\Models\User;
use App\Services\AuditService;
use App\Services\TriggerDispatcher;
use App\Enums\TriggerType;
use App\Support\TriggerOccurrence;
use App\Services\TenantContext;
use App\Services\LeadConversionService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LeadController extends Controller
{
    public function index(Request $request)
    {
        $query = Lead::query()
            ->with('responsible')
            ->orderByDesc('created_at');

        if ($request->filled('search')) {
            $search = trim(
                (string) $request->input('search')
            );

            $query->where(function ($builder) use ($search): void {
                $builder
                    ->where('name', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%')
                    ->orWhere('phone', 'like', '%' . $search . '%');
            });
        }

        if ($request->filled('status')) {
            $query->where(
                'status',
                $request->input('status')
            );
        }

        if ($request->filled('source')) {
            $query->where(
                'source',
                $request->input('source')
            );
        }

        if ($request->filled('responsible_user_id')) {
            $query->where(
                'responsible_user_id',
                $request->integer('responsible_user_id')
            );
        }

        return view('leads.index', [
            'leads' => $query
                ->paginate(20)
                ->withQueryString(),
            'statuses' => LeadStatus::cases(),
            'sources' => LeadSource::cases(),
            'responsibles' => $this->responsibles(),
        ]);
    }

    public function create()
    {
        return view('leads.create', [
            'statuses' => LeadStatus::cases(),
            'sources' => LeadSource::cases(),
            'responsibles' => $this->responsibles(),
        ]);
    }

    public function store(
        Request $request,
        AuditService $audits,
        TriggerDispatcher $triggers
    ) {
        $data = $this->validatedData(
            $request
        );

        $lead = Lead::create($data);

        $audits->log(
            'lead.created',
            'Lead criado: '
                . $lead->name
                . '.'
        );

        $triggers->dispatch(
            TriggerOccurrence::forTenant(
                type: TriggerType::LEAD_CREATED,
                tenant: app(TenantContext::class)->get(),
                subjectType: 'lead',
                subjectId: $lead->id,
                payload: [
                    'lead_id' => $lead->id,
                    'name' => $lead->name,
                    'status' => $lead->status->value,
                    'source' => $lead->source->value,
                    'responsible_user_id' =>
                        $lead->responsible_user_id,
                ]
            )
        );

        return redirect()
            ->route('leads.index')
            ->with(
                'success',
                __('leads.messages.created')
            );
    }

    public function edit(int $id)
    {
        return view('leads.edit', [
            'lead' => Lead::findOrFail($id),
            'statuses' => LeadStatus::cases(),
            'sources' => LeadSource::cases(),
            'responsibles' => $this->responsibles(),
        ]);
    }

    public function update(
        Request $request,
        int $id,
        AuditService $audits
    ) {
        $lead = Lead::findOrFail($id);

        $data = $this->validatedData(
            $request
        );

        $changes = [];

        foreach ([
            'name',
            'email',
            'phone',
            'status',
            'source',
            'responsible_user_id',
            'tags',
            'notes',
        ] as $field) {
            $before = $lead->{$field};
            $after = $data[$field] ?? null;

            if ($before instanceof \BackedEnum) {
                $before = $before->value;
            }

            if ($after instanceof \BackedEnum) {
                $after = $after->value;
            }

            if ($before !== $after) {
                $changes[] = $field;
            }
        }

        $lead->fill($data);
        $lead->save();

        $audits->log(
            'lead.updated',
            empty($changes)
                ? 'Lead atualizado sem alterações: '
                    . $lead->name
                    . '.'
                : 'Lead atualizado: '
                    . $lead->name
                    . '. Alterações: '
                    . implode(', ', $changes)
                    . '.'
        );

        return redirect()
            ->route('leads.index')
            ->with(
                'success',
                __('leads.messages.updated')
            );
    }

    public function destroy(
        int $id,
        AuditService $audits
    ) {
        $lead = Lead::findOrFail($id);

        $name = $lead->name;

        $lead->delete();

        $audits->log(
            'lead.deleted',
            'Lead excluído: '
                . $name
                . '.'
        );

        return redirect()
            ->route('leads.index')
            ->with(
                'success',
                __('leads.messages.deleted')
            );
    }

    private function validatedData(
        Request $request
    ): array {
        $tenant = app(TenantContext::class)->get();

        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'email' => [
                'nullable',
                'email',
                'max:255',
            ],

            'phone' => [
                'nullable',
                'string',
                'max:50',
            ],

            'status' => [
                'required',
                Rule::enum(LeadStatus::class),
            ],

            'source' => [
                'required',
                Rule::enum(LeadSource::class),
            ],

            'responsible_user_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')
                    ->where(
                        fn ($query) => $query->where(
                            'tenant_id',
                            $tenant->id
                        )
                    ),
            ],

            'tags' => [
                'nullable',
                'array',
                'max:20',
            ],

            'tags.*' => [
                'required',
                'string',
                'max:50',
            ],

            'notes' => [
                'nullable',
                'string',
                'max:10000',
            ],
        ]);

        $data['email'] = $this->nullableTrim(
            $data['email'] ?? null
        );

        $data['phone'] = $this->nullableTrim(
            $data['phone'] ?? null
        );

        $data['notes'] = $this->nullableTrim(
            $data['notes'] ?? null
        );

        $data['tags'] = $this->normalizeTags(
            $data['tags'] ?? null
        );

        return $data;
    }

    private function responsibles()
    {
        return User::query()
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'email',
            ]);
    }

    private function nullableTrim(
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

    private function normalizeTags(
        ?array $tags
    ): ?array {
        if (!$tags) {
            return null;
        }

        $normalized = collect($tags)
            ->map(
                fn ($tag) => trim(
                    (string) $tag
                )
            )
            ->filter()
            ->unique()
            ->values()
            ->all();

        return $normalized === []
            ? null
            : $normalized;
    }

    public function convert(
        Request $request,
        int $id,
        LeadConversionService $conversion
    ) {
        $lead = Lead::findOrFail($id);

        $data = $request->validate([
            'customer_type' => [
                'required',
                \Illuminate\Validation\Rule::enum(
                    CustomerType::class
                ),
            ],
        ]);

        $customer = $conversion->convert(
            $lead,
            CustomerType::from(
                $data['customer_type']
            )
        );

        return redirect()
            ->route(
                'customers.show',
                $customer->id
            )
            ->with(
                'success',
                __('leads.conversion_success')
            );
    }
}
