<?php

namespace App\Services;

use App\Enums\CustomerType;
use App\Models\Customer;
use App\Models\CustomerEmail;
use App\Models\CustomerPhone;
use App\Models\Lead;
use App\Support\PhoneNumber;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class LeadConversionService
{
    public function __construct(
        private readonly AuditService $audits,
        private readonly CustomerHistoryService $history
    ) {
    }

    public function convert(
        Lead $lead,
        CustomerType $customerType = CustomerType::INDIVIDUAL
    ): Customer {
        if ($lead->isConverted()) {
            throw ValidationException::withMessages([
                'lead' =>
                    'Este lead já foi convertido em cliente.',
            ]);
        }

        return DB::transaction(
            function () use (
                $lead,
                $customerType
            ): Customer {
                $lead = Lead::query()
                    ->lockForUpdate()
                    ->findOrFail(
                        $lead->id
                    );

                if ($lead->isConverted()) {
                    throw ValidationException::withMessages([
                        'lead' =>
                            'Este lead já foi convertido em cliente.',
                    ]);
                }

                $customer = Customer::create([
                    'type' => $customerType,
                    'name' => $lead->name,
                    'responsible_user_id' =>
                        $lead->responsible_user_id,
                    'tags' => $lead->tags,
                    'notes' => $lead->notes,
                ]);

                $this->copyEmail(
                    $lead,
                    $customer
                );

                $this->copyPhone(
                    $lead,
                    $customer
                );

                $lead->converted_customer_id =
                    $customer->id;

                $lead->converted_at = now();

                $lead->save();

                $this->history->record(
                    $customer,
                    'customer.converted_from_lead',
                    'Cliente convertido a partir do lead '
                        . $lead->name
                        . '.',
                    $lead,
                    [
                        'lead_id' => $lead->id,
                    ]
                );

                $this->audits->log(
                    'lead.converted',
                    'Lead convertido em cliente: '
                        . $lead->name
                        . '.'
                );

                return $customer;
            }
        );
    }

    private function copyEmail(
        Lead $lead,
        Customer $customer
    ): void {
        $email = trim(
            (string) $lead->email
        );

        if ($email === '') {
            return;
        }

        CustomerEmail::create([
            'customer_id' => $customer->id,
            'label' => 'Lead',
            'email' => mb_strtolower($email),
            'is_primary' => true,
        ]);
    }

    private function copyPhone(
        Lead $lead,
        Customer $customer
    ): void {
        $value = trim(
            (string) $lead->phone
        );

        if ($value === '') {
            return;
        }

        $digits = preg_replace(
            '/\D+/',
            '',
            $value
        );

        if ($digits === '') {
            return;
        }

        $tenant = app(TenantContext::class)->get();

        try {
            $phone = PhoneNumber::fromNational(
                $tenant->country_code,
                $digits
            );
        } catch (InvalidArgumentException) {
            return;
        }

        CustomerPhone::create([
            'customer_id' => $customer->id,
            'label' => 'Lead',
            'country_code' =>
                $phone->country()->code(),
            'national_number' =>
                $phone->nationalNumber(),
            'is_primary' => true,
        ]);
    }
}
