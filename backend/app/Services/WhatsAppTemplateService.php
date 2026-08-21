<?php

namespace App\Services;

use App\Models\WhatsAppTemplate;
use RuntimeException;

class WhatsAppTemplateService
{
    public function create(
        array $attributes
    ): WhatsAppTemplate {
        return WhatsAppTemplate::query()
            ->create(
                $attributes
            );
    }

    public function update(
        WhatsAppTemplate $template,
        array $attributes
    ): WhatsAppTemplate {
        $this->assertCurrentTenant(
            $template
        );

        $template->fill(
            $attributes
        );

        $template->save();

        return $template->refresh();
    }

    public function placeholders(
        WhatsAppTemplate $template
    ): array {
        $this->assertCurrentTenant(
            $template
        );

        preg_match_all(
            '/\{\{\s*([a-zA-Z0-9_.-]+)\s*\}\}/',
            $template->body_template,
            $matches
        );

        return array_values(
            array_unique(
                $matches[1] ?? []
            )
        );
    }

    public function render(
        WhatsAppTemplate $template,
        array $variables = []
    ): string {
        $this->assertCurrentTenant(
            $template
        );

        $required =
            $this->placeholders(
                $template
            );

        $missing = array_values(
            array_diff(
                $required,
                array_keys(
                    $variables
                )
            )
        );

        if ($missing !== []) {
            throw new RuntimeException(
                'Missing WhatsApp template variables: '
                . implode(
                    ', ',
                    $missing
                )
            );
        }

        $unknown = array_values(
            array_diff(
                array_keys(
                    $variables
                ),
                $required
            )
        );

        if ($unknown !== []) {
            throw new RuntimeException(
                'Unknown WhatsApp template variables: '
                . implode(
                    ', ',
                    $unknown
                )
            );
        }

        return preg_replace_callback(
            '/\{\{\s*([a-zA-Z0-9_.-]+)\s*\}\}/',
            function (
                array $match
            ) use (
                $variables
            ): string {
                return (string) $variables[
                    $match[1]
                ];
            },
            $template->body_template
        );
    }

    private function assertCurrentTenant(
        WhatsAppTemplate $template
    ): void {
        $tenant = app(
            TenantContext::class
        )->get();

        if (
            (int) $template->tenant_id !==
            (int) $tenant->id
        ) {
            throw new RuntimeException(
                'WhatsApp template does not belong to current tenant.'
            );
        }
    }
}