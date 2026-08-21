<?php

namespace App\Http\Controllers;

use App\Services\TenantContext;
use App\Models\User;
use App\Models\Pipeline;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Validation\Rule;

class CompanyOnboardingController extends Controller
{
    public function edit(
        TenantContext $tenantContext
    ): View {
        $tenant = $tenantContext->get();

        $teamMembers = User::query()
            ->where('tenant_id', $tenant->id)
            ->orderBy('name')
            ->get();

        $defaultPipeline = Pipeline::query()
            ->where('is_default', true)
            ->where('is_active', true)
            ->with([
                'stages' => fn ($query) => $query
                    ->where('is_active', true)
                    ->orderBy('position'),
            ])
            ->first();

        $checklist = [
            [
                'label' => __('onboarding.checklist_items.company'),
                'status' =>
                    filled($tenant->name)
                    && filled($tenant->country_code)
                    && filled($tenant->locale),
            ],
            [
                'label' => __('onboarding.checklist_items.segment'),
                'status' => filled($tenant->segment),
            ],
            [
                'label' => __('onboarding.checklist_items.team'),
                'status' => $teamMembers->isNotEmpty(),
            ],
            [
                'label' => __('onboarding.checklist_items.pipeline'),
                'status' =>
                    $defaultPipeline !== null,
            ],
            [
                'label' => __('onboarding.checklist_items.import'),
                'status' => null,
            ],
        ];

        $countries = config(
            'global.countries',
            []
        );

        $locales = config(
            'global.locales',
            []
        );

        return view(
            'onboarding.company',
            [
                'tenant' => $tenant,
                'countryName' =>
                    $countries[$tenant->country_code]
                    ?? $tenant->country_code,
                'localeName' =>
                    $locales[$tenant->locale]
                    ?? $tenant->locale,
                'segments' => [
                    'services' => __('onboarding.segments.services'),
                    'commerce' => __('onboarding.segments.commerce'),
                    'industry' => __('onboarding.segments.industry'),
                    'technology' => __('onboarding.segments.technology'),
                    'other' => __('onboarding.segments.other'),
                ],
                'teamMembers' => $teamMembers,
                'defaultPipeline' => $defaultPipeline,
                'checklist' => $checklist,
            ]
        );
    }

    public function update(
        Request $request,
        TenantContext $tenantContext
    ): RedirectResponse {
        $data = $request->validate([
            'company_name' => [
                'required',
                'string',
                'max:255',
            ],
            'segment' => [
                'nullable',
                'string',
                Rule::in(
                    array_keys(
                        config(
                            'onboarding.segments',
                            []
                        )
                    )
                ),
            ],
        ]);

        $tenant = $tenantContext->get();

        $tenant->update([
            'name' => $data['company_name'],
            'segment' =>
                $data['segment']
                ?? $tenant->segment,
        ]);

        return redirect()
            ->route('onboarding.company.edit');
    }
}
