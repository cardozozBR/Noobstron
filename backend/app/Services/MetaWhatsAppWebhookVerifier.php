<?php

namespace App\Services;

use App\Contracts\WhatsAppWebhookVerifier;
use Illuminate\Http\Request;
use RuntimeException;

class MetaWhatsAppWebhookVerifier implements WhatsAppWebhookVerifier
{
    public function __construct(
        private readonly WhatsAppProviderConfigService $configs
    ) {}

    public function verify(Request $request): bool
    {
        $config = $this->configs->active('meta');

        $settings = $config->settings;

        if (! is_array($settings)) {
            throw new RuntimeException(
                'Meta WhatsApp settings are invalid.'
            );
        }

        $appSecret = $settings['app_secret'] ?? null;

        if (
            ! is_string($appSecret)
            || trim($appSecret) === ''
        ) {
            throw new RuntimeException(
                'Meta WhatsApp app secret is not configured.'
            );
        }

        $signature = $request->header(
            'X-Hub-Signature-256'
        );

        if (
            ! is_string($signature)
            || ! str_starts_with(
                $signature,
                'sha256='
            )
        ) {
            return false;
        }

        $provided = substr(
            $signature,
            strlen('sha256=')
        );

        if ($provided === '') {
            return false;
        }

        $expected = hash_hmac(
            'sha256',
            $request->getContent(),
            trim($appSecret)
        );

        return hash_equals(
            $expected,
            $provided
        );
    }
}
