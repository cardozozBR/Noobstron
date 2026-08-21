<?php

namespace Tests\Feature;

use App\Services\AiProviderConfigurationResolver;
use RuntimeException;
use Tests\TestCase;

class AiProviderConfigurationResolverTest extends TestCase
{
    public function test_explicit_provider_configuration_can_be_resolved(): void
    {
        config()->set(
            'ai.providers.fake',
            [
                'enabled' =>
                    true,

                'model' =>
                    'fake-model',

                'parameters' => [
                    'temperature' =>
                        0.4,
                ],
            ]
        );

        $configuration = app(
            AiProviderConfigurationResolver::class
        )->resolve(
            ' FAKE '
        );

        $this->assertSame(
            'fake',
            $configuration->provider
        );

        $this->assertSame(
            'fake-model',
            $configuration->model
        );

        $this->assertTrue(
            $configuration->enabled
        );

        $this->assertSame(
            0.4,
            $configuration->parameter(
                'temperature'
            )
        );
    }

    public function test_default_provider_can_be_resolved(): void
    {
        config()->set(
            'ai.default',
            'fake'
        );

        config()->set(
            'ai.providers.fake',
            [
                'enabled' =>
                    true,

                'model' =>
                    'default-model',

                'parameters' =>
                    [],
            ]
        );

        $configuration = app(
            AiProviderConfigurationResolver::class
        )->resolve();

        $this->assertSame(
            'fake',
            $configuration->provider
        );

        $this->assertSame(
            'default-model',
            $configuration->model
        );
    }

    public function test_disabled_provider_configuration_is_preserved(): void
    {
        config()->set(
            'ai.providers.fake',
            [
                'enabled' =>
                    false,

                'model' =>
                    'fake-model',

                'parameters' =>
                    [],
            ]
        );

        $configuration = app(
            AiProviderConfigurationResolver::class
        )->resolve(
            'fake'
        );

        $this->assertFalse(
            $configuration->enabled
        );
    }

    public function test_unknown_provider_configuration_is_rejected(): void
    {
        config()->set(
            'ai.providers',
            []
        );

        $this->expectException(
            RuntimeException::class
        );

        app(
            AiProviderConfigurationResolver::class
        )->resolve(
            'missing'
        );
    }

    public function test_missing_default_provider_is_rejected(): void
    {
        config()->set(
            'ai.default',
            null
        );

        $this->expectException(
            RuntimeException::class
        );

        app(
            AiProviderConfigurationResolver::class
        )->resolve();
    }

    public function test_provider_without_model_is_rejected(): void
    {
        config()->set(
            'ai.providers.fake',
            [
                'enabled' =>
                    true,

                'model' =>
                    null,

                'parameters' =>
                    [],
            ]
        );

        $this->expectException(
            RuntimeException::class
        );

        app(
            AiProviderConfigurationResolver::class
        )->resolve(
            'fake'
        );
    }

    public function test_invalid_enabled_flag_is_rejected(): void
    {
        config()->set(
            'ai.providers.fake',
            [
                'enabled' =>
                    'yes',

                'model' =>
                    'fake-model',

                'parameters' =>
                    [],
            ]
        );

        $this->expectException(
            RuntimeException::class
        );

        app(
            AiProviderConfigurationResolver::class
        )->resolve(
            'fake'
        );
    }

    public function test_invalid_parameters_are_rejected(): void
    {
        config()->set(
            'ai.providers.fake',
            [
                'enabled' =>
                    true,

                'model' =>
                    'fake-model',

                'parameters' =>
                    'invalid',
            ]
        );

        $this->expectException(
            RuntimeException::class
        );

        app(
            AiProviderConfigurationResolver::class
        )->resolve(
            'fake'
        );
    }
}