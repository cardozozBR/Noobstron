<?php

namespace App\Services;

use App\Enums\ProposalStatus;
use App\Models\CatalogItem;
use App\Models\Customer;
use App\Models\Opportunity;
use App\Models\Proposal;
use App\Support\Currency;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ProposalService
{
    public function create(array $data): Proposal
    {
        return DB::transaction(function () use ($data): Proposal {
            $items = $data['items'] ?? [];

            if (! is_array($items) || $items === []) {
                throw new RuntimeException(
                    'Proposal must contain at least one item.'
                );
            }

            $currency = $this->normalizeCurrency(
                $data['currency'] ?? null
            );

            $customer = $this->resolveCustomer(
                $data['customer_id'] ?? null
            );

            $opportunity = $this->resolveOpportunity(
                $data['opportunity_id'] ?? null
            );

            $proposal = Proposal::create([
                'customer_id' => $customer?->id,
                'opportunity_id' => $opportunity?->id,
                'number' => $this->normalizeNumber(
                    $data['number'] ?? null
                ),
                'status' => $this->normalizeStatus(
                    $data['status'] ?? ProposalStatus::DRAFT
                ),
                'currency' => $currency,
                'valid_until' => $data['valid_until'] ?? null,
                'subtotal_minor' => 0,
                'discount_minor' => 0,
                'tax_minor' => 0,
                'total_minor' => 0,
                'notes' => $this->normalizeNullableText(
                    $data['notes'] ?? null
                ),
            ]);

            $this->replaceItems(
                $proposal,
                $items
            );

            return $this->recalculate(
                $proposal
            );
        });
    }

    public function update(
        Proposal $proposal,
        array $data
    ): Proposal {
        return DB::transaction(function () use ($proposal, $data): Proposal {
            $proposal = $this->resolveProposal(
                $proposal
            );

            $payload = [];

            if (array_key_exists('customer_id', $data)) {
                $payload['customer_id'] =
                    $this->resolveCustomer(
                        $data['customer_id']
                    )?->id;
            }

            if (array_key_exists('opportunity_id', $data)) {
                $payload['opportunity_id'] =
                    $this->resolveOpportunity(
                        $data['opportunity_id']
                    )?->id;
            }

            if (array_key_exists('number', $data)) {
                $payload['number'] =
                    $this->normalizeNumber(
                        $data['number']
                    );
            }

            if (array_key_exists('status', $data)) {
                $payload['status'] =
                    $this->normalizeStatus(
                        $data['status']
                    );
            }

            if (array_key_exists('currency', $data)) {
                $payload['currency'] =
                    $this->normalizeCurrency(
                        $data['currency']
                    );
            }

            if (array_key_exists('valid_until', $data)) {
                $payload['valid_until'] =
                    $data['valid_until'];
            }

            if (array_key_exists('notes', $data)) {
                $payload['notes'] =
                    $this->normalizeNullableText(
                        $data['notes']
                    );
            }

            if ($payload !== []) {
                $proposal->update($payload);
            }

            if (array_key_exists('items', $data)) {
                if (
                    ! is_array($data['items'])
                    || $data['items'] === []
                ) {
                    throw new RuntimeException(
                        'Proposal must contain at least one item.'
                    );
                }

                $this->replaceItems(
                    $proposal,
                    $data['items']
                );
            }

            return $this->recalculate(
                $proposal
            );
        });
    }

    private function replaceItems(
        Proposal $proposal,
        array $items
    ): void {
        $proposal->items()->delete();

        foreach (
            array_values($items)
            as $index => $itemData
        ) {
            $catalogItem = $this->resolveCatalogItem(
                $itemData['catalog_item_id'] ?? null
            );

            $quantity = $this->normalizeQuantity(
                $itemData['quantity'] ?? 1
            );

            $unitPriceMinor = $catalogItem !== null
                ? $catalogItem->price_minor
                : $this->normalizeMoney(
                    $itemData['unit_price_minor'] ?? null
                );

            $discountMinor = $this->normalizeMoney(
                $itemData['discount_minor'] ?? 0
            );

            $taxes = $this->normalizeTaxes(
                $itemData['taxes'] ?? []
            );

            $taxMinor = array_sum(
                array_column(
                    $taxes,
                    'amount_minor'
                )
            );

            $grossMinor = (int) round(
                $unitPriceMinor
                * $quantity
            );

            if ($discountMinor > $grossMinor) {
                throw new RuntimeException(
                    'Proposal item discount cannot exceed gross amount.'
                );
            }

            $totalMinor =
                $grossMinor
                - $discountMinor
                + $taxMinor;

            $proposal->items()->create([
                'catalog_item_id' => $catalogItem?->id,
                'position' => $index + 1,
                'item_type' => $catalogItem?->type->value
                    ?? trim(
                        (string) (
                            $itemData['item_type']
                            ?? 'service'
                        )
                    ),
                'name' => $catalogItem?->name
                    ?? trim(
                        (string) (
                            $itemData['name']
                            ?? ''
                        )
                    ),
                'code' => $catalogItem?->code
                    ?? $this->normalizeNullableText(
                        $itemData['code'] ?? null
                    ),
                'quantity' => $quantity,
                'unit_price_minor' => $unitPriceMinor,
                'discount_minor' => $discountMinor,
                'tax_minor' => $taxMinor,
                'total_minor' => $totalMinor,
                'taxes' => $taxes === []
                    ? null
                    : $taxes,
            ]);
        }
    }

    private function recalculate(
        Proposal $proposal
    ): Proposal {
        $items = $proposal->items()
            ->get();

        $subtotal = $items->sum(
            fn ($item) =>
                (int) round(
                    $item->unit_price_minor
                    * (float) $item->quantity
                )
        );

        $discount = $items->sum(
            'discount_minor'
        );

        $tax = $items->sum(
            'tax_minor'
        );

        $total =
            $subtotal
            - $discount
            + $tax;

        $proposal->update([
            'subtotal_minor' => $subtotal,
            'discount_minor' => $discount,
            'tax_minor' => $tax,
            'total_minor' => $total,
        ]);

        return $proposal
            ->fresh([
                'items',
                'customer',
                'opportunity',
            ]);
    }

    private function resolveProposal(
        Proposal $proposal
    ): Proposal {
        $resolved = Proposal::query()
            ->find(
                $proposal->getKey()
            );

        if ($resolved === null) {
            throw (
                new ModelNotFoundException()
            )->setModel(
                Proposal::class,
                [$proposal->getKey()]
            );
        }

        return $resolved;
    }

    private function resolveCatalogItem(
        mixed $id
    ): ?CatalogItem {
        if ($id === null || $id === '') {
            return null;
        }

        return CatalogItem::query()
            ->findOrFail(
                (int) $id
            );
    }

    private function resolveCustomer(
        mixed $id
    ): ?Customer {
        if ($id === null || $id === '') {
            return null;
        }

        return Customer::query()
            ->findOrFail(
                (int) $id
            );
    }

    private function resolveOpportunity(
        mixed $id
    ): ?Opportunity {
        if ($id === null || $id === '') {
            return null;
        }

        return Opportunity::query()
            ->findOrFail(
                (int) $id
            );
    }

    private function normalizeNumber(
        mixed $value
    ): string {
        $number = trim(
            (string) $value
        );

        if ($number === '') {
            throw new RuntimeException(
                'Proposal number is required.'
            );
        }

        return $number;
    }

    private function normalizeStatus(
        mixed $value
    ): ProposalStatus {
        if ($value instanceof ProposalStatus) {
            return $value;
        }

        $status = ProposalStatus::tryFrom(
            strtolower(
                trim(
                    (string) $value
                )
            )
        );

        if ($status === null) {
            throw new RuntimeException(
                'Proposal status is invalid.'
            );
        }

        return $status;
    }

    private function normalizeCurrency(
        mixed $value
    ): string {
        $currency = trim(
            (string) ($value ?? '')
        );

        if ($currency === '') {
            $currency = app(
                TenantContext::class
            )->get()->currency;
        }

        return Currency::normalize(
            $currency
        );
    }

    private function normalizeQuantity(
        mixed $value
    ): float {
        if (! is_numeric($value)) {
            throw new RuntimeException(
                'Proposal item quantity is invalid.'
            );
        }

        $quantity = (float) $value;

        if ($quantity <= 0) {
            throw new RuntimeException(
                'Proposal item quantity must be positive.'
            );
        }

        return round(
            $quantity,
            4
        );
    }

    private function normalizeMoney(
        mixed $value
    ): int {
        if (
            ! is_int($value)
            && ! (
                is_string($value)
                && preg_match(
                    '/^\d+$/',
                    trim($value)
                ) === 1
            )
        ) {
            throw new RuntimeException(
                'Proposal monetary value is invalid.'
            );
        }

        $amount = (int) $value;

        if ($amount < 0) {
            throw new RuntimeException(
                'Proposal monetary value cannot be negative.'
            );
        }

        return $amount;
    }

    private function normalizeTaxes(
        mixed $value
    ): array {
        if ($value === null) {
            return [];
        }

        if (! is_array($value)) {
            throw new RuntimeException(
                'Proposal item taxes are invalid.'
            );
        }

        $normalized = [];

        foreach ($value as $tax) {
            if (! is_array($tax)) {
                throw new RuntimeException(
                    'Proposal item tax is invalid.'
                );
            }

            $code = trim(
                (string) ($tax['code'] ?? '')
            );

            if ($code === '') {
                throw new RuntimeException(
                    'Proposal item tax code is required.'
                );
            }

            $normalized[] = [
                'code' => $code,
                'amount_minor' => $this->normalizeMoney(
                    $tax['amount_minor'] ?? null
                ),
            ];
        }

        return $normalized;
    }

    private function normalizeNullableText(
        mixed $value
    ): ?string {
        if ($value === null) {
            return null;
        }

        $value = trim(
            (string) $value
        );

        return $value === ''
            ? null
            : $value;
    }
}
