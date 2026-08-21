<?php

namespace Tests\Unit;

use App\Contracts\AiProvider;
use App\Services\AiProviderRegistry;
use App\Support\AiRequest;
use App\Support\AiResult;
use RuntimeException;
use Tests\TestCase;

class AiProviderRegistryTest extends TestCase
{
    public function test_provider_can_be_registered_and_resolved(): void
    {
        $registry =
            new AiProviderRegistry();

        $provider =
            $this->provider(
                'fake'
            );

        $registry->register(
            $provider
        );

        $this->assertSame(
            $provider,
            $registry->resolve(
                'fake'
            )
        );

        $this->assertTrue(
            $registry->has(
                'fake'
            )
        );
    }

    public function test_provider_code_is_normalized(): void
    {
        $registry =
            new AiProviderRegistry();

        $provider =
            $this->provider(
                '  TEST-PROVIDER  '
            );

        $registry->register(
            $provider
        );

        $this->assertSame(
            $provider,
            $registry->resolve(
                'test-provider'
            )
        );

        $this->assertSame(
            $provider,
            $registry->resolve(
                ' TEST-PROVIDER '
            )
        );

        $this->assertSame(
            [
                'test-provider',
            ],
            $registry->codes()
        );
    }

    public function test_duplicate_provider_is_rejected(): void
    {
        $registry =
            new AiProviderRegistry();

        $registry->register(
            $this->provider(
                'fake'
            )
        );

        $this->expectException(
            RuntimeException::class
        );

        $registry->register(
            $this->provider(
                ' FAKE '
            )
        );
    }

    public function test_unknown_provider_is_rejected(): void
    {
        $registry =
            new AiProviderRegistry();

        $this->expectException(
            RuntimeException::class
        );

        $registry->resolve(
            'missing'
        );
    }

    public function test_blank_provider_code_is_rejected_on_registration(): void
    {
        $registry =
            new AiProviderRegistry();

        $this->expectException(
            RuntimeException::class
        );

        $registry->register(
            $this->provider(
                '   '
            )
        );
    }

    public function test_blank_provider_code_is_rejected_on_resolution(): void
    {
        $registry =
            new AiProviderRegistry();

        $this->expectException(
            RuntimeException::class
        );

        $registry->resolve(
            '   '
        );
    }

    public function test_has_rejects_blank_provider_code(): void
    {
        $registry =
            new AiProviderRegistry();

        $this->expectException(
            RuntimeException::class
        );

        $registry->has(
            '   '
        );
    }

    public function test_codes_are_sorted(): void
    {
        $registry =
            new AiProviderRegistry();

        $registry->register(
            $this->provider(
                'zeta'
            )
        );

        $registry->register(
            $this->provider(
                'alpha'
            )
        );

        $registry->register(
            $this->provider(
                'beta'
            )
        );

        $this->assertSame(
            [
                'alpha',
                'beta',
                'zeta',
            ],
            $registry->codes()
        );
    }

    public function test_registry_is_singleton_in_container(): void
    {
        $first = app(
            AiProviderRegistry::class
        );

        $second = app(
            AiProviderRegistry::class
        );

        $this->assertSame(
            $first,
            $second
        );
    }

    public function test_provider_instance_is_preserved(): void
    {
        $registry =
            new AiProviderRegistry();

        $provider =
            $this->provider(
                'fake'
            );

        $registry->register(
            $provider
        );

        $resolved =
            $registry->resolve(
                'fake'
            );

        $this->assertSame(
            spl_object_id(
                $provider
            ),
            spl_object_id(
                $resolved
            )
        );
    }

    private function provider(
        string $code
    ): AiProvider {
        return new class(
            $code
        ) implements AiProvider {
            public function __construct(
                private readonly string $code
            ) {
            }

            public function code(): string
            {
                return $this->code;
            }

            public function execute(
                AiRequest $request
            ): AiResult {
                return new AiResult(
                    content: 'Fake',
                    model: 'fake-model',
                    inputTokens: 1,
                    outputTokens: 1,
                );
            }
        };
    }
}