<?php

namespace App\Services;

use App\Models\Tenant;
use RuntimeException;

class TenantContext
{
    private ?Tenant $tenant = null;

    public function set(Tenant $tenant): void
    {
        $this->tenant = $tenant;
    }

    public function get(): Tenant
    {
        if (!$this->tenant) {
            throw new RuntimeException('Nenhum tenant foi definido no contexto atual.');
        }

        return $this->tenant;
    }

    public function id(): int
    {
        return $this->get()->id;
    }

    public function clear(): void
    {
        $this->tenant = null;
    }
}