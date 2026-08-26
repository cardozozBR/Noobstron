<?php

namespace App\Http\Controllers;

use App\Enums\CommercialContactStatus;
use App\Models\CommercialContact;
use App\Models\Tenant;
use App\Services\PlatformAdminAuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PlatformCommercialContactController extends Controller
{
    public function index(Request $request): View
    {
        $status = trim(
            (string) $request->query('status', '')
        );

        $validStatuses = array_map(
            fn (CommercialContactStatus $status) =>
                $status->value,
            CommercialContactStatus::cases()
        );

        $query = CommercialContact::query()
            ->with(
                'convertedTenant.latestSubscription.plan.prices'
            )
            ->latest();

        if (
            $status !== ''
            && in_array(
                $status,
                $validStatuses,
                true
            )
        ) {
            $query->where(
                'status',
                $status
            );
        } else {
            $status = '';
        }

        return view('platform.contacts.index', [
            'contacts' => $query
                ->paginate(30)
                ->withQueryString()
                ->through(
                    function (CommercialContact $contact): CommercialContact {
                        $subscription = $contact
                            ->convertedTenant
                            ?->latestSubscription;

                        $currency = $subscription?->currency
                            ?: $contact
                                ->convertedTenant
                                ?->currency;

                        $amountMinor = $subscription
                            ?->amount_minor;

                        if (
                            $amountMinor === null
                            && $subscription?->plan !== null
                            && $currency !== null
                        ) {
                            $amountMinor = $subscription
                                ->plan
                                ->prices
                                ->firstWhere(
                                    'currency',
                                    $currency
                                )
                                ?->amount_minor;
                        }

                        $contact->setAttribute(
                            'contracted_revenue_minor',
                            $amountMinor
                        );

                        $contact->setAttribute(
                            'contracted_revenue_currency',
                            $currency
                        );

                        return $contact;
                    }
                ),

            'status' => $status,

            'statuses' =>
                CommercialContactStatus::cases(),

            'tenants' => Tenant::query()
                ->withoutGlobalScopes()
                ->orderBy('name')
                ->get([
                    'id',
                    'name',
                    'slug',
                    'status',
                ]),
        ]);
    }

    public function updateStatus(
        Request $request,
        CommercialContact $contact
    ): RedirectResponse {
        $data = $request->validate([
            'status' => [
                'required',
                Rule::enum(
                    CommercialContactStatus::class
                ),
            ],
        ]);

        $contact->forceFill([
            'status' => $data['status'],
        ])->save();

        return redirect()
            ->route(
                'platform.contacts.index',
                [
                    'status' =>
                        $request->query('status'),
                ]
            )
            ->with(
                'success',
                __('platform.contacts.status_updated')
            );
    }

    public function convert(
        Request $request,
        CommercialContact $contact,
        PlatformAdminAuditService $audit
    ): RedirectResponse {
        $data = $request->validate([
            'tenant_id' => [
                'required',
                'integer',
                Rule::exists('tenants', 'id'),
            ],
        ]);

        $tenant = Tenant::query()
            ->withoutGlobalScopes()
            ->findOrFail(
                $data['tenant_id']
            );

        DB::transaction(
            function () use (
                $contact,
                $tenant,
                $audit
            ): void {
                $contact = CommercialContact::query()
                    ->whereKey($contact->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $before = [
                    'status' =>
                        $contact->status->value,

                    'converted_tenant_id' =>
                        $contact->converted_tenant_id,

                    'converted_at' =>
                        $contact->converted_at?->toISOString(),
                ];

                $contact->forceFill([
                    'status' =>
                        CommercialContactStatus::CONVERTED,

                    'converted_tenant_id' =>
                        $tenant->id,

                    'converted_at' =>
                        now(),
                ])->save();

                $audit->log(
                    action: 'commercial_contact.converted',
                    tenant: $tenant,
                    entityType: CommercialContact::class,
                    entityId: $contact->id,
                    beforeState: $before,
                    afterState: [
                        'status' =>
                            $contact->status->value,

                        'converted_tenant_id' =>
                            $contact->converted_tenant_id,

                        'converted_at' =>
                            $contact->converted_at?->toISOString(),
                    ],
                );
            }
        );

        return redirect()
            ->route(
                'platform.contacts.index',
                [
                    'status' =>
                        $request->query('status'),
                ]
            )
            ->with(
                'success',
                __('platform.contacts.converted')
            );
    }
}
