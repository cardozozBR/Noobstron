<?php

namespace App\Services;

use App\Enums\CatalogItemType;
use App\Models\CatalogItem;
use App\Support\Currency;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class CatalogService
{
    public function create(array $data): CatalogItem
    {
        return DB::transaction(function () use ($data): CatalogItem {
            $code = $this->normalizeCode(
                $data['code'] ?? null
            );

            $this->assertCodeAvailable($code);

            return CatalogItem::create([
                'type' => $this->normalizeType(
                    $data['type'] ?? null
                ),
                'name' => $this->normalizeName(
                    $data['name'] ?? null
                ),
                'code' => $code,
                'price_minor' => $this->normalizePrice(
                    $data['price_minor'] ?? 0
                ),
                'currency' => $this->normalizeCurrency(
                    $data['currency'] ?? null
                ),
                'is_active' => $this->normalizeActive(
                    $data['is_active'] ?? true
                ),
            ]);
        });
    }

    public function update(
        CatalogItem $item,
        array $data
    ): CatalogItem {
        return DB::transaction(function () use ($item, $data): CatalogItem {
            $item = $this->resolveItem($item);

            $payload = [];

            if (array_key_exists('type', $data)) {
                $payload['type'] = $this->normalizeType(
                    $data['type']
                );
            }

            if (array_key_exists('name', $data)) {
                $payload['name'] = $this->normalizeName(
                    $data['name']
                );
            }

            if (array_key_exists('code', $data)) {
                $code = $this->normalizeCode(
                    $data['code']
                );

                $this->assertCodeAvailable(
                    $code,
                    $item
                );

                $payload['code'] = $code;
            }

            if (array_key_exists('price_minor', $data)) {
                $payload['price_minor'] =
                    $this->normalizePrice(
                        $data['price_minor']
                    );
            }

            if (array_key_exists('currency', $data)) {
                $payload['currency'] =
                    $this->normalizeCurrency(
                        $data['currency']
                    );
            }

            if (array_key_exists('is_active', $data)) {
                $payload['is_active'] =
                    $this->normalizeActive(
                        $data['is_active']
                    );
            }

            $item->update($payload);

            return $item->fresh();
        });
    }

    public function activate(
        CatalogItem $item
    ): CatalogItem {
        return $this->update(
            $item,
            ['is_active' => true]
        );
    }

    public function deactivate(
        CatalogItem $item
    ): CatalogItem {
        return $this->update(
            $item,
            ['is_active' => false]
        );
    }

    private function resolveItem(
        CatalogItem $item
    ): CatalogItem {
        $resolved = CatalogItem::query()
            ->find($item->getKey());

        if ($resolved === null) {
            throw (
                new ModelNotFoundException()
            )->setModel(
                CatalogItem::class,
                [$item->getKey()]
            );
        }

        return $resolved;
    }

    private function normalizeType(
        mixed $value
    ): CatalogItemType {
        if ($value instanceof CatalogItemType) {
            return $value;
        }

        $type = CatalogItemType::tryFrom(
            strtolower(
                trim(
                    (string) $value
                )
            )
        );

        if ($type === null) {
            throw new RuntimeException(
                'Catalog item type is invalid.'
            );
        }

        return $type;
    }

    private function normalizeName(
        mixed $value
    ): string {
        $name = trim(
            (string) $value
        );

        if ($name === '') {
            throw new RuntimeException(
                'Catalog item name is required.'
            );
        }

        return $name;
    }

    private function normalizeCode(
        mixed $value
    ): ?string {
        if ($value === null) {
            return null;
        }

        $code = trim(
            (string) $value
        );

        return $code === ''
            ? null
            : $code;
    }

    private function normalizePrice(
        mixed $value
    ): int {
        if (
            ! is_int($value)
            && ! (
                is_string($value)
                && preg_match(
                    '/^-?\d+$/',
                    trim($value)
                ) === 1
            )
        ) {
            throw new RuntimeException(
                'Catalog item price is invalid.'
            );
        }

        $price = (int) $value;

        if ($price < 0) {
            throw new RuntimeException(
                'Catalog item price cannot be negative.'
            );
        }

        return $price;
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

    private function normalizeActive(
        mixed $value
    ): bool {
        if (is_bool($value)) {
            return $value;
        }

        if ($value === 1 || $value === '1') {
            return true;
        }

        if ($value === 0 || $value === '0') {
            return false;
        }

        throw new RuntimeException(
            'Catalog item active state is invalid.'
        );
    }

    private function assertCodeAvailable(
        ?string $code,
        ?CatalogItem $ignore = null
    ): void {
        if ($code === null) {
            return;
        }

        $query = CatalogItem::query()
            ->where('code', $code);

        if ($ignore !== null) {
            $query->where(
                'id',
                '!=',
                $ignore->getKey()
            );
        }

        if ($query->exists()) {
            throw new RuntimeException(
                'Catalog item code already exists in current tenant.'
            );
        }
    }
}
