<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use RuntimeException;

class WhatsAppTemplate extends Model
{
    use BelongsToTenant;

    protected $table = 'whatsapp_templates';

    protected $fillable = [
        'tenant_id',
        'name',
        'body_template',
        'provider',
        'provider_template_name',
        'language',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    protected $attributes = [
        'active' => true,
    ];

    protected static function booted(): void
    {
        static::saving(
            function (
                WhatsAppTemplate $template
            ): void {
                $template->normalize();

                if (
                    blank(
                        $template->name
                    )
                ) {
                    throw new RuntimeException(
                        'WhatsApp template name is required.'
                    );
                }

                if (
                    blank(
                        $template->body_template
                    )
                ) {
                    throw new RuntimeException(
                        'WhatsApp template body is required.'
                    );
                }
            }
        );
    }

    private function normalize(): void
    {
        foreach ([
            'name',
            'body_template',
            'provider',
            'provider_template_name',
            'language',
        ] as $field) {
            if (
                ! is_string(
                    $this->{$field}
                )
            ) {
                continue;
            }

            $value = trim(
                $this->{$field}
            );

            if (
                in_array(
                    $field,
                    [
                        'provider',
                        'language',
                    ],
                    true
                )
            ) {
                $value = strtolower(
                    $value
                );
            }

            $this->{$field} =
                $value === ''
                    ? null
                    : $value;
        }
    }
}