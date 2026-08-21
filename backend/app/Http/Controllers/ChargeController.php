<?php

namespace App\Http\Controllers;

use App\Enums\ReceivableStatus;
use App\Models\Charge;
use App\Models\Receivable;
use App\Services\ChargeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class ChargeController extends Controller
{
    public function index(): View
    {
        $charges = Charge::query()
            ->with([
                'receivable.customer',
            ])
            ->latest('id')
            ->paginate(25);

        return view(
            'charges.index',
            compact('charges')
        );
    }

    public function create(): View
    {
        $receivables = Receivable::query()
            ->with('customer')
            ->where(
                'status',
                ReceivableStatus::PENDING->value
            )
            ->orderBy('due_date')
            ->get();

        return view(
            'charges.create',
            compact('receivables')
        );
    }

    public function store(
        Request $request,
        ChargeService $service
    ): RedirectResponse {
        $data = $request->validate([
            'receivable_id' => [
                'required',
                'integer',
            ],
            'scheduled_at' => [
                'nullable',
                'date',
            ],
            'channel' => [
                'nullable',
                'string',
                'max:50',
            ],
            'recipient' => [
                'nullable',
                'string',
                'max:255',
            ],
            'external_reference' => [
                'nullable',
                'string',
                'max:255',
            ],
        ]);

        try {
            $service->create(
                $data
            );
        } catch (RuntimeException $exception) {
            return back()
                ->withInput()
                ->withErrors([
                    'charge' =>
                        $exception->getMessage(),
                ]);
        }

        return redirect()
            ->route('charges.index')
            ->with(
                'status',
                __('charges.messages.created')
            );
    }

    public function markSent(
        Request $request,
        int $id,
        ChargeService $service
    ): RedirectResponse {
        $charge = Charge::query()
            ->findOrFail($id);

        $data = $request->validate([
            'external_reference' => [
                'nullable',
                'string',
                'max:255',
            ],
        ]);

        try {
            $service->markSent(
                $charge,
                $data['external_reference']
                    ?? null
            );
        } catch (RuntimeException $exception) {
            return back()
                ->withErrors([
                    'charge' =>
                        $exception->getMessage(),
                ]);
        }

        return redirect()
            ->route('charges.index')
            ->with(
                'status',
                __('charges.messages.sent')
            );
    }

    public function markFailed(
        Request $request,
        int $id,
        ChargeService $service
    ): RedirectResponse {
        $charge = Charge::query()
            ->findOrFail($id);

        $data = $request->validate([
            'failure_reason' => [
                'required',
                'string',
                'max:2000',
            ],
        ]);

        try {
            $service->markFailed(
                $charge,
                $data['failure_reason']
            );
        } catch (RuntimeException $exception) {
            return back()
                ->withErrors([
                    'charge' =>
                        $exception->getMessage(),
                ]);
        }

        return redirect()
            ->route('charges.index')
            ->with(
                'status',
                __('charges.messages.failed')
            );
    }

    public function cancel(
        int $id,
        ChargeService $service
    ): RedirectResponse {
        $charge = Charge::query()
            ->findOrFail($id);

        try {
            $service->cancel(
                $charge
            );
        } catch (RuntimeException $exception) {
            return back()
                ->withErrors([
                    'charge' =>
                        $exception->getMessage(),
                ]);
        }

        return redirect()
            ->route('charges.index')
            ->with(
                'status',
                __('charges.messages.cancelled')
            );
    }
}