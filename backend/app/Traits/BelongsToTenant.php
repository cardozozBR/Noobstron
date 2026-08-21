<?php

namespace App\Traits;

use App\Services\TenantContext;
use Illuminate\Database\Eloquent\Builder;

trait BelongsToTenant
{
    protected static function bootBelongsToTenant(): void
    {
        static::addGlobalScope('tenant', function (Builder $builder) {
            $tenant = app(TenantContext::class)->get();

            $builder->where(
                $builder->getModel()->getTable() . '.tenant_id',
                $tenant->id
            );
        });

        static::creating(function ($model) {
            if ($model->tenant_id === null) {
                $model->tenant_id = app(
                    TenantContext::class
                )->id();
            }
        });
    }
}