<?php

namespace App\Services;

use App\Enums\ActivityStatus;
use App\Enums\ActivityType;
use App\Models\Activity;
use App\Models\Customer;
use App\Models\Opportunity;
use App\Models\User;
use RuntimeException;

class ActivityService
{
    public function create(array $data): Activity
    {
        $activity = new Activity();

        $this->fill(
            $activity,
            $data,
            false
        );

        $activity->save();

        return $activity->fresh();
    }

    public function update(
        Activity $activity,
        array $data
    ): Activity {
        $this->assertCurrentTenant(
            $activity
        );

        $this->fill(
            $activity,
            $data,
            true
        );

        $activity->save();

        return $activity->fresh();
    }

    public function complete(
        Activity $activity
    ): Activity {
        return $this->update(
            $activity,
            [
                'status' =>
                    ActivityStatus::COMPLETED,
            ]
        );
    }

    public function reopen(
        Activity $activity
    ): Activity {
        return $this->update(
            $activity,
            [
                'status' =>
                    ActivityStatus::PENDING,
            ]
        );
    }

    public function cancel(
        Activity $activity
    ): Activity {
        return $this->update(
            $activity,
            [
                'status' =>
                    ActivityStatus::CANCELLED,
            ]
        );
    }

    private function fill(
        Activity $activity,
        array $data,
        bool $partial
    ): void {
        if (
            ! $partial
            || array_key_exists(
                'type',
                $data
            )
        ) {
            $activity->type =
                $this->type(
                    $data['type']
                        ?? ActivityType::TASK
                );
        }

        if (
            ! $partial
            || array_key_exists(
                'status',
                $data
            )
        ) {
            $activity->status =
                $this->status(
                    $data['status']
                        ?? ActivityStatus::PENDING
                );
        }

        if (
            ! $partial
            || array_key_exists(
                'title',
                $data
            )
        ) {
            $activity->title = trim(
                (string) (
                    $data['title']
                    ?? ''
                )
            );
        }

        if (
            ! $partial
            || array_key_exists(
                'description',
                $data
            )
        ) {
            $activity->description =
                $this->nullableText(
                    $data['description']
                    ?? null
                );
        }

        if (
            ! $partial
            || array_key_exists(
                'customer_id',
                $data
            )
        ) {
            $activity->customer_id =
                $this->customerId(
                    $data['customer_id']
                    ?? null
                );
        }

        if (
            ! $partial
            || array_key_exists(
                'opportunity_id',
                $data
            )
        ) {
            $activity->opportunity_id =
                $this->opportunityId(
                    $data['opportunity_id']
                    ?? null
                );
        }

        if (
            ! $partial
            || array_key_exists(
                'responsible_user_id',
                $data
            )
        ) {
            $activity->responsible_user_id =
                $this->responsibleId(
                    $data['responsible_user_id']
                    ?? null
                );
        }

        if (
            ! $partial
            || array_key_exists(
                'due_at',
                $data
            )
        ) {
            $activity->due_at =
                $data['due_at']
                ?? null;
        }
    }

    private function type(
        ActivityType|string $type
    ): ActivityType {
        if ($type instanceof ActivityType) {
            return $type;
        }

        return ActivityType::tryFrom(
            trim($type)
        ) ?? throw new RuntimeException(
            'Invalid activity type.'
        );
    }

    private function status(
        ActivityStatus|string $status
    ): ActivityStatus {
        if (
            $status
            instanceof ActivityStatus
        ) {
            return $status;
        }

        return ActivityStatus::tryFrom(
            trim($status)
        ) ?? throw new RuntimeException(
            'Invalid activity status.'
        );
    }

    private function nullableText(
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

    private function customerId(
        mixed $id
    ): ?int {
        if (
            $id === null
            || $id === ''
        ) {
            return null;
        }

        $customer = Customer::query()
            ->find((int) $id);

        if ($customer === null) {
            throw new RuntimeException(
                'Customer does not belong to current tenant.'
            );
        }

        return $customer->id;
    }

    private function opportunityId(
        mixed $id
    ): ?int {
        if (
            $id === null
            || $id === ''
        ) {
            return null;
        }

        $opportunity = Opportunity::query()
            ->find((int) $id);

        if ($opportunity === null) {
            throw new RuntimeException(
                'Opportunity does not belong to current tenant.'
            );
        }

        return $opportunity->id;
    }

    private function responsibleId(
        mixed $id
    ): ?int {
        if (
            $id === null
            || $id === ''
        ) {
            return null;
        }

        $user = User::query()
            ->find((int) $id);

        if ($user === null) {
            throw new RuntimeException(
                'Responsible user does not belong to current tenant.'
            );
        }

        return $user->id;
    }

    private function assertCurrentTenant(
        Activity $activity
    ): void {
        if (
            Activity::query()
                ->whereKey($activity->id)
                ->doesntExist()
        ) {
            throw new RuntimeException(
                'Activity does not belong to current tenant.'
            );
        }
    }
}
