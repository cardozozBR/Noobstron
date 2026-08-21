<?php

namespace App\Services;

use App\Enums\ConversationChannel;
use App\Enums\ConversationStatus;
use App\Models\Conversation;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

class ConversationInboxService
{
    public function paginate(
        array $filters = [],
        int $perPage = 20
    ): LengthAwarePaginator {
        if ($perPage < 1 || $perPage > 100) {
            throw ValidationException::withMessages([
                'per_page' =>
                    'Per page must be between 1 and 100.',
            ]);
        }

        $query = Conversation::query()
            ->with([
                'responsible:id,name,email',
                'lead:id,name',
                'customer:id,name',
            ]);

        $this->applySearch(
            $query,
            $filters
        );

        $this->applyChannel(
            $query,
            $filters
        );

        $this->applyStatus(
            $query,
            $filters
        );

        $this->applyResponsible(
            $query,
            $filters
        );

        $this->applyLead(
            $query,
            $filters
        );

        $this->applyCustomer(
            $query,
            $filters
        );

        return $query
            ->orderByRaw(
                'last_message_at DESC NULLS LAST'
            )
            ->orderByDesc(
                'id'
            )
            ->paginate(
                $perPage
            )
            ->withQueryString();
    }

    private function applySearch(
        Builder $query,
        array $filters
    ): void {
        $search = trim(
            (string) (
                $filters['search'] ?? ''
            )
        );

        if ($search === '') {
            return;
        }

        $query->where(
            function (
                Builder $builder
            ) use (
                $search
            ): void {
                $builder
                    ->where(
                        'display_name',
                        'like',
                        '%' . $search . '%'
                    )
                    ->orWhere(
                        'external_address',
                        'like',
                        '%' . $search . '%'
                    )
                    ->orWhereHas(
                        'emailMessages',
                        function (
                            Builder $messageQuery
                        ) use (
                            $search
                        ): void {
                            $messageQuery
                                ->where(
                                    'subject',
                                    'like',
                                    '%' . $search . '%'
                                )
                                ->orWhere(
                                    'body',
                                    'like',
                                    '%' . $search . '%'
                                );
                        }
                    )
                    ->orWhereHas(
                        'whatsappMessages',
                        function (
                            Builder $messageQuery
                        ) use (
                            $search
                        ): void {
                            $messageQuery->where(
                                'body',
                                'like',
                                '%' . $search . '%'
                            );
                        }
                    );
            }
        );
    }

    private function applyChannel(
        Builder $query,
        array $filters
    ): void {
        $value = $filters['channel']
            ?? null;

        if ($value === null || $value === '') {
            return;
        }

        $channel = $value instanceof ConversationChannel
            ? $value
            : ConversationChannel::tryFrom(
                (string) $value
            );

        if ($channel === null) {
            throw ValidationException::withMessages([
                'channel' =>
                    'Invalid conversation channel.',
            ]);
        }

        $query->where(
            'channel',
            $channel->value
        );
    }

    private function applyStatus(
        Builder $query,
        array $filters
    ): void {
        $value = $filters['status']
            ?? null;

        if ($value === null || $value === '') {
            return;
        }

        $status = $value instanceof ConversationStatus
            ? $value
            : ConversationStatus::tryFrom(
                (string) $value
            );

        if ($status === null) {
            throw ValidationException::withMessages([
                'status' =>
                    'Invalid conversation status.',
            ]);
        }

        $query->where(
            'status',
            $status->value
        );
    }

    private function applyResponsible(
        Builder $query,
        array $filters
    ): void {
        if (
            ! array_key_exists(
                'responsible_user_id',
                $filters
            )
        ) {
            return;
        }

        $value =
            $filters['responsible_user_id'];

        if (
            $value === null ||
            $value === ''
        ) {
            return;
        }

        if ($value === 'unassigned') {
            $query->whereNull(
                'responsible_user_id'
            );

            return;
        }

        if (
            filter_var(
                $value,
                FILTER_VALIDATE_INT
            ) === false
        ) {
            throw ValidationException::withMessages([
                'responsible_user_id' =>
                    'Invalid responsible user.',
            ]);
        }

        $query->where(
            'responsible_user_id',
            (int) $value
        );
    }

    private function applyLead(
        Builder $query,
        array $filters
    ): void {
        $value = $filters['lead_id']
            ?? null;

        if ($value === null || $value === '') {
            return;
        }

        if (
            filter_var(
                $value,
                FILTER_VALIDATE_INT
            ) === false
        ) {
            throw ValidationException::withMessages([
                'lead_id' =>
                    'Invalid lead.',
            ]);
        }

        $query->where(
            'lead_id',
            (int) $value
        );
    }

    private function applyCustomer(
        Builder $query,
        array $filters
    ): void {
        $value = $filters['customer_id']
            ?? null;

        if ($value === null || $value === '') {
            return;
        }

        if (
            filter_var(
                $value,
                FILTER_VALIDATE_INT
            ) === false
        ) {
            throw ValidationException::withMessages([
                'customer_id' =>
                    'Invalid customer.',
            ]);
        }

        $query->where(
            'customer_id',
            (int) $value
        );
    }
}