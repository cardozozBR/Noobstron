<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\CustomerHistory;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class CustomerHistoryService
{
    public function record(
        Customer $customer,
        string $event,
        ?string $description = null,
        ?Model $subject = null,
        ?array $metadata = null,
        ?User $user = null
    ): CustomerHistory {
        $user ??= auth()->user();

        return CustomerHistory::create([
            'customer_id' => $customer->id,
            'user_id' => $user?->id,
            'event' => $event,
            'description' => $description,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'metadata' => $metadata,
        ]);
    }
}
