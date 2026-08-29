<?php

namespace App\Services;

use App\Contracts\WhatsAppWebhookNormalizer;
use App\Enums\WhatsAppWebhookEventType;
use App\Support\WhatsAppWebhookEvent;
use Illuminate\Http\Request;

class MetaWhatsAppWebhookNormalizer implements WhatsAppWebhookNormalizer
{
    public function normalize(Request $request): array
    {
        $payload = $request->json()->all();

        if (! is_array($payload)) {
            return [];
        }

        $events = [];

        foreach (
            data_get($payload, 'entry', []) as $entry
        ) {
            if (! is_array($entry)) {
                continue;
            }

            foreach (
                data_get($entry, 'changes', []) as $change
            ) {
                if (! is_array($change)) {
                    continue;
                }

                if (
                    data_get($change, 'field')
                    !== 'messages'
                ) {
                    continue;
                }

                $value = data_get(
                    $change,
                    'value',
                    []
                );

                if (! is_array($value)) {
                    continue;
                }

                $events = [
                    ...$events,
                    ...$this->messageEvents(
                        $value
                    ),
                    ...$this->statusEvents(
                        $value
                    ),
                ];
            }
        }

        return $events;
    }

    private function messageEvents(array $value): array
    {
        $events = [];

        $contacts = data_get(
            $value,
            'contacts',
            []
        );

        $contactNames = [];

        foreach ($contacts as $contact) {
            if (! is_array($contact)) {
                continue;
            }

            $waId = data_get(
                $contact,
                'wa_id'
            );

            $name = data_get(
                $contact,
                'profile.name'
            );

            if (
                is_string($waId)
                && trim($waId) !== ''
                && is_string($name)
                && trim($name) !== ''
            ) {
                $contactNames[
                    trim($waId)
                ] = trim($name);
            }
        }

        foreach (
            data_get($value, 'messages', []) as $message
        ) {
            if (! is_array($message)) {
                continue;
            }

            if (
                data_get($message, 'type')
                !== 'text'
            ) {
                continue;
            }

            $id = data_get(
                $message,
                'id'
            );

            $phone = data_get(
                $message,
                'from'
            );

            $body = data_get(
                $message,
                'text.body'
            );

            if (
                ! is_string($id)
                || trim($id) === ''
                || ! is_string($phone)
                || trim($phone) === ''
                || ! is_string($body)
                || trim($body) === ''
            ) {
                continue;
            }

            $normalizedPhone =
                preg_replace(
                    '/\D+/',
                    '',
                    $phone
                ) ?? '';

            $events[] =
                new WhatsAppWebhookEvent(
                    type: WhatsAppWebhookEventType::RECEIVED,
                    provider: 'meta',
                    providerMessageId: trim($id),
                    phone: $normalizedPhone,
                    body: trim($body),
                    recipientName: $contactNames[
                            $normalizedPhone
                        ] ?? null
                );
        }

        return $events;
    }

    private function statusEvents(array $value): array
    {
        $events = [];

        foreach (
            data_get($value, 'statuses', []) as $status
        ) {
            if (! is_array($status)) {
                continue;
            }

            $id = data_get(
                $status,
                'id'
            );

            $state = data_get(
                $status,
                'status'
            );

            if (
                ! is_string($id)
                || trim($id) === ''
                || ! is_string($state)
            ) {
                continue;
            }

            $type = match (
                strtolower(trim($state))
            ) {
                'delivered' => WhatsAppWebhookEventType::DELIVERED,

                'read' => WhatsAppWebhookEventType::READ,

                'failed' => WhatsAppWebhookEventType::FAILED,

                default => null,
            };

            if ($type === null) {
                continue;
            }

            $failureReason = null;

            if (
                $type ===
                WhatsAppWebhookEventType::FAILED
            ) {
                $failureReason =
                    $this->failureReason(
                        $status
                    );
            }

            $events[] =
                new WhatsAppWebhookEvent(
                    type: $type,
                    provider: 'meta',
                    providerMessageId: trim($id),
                    failureReason: $failureReason
                );
        }

        return $events;
    }

    private function failureReason(
        array $status
    ): string {
        $errors = data_get(
            $status,
            'errors',
            []
        );

        if (is_array($errors)) {
            foreach ($errors as $error) {
                if (! is_array($error)) {
                    continue;
                }

                foreach ([
                    'error_data.details',
                    'message',
                    'title',
                ] as $path) {
                    $value = data_get(
                        $error,
                        $path
                    );

                    if (
                        is_string($value)
                        && trim($value) !== ''
                    ) {
                        return trim($value);
                    }
                }
            }
        }

        return 'Meta WhatsApp delivery failed.';
    }
}
