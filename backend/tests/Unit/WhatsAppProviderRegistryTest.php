<?php

namespace Tests\Unit;

use App\Contracts\WhatsAppProvider;
use App\Models\WhatsAppMessage;
use App\Services\WhatsAppProviderRegistry;
use App\Support\WhatsAppProviderResult;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class WhatsAppProviderRegistryTest extends TestCase
{
    public function test_provider_can_be_registered_and_resolved(): void
    {
        $registry = new WhatsAppProviderRegistry();

        $provider = $this->provider(
            'meta'
        );

        $registry->register(
            $provider
        );

        $this->assertTrue(
            $registry->has(
                'META'
            )
        );

        $this->assertSame(
            $provider,
            $registry->get(
                ' meta '
            )
        );
    }

    public function test_unknown_provider_is_rejected(): void
    {
        $registry = new WhatsAppProviderRegistry();

        $this->expectException(
            RuntimeException::class
        );

        $registry->get(
            'unknown'
        );
    }

    public function test_duplicate_registration_replaces_provider(): void
    {
        $registry = new WhatsAppProviderRegistry();

        $first = $this->provider(
            'meta'
        );

        $second = $this->provider(
            'META'
        );

        $registry->register(
            $first
        );

        $registry->register(
            $second
        );

        $this->assertSame(
            $second,
            $registry->get(
                'meta'
            )
        );
    }

    private function provider(
        string $name
    ): WhatsAppProvider {
        return new class(
            $name
        ) implements WhatsAppProvider {
            public function __construct(
                private readonly string $providerName
            ) {
            }

            public function name(): string
            {
                return $this->providerName;
            }

            public function send(
                WhatsAppMessage $message
            ): WhatsAppProviderResult {
                return new WhatsAppProviderResult(
                    strtolower(
                        trim(
                            $this->providerName
                        )
                    ),
                    'fake-message-id'
                );
            }
        };
    }
}