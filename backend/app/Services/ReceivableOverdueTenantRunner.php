<?php

namespace App\Services;

use App\Models\Tenant;
use Illuminate\Support\Carbon;

class ReceivableOverdueTenantRunner
{
    public function __construct(
        private ReceivableOverdueTriggerService $service,
        private TenantContext $tenantContext
    ) {
    }

    public function dispatch(
        ?Carbon $now = null
    ): int {
        $now ??= now();

        $total = 0;

        $tenants = Tenant::query()
            ->where(
                'status',
                'active'
            )
            ->orderBy('id')
            ->get();

        try {
            foreach ($tenants as $tenant) {
                $this->tenantContext->set(
                    $tenant
                );

                $tenantToday = $now
                    ->copy()
                    ->setTimezone(
                        $tenant->timezone
                    )
                    ->startOfDay();

                $total +=
                    $this->service->dispatchForDate(
                        $tenantToday
                    );
            }
        } finally {
            $this->tenantContext->clear();
        }

        return $total;
    }
}