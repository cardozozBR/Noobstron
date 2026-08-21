<?php

namespace App\Http\Controllers;

use App\Services\MercadoPagoSubscriptionWebhookService;
use App\Services\MercadoPagoWebhookSignatureService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MercadoPagoSubscriptionWebhookController
    extends Controller
{
    public function handle(
        Request $request,
        MercadoPagoWebhookSignatureService $signature,
        MercadoPagoSubscriptionWebhookService $webhook,
    ): JsonResponse {
        $dataId = $request->query(
            'data_id'
        );

        if (! is_string($dataId)) {
            $dataId = $request->query(
                'data.id'
            );
        }

        if (! is_string($dataId)) {
            $dataId = data_get(
                $request->all(),
                'data.id'
            );
        }

        $requestId = (string) $request->header(
            'x-request-id',
            ''
        );

        $xSignature = (string) $request->header(
            'x-signature',
            ''
        );

        if (
            ! is_string($dataId)
            || ! $signature->verify(
                $xSignature,
                $requestId,
                $dataId,
            )
        ) {
            return response()->json(
                ['ok' => false],
                401
            );
        }

        $type = strtolower(
            trim(
                (string) $request->input(
                    'type',
                    $request->query(
                        'type',
                        ''
                    )
                )
            )
        );

        if (
            $type ===
            'subscription_authorized_payment'
        ) {
            $webhook->processAuthorizedPayment(
                $dataId
            );
        }

        return response()->json([
            'ok' => true,
        ]);
        if (
    $type ===
    'subscription_preapproval'
) {
    $webhook->processPreapproval(
        $dataId
    );
}
    }
}