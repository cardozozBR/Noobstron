<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;

use App\Enums\ProposalStatus;
use App\Mail\ProposalMail;
use App\Models\CatalogItem;
use App\Models\Customer;
use App\Models\Opportunity;
use App\Enums\TriggerType;
use App\Models\Proposal;
use App\Services\AuditService;
use App\Services\ProposalService;
use App\Services\TriggerDispatcher;
use App\Support\TriggerOccurrence;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;

class ProposalController extends Controller
{
    public function index(Request $request)
    {
        $query = Proposal::query()
            ->with([
                'customer',
                'opportunity',
            ])
            ->latest();

        if ($request->filled('search')) {
            $search = trim(
                (string) $request->input('search')
            );

            $query->where(
                'number',
                'like',
                '%' . $search . '%'
            );
        }

        if ($request->filled('status')) {
            $query->where(
                'status',
                $request->input('status')
            );
        }

        return view('proposals.index', [
            'proposals' => $query
                ->paginate(20)
                ->withQueryString(),
            'statuses' => ProposalStatus::cases(),
        ]);
    }

    public function create()
    {
        return view('proposals.create', [
            'statuses' => ProposalStatus::cases(),
            'customers' => Customer::query()
                ->orderBy('name')
                ->get(),
            'opportunities' => Opportunity::query()
                ->orderBy('name')
                ->get(),
            'catalogItems' => CatalogItem::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function store(
        Request $request,
        ProposalService $service,
        AuditService $audits
    ) {
        $proposal = $service->create(
            $this->validatedData(
                $request
            )
        );

        $audits->log(
            'proposal.created',
            'Proposta criada: '
                . $proposal->number
                . '.'
        );

        return redirect()
            ->route('proposals.index')
            ->with(
                'success',
                __('proposals.created')
            );
    }

    public function edit(int $id)
    {
        return view('proposals.edit', [
            'proposal' => Proposal::query()
                ->with('items')
                ->findOrFail($id),
            'statuses' => ProposalStatus::cases(),
            'customers' => Customer::query()
                ->orderBy('name')
                ->get(),
            'opportunities' => Opportunity::query()
                ->orderBy('name')
                ->get(),
            'catalogItems' => CatalogItem::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function update(
        Request $request,
        int $id,
        ProposalService $service,
        AuditService $audits
    ) {
        $proposal = Proposal::query()
            ->findOrFail($id);

        $updated = $service->update(
            $proposal,
            $this->validatedData(
                $request
            )
        );

        $audits->log(
            'proposal.updated',
            'Proposta atualizada: '
                . $updated->number
                . '.'
        );

        return redirect()
            ->route('proposals.index')
            ->with(
                'success',
                __('proposals.updated')
            );
    }

    public function destroy(
        int $id,
        AuditService $audits
    ) {
        $proposal = Proposal::query()
            ->findOrFail($id);

        $number = $proposal->number;

        $proposal->delete();

        $audits->log(
            'proposal.deleted',
            'Proposta excluída: '
                . $number
                . '.'
        );

        return redirect()
            ->route('proposals.index')
            ->with(
                'success',
                __('proposals.deleted')
            );
    }

    private function validatedData(
        Request $request
    ): array {
        return $request->validate([
            'customer_id' => [
                'nullable',
                'integer',
            ],
            'opportunity_id' => [
                'nullable',
                'integer',
            ],
            'number' => [
                'required',
                'string',
                'max:100',
            ],
            'status' => [
                'required',
                Rule::enum(
                    ProposalStatus::class
                ),
            ],
            'currency' => [
                'nullable',
                'string',
                'size:3',
            ],
            'valid_until' => [
                'nullable',
                'date',
            ],
            'notes' => [
                'nullable',
                'string',
            ],
            'items' => [
                'required',
                'array',
                'min:1',
            ],
            'items.*.catalog_item_id' => [
                'nullable',
                'integer',
            ],
            'items.*.item_type' => [
                'nullable',
                'string',
                'max:20',
            ],
            'items.*.name' => [
                'nullable',
                'string',
                'max:255',
            ],
            'items.*.code' => [
                'nullable',
                'string',
                'max:100',
            ],
            'items.*.quantity' => [
                'required',
                'numeric',
                'gt:0',
            ],
            'items.*.unit_price_minor' => [
                'nullable',
                'integer',
                'min:0',
            ],
            'items.*.discount_minor' => [
                'nullable',
                'integer',
                'min:0',
            ],
            'items.*.taxes' => [
                'nullable',
                'array',
            ],
            'items.*.taxes.*.code' => [
                'required_with:items.*.taxes',
                'string',
                'max:100',
            ],
            'items.*.taxes.*.amount_minor' => [
                'required_with:items.*.taxes',
                'integer',
                'min:0',
            ],
        ]);
    }

    public function send(
        Request $request,
        int $id,
        AuditService $audits,
        TriggerDispatcher $triggers
    ) {
        $data = $request->validate([
            'email' => [
                'required',
                'email',
                'max:255',
            ],
        ]);

        $proposal = Proposal::query()
            ->with([
                'tenant',
                'customer',
                'opportunity',
                'items',
            ])
            ->findOrFail($id);

        $mail = (
            new ProposalMail(
                $proposal
            )
        )->locale(
            $proposal->tenant->locale
        );

        Mail::to(
            $data['email']
        )->send(
            $mail
        );

        $proposal->update([
            'status' => ProposalStatus::SENT,
        ]);

        $triggers->dispatch(
            TriggerOccurrence::forTenant(
                type: TriggerType::PROPOSAL_SENT,
                tenant: $proposal->tenant,
                subjectType: 'proposal',
                subjectId: $proposal->id,
                payload: [
                    'proposal_id' => (int) $proposal->id,
                    'customer_id' => $proposal->customer_id !== null
                        ? (int) $proposal->customer_id
                        : null,
                    'opportunity_id' => $proposal->opportunity_id !== null
                        ? (int) $proposal->opportunity_id
                        : null,
                    'email' => $data['email'],
                    'status' => ProposalStatus::SENT->value,
                ],
            )
        );

        $audits->log(
            'proposal.sent',
            'Proposta enviada: '
                . $proposal->number
                . ' para '
                . $data['email']
                . '.'
        );

        return redirect()
            ->route(
                'proposals.edit',
                $proposal->id
            )
            ->with(
                'success',
                __('proposals.sent_success')
            );
    }
    public function pdf(int $id)
    {
        $proposal = Proposal::query()
            ->with([
                'tenant',
                'customer',
                'opportunity',
                'items',
            ])
            ->findOrFail($id);

        $pdf = Pdf::loadView(
            'proposals.pdf',
            [
                'proposal' => $proposal,
            ]
        )->setPaper(
            'a4',
            'portrait'
        );

        $filename = 'proposal-'
            . preg_replace(
                '/[^A-Za-z0-9._-]+/',
                '-',
                $proposal->number
            )
            . '.pdf';

        return $pdf->download(
            $filename
        );
    }
}
