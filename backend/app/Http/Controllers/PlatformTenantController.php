<?php

namespace App\Http\Controllers;

use App\Models\PlanUsageLimit;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\TenantFeature;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PlatformTenantController extends Controller
{
    public function index(
        Request $request
    ): View {
        $query = Tenant::query()
            ->orderBy('name')
            ->orderBy('id');

        $search = trim(
            (string) $request->query(
                'q',
                ''
            )
        );

        if ($search !== '') {
            $query->where(
                function ($query) use ($search): void {
                    $query
                        ->where(
                            'name',
                            'like',
                            '%' . $search . '%'
                        )
                        ->orWhere(
                            'slug',
                            'like',
                            '%' . $search . '%'
                        );
                }
            );
        }

        $status = trim(
            (string) $request->query(
                'status',
                ''
            )
        );

        if ($status !== '') {
            $query->where(
                'status',
                $status
            );
        }

        $tenants = $query
            ->paginate(25)
            ->withQueryString();

        $tenantIds = $tenants
            ->getCollection()
            ->pluck('id');

        $userCounts = $this->userCounts(
            $tenantIds
        );

        $subscriptions =
            $this->latestSubscriptions(
                $tenantIds
            );

        return view(
            'platform.tenants.index',
            [
                'tenants' => $tenants,
                'userCounts' => $userCounts,
                'subscriptions' =>
                    $subscriptions,
                'search' => $search,
                'status' => $status,
            ]
        );
    }

    public function show(
        Tenant $tenant
    ): View {
        $subscription =
            Subscription::query()
                ->where(
                    'tenant_id',
                    $tenant->id
                )
                ->with('plan')
                ->orderByDesc('id')
                ->first();

        $features =
            TenantFeature::query()
                ->where(
                    'tenant_id',
                    $tenant->id
                )
                ->orderBy('feature')
                ->get();

        $usageLimits = collect();

        if ($subscription?->plan_id) {
            $usageLimits =
                PlanUsageLimit::query()
                    ->where(
                        'plan_id',
                        $subscription->plan_id
                    )
                    ->orderBy('metric')
                    ->get();
        }

        $userCount = (int) DB::table(
            'users'
        )
            ->where(
                'tenant_id',
                $tenant->id
            )
            ->count();

        return view(
            'platform.tenants.show',
            [
                'tenant' => $tenant,
                'subscription' =>
                    $subscription,
                'features' => $features,
                'usageLimits' =>
                    $usageLimits,
                'userCount' => $userCount,
            ]
        );
    }

    private function userCounts(
        Collection $tenantIds
    ): Collection {
        if ($tenantIds->isEmpty()) {
            return collect();
        }

        return DB::table('users')
            ->select(
                'tenant_id',
                DB::raw(
                    'COUNT(*) as aggregate'
                )
            )
            ->whereIn(
                'tenant_id',
                $tenantIds
            )
            ->groupBy('tenant_id')
            ->pluck(
                'aggregate',
                'tenant_id'
            );
    }

    private function latestSubscriptions(
        Collection $tenantIds
    ): Collection {
        if ($tenantIds->isEmpty()) {
            return collect();
        }

        return Subscription::query()
            ->whereIn(
                'tenant_id',
                $tenantIds
            )
            ->with('plan')
            ->orderByDesc('id')
            ->get()
            ->unique('tenant_id')
            ->keyBy('tenant_id');
    }
}