<?php

namespace App\Http\Controllers;

use App\Services\FinancialIndicatorService;
use App\Services\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class FinancialIndicatorController extends Controller
{
    public function index(
        Request $request,
        FinancialIndicatorService $service
    ): View {
        $validated = $request->validate([
            'from' => [
                'nullable',
                'date',
            ],
            'until' => [
                'nullable',
                'date',
                'after_or_equal:from',
            ],
        ]);

        $today = now();

        $from = isset($validated['from'])
            ? Carbon::parse(
                $validated['from']
            )->startOfDay()
            : $today->copy()
                ->startOfMonth();

        $until = isset($validated['until'])
            ? Carbon::parse(
                $validated['until']
            )->endOfDay()
            : $today->copy()
                ->endOfDay();

        $summary = [
            'received_minor' =>
                $service->receivedMinor(
                    $from,
                    $until
                ),

            'outstanding_minor' =>
                $service->outstandingMinor(),

            'overdue_minor' =>
                $service->overdueMinor(
                    $today
                ),
        ];

        $revenueByPeriod =
            $service->revenueByPeriod(
                $from,
                $until
            );

        $revenueByCustomer =
            $service->revenueByCustomer(
                $from,
                $until
            );

        $tenant = app(
            TenantContext::class
        )->get();

        return view(
            'financial-indicators.index',
            compact(
                'summary',
                'revenueByPeriod',
                'revenueByCustomer',
                'from',
                'until',
                'tenant'
            )
        );
    }
}