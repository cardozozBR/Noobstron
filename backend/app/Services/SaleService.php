<?php

namespace App\Services;

use App\Enums\ProposalStatus;
use App\Models\Opportunity;
use App\Models\Proposal;
use App\Models\Sale;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class SaleService
{
    public function __construct(
        private readonly TenantContext $tenantContext
    ) {
    }

    public function close(
        Opportunity $opportunity,
        array $data = []
    ): Sale {
        return DB::transaction(function () use (
            $opportunity,
            $data
        ): Sale {
            $tenant = $this->tenantContext->get();

            /*
             * Recarrega pelo escopo multi-tenant.
             * Um model recebido de outro tenant não pode ser utilizado
             * apenas por ter sido previamente carregado.
             */
            $opportunity = Opportunity::query()
                ->with('customer')
                ->findOrFail(
                    $opportunity->id
                );

            if (
                (int) $opportunity->tenant_id
                !== (int) $tenant->id
            ) {
                throw new ModelNotFoundException();
            }

            if (! $opportunity->customer_id) {
                throw new RuntimeException(
                    'A oportunidade precisa possuir cliente para ser fechada.'
                );
            }

            if (
                Sale::query()
                    ->where(
                        'opportunity_id',
                        $opportunity->id
                    )
                    ->exists()
            ) {
                throw new RuntimeException(
                    'A oportunidade já possui venda registrada.'
                );
            }

            $proposal = $this->resolveProposal(
                $opportunity,
                $data['proposal_id'] ?? null
            );

            [
                $totalMinor,
                $currency,
            ] = $this->resolveValue(
                $opportunity,
                $proposal,
                $data
            );

            $sale = Sale::query()->create([
                'tenant_id' => $tenant->id,
                'customer_id' => $opportunity->customer_id,
                'opportunity_id' => $opportunity->id,
                'proposal_id' => $proposal?->id,

                'number' => $this->resolveNumber(
                    $data['number'] ?? null
                ),

                'currency' => $currency,
                'total_minor' => $totalMinor,

                'closed_at' => $data['closed_at']
                    ?? now(),

                'customer_name' =>
                    $opportunity->customer->name,

                'opportunity_title' =>
                    $opportunity->name,

                'proposal_number' =>
                    $proposal?->number,
            ]);

            app(AuditService::class)->log(
                'sale.closed',
                'Venda fechada: '
                    . $sale->number
                    . '. Oportunidade: '
                    . $sale->opportunity_title
                    . '. Valor: '
                    . $sale->total_minor
                    . ' '
                    . $sale->currency
                    . '.'
            );

            return $sale;
        });
    }

    private function resolveProposal(
        Opportunity $opportunity,
        mixed $proposalId
    ): ?Proposal {
        if (
            $proposalId === null
            || $proposalId === ''
        ) {
            return null;
        }

        $proposal = Proposal::query()
            ->findOrFail(
                (int) $proposalId
            );

        if (
            (int) $proposal->opportunity_id
            !== (int) $opportunity->id
        ) {
            throw new RuntimeException(
                'A proposta não pertence à oportunidade informada.'
            );
        }

        if (
            (int) $proposal->customer_id
            !== (int) $opportunity->customer_id
        ) {
            throw new RuntimeException(
                'A proposta não pertence ao cliente da oportunidade.'
            );
        }

        if (
            $proposal->status
            !== ProposalStatus::ACCEPTED
        ) {
            throw new RuntimeException(
                'Somente propostas aceitas podem originar uma venda.'
            );
        }

        return $proposal;
    }

    private function resolveValue(
        Opportunity $opportunity,
        ?Proposal $proposal,
        array $data
    ): array {
        /*
         * Quando há proposta aceita, ela é a fonte de verdade
         * para valor e moeda.
         */
        if ($proposal) {
            return [
                (int) $proposal->total_minor,
                strtoupper(
                    $proposal->currency
                ),
            ];
        }

        $totalMinor = array_key_exists(
            'total_minor',
            $data
        )
            ? $data['total_minor']
            : $opportunity->value_minor;

        if (
            ! is_int($totalMinor)
            && ! (
                is_string($totalMinor)
                && ctype_digit($totalMinor)
            )
        ) {
            throw new RuntimeException(
                'O valor final da venda deve ser informado em minor units.'
            );
        }

        $totalMinor = (int) $totalMinor;

        if ($totalMinor < 0) {
            throw new RuntimeException(
                'O valor final da venda não pode ser negativo.'
            );
        }

        $currency = strtoupper(
            trim(
                (string) (
                    $data['currency']
                    ?? $opportunity->currency
                )
            )
        );

        if (
            ! preg_match(
                '/^[A-Z]{3}$/',
                $currency
            )
        ) {
            throw new RuntimeException(
                'A moeda da venda é inválida.'
            );
        }

        return [
            $totalMinor,
            $currency,
        ];
    }

    private function resolveNumber(
        mixed $number
    ): string {
        if ($number !== null) {
            $number = trim(
                (string) $number
            );

            if ($number !== '') {
                return mb_strtoupper(
                    $number
                );
            }
        }

        return 'SALE-'
            . now()->format('Ymd')
            . '-'
            . Str::upper(
                substr(
                    (string) Str::ulid(),
                    -10
                )
            );
    }
}
