<?php

namespace Tests\Feature;

use App\Services\AiProviderRegistry;
use App\Services\OpenAiProvider;
use Tests\TestCase;

class OpenAiProviderRegistryTest extends TestCase
{
    public function test_openai_provider_is_registered_in_container_registry(): void
    {
        $registry = app(
            AiProviderRegistry::class
        );

        $provider = $registry->resolve(
            'openai'
        );

        $this->assertInstanceOf(
            OpenAiProvider::class,
            $provider
        );

        $this->assertSame(
            'openai',
            $provider->code()
        );
    }

    public function test_openai_provider_can_be_resolved_with_normalized_code(): void
    {
        $registry = app(
            AiProviderRegistry::class
        );

        $provider = $registry->resolve(
            ' OPENAI '
        );

        $this->assertInstanceOf(
            OpenAiProvider::class,
            $provider
        );
    }

    public function test_registry_contains_openai_code(): void
    {
        $registry = app(
            AiProviderRegistry::class
        );

        $this->assertTrue(
            $registry->has(
                'openai'
            )
        );

        $this->assertContains(
            'openai',
            $registry->codes()
        );
    }

    public function test_registry_remains_singleton_with_openai_registered(): void
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

        $this->assertSame(
            $first->resolve(
                'openai'
            ),
            $second->resolve(
                'openai'
            )
        );
    }

    public function test_registered_openai_instance_is_container_resolved_instance(): void
    {
        $registry = app(
            AiProviderRegistry::class
        );

        $registered = $registry->resolve(
            'openai'
        );

        $resolved = app(
            OpenAiProvider::class
        );

        $this->assertInstanceOf(
            OpenAiProvider::class,
            $registered
        );

        $this->assertInstanceOf(
            OpenAiProvider::class,
            $resolved
        );
    }
}