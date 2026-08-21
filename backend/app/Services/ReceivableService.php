<?php

namespace App\Services;

use App\Enums\ReceivableStatus;
use App\Models\Customer;
use App\Models\Receivable;
use App\Models\Sale;
use App\Support\Currency;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ReceivableService
{
    public function create(
        array $data
    ): Receivable {
        return DB::transaction(
            function () use ($data): Receivable {
                $customer = $this->resolveCustomer(
                    $data['customer_id'] ?? null
                );

                $sale = $this->resolveSale(
                    $data['sale_id'] ?? null
                );

                if (
                    $sale !== null &&
                    $sale->customer_id !== $customer->id
                ) {
                    throw new RuntimeException(
                        'Receivable sale must belong to customer.'
                    );
                }

                $receivable = Receivable::query()->create([
                    'customer_id' => $customer->id,
                    'sale_id' => $sale?->id,
                    'title' => $this->normalizeTitle(
                        $data['title'] ?? null
                    ),
                    'currency' => $this->normalizeCurrency(
                        $data['currency'] ?? null
                    ),
                    'amount_minor' => $this->normalizeAmount(
                        $data['amount_minor'] ?? null
                    ),
                    'due_date' => $this->normalizeDueDate(
                        $data['due_date'] ?? null
                    ),
                    'status' => ReceivableStatus::PENDING,
                ]);

                app(AuditService::class)->log(
                    'receivable.created',
                    'Conta a receber criada: '
                        . $receivable->title
                        . '. Cliente: '
                        . $customer->name
                        . '. Valor: '
                        . $receivable->currency
                        . ' '
                        . $receivable->amount_minor
                        . ' minor units.'
                );

                return $receivable;
            }
        );
    }

    public function update(
        Receivable $receivable,
        array $data
    ): Receivable {
        $receivable = $this->resolveReceivable(
            $receivable
        );

        if (
            $receivable->status !==
            ReceivableStatus::PENDING
        ) {
            throw new RuntimeException(
                'Only pending receivables can be updated.'
            );
        }

        return DB::transaction(
            function () use (
                $receivable,
                $data
            ): Receivable {
                $payload = [];

                if (
                    array_key_exists(
                        'customer_id',
                        $data
                    )
                ) {
                    $customer = $this->resolveCustomer(
                        $data['customer_id']
                    );

                    $payload['customer_id'] =
                        $customer->id;
                }

                if (
                    array_key_exists(
                        'sale_id',
                        $data
                    )
                ) {
                    $sale = $this->resolveSale(
                        $data['sale_id']
                    );

                    $payload['sale_id'] =
                        $sale?->id;
                }

                if (
                    array_key_exists(
                        'title',
                        $data
                    )
                ) {
                    $payload['title'] =
                        $this->normalizeTitle(
                            $data['title']
                        );
                }

                if (
                    array_key_exists(
                        'currency',
                        $data
                    )
                ) {
                    $payload['currency'] =
                        $this->normalizeCurrency(
                            $data['currency']
                        );
                }

                if (
                    array_key_exists(
                        'amount_minor',
                        $data
                    )
                ) {
                    $payload['amount_minor'] =
                        $this->normalizeAmount(
                            $data['amount_minor']
                        );
                }

                if (
                    array_key_exists(
                        'due_date',
                        $data
                    )
                ) {
                    $payload['due_date'] =
                        $this->normalizeDueDate(
                            $data['due_date']
                        );
                }

                $customerId =
                    $payload['customer_id']
                    ?? $receivable->customer_id;

                $saleId =
                    array_key_exists(
                        'sale_id',
                        $payload
                    )
                        ? $payload['sale_id']
                        : $receivable->sale_id;

                if ($saleId !== null) {
                    $sale = $this->resolveSale(
                        $saleId
                    );

                    if (
                        $sale->customer_id !==
                        $customerId
                    ) {
                        throw new RuntimeException(
                            'Receivable sale must belong to customer.'
                        );
                    }
                }

                $receivable->fill(
                    $payload
                );

                $receivable->save();

                app(AuditService::class)->log(
                    'receivable.updated',
                    'Conta a receber atualizada: '
                        . $receivable->title
                        . '.'
                );

                return $receivable->refresh();
            }
        );
    }

    public function markPaid(
        Receivable $receivable,
        ?string $paymentReference = null
    ): Receivable {
        $receivable = $this->resolveReceivable(
            $receivable
        );

        if (
            $receivable->status !==
            ReceivableStatus::PENDING
        ) {
            throw new RuntimeException(
                'Only pending receivables can be paid.'
            );
        }

        return DB::transaction(
            function () use (
                $receivable,
                $paymentReference
            ): Receivable {
                $receivable->status =
                    ReceivableStatus::PAID;

                $receivable->paid_at = now();

                $receivable->payment_reference =
                    $paymentReference;

                $receivable->save();

                app(AuditService::class)->log(
                    'receivable.paid',
                    'Conta a receber paga: '
                        . $receivable->title
                        . '. Valor: '
                        . $receivable->currency
                        . ' '
                        . $receivable->amount_minor
                        . ' minor units.'
                        . (
                            $receivable->payment_reference
                                ? ' Referencia: '
                                    . $receivable
                                        ->payment_reference
                                    . '.'
                                : ''
                        )
                );

                return $receivable->refresh();
            }
        );
    }

    public function cancel(
        Receivable $receivable
    ): Receivable {
        $receivable = $this->resolveReceivable(
            $receivable
        );

        if (
            $receivable->status !==
            ReceivableStatus::PENDING
        ) {
            throw new RuntimeException(
                'Only pending receivables can be cancelled.'
            );
        }

        return DB::transaction(
            function () use (
                $receivable
            ): Receivable {
                $receivable->status =
                    ReceivableStatus::CANCELLED;

                $receivable->paid_at = null;

                $receivable->payment_reference = null;

                $receivable->save();

                app(AuditService::class)->log(
                    'receivable.cancelled',
                    'Conta a receber cancelada: '
                        . $receivable->title
                        . '.'
                );

                return $receivable->refresh();
            }
        );
    }

    private function resolveReceivable(
        Receivable $receivable
    ): Receivable {
        $resolved = Receivable::query()->find(
            $receivable->id
        );

        if ($resolved === null) {
            throw new RuntimeException(
                'Receivable does not belong to current tenant.'
            );
        }

        return $resolved;
    }

    private function resolveCustomer(
        mixed $customerId
    ): Customer {
        if (
            $customerId === null ||
            $customerId === ''
        ) {
            throw new RuntimeException(
                'Receivable customer is required.'
            );
        }

        $customer = Customer::query()->find(
            (int) $customerId
        );

        if ($customer === null) {
            throw new RuntimeException(
                'Receivable customer is invalid.'
            );
        }

        return $customer;
    }

    private function resolveSale(
        mixed $saleId
    ): ?Sale {
        if (
            $saleId === null ||
            $saleId === ''
        ) {
            return null;
        }

        $sale = Sale::query()->find(
            (int) $saleId
        );

        if ($sale === null) {
            throw new RuntimeException(
                'Receivable sale is invalid.'
            );
        }

        return $sale;
    }

    private function normalizeTitle(
        mixed $title
    ): string {
        $title = trim(
            (string) $title
        );

        if ($title === '') {
            throw new RuntimeException(
                'Receivable title is required.'
            );
        }

        return $title;
    }

    private function normalizeCurrency(
        mixed $currency
    ): string {
        $currency = trim(
            (string) $currency
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

    private function normalizeAmount(
        mixed $amount
    ): int {
        if (
            filter_var(
                $amount,
                FILTER_VALIDATE_INT
            ) === false
        ) {
            throw new RuntimeException(
                'Receivable amount is invalid.'
            );
        }

        $amount = (int) $amount;

        if ($amount < 0) {
            throw new RuntimeException(
                'Receivable amount cannot be negative.'
            );
        }

        return $amount;
    }

    private function normalizeDueDate(
        mixed $dueDate
    ): string {
        $dueDate = trim(
            (string) $dueDate
        );

        if (
            ! preg_match(
                '/^\d{4}-\d{2}-\d{2}$/',
                $dueDate
            )
        ) {
            throw new RuntimeException(
                'Receivable due date is invalid.'
            );
        }

        $parts = array_map(
            'intval',
            explode(
                '-',
                $dueDate
            )
        );

        if (
            ! checkdate(
                $parts[1],
                $parts[2],
                $parts[0]
            )
        ) {
            throw new RuntimeException(
                'Receivable due date is invalid.'
            );
        }

        return $dueDate;
    }
}