<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use RuntimeException;

class EmailTemplate extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'name',
        'subject_template',
        'body_template',
    ];

    protected static function booted(): void
    {
        static::saving(
            function (EmailTemplate $template): void {
                $template->name = trim(
                    (string) $template->name
                );

                $template->subject_template = trim(
                    (string) $template->subject_template
                );

                $template->body_template = trim(
                    (string) $template->body_template
                );

                if ($template->name === '') {
                    throw new RuntimeException(
                        'Template name is required.'
                    );
                }

                if ($template->subject_template === '') {
                    throw new RuntimeException(
                        'Subject template is required.'
                    );
                }

                if ($template->body_template === '') {
                    throw new RuntimeException(
                        'Body template is required.'
                    );
                }
            }
        );
    }
}