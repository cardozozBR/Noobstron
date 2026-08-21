<?php

namespace Tests\Unit;

use App\Contracts\WhatsAppProvider;
use App\Models\WhatsAppMessage;
use App\Support\WhatsAppProviderResult;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class WhatsAppProviderContractTest extends TestCase
{
    public function test_provider_contract_can_be_implemented(): void
    {
        $provider = new class implements WhatsAppProvider {
            public function name(): string
            {
                return 'fake';
            }

            public function send(
                WhatsAppMessage $message
            ): WhatsAppProviderResult {
                return new WhatsAppProviderResult(
                    'fake',
                    'provider-message-1'
                );
            }
        };

        $result = $provider->send(
            new WhatsAppMessage()
        );

        $this->assertSame(
            'fake',
            $provider->name()
        );

        $this->assertSame(
            'fake',
            $result->provider
        );

        $this->assertSame(
            'provider-message-1',
            $result->messageId
        );
    }

    public function test_provider_result_requires_provider_name(): void
    {
        $this->expectException(
            RuntimeException::class
        );

        new WhatsAppProviderResult(
            '   ',
            'message-1'
        );
    }

    public function test_provider_result_requires_message_id(): void
    {
        $this->expectException(
            RuntimeException::class
        );

        new WhatsAppProviderResult(
            'fake',
            '   '
        );
    }
}