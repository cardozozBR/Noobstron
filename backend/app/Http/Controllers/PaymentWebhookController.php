<?php

namespace App\Http\Controllers;

use App\Contracts\PaymentWebhookNormalizer;
use App\Contracts\PaymentWebhookVerifier;
use App\Models\Tenant;
use App\Services\PaymentWebhookHttpService;
use App\Services\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class PaymentWebhookController extends Controller
{
    public function handle(
        string $tenantSlug,
        string $provider,
        Request $request,
        TenantContext $tenantContext,
        PaymentWebhookHttpService $httpService,
    ): JsonResponse {
        $tenant = Tenant::query()
            ->withoutGlobalScopes()
            ->where('slug', $tenantSlug)
            ->where('status', 'active')
            ->firstOrFail();

        $tenantContext->set($tenant);

        $provider = strtolower(trim($provider));

        if ($provider === '') {
            abort(404);
        }

        $verifierKey =
            'payment.webhook.verifier.' . $provider;

        $normalizerKey =
            'payment.webhook.normalizer.' . $provider;

        if (
            ! app()->bound($verifierKey)
            || ! app()->bound($normalizerKey)
        ) {
            abort(404);
        }

        /** @var PaymentWebhookVerifier $verifier */
        $verifier = app($verifierKey);

        /** @var PaymentWebhookNormalizer $normalizer */
        $normalizer = app($normalizerKey);

        try {
            $charge = $httpService->handle(
                $request,
                $provider,
                $verifier,
                $normalizer,
            );
        }
        catch (RuntimeException $exception) {
            if (
                $exception->getMessage()
                === 'Invalid payment webhook signature.'
            ) {
                return response()->json(
                    ['ok' => false],
                    401
                );
            }

            throw $exception;
        }

        return response()->json([
            'ok' => true,
            'charge_id' => $charge->id,
        ]);
    }
}