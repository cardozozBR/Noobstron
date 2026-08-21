<?php

namespace App\Http\Controllers;

use App\Enums\ProposalStatus;
use App\Models\Opportunity;
use App\Models\Proposal;
use App\Models\AuditLog;
use App\Models\Sale;
use App\Services\SaleService;
use App\Services\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class SaleController extends Controller
{
    public function index(): View
    {
        $tenant = app(TenantContext::class)->get();

        $sales = Sale::query()
            ->with([
                'customer',
                'opportunity',
                'proposal',
            ])
            ->orderByDesc('closed_at')
            ->orderByDesc('id')
            ->paginate(25);

        $history = AuditLog::query()
            ->with('user')
            ->where(
                'action',
                'sale.closed'
            )
            ->latest()
            ->limit(20)
            ->get();
        return view(
            'sales.index',
            compact(
                'sales',
                'history',
                'tenant'
            )
        );
    }

    public function create(int $opportunityId): View
    {
        $opportunity = Opportunity::query()
            ->with('customer')
            ->findOrFail($opportunityId);

        if (
            Sale::query()
                ->where(
                    'opportunity_id',
                    $opportunity->id
                )
                ->exists()
        ) {
            abort(409);
        }

        $proposals = Proposal::query()
            ->where(
                'opportunity_id',
                $opportunity->id
            )
            ->where(
                'customer_id',
                $opportunity->customer_id
            )
            ->where(
                'status',
                ProposalStatus::ACCEPTED->value
            )
            ->orderByDesc('id')
            ->get();

        return view(
            'sales.create',
            compact(
                'opportunity',
                'proposals'
            )
        );
    }

    public function store(
        Request $request,
        int $opportunityId,
        SaleService $service
    ): RedirectResponse {
        $opportunity = Opportunity::query()
            ->findOrFail($opportunityId);

        $data = $request->validate([
            'proposal_id' => [
                'nullable',
                'integer',
            ],
            'number' => [
                'nullable',
                'string',
                'max:100',
            ],
            'total_minor' => [
                'nullable',
                'integer',
                'min:0',
            ],
            'currency' => [
                'nullable',
                'string',
                'size:3',
            ],
            'closed_at' => [
                'nullable',
                'date',
            ],
        ]);

        try {
            $sale = $service->close(
                $opportunity,
                $data
            );
        } catch (RuntimeException $exception) {
            return back()
                ->withInput()
                ->withErrors([
                    'sale' =>
                        $exception->getMessage(),
                ]);
        }

        return redirect()
            ->route('sales.index')
            ->with(
                'status',
                __('sales.messages.created', [
                    'number' => $sale->number,
                ])
            );
    }
}
