<?php

namespace App\Http\Controllers;

use App\Services\StripeSubscriptionWebhookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StripeSubscriptionWebhookController
    extends Controller
{
    public function handle(
        Request $request,
        StripeSubscriptionWebhookService $webhook,
    ): JsonResponse {
        $signature = (string) $request->header(
            'stripe-signature',
            ''
        );

        if (! $webhook->handle(
            $request->getContent(),
            $signature,
        )) {
            return response()->json(
                ['ok' => false],
                400
            );
        }

        return response()->json([
            'ok' => true,
        ]);
    }
}