<?php

namespace App\Services;

use App\Contracts\AutomationActionHandler;
use App\Enums\AutomationActionType;
use App\Support\AutomationAction;
use App\Support\AutomationActionResult;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

class SendWebhookActionHandler implements AutomationActionHandler
{
    public function handle(
        AutomationAction $action,
        array $context
    ): AutomationActionResult {
        if (
            $action->type
            !== AutomationActionType::SEND_WEBHOOK
        ) {
            throw new RuntimeException(
                'Invalid action type for send webhook handler.'
            );
        }

        $url = trim(
            (string) (
                $action->parameters['url']
                ?? ''
            )
        );

        if (
            $url === ''
            || filter_var(
                $url,
                FILTER_VALIDATE_URL
            ) === false
        ) {
            throw new RuntimeException(
                'Valid webhook URL is required.'
            );
        }

        $scheme = strtolower(
            (string) parse_url(
                $url,
                PHP_URL_SCHEME
            )
        );

        if (
            ! in_array(
                $scheme,
                [
                    'http',
                    'https',
                ],
                true
            )
        ) {
            throw new RuntimeException(
                'Webhook URL must use HTTP or HTTPS.'
            );
        }

        $payload =
            $action->parameters['payload']
            ?? [];

        if (! is_array($payload)) {
            throw new RuntimeException(
                'Webhook payload must be an array.'
            );
        }

        $headers =
            $action->parameters['headers']
            ?? [];

        if (! is_array($headers)) {
            throw new RuntimeException(
                'Webhook headers must be an array.'
            );
        }

        try {
            $response = Http::acceptJson()
                ->withHeaders(
                    $headers
                )
                ->post(
                    $url,
                    $payload
                );
        } catch (Throwable $exception) {
            return AutomationActionResult::failure(
                $exception->getMessage()
            );
        }

        if ($response->failed()) {
            return AutomationActionResult::failure(
                'Webhook request failed with HTTP status '
                . $response->status()
                . '.'
            );
        }

        return AutomationActionResult::success([
            'url' =>
                $url,

            'status' =>
                $response->status(),
        ]);
    }
}