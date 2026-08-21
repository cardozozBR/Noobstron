<?php

namespace App\Services;

use App\Enums\CustomerType;
use App\Enums\ImportTarget;
use App\Enums\LeadSource;
use App\Enums\LeadStatus;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ImportRowValidator
{
    public function validate(
        array $row,
        ImportTarget $target
    ): array {
        $rules = match ($target) {
            ImportTarget::LEADS => [
                'name' => [
                    'required',
                    'string',
                    'max:255',
                ],
                'email' => [
                    'nullable',
                    'email',
                    'max:255',
                ],
                'phone' => [
                    'nullable',
                    'string',
                    'min:8',
                    'max:20',
                ],
                'status' => [
                    'nullable',
                    Rule::enum(
                        LeadStatus::class
                    ),
                ],
                'source' => [
                    'nullable',
                    Rule::enum(
                        LeadSource::class
                    ),
                ],
                'tags' => [
                    'nullable',
                    'array',
                ],
                'notes' => [
                    'nullable',
                    'string',
                ],
            ],

            ImportTarget::CUSTOMERS => [
                'type' => [
                    'required',
                    Rule::enum(
                        CustomerType::class
                    ),
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
                ],
                'tax_identifier_type' => [
                    'nullable',
                    'string',
                    'max:32',
                ],
                'tax_identifier' => [
                    'nullable',
                    'string',
                    'max:64',
                ],
                'email' => [
                    'nullable',
                    'email',
                    'max:255',
                ],
                'phone' => [
                    'nullable',
                    'string',
                    'min:8',
                    'max:20',
                ],
                'tags' => [
                    'nullable',
                    'array',
                ],
                'notes' => [
                    'nullable',
                    'string',
                ],
            ],
        };

        $validator = Validator::make(
            $row,
            $rules
        );

        if ($validator->fails()) {
            return [
                'valid' => false,
                'data' => $row,
                'errors' =>
                    $validator->errors()
                        ->toArray(),
            ];
        }

        return [
            'valid' => true,
            'data' => $validator
                ->validated(),
            'errors' => [],
        ];
    }
}
