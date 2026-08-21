<?php

namespace App\Models;

use App\Enums\CatalogItemType;
use App\Support\Currency;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use RuntimeException;

class CatalogItem extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'type',
        'name',
        'code',
        'price_minor',
        'currency',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'type' => CatalogItemType::class,
            'price_minor' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (CatalogItem $item): void {
            $item->name = trim(
                (string) $item->name
            );

            if ($item->name === '') {
                throw new RuntimeException(
                    'Catalog item name is required.'
                );
            }

            $code = trim(
                (string) ($item->code ?? '')
            );

            $item->code = $code === ''
                ? null
                : $code;

            $type = CatalogItemType::tryFrom(
                (string) (
                    $item->getAttributes()['type']
                    ?? ''
                )
            );

            if ($type === null) {
                throw new RuntimeException(
                    'Catalog item type is invalid.'
                );
            }

            $item->type = $type;

            $priceMinor = (int) $item->price_minor;

            if ($priceMinor < 0) {
                throw new RuntimeException(
                    'Catalog item price cannot be negative.'
                );
            }

            $item->price_minor = $priceMinor;

            $item->currency = Currency::normalize(
                (string) $item->currency
            );
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(
            Tenant::class
        );
    }
}
