<?php

namespace Tests\Unit;

use App\Enums\UsageMetric;
use Tests\TestCase;

class UsageMetricTest extends TestCase
{
    public function test_usage_metrics_are_defined(): void
    {
        $this->assertSame(
            'users',
            UsageMetric::USERS->value
        );

        $this->assertSame(
            'storage_bytes',
            UsageMetric::STORAGE_BYTES->value
        );

        $this->assertSame(
            'messages',
            UsageMetric::MESSAGES->value
        );

        $this->assertSame(
            'ai_tokens',
            UsageMetric::AI_TOKENS->value
        );
    }

    public function test_usage_metrics_have_labels(): void
    {
        $this->assertSame(
            'Usuários',
            UsageMetric::USERS->label()
        );

        $this->assertSame(
            'Storage',
            UsageMetric::STORAGE_BYTES->label()
        );

        $this->assertSame(
            'Mensagens',
            UsageMetric::MESSAGES->label()
        );

        $this->assertSame(
            'IA (tokens)',
            UsageMetric::AI_TOKENS->label()
        );
    }
}