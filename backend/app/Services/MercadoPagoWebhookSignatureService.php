<?php

namespace App\Services;

class MercadoPagoWebhookSignatureService
{
    public function verify(
        string $signature,
        string $requestId,
        string $dataId,
    ): bool {
        $secret = trim(
            (string) config(
                'services.mercado_pago.webhook_secret'
            )
        );

        if (
            $secret === ''
            || trim($signature) === ''
            || trim($requestId) === ''
            || trim($dataId) === ''
        ) {
            return false;
        }

        $parts = [];

        foreach (
            explode(',', $signature)
            as $part
        ) {
            [$key, $value] = array_pad(
                explode('=', trim($part), 2),
                2,
                null
            );

            if (
                is_string($key)
                && is_string($value)
            ) {
                $parts[$key] = $value;
            }
        }

        $timestamp = $parts['ts'] ?? null;
        $receivedHash = $parts['v1'] ?? null;

        if (
            ! is_string($timestamp)
            || $timestamp === ''
            || ! is_string($receivedHash)
            || $receivedHash === ''
        ) {
            return false;
        }

        $manifest =
            'id:' . trim($dataId) . ';'
            . 'request-id:' . trim($requestId) . ';'
            . 'ts:' . $timestamp . ';';

        $expectedHash = hash_hmac(
            'sha256',
            $manifest,
            $secret
        );

        return hash_equals(
            $expectedHash,
            $receivedHash
        );
    }
}