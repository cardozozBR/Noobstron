<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use RuntimeException;

class WhatsAppProviderConfig extends Model
{
    use BelongsToTenant;

    protected $table = 'whatsapp_provider_configs';

    protected $fillable = [
        'tenant_id',
        'provider',
        'sender_id',
        'settings',
        'active',
    ];

    protected $casts = [
        'settings' => 'encrypted:array',
        'active' => 'boolean',
    ];

    protected $attributes = [
        'active' => true,
    ];

    protected static function booted(): void
    {
        static::saving(
            function (
                WhatsAppProviderConfig $config
            ): void {
                $config->normalize();

                if (
                    blank(
                        $config->provider
                    )
                ) {
                    throw new RuntimeException(
                        'WhatsApp provider is required.'
                    );
                }

                if (
                    blank(
                        $config->sender_id
                    )
                ) {
                    throw new RuntimeException(
                        'WhatsApp sender id is required.'
                    );
                }
            }
        );
    }

    private function normalize(): void
    {
        if (
            is_string(
                $this->provider
            )
        ) {
            $this->provider =
                strtolower(
                    trim(
                        $this->provider
                    )
                );
        }

        if (
            is_string(
                $this->sender_id
            )
        ) {
            $this->sender_id =
                trim(
                    $this->sender_id
                );
        }
    }
}