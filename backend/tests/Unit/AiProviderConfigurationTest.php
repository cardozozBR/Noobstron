<?php

namespace Tests\Unit;

use App\Support\AiProviderConfiguration;
use InvalidArgumentException;
use Tests\TestCase;

class AiProviderConfigurationTest extends TestCase
{
    public function test_configuration_preserves_values(): void
    {
        $configuration =
            new AiProviderConfiguration(
                provider:
                    'openai',
                model:
                    'gpt-example',
                enabled:
                    true,
                parameters: [
                    'temperature' =>
                        0.2,
                ],
            );

        $this->assertSame(
            'openai',
            $configuration->provider
        );

        $this->assertSame(
            'gpt-example',
            $configuration->model
        );

        $this->assertTrue(
            $configuration->enabled
        );

        $this->assertSame(
            0.2,
            $configuration->parameter(
                'temperature'
            )
        );
    }

    public function test_provider_is_normalized(): void
    {
        $configuration =
            new AiProviderConfiguration(
                provider:
                    '  OPENAI  ',
                model:
                    'model-one',
            );

        $this->assertSame(
            'openai',
            $configuration
                ->normalizedProvider()
        );
    }

    public function test_model_is_trimmed_when_read_as_normalized(): void
    {
        $configuration =
            new AiProviderConfiguration(
                provider:
                    'openai',
                model:
                    '  model-one  ',
            );

        $this->assertSame(
            'model-one',
            $configuration
                ->normalizedModel()
        );
    }

    public function test_missing_parameter_returns_default(): void
    {
        $configuration =
            new AiProviderConfiguration(
                provider:
                    'openai',
                model:
                    'model-one',
            );

        $this->assertSame(
            123,
            $configuration->parameter(
                'missing',
                123
            )
        );
    }

    public function test_blank_provider_is_rejected(): void
    {
        $this->expectException(
            InvalidArgumentException::class
        );

        new AiProviderConfiguration(
            provider:
                '   ',
            model:
                'model-one',
        );
    }

    public function test_blank_model_is_rejected(): void
    {
        $this->expectException(
            InvalidArgumentException::class
        );

        new AiProviderConfiguration(
            provider:
                'openai',
            model:
                '   ',
        );
    }
}