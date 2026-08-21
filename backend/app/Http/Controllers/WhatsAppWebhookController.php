<?php

namespace App\Http\Controllers;

use App\Contracts\WhatsAppWebhookNormalizer;
use App\Contracts\WhatsAppWebhookVerifier;
use App\Models\Tenant;
use App\Services\TenantContext;
use App\Services\WhatsAppWebhookHttpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class WhatsAppWebhookController extends Controller
{
    public function handle(
        string $tenantSlug,
        string $provider,
        Request $request,
        TenantContext $tenantContext,
        WhatsAppWebhookHttpService $httpService
    ): JsonResponse {
        $tenant = Tenant::query()
            ->withoutGlobalScopes()
            ->where(
                'slug',
                $tenantSlug
            )
            ->where(
                'status',
                'active'
            )
            ->firstOrFail();

        $tenantContext->set(
            $tenant
        );

        $provider = strtolower(
            trim(
                $provider
            )
        );

        if ($provider === '') {
            abort(
                404
            );
        }

        $verifierKey =
            'whatsapp.webhook.verifier.'
            . $provider;

        $normalizerKey =
            'whatsapp.webhook.normalizer.'
            . $provider;

        if (
            ! app()->bound(
                $verifierKey
            )
            || ! app()->bound(
                $normalizerKey
            )
        ) {
            abort(
                404
            );
        }

        /** @var WhatsAppWebhookVerifier $verifier */
        $verifier = app(
            $verifierKey
        );

        /** @var WhatsAppWebhookNormalizer $normalizer */
        $normalizer = app(
            $normalizerKey
        );

        try {
            $processed = $httpService
                ->handle(
                    $request,
                    $verifier,
                    $normalizer
                );
        }
        catch (RuntimeException $exception) {
            if (
                $exception->getMessage() ===
                'Invalid WhatsApp webhook signature.'
            ) {
                return response()
                    ->json(
                        [
                            'ok' =>
                                false,
                        ],
                        401
                    );
            }

            throw $exception;
        }

        return response()
            ->json([
                'ok' =>
                    true,

                'processed' =>
                    $processed,
            ]);
    }
}