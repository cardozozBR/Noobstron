<?php

namespace App\Http\Controllers;

use App\Enums\CustomerType;
use App\Enums\Feature;
use App\Models\Customer;
use App\Models\User;
use App\Services\AuditService;
use App\Services\CustomerHistoryService;
use App\Services\TenantContext;
use App\Support\BrazilTaxIdentifier;
use App\Support\TaxIdentifier;
use App\Support\TenantCapabilities;
use App\Support\TenantGlobalSettings;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $query = Customer::query()
            ->with('responsible')
            ->orderBy('name');

        if ($request->filled('search')) {
            $search = trim(
                (string) $request->input('search')
            );

            $phoneSearch = preg_replace(
                '/\D+/',
                '',
                $search
            );

            $query->where(function ($builder) use (
                $search,
                $phoneSearch
            ): void {
                $builder
                    ->where(
                        'name',
                        'like',
                        '%' . $search . '%'
                    )
                    ->orWhere(
                        'legal_name',
                        'like',
                        '%' . $search . '%'
                    )
                    ->orWhere(
                        'tax_identifier',
                        'like',
                        '%' . $search . '%'
                    )
                    ->orWhereHas(
                        'contacts',
                        function ($contactQuery) use ($search): void {
                            $contactQuery
                                ->where(
                                    'name',
                                    'like',
                                    '%' . $search . '%'
                                )
                                ->orWhere(
                                    'role',
                                    'like',
                                    '%' . $search . '%'
                                );
                        }
                    )
                    ->orWhereHas(
                        'emails',
                        function ($emailQuery) use ($search): void {
                            $emailQuery->where(
                                'email',
                                'like',
                                '%' . $search . '%'
                            );
                        }
                    );

                if ($phoneSearch !== '') {
                    $builder->orWhereHas(
                        'phones',
                        function ($phoneQuery) use ($phoneSearch): void {
                            $phoneQuery->where(
                                'national_number',
                                'like',
                                '%' . $phoneSearch . '%'
                            );
                        }
                    );
                }
            });
        }

        if ($request->filled('type')) {
            $query->where(
                'type',
                $request->input('type')
            );
        }

        if ($request->filled('responsible_user_id')) {
            $query->where(
                'responsible_user_id',
                $request->integer(
                    'responsible_user_id'
                )
            );
        }

        return view('customers.index', [
            'customers' => $query
                ->paginate(20)
                ->withQueryString(),

            'types' => CustomerType::cases(),

            'responsibles' => $this->responsibles(),
        ]);
    }

    public function show(int $id)
    {
        $customer = Customer::query()
            ->with([
                'responsible',
                'contacts',
                'phones.contact',
                'emails.contact',
                'addresses',
                'history.user',
            ])
            ->findOrFail($id);

        return view('customers.show', [
            'customer' => $customer,
            'contactTypes' => \App\Enums\CustomerContactType::cases(),
            'countries' => TenantGlobalSettings::countries(),
        ]);
    }

    public function create()
    {
        return view('customers.create', [
            'types' => CustomerType::cases(),
            'responsibles' => $this->responsibles(),
            'countries' => TenantGlobalSettings::countries(),
        ]);
    }

    public function store(
        Request $request,
        AuditService $audits,
        CustomerHistoryService $history,
        TenantCapabilities $capabilities
    ) {
        $tenant = app(TenantContext::class)->get();

        $limit = $capabilities->limit(
            $tenant,
            Feature::CUSTOMERS
        );

        if (
            $limit !== null
            && Customer::query()->count() >= $limit
        ) {
            return back()
                ->withInput()
                ->withErrors([
                    'limit' =>
                        'Limite de clientes do tenant atingido.',
                ]);
        }

        $data = $this->validatedData($request);

        $customer = Customer::create($data);

        $history->record(
            $customer,
            'customer.created',
            'Cliente criado.'
        );

        $audits->log(
            'customer.created',
            'Cliente criado: ' . $customer->name . '.'
        );

        return redirect()
            ->route(
                'customers.show',
                $customer->id
            )
            ->with(
                'success',
                'Cliente criado com sucesso.'
            );
    }

    public function edit(int $id)
    {
        return view('customers.edit', [
            'customer' => Customer::findOrFail($id),
            'types' => CustomerType::cases(),
            'responsibles' => $this->responsibles(),
            'countries' => TenantGlobalSettings::countries(),
        ]);
    }

    public function update(
        Request $request,
        int $id,
        AuditService $audits,
        CustomerHistoryService $history
    ) {
        $customer = Customer::findOrFail($id);

        $data = $this->validatedData($request);

        $customer->fill($data);
        $customer->save();

        $history->record(
            $customer,
            'customer.updated',
            'Dados principais do cliente atualizados.'
        );

        $audits->log(
            'customer.updated',
            'Cliente atualizado: '
                . $customer->name
                . '.'
        );

        return redirect()
            ->route(
                'customers.show',
                $customer->id
            )
            ->with(
                'success',
                'Cliente atualizado com sucesso.'
            );
    }

    public function destroy(
        int $id,
        AuditService $audits
    ) {
        $customer = Customer::findOrFail($id);

        $name = $customer->name;

        $customer->delete();

        $audits->log(
            'customer.deleted',
            'Cliente excluído: '
                . $name
                . '.'
        );

        return redirect()
            ->route('customers.index')
            ->with(
                'success',
                'Cliente excluído com sucesso.'
            );
    }

    private function validatedData(
        Request $request
    ): array {
        $tenant = app(TenantContext::class)->get();

        $data = $request->validate([
            'type' => [
                'required',
                Rule::enum(CustomerType::class),
            ],

            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'legal_name' => [
                'nullable',
                'string',
                'max:255',
            ],

            'tax_country_code' => [
                'nullable',
                'string',
                'size:2',
                Rule::in(
                    TenantGlobalSettings::countries()
                ),
            ],

            'tax_identifier_type' => [
                'nullable',
                'string',
                'max:50',
            ],

            'tax_identifier' => [
                'nullable',
                'string',
                'max:100',
            ],

            'responsible_user_id' => [
                'nullable',
                'integer',

                Rule::exists(
                    'users',
                    'id'
                )->where(
                    fn ($query) => $query->where(
                        'tenant_id',
                        $tenant->id
                    )
                ),
            ],

            'tags' => [
                'nullable',
                'array',
                'max:20',
            ],

            'tags.*' => [
                'nullable',
                'string',
                'max:50',
            ],

            'notes' => [
                'nullable',
                'string',
                'max:10000',
            ],
        ]);

        $type = CustomerType::from(
            $data['type']
        );

        $data['type'] = $type;

        $data['name'] = trim(
            $data['name']
        );

        $data['legal_name'] = $this->nullableTrim(
            $data['legal_name'] ?? null
        );

        $data['notes'] = $this->nullableTrim(
            $data['notes'] ?? null
        );

        $data['tags'] = $this->normalizeTags(
            $data['tags'] ?? null
        );

        $this->normalizeTaxIdentifier(
            $data,
            $type
        );

        return $data;
    }

    private function normalizeTaxIdentifier(
        array &$data,
        CustomerType $customerType
    ): void {
        $country = strtoupper(
            trim(
                (string) (
                    $data['tax_country_code'] ?? ''
                )
            )
        );

        $identifierType = strtoupper(
            trim(
                (string) (
                    $data['tax_identifier_type'] ?? ''
                )
            )
        );

        $value = trim(
            (string) (
                $data['tax_identifier'] ?? ''
            )
        );

        if (
            $country === ''
            && $identifierType === ''
            && $value === ''
        ) {
            $data['tax_country_code'] = null;
            $data['tax_identifier_type'] = null;
            $data['tax_identifier'] = null;

            return;
        }

        if (
            $country === ''
            || $identifierType === ''
            || $value === ''
        ) {
            throw ValidationException::withMessages([
                'tax_identifier' =>
                    'País, tipo e documento fiscal devem ser informados juntos.',
            ]);
        }

        try {
            if ($country === 'BR') {
                $tax = $this->brazilTaxIdentifier(
                    $customerType,
                    $identifierType,
                    $value
                );
            } else {
                $tax = TaxIdentifier::create(
                    $country,
                    $identifierType,
                    $value
                );
            }
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages([
                'tax_identifier' =>
                    'Documento fiscal inválido.',
            ]);
        }

        $data['tax_country_code'] =
            $tax->country()->code();

        $data['tax_identifier_type'] =
            $tax->type();

        $data['tax_identifier'] =
            $tax->value();
    }

    private function brazilTaxIdentifier(
        CustomerType $customerType,
        string $identifierType,
        string $value
    ): TaxIdentifier {
        if (
            $customerType === CustomerType::INDIVIDUAL
            && $identifierType !== 'CPF'
        ) {
            throw new InvalidArgumentException(
                'Pessoa física brasileira exige CPF.'
            );
        }

        if (
            $customerType === CustomerType::COMPANY
            && $identifierType !== 'CNPJ'
        ) {
            throw new InvalidArgumentException(
                'Pessoa jurídica brasileira exige CNPJ.'
            );
        }

        if ($identifierType === 'CPF') {
            return BrazilTaxIdentifier::cpf(
                $value
            );
        }

        return BrazilTaxIdentifier::cnpj(
            $value
        );
    }

    private function responsibles()
    {
        return User::query()
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'email',
            ]);
    }

    private function normalizeTags(
        ?array $tags
    ): ?array {
        if (!$tags) {
            return null;
        }

        $normalized = collect($tags)
            ->map(
                fn ($tag) => trim(
                    (string) $tag
                )
            )
            ->filter()
            ->unique()
            ->values()
            ->all();

        return $normalized === []
            ? null
            : $normalized;
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
}
