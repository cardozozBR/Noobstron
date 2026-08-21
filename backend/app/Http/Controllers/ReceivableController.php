<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Receivable;
use App\Models\Sale;
use App\Services\ReceivableService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class ReceivableController extends Controller
{
    public function index(): View
    {
        $receivables = Receivable::query()
            ->with([
                'customer',
                'sale',
            ])
            ->orderBy('due_date')
            ->orderByDesc('id')
            ->paginate(25);

        return view(
            'receivables.index',
            compact('receivables')
        );
    }

    public function create(): View
    {
        return view(
            'receivables.create',
            [
                'customers' => Customer::query()
                    ->orderBy('name')
                    ->get(),
                'sales' => Sale::query()
                    ->orderByDesc('closed_at')
                    ->orderByDesc('id')
                    ->get(),
            ]
        );
    }

    public function store(
        Request $request,
        ReceivableService $service
    ): RedirectResponse {
        try {
            $service->create(
                $this->validatedData(
                    $request
                )
            );
        } catch (RuntimeException $exception) {
            return back()
                ->withInput()
                ->withErrors([
                    'receivable' =>
                        $exception->getMessage(),
                ]);
        }

        return redirect()
            ->route('receivables.index')
            ->with(
                'status',
                __('receivables.messages.created')
            );
    }

    public function edit(int $id): View
    {
        $receivable = Receivable::query()
            ->findOrFail($id);

        return view(
            'receivables.edit',
            [
                'receivable' => $receivable,
                'customers' => Customer::query()
                    ->orderBy('name')
                    ->get(),
                'sales' => Sale::query()
                    ->orderByDesc('closed_at')
                    ->orderByDesc('id')
                    ->get(),
            ]
        );
    }

    public function update(
        Request $request,
        int $id,
        ReceivableService $service
    ): RedirectResponse {
        $receivable = Receivable::query()
            ->findOrFail($id);

        try {
            $service->update(
                $receivable,
                $this->validatedData(
                    $request,
                    false
                )
            );
        } catch (RuntimeException $exception) {
            return back()
                ->withInput()
                ->withErrors([
                    'receivable' =>
                        $exception->getMessage(),
                ]);
        }

        return redirect()
            ->route('receivables.index')
            ->with(
                'status',
                __('receivables.messages.updated')
            );
    }

    public function pay(
        Request $request,
        int $id,
        ReceivableService $service
    ): RedirectResponse {
        $receivable = Receivable::query()
            ->findOrFail($id);

        $data = $request->validate([
            'payment_reference' => [
                'nullable',
                'string',
                'max:255',
            ],
        ]);

        try {
            $service->markPaid(
                $receivable,
                $data['payment_reference']
                    ?? null
            );
        } catch (RuntimeException $exception) {
            return back()
                ->withErrors([
                    'receivable' =>
                        $exception->getMessage(),
                ]);
        }

        return redirect()
            ->route('receivables.index')
            ->with(
                'status',
                __('receivables.messages.paid')
            );
    }

    public function cancel(
        int $id,
        ReceivableService $service
    ): RedirectResponse {
        $receivable = Receivable::query()
            ->findOrFail($id);

        try {
            $service->cancel(
                $receivable
            );
        } catch (RuntimeException $exception) {
            return back()
                ->withErrors([
                    'receivable' =>
                        $exception->getMessage(),
                ]);
        }

        return redirect()
            ->route('receivables.index')
            ->with(
                'status',
                __('receivables.messages.cancelled')
            );
    }

    private function validatedData(
        Request $request,
        bool $creating = true
    ): array {
        $required = $creating
            ? 'required'
            : 'sometimes';

        return $request->validate([
            'customer_id' => [
                $required,
                'integer',
            ],
            'sale_id' => [
                'nullable',
                'integer',
            ],
            'title' => [
                $required,
                'string',
                'max:255',
            ],
            'currency' => [
                'nullable',
                'string',
                'size:3',
            ],
            'amount_minor' => [
                $required,
                'integer',
                'min:0',
            ],
            'due_date' => [
                $required,
                'date_format:Y-m-d',
            ],
        ]);
    }
}