<?php

namespace App\Services;

use App\Models\EmailTemplate;
use RuntimeException;

class EmailTemplateService
{
    public function create(
        array $data
    ): EmailTemplate {
        return EmailTemplate::query()->create([
            'name' =>
                $data['name'] ?? null,

            'subject_template' =>
                $data['subject_template'] ?? null,

            'body_template' =>
                $data['body_template'] ?? null,
        ]);
    }

    public function update(
        EmailTemplate $template,
        array $data
    ): EmailTemplate {
        $template = $this->currentTenantTemplate(
            $template
        );

        $template->fill([
            'name' =>
                array_key_exists(
                    'name',
                    $data
                )
                    ? $data['name']
                    : $template->name,

            'subject_template' =>
                array_key_exists(
                    'subject_template',
                    $data
                )
                    ? $data['subject_template']
                    : $template->subject_template,

            'body_template' =>
                array_key_exists(
                    'body_template',
                    $data
                )
                    ? $data['body_template']
                    : $template->body_template,
        ]);

        $template->save();

        return $template->refresh();
    }

    public function render(
        EmailTemplate $template,
        array $variables
    ): array {
        $template = $this->currentTenantTemplate(
            $template
        );

        $required = array_values(
            array_unique(
                array_merge(
                    $this->placeholders(
                        $template->subject_template
                    ),
                    $this->placeholders(
                        $template->body_template
                    )
                )
            )
        );

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
                'Unknown template variables: '
                . implode(
                    ', ',
                    $unknown
                )
            );
        }

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
                'Missing template variables: '
                . implode(
                    ', ',
                    $missing
                )
            );
        }

        $subject =
            $this->replaceVariables(
                $template->subject_template,
                $variables
            );

        $body =
            $this->replaceVariables(
                $template->body_template,
                $variables
            );

        return [
            'subject' =>
                $subject,

            'body' =>
                $body,
        ];
    }

    public function placeholders(
        string $content
    ): array {
        preg_match_all(
            '/{{\s*([a-zA-Z0-9_]+)\s*}}/',
            $content,
            $matches
        );

        return array_values(
            array_unique(
                $matches[1] ?? []
            )
        );
    }

    private function replaceVariables(
        string $content,
        array $variables
    ): string {
        foreach (
            $variables as
            $name => $value
        ) {
            $content = preg_replace(
                '/{{\s*'
                    . preg_quote(
                        (string) $name,
                        '/'
                    )
                    . '\s*}}/',
                (string) $value,
                $content
            );
        }

        return $content;
    }

    private function currentTenantTemplate(
        EmailTemplate $template
    ): EmailTemplate {
        return EmailTemplate::query()
            ->findOrFail(
                $template->getKey()
            );
    }
}