<?php

namespace App\Support;

use App\Services\TenantContext;

final class TenantMoneyFormatter
{
    public function __construct(
        private readonly TenantContext $tenantContext,
    ) {
    }

    public function format(Money $money): string
    {
        $tenant = $this->tenantContext->get();

        return MoneyFormatter::format(
            $money,
            $tenant->locale
        );
    }

    public function formatMinor(
        int $minor,
        ?string $currency = null
    ): string {
        $tenant = $this->tenantContext->get();

        $money = Money::fromMinor(
            $minor,
            $currency ?? $tenant->currency
        );

        return MoneyFormatter::format(
            $money,
            $tenant->locale
        );
    }

    public function tenantCurrency(): string
    {
        return $this->tenantContext->get()->currency;
    }
}