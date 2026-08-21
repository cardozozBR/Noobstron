<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use RuntimeException;

class ProposalItem extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'proposal_id',
        'catalog_item_id',
        'position',
        'item_type',
        'name',
        'code',
        'quantity',
        'unit_price_minor',
        'discount_minor',
        'tax_minor',
        'total_minor',
        'taxes',
    ];

    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'quantity' => 'decimal:4',
            'unit_price_minor' => 'integer',
            'discount_minor' => 'integer',
            'tax_minor' => 'integer',
            'total_minor' => 'integer',
            'taxes' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (ProposalItem $item): void {
            $item->name = trim(
                (string) $item->name
            );

            if ($item->name === '') {
                throw new RuntimeException(
                    'Proposal item name is required.'
                );
            }

            $code = trim(
                (string) ($item->code ?? '')
            );

            $item->code = $code === ''
                ? null
                : $code;

            if ((float) $item->quantity <= 0) {
                throw new RuntimeException(
                    'Proposal item quantity must be positive.'
                );
            }

            foreach ([
                'unit_price_minor',
                'discount_minor',
                'tax_minor',
                'total_minor',
            ] as $field) {
                if ((int) $item->{$field} < 0) {
                    throw new RuntimeException(
                        'Proposal item monetary values cannot be negative.'
                    );
                }
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(
            Tenant::class
        );
    }

    public function proposal(): BelongsTo
    {
        return $this->belongsTo(
            Proposal::class
        );
    }

    public function catalogItem(): BelongsTo
    {
        return $this->belongsTo(
            CatalogItem::class
        );
    }
}
