<?php

namespace App\Http\Controllers;

use App\Enums\CustomerContactType;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\CustomerContact;
use App\Models\CustomerEmail;
use App\Models\CustomerPhone;
use App\Services\AuditService;
use App\Services\CustomerHistoryService;
use App\Services\TenantContext;
use App\Support\InternationalAddress;
use App\Support\PhoneNumber;
use App\Support\TenantGlobalSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class CustomerRelationshipController extends Controller
{
    public function storeContact(
        Request $request,
        int $customerId,
        AuditService $audits,
        CustomerHistoryService $history
    ) {
        $customer = $this->customer(
            $customerId
        );

        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'role' => [
                'nullable',
                'string',
                'max:255',
            ],

            'type' => [
                'required',
                Rule::enum(
                    CustomerContactType::class
                ),
            ],

            'notes' => [
                'nullable',
                'string',
                'max:10000',
            ],
        ]);

        $contact = CustomerContact::create([
            'customer_id' => $customer->id,
            'name' => trim(
                $data['name']
            ),
            'role' => $this->nullableTrim(
                $data['role'] ?? null
            ),
            'type' => CustomerContactType::from(
                $data['type']
            ),
            'notes' => $this->nullableTrim(
                $data['notes'] ?? null
            ),
        ]);

        $history->record(
            $customer,
            'customer.contact.created',
            'Contato criado: '
                . $contact->name
                . '.',
            $contact
        );

        $audits->log(
            'customer.contact.created',
            'Contato criado para '
                . $customer->name
                . ': '
                . $contact->name
                . '.'
        );

        return $this->backToCustomer(
            $customer,
            'Contato criado com sucesso.'
        );
    }

    public function updateContact(
        Request $request,
        int $customerId,
        int $contactId,
        AuditService $audits,
        CustomerHistoryService $history
    ) {
        $customer = $this->customer(
            $customerId
        );

        $contact = CustomerContact::query()
            ->where(
                'customer_id',
                $customer->id
            )
            ->findOrFail(
                $contactId
            );

        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'role' => [
                'nullable',
                'string',
                'max:255',
            ],

            'type' => [
                'required',
                Rule::enum(
                    CustomerContactType::class
                ),
            ],

            'notes' => [
                'nullable',
                'string',
                'max:10000',
            ],
        ]);

        $contact->fill([
            'name' => trim(
                $data['name']
            ),
            'role' => $this->nullableTrim(
                $data['role'] ?? null
            ),
            'type' => CustomerContactType::from(
                $data['type']
            ),
            'notes' => $this->nullableTrim(
                $data['notes'] ?? null
            ),
        ]);

        $contact->save();

        $history->record(
            $customer,
            'customer.contact.updated',
            'Contato atualizado: '
                . $contact->name
                . '.',
            $contact
        );

        $audits->log(
            'customer.contact.updated',
            'Contato atualizado para '
                . $customer->name
                . ': '
                . $contact->name
                . '.'
        );

        return $this->backToCustomer(
            $customer,
            'Contato atualizado com sucesso.'
        );
    }

    public function destroyContact(
        int $customerId,
        int $contactId,
        AuditService $audits,
        CustomerHistoryService $history
    ) {
        $customer = $this->customer(
            $customerId
        );

        $contact = CustomerContact::query()
            ->where(
                'customer_id',
                $customer->id
            )
            ->findOrFail(
                $contactId
            );

        $name = $contact->name;

        $history->record(
            $customer,
            'customer.contact.deleted',
            'Contato excluído: '
                . $name
                . '.',
            $contact,
            [
                'contact_name' => $name,
            ]
        );

        $contact->delete();

        $audits->log(
            'customer.contact.deleted',
            'Contato excluído de '
                . $customer->name
                . ': '
                . $name
                . '.'
        );

        return $this->backToCustomer(
            $customer,
            'Contato excluído com sucesso.'
        );
    }

    public function storePhone(
        Request $request,
        int $customerId,
        AuditService $audits,
        CustomerHistoryService $history
    ) {
        $customer = $this->customer(
            $customerId
        );

        $data = $this->validatePhone(
            $request,
            $customer
        );

        $phone = DB::transaction(
            function () use (
                $customer,
                $data
            ): CustomerPhone {
                if ($data['is_primary']) {
                    CustomerPhone::query()
                        ->where(
                            'customer_id',
                            $customer->id
                        )
                        ->update([
                            'is_primary' => false,
                        ]);
                }

                return CustomerPhone::create([
                    'customer_id' => $customer->id,
                    'customer_contact_id' =>
                        $data['customer_contact_id'],
                    'label' => $data['label'],
                    'country_code' =>
                        $data['country_code'],
                    'national_number' =>
                        $data['national_number'],
                    'is_primary' =>
                        $data['is_primary'],
                ]);
            }
        );

        $history->record(
            $customer,
            'customer.phone.created',
            'Telefone criado: '
                . $phone->country_code
                . ' '
                . $phone->national_number
                . '.',
            $phone
        );

        $audits->log(
            'customer.phone.created',
            'Telefone criado para '
                . $customer->name
                . '.'
        );

        return $this->backToCustomer(
            $customer,
            'Telefone criado com sucesso.'
        );
    }

    public function updatePhone(
        Request $request,
        int $customerId,
        int $phoneId,
        AuditService $audits,
        CustomerHistoryService $history
    ) {
        $customer = $this->customer(
            $customerId
        );

        $phone = CustomerPhone::query()
            ->where(
                'customer_id',
                $customer->id
            )
            ->findOrFail(
                $phoneId
            );

        $data = $this->validatePhone(
            $request,
            $customer
        );

        DB::transaction(
            function () use (
                $customer,
                $phone,
                $data
            ): void {
                if ($data['is_primary']) {
                    CustomerPhone::query()
                        ->where(
                            'customer_id',
                            $customer->id
                        )
                        ->whereKeyNot(
                            $phone->id
                        )
                        ->update([
                            'is_primary' => false,
                        ]);
                }

                $phone->fill([
                    'customer_contact_id' =>
                        $data['customer_contact_id'],
                    'label' => $data['label'],
                    'country_code' =>
                        $data['country_code'],
                    'national_number' =>
                        $data['national_number'],
                    'is_primary' =>
                        $data['is_primary'],
                ]);

                $phone->save();
            }
        );

        $history->record(
            $customer,
            'customer.phone.updated',
            'Telefone atualizado.',
            $phone
        );

        $audits->log(
            'customer.phone.updated',
            'Telefone atualizado para '
                . $customer->name
                . '.'
        );

        return $this->backToCustomer(
            $customer,
            'Telefone atualizado com sucesso.'
        );
    }

    public function destroyPhone(
        int $customerId,
        int $phoneId,
        AuditService $audits,
        CustomerHistoryService $history
    ) {
        $customer = $this->customer(
            $customerId
        );

        $phone = CustomerPhone::query()
            ->where(
                'customer_id',
                $customer->id
            )
            ->findOrFail(
                $phoneId
            );

        $history->record(
            $customer,
            'customer.phone.deleted',
            'Telefone excluído.',
            $phone,
            [
                'country_code' =>
                    $phone->country_code,

                'national_number' =>
                    $phone->national_number,
            ]
        );

        $phone->delete();

        $audits->log(
            'customer.phone.deleted',
            'Telefone excluído de '
                . $customer->name
                . '.'
        );

        return $this->backToCustomer(
            $customer,
            'Telefone excluído com sucesso.'
        );
    }

    public function storeEmail(
        Request $request,
        int $customerId,
        AuditService $audits,
        CustomerHistoryService $history
    ) {
        $customer = $this->customer(
            $customerId
        );

        $data = $this->validateEmail(
            $request,
            $customer
        );

        $email = DB::transaction(
            function () use (
                $customer,
                $data
            ): CustomerEmail {
                if ($data['is_primary']) {
                    CustomerEmail::query()
                        ->where(
                            'customer_id',
                            $customer->id
                        )
                        ->update([
                            'is_primary' => false,
                        ]);
                }

                return CustomerEmail::create([
                    'customer_id' =>
                        $customer->id,

                    'customer_contact_id' =>
                        $data['customer_contact_id'],

                    'label' =>
                        $data['label'],

                    'email' =>
                        $data['email'],

                    'is_primary' =>
                        $data['is_primary'],
                ]);
            }
        );

        $history->record(
            $customer,
            'customer.email.created',
            'E-mail criado: '
                . $email->email
                . '.',
            $email
        );

        $audits->log(
            'customer.email.created',
            'E-mail criado para '
                . $customer->name
                . ': '
                . $email->email
                . '.'
        );

        return $this->backToCustomer(
            $customer,
            'E-mail criado com sucesso.'
        );
    }

    public function updateEmail(
        Request $request,
        int $customerId,
        int $emailId,
        AuditService $audits,
        CustomerHistoryService $history
    ) {
        $customer = $this->customer(
            $customerId
        );

        $email = CustomerEmail::query()
            ->where(
                'customer_id',
                $customer->id
            )
            ->findOrFail(
                $emailId
            );

        $data = $this->validateEmail(
            $request,
            $customer
        );

        DB::transaction(
            function () use (
                $customer,
                $email,
                $data
            ): void {
                if ($data['is_primary']) {
                    CustomerEmail::query()
                        ->where(
                            'customer_id',
                            $customer->id
                        )
                        ->whereKeyNot(
                            $email->id
                        )
                        ->update([
                            'is_primary' => false,
                        ]);
                }

                $email->fill([
                    'customer_contact_id' =>
                        $data['customer_contact_id'],

                    'label' =>
                        $data['label'],

                    'email' =>
                        $data['email'],

                    'is_primary' =>
                        $data['is_primary'],
                ]);

                $email->save();
            }
        );

        $history->record(
            $customer,
            'customer.email.updated',
            'E-mail atualizado: '
                . $email->email
                . '.',
            $email
        );

        $audits->log(
            'customer.email.updated',
            'E-mail atualizado para '
                . $customer->name
                . '.'
        );

        return $this->backToCustomer(
            $customer,
            'E-mail atualizado com sucesso.'
        );
    }

    public function destroyEmail(
        int $customerId,
        int $emailId,
        AuditService $audits,
        CustomerHistoryService $history
    ) {
        $customer = $this->customer(
            $customerId
        );

        $email = CustomerEmail::query()
            ->where(
                'customer_id',
                $customer->id
            )
            ->findOrFail(
                $emailId
            );

        $value = $email->email;

        $history->record(
            $customer,
            'customer.email.deleted',
            'E-mail excluído: '
                . $value
                . '.',
            $email,
            [
                'email' => $value,
            ]
        );

        $email->delete();

        $audits->log(
            'customer.email.deleted',
            'E-mail excluído de '
                . $customer->name
                . ': '
                . $value
                . '.'
        );

        return $this->backToCustomer(
            $customer,
            'E-mail excluído com sucesso.'
        );
    }

    public function storeAddress(
        Request $request,
        int $customerId,
        AuditService $audits,
        CustomerHistoryService $history
    ) {
        $customer = $this->customer(
            $customerId
        );

        $data = $this->validateAddress(
            $request
        );

        $address = DB::transaction(
            function () use (
                $customer,
                $data
            ): CustomerAddress {
                if ($data['is_primary']) {
                    CustomerAddress::query()
                        ->where(
                            'customer_id',
                            $customer->id
                        )
                        ->update([
                            'is_primary' => false,
                        ]);
                }

                return CustomerAddress::create([
                    'customer_id' =>
                        $customer->id,

                    'label' =>
                        $data['label'],

                    'country_code' =>
                        $data['country_code'],

                    'line1' =>
                        $data['line1'],

                    'line2' =>
                        $data['line2'],

                    'city' =>
                        $data['city'],

                    'region' =>
                        $data['region'],

                    'postal_code' =>
                        $data['postal_code'],

                    'is_primary' =>
                        $data['is_primary'],
                ]);
            }
        );

        $history->record(
            $customer,
            'customer.address.created',
            'Endereço criado: '
                . $address->city
                . '.',
            $address
        );

        $audits->log(
            'customer.address.created',
            'Endereço criado para '
                . $customer->name
                . '.'
        );

        return $this->backToCustomer(
            $customer,
            'Endereço criado com sucesso.'
        );
    }

    public function updateAddress(
        Request $request,
        int $customerId,
        int $addressId,
        AuditService $audits,
        CustomerHistoryService $history
    ) {
        $customer = $this->customer(
            $customerId
        );

        $address = CustomerAddress::query()
            ->where(
                'customer_id',
                $customer->id
            )
            ->findOrFail(
                $addressId
            );

        $data = $this->validateAddress(
            $request
        );

        DB::transaction(
            function () use (
                $customer,
                $address,
                $data
            ): void {
                if ($data['is_primary']) {
                    CustomerAddress::query()
                        ->where(
                            'customer_id',
                            $customer->id
                        )
                        ->whereKeyNot(
                            $address->id
                        )
                        ->update([
                            'is_primary' => false,
                        ]);
                }

                $address->fill([
                    'label' =>
                        $data['label'],

                    'country_code' =>
                        $data['country_code'],

                    'line1' =>
                        $data['line1'],

                    'line2' =>
                        $data['line2'],

                    'city' =>
                        $data['city'],

                    'region' =>
                        $data['region'],

                    'postal_code' =>
                        $data['postal_code'],

                    'is_primary' =>
                        $data['is_primary'],
                ]);

                $address->save();
            }
        );

        $history->record(
            $customer,
            'customer.address.updated',
            'Endereço atualizado.',
            $address
        );

        $audits->log(
            'customer.address.updated',
            'Endereço atualizado para '
                . $customer->name
                . '.'
        );

        return $this->backToCustomer(
            $customer,
            'Endereço atualizado com sucesso.'
        );
    }

    public function destroyAddress(
        int $customerId,
        int $addressId,
        AuditService $audits,
        CustomerHistoryService $history
    ) {
        $customer = $this->customer(
            $customerId
        );

        $address = CustomerAddress::query()
            ->where(
                'customer_id',
                $customer->id
            )
            ->findOrFail(
                $addressId
            );

        $history->record(
            $customer,
            'customer.address.deleted',
            'Endereço excluído.',
            $address,
            [
                'city' =>
                    $address->city,

                'country_code' =>
                    $address->country_code,
            ]
        );

        $address->delete();

        $audits->log(
            'customer.address.deleted',
            'Endereço excluído de '
                . $customer->name
                . '.'
        );

        return $this->backToCustomer(
            $customer,
            'Endereço excluído com sucesso.'
        );
    }

    private function customer(
        int $customerId
    ): Customer {
        return Customer::findOrFail(
            $customerId
        );
    }

    private function validatePhone(
        Request $request,
        Customer $customer
    ): array {
        $tenant = app(
            TenantContext::class
        )->get();

        $data = $request->validate([
            'customer_contact_id' => [
                'nullable',
                'integer',

                Rule::exists(
                    'customer_contacts',
                    'id'
                )->where(
                    fn ($query) => $query
                        ->where(
                            'tenant_id',
                            $tenant->id
                        )
                        ->where(
                            'customer_id',
                            $customer->id
                        )
                ),
            ],

            'label' => [
                'nullable',
                'string',
                'max:100',
            ],

            'country_code' => [
                'required',
                'string',
                'size:2',

                Rule::in(
                    TenantGlobalSettings::countries()
                ),
            ],

            'national_number' => [
                'required',
                'string',
                'max:30',
            ],

            'is_primary' => [
                'nullable',
                'boolean',
            ],
        ]);

        $digits = preg_replace(
            '/\D+/',
            '',
            $data['national_number']
        );

        try {
            $phone = PhoneNumber::fromNational(
                strtoupper(
                    $data['country_code']
                ),
                $digits
            );
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages([
                'national_number' =>
                    'Telefone inválido.',
            ]);
        }

        return [
            'customer_contact_id' =>
                $data['customer_contact_id']
                ?? null,

            'label' =>
                $this->nullableTrim(
                    $data['label'] ?? null
                ),

            'country_code' =>
                $phone->country()->code(),

            'national_number' =>
                $phone->nationalNumber(),

            'is_primary' =>
                $request->boolean(
                    'is_primary'
                ),
        ];
    }

    private function validateEmail(
        Request $request,
        Customer $customer
    ): array {
        $tenant = app(
            TenantContext::class
        )->get();

        $data = $request->validate([
            'customer_contact_id' => [
                'nullable',
                'integer',

                Rule::exists(
                    'customer_contacts',
                    'id'
                )->where(
                    fn ($query) => $query
                        ->where(
                            'tenant_id',
                            $tenant->id
                        )
                        ->where(
                            'customer_id',
                            $customer->id
                        )
                ),
            ],

            'label' => [
                'nullable',
                'string',
                'max:100',
            ],

            'email' => [
                'required',
                'email',
                'max:255',
            ],

            'is_primary' => [
                'nullable',
                'boolean',
            ],
        ]);

        return [
            'customer_contact_id' =>
                $data['customer_contact_id']
                ?? null,

            'label' =>
                $this->nullableTrim(
                    $data['label'] ?? null
                ),

            'email' =>
                mb_strtolower(
                    trim(
                        $data['email']
                    )
                ),

            'is_primary' =>
                $request->boolean(
                    'is_primary'
                ),
        ];
    }

    private function validateAddress(
        Request $request
    ): array {
        $data = $request->validate([
            'label' => [
                'nullable',
                'string',
                'max:100',
            ],

            'country_code' => [
                'required',
                'string',
                'size:2',

                Rule::in(
                    TenantGlobalSettings::countries()
                ),
            ],

            'line1' => [
                'required',
                'string',
                'max:255',
            ],

            'line2' => [
                'nullable',
                'string',
                'max:255',
            ],

            'city' => [
                'required',
                'string',
                'max:255',
            ],

            'region' => [
                'nullable',
                'string',
                'max:255',
            ],

            'postal_code' => [
                'nullable',
                'string',
                'max:50',
            ],

            'is_primary' => [
                'nullable',
                'boolean',
            ],
        ]);

        try {
            $address = InternationalAddress::create(
                strtoupper(
                    $data['country_code']
                ),
                $data['line1'],
                $data['city'],
                $data['region'] ?? null,
                $data['postal_code'] ?? null,
                $data['line2'] ?? null
            );
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages([
                'line1' =>
                    'Endereço inválido.',
            ]);
        }

        return [
            'label' =>
                $this->nullableTrim(
                    $data['label'] ?? null
                ),

            'country_code' =>
                $address->country->code(),

            'line1' =>
                $address->line1,

            'line2' =>
                $address->line2,

            'city' =>
                $address->city,

            'region' =>
                $address->region,

            'postal_code' =>
                $address->postalCode,

            'is_primary' =>
                $request->boolean(
                    'is_primary'
                ),
        ];
    }

    private function nullableTrim(
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

    private function backToCustomer(
        Customer $customer,
        string $message
    ) {
        return redirect()
            ->route(
                'customers.show',
                $customer->id
            )
            ->with(
                'success',
                $message
            );
    }
}
