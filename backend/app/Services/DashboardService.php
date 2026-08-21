<?php

namespace App\Services;

use App\Enums\ActivityStatus;
use App\Models\Activity;
use App\Models\Lead;
use App\Models\Opportunity;
use App\Models\PipelineStage;
use App\Models\User;
use Illuminate\Support\Collection;

class DashboardService
{
    public function metrics(): array
    {
        $opportunities = Opportunity::query();

        $totalLeads = Lead::query()->count();

        $convertedLeads = Lead::query()
            ->whereNotNull('converted_customer_id')
            ->count();

        $leadConversionRate = $totalLeads > 0
            ? round(($convertedLeads / $totalLeads) * 100, 2)
            : 0.0;

        $totalOpportunities = (clone $opportunities)->count();

        $pipelineValueMinor = (int) (clone $opportunities)
            ->sum('value_minor');

        $weightedPipelineValueMinor = (int) round(
            (clone $opportunities)
                ->get(['value_minor', 'probability'])
                ->sum(
                    fn (Opportunity $opportunity): float =>
                        $opportunity->value_minor
                        * $opportunity->probability
                        / 100
                )
        );

        $pendingActivities = Activity::query()
            ->where('status', ActivityStatus::PENDING->value)
            ->count();

        $overdueActivities = Activity::query()
            ->where('status', ActivityStatus::PENDING->value)
            ->whereNotNull('due_at')
            ->where('due_at', '<', now())
            ->count();

        $dueSoonActivities = Activity::query()
            ->where('status', ActivityStatus::PENDING->value)
            ->whereBetween('due_at', [
                now(),
                now()->copy()->addHours(24),
            ])
            ->count();

        return [
            'total_leads' => $totalLeads,
            'converted_leads' => $convertedLeads,
            'lead_conversion_rate' => $leadConversionRate,
            'total_opportunities' => $totalOpportunities,
            'pipeline_value_minor' => $pipelineValueMinor,
            'weighted_pipeline_value_minor' => $weightedPipelineValueMinor,
            'pending_activities' => $pendingActivities,
            'overdue_activities' => $overdueActivities,
            'due_soon_activities' => $dueSoonActivities,
        ];
    }

    public function opportunitiesByStage(): Collection
    {
        return PipelineStage::query()
            ->where('is_active', true)
            ->withCount('opportunities')
            ->withSum('opportunities', 'value_minor')
            ->orderBy('pipeline_id')
            ->orderBy('position')
            ->get();
    }

    public function opportunitiesByResponsible(): Collection
    {
        return User::query()
            ->whereHas('assignedOpportunities')
            ->withCount('assignedOpportunities')
            ->withSum(
                'assignedOpportunities',
                'value_minor'
            )
            ->orderBy('name')
            ->get();
    }

    public function upcomingActivities(int $limit = 10): Collection
    {
        return Activity::query()
            ->with([
                'customer',
                'opportunity',
                'responsible',
            ])
            ->where('status', ActivityStatus::PENDING->value)
            ->whereNotNull('due_at')
            ->where('due_at', '>=', now())
            ->orderBy('due_at')
            ->limit($limit)
            ->get();
    }
}
