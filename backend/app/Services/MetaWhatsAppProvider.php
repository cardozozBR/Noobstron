<?php

namespace App\Services;

use App\Contracts\WhatsAppProvider;
use App\Models\WhatsAppMessage;
use App\Models\WhatsAppTemplate;
use App\Support\WhatsAppProviderResult;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class MetaWhatsAppProvider implements WhatsAppProvider
{
    public function __construct(
        private readonly WhatsAppProviderConfigService $configs,
        private readonly WhatsAppTemplateService $templates
    ) {}

    public function name(): string
    {
        return 'meta';
    }

    public function send(
        WhatsAppMessage $message
    ): WhatsAppProviderResult {
        $config = $this->configs->active(
            $this->name()
        );

        $settings = $config->settings;

        if (! is_array($settings)) {
            throw new RuntimeException(
                'Meta WhatsApp settings are invalid.'
            );
        }

        $token = $settings['token'] ?? null;

        if (
            ! is_string($token)
            || trim($token) === ''
        ) {
            throw new RuntimeException(
                'Meta WhatsApp access token is not configured.'
            );
        }

        $graphVersion =
            $settings['graph_version'] ?? null;

        if (
            ! is_string($graphVersion)
            || trim($graphVersion) === ''
        ) {
            throw new RuntimeException(
                'Meta WhatsApp Graph API version is not configured.'
            );
        }

        $senderId = trim(
            (string) $config->sender_id
        );

        if ($senderId === '') {
            throw new RuntimeException(
                'Meta WhatsApp phone number id is not configured.'
            );
        }

        $payload = $message->whatsapp_template_id
            ? $this->templatePayload($message)
            : $this->textPayload($message);

        $response = Http::withToken(
            trim($token)
        )
            ->acceptJson()
            ->asJson()
            ->timeout(30)
            ->post(
                sprintf(
                    'https://graph.facebook.com/%s/%s/messages',
                    trim($graphVersion),
                    $senderId
                ),
                $payload
            );

        $response->throw();

        $messageId = $response->json(
            'messages.0.id'
        );

        if (
            ! is_string($messageId)
            || trim($messageId) === ''
        ) {
            throw new RuntimeException(
                'Meta WhatsApp response does not contain a message id.'
            );
        }

        return new WhatsAppProviderResult(
            provider: $this->name(),
            messageId: trim($messageId)
        );
    }

    private function textPayload(
        WhatsAppMessage $message
    ): array {
        return [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $message->phone,
            'type' => 'text',
            'text' => [
                'preview_url' => false,
                'body' => $message->body,
            ],
        ];
    }

    private function templatePayload(
        WhatsAppMessage $message
    ): array {
        $template = $message->template;

        if (! $template instanceof WhatsAppTemplate) {
            throw new RuntimeException(
                'WhatsApp template was not found.'
            );
        }

        if (! $template->active) {
            throw new RuntimeException(
                'WhatsApp template is inactive.'
            );
        }

        if ($template->provider !== $this->name()) {
            throw new RuntimeException(
                'WhatsApp template provider is not Meta.'
            );
        }

        $templateName = trim(
            (string) $template->provider_template_name
        );

        if ($templateName === '') {
            throw new RuntimeException(
                'Meta WhatsApp template name is not configured.'
            );
        }

        $language = $this->metaLanguageCode(
            (string) $template->language
        );

        if ($language === '') {
            throw new RuntimeException(
                'Meta WhatsApp template language is not configured.'
            );
        }

        $variables = $message->template_variables ?? [];

        if (! is_array($variables)) {
            throw new RuntimeException(
                'WhatsApp template variables are invalid.'
            );
        }

        $this->templates->render(
            $template,
            $variables
        );

        $placeholders = $this->templates->placeholders(
            $template
        );

        $templatePayload = [
            'name' => $templateName,
            'language' => [
                'code' => $language,
            ],
        ];

        if ($placeholders !== []) {
            $templatePayload['components'] = [
                [
                    'type' => 'body',
                    'parameters' => array_map(
                        static function (
                            string $placeholder
                        ) use (
                            $variables
                        ): array {
                            return [
                                'type' => 'text',
                                'text' => (string) $variables[
                                    $placeholder
                                ],
                            ];
                        },
                        $placeholders
                    ),
                ],
            ];
        }

        return [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $message->phone,
            'type' => 'template',
            'template' => $templatePayload,
        ];
    }

    private function metaLanguageCode(
        string $language
    ): string {
        $language = trim(
            str_replace(
                '-',
                '_',
                $language
            )
        );

        if ($language === '') {
            return '';
        }

        $parts = explode(
            '_',
            $language,
            2
        );

        if (count($parts) === 1) {
            return strtolower(
                $parts[0]
            );
        }

        return strtolower(
            $parts[0]
        )
            .'_'
            .strtoupper(
                $parts[1]
            );
    }
}