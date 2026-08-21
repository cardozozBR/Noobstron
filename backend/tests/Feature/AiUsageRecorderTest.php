<?php

namespace Tests\Feature;

use App\Models\AiUsageRecord;
use App\Models\Tenant;
use App\Services\AiUsageRecorder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class AiUsageRecorderTest extends TestCase
{
    use RefreshDatabase;

    public function test_ai_usage_can_be_recorded(): void
    {
        $tenant = $this->tenant('ai-usage-record');

        $record = app(AiUsageRecorder::class)
            ->record(
                tenant: $tenant,
                provider: ' OpenAI ',
                model: ' gpt-example ',
                inputTokens: 120,
                outputTokens: 80,
                operation: ' assistant ',
            );

        $this->assertSame(
            $tenant->id,
            $record->tenant_id
        );

        $this->assertSame(
            'openai',
            $record->provider
        );

        $this->assertSame(
            'gpt-example',
            $record->model
        );

        $this->assertSame(
            'assistant',
            $record->operation
        );

        $this->assertSame(
            120,
            $record->input_tokens
        );

        $this->assertSame(
            80,
            $record->output_tokens
        );

        $this->assertSame(
            200,
            $record->total_tokens
        );
    }

    public function test_token_usage_cannot_be_negative(): void
    {
        $tenant = $this->tenant('ai-usage-negative');

        $this->expectException(
            InvalidArgumentException::class
        );

        app(AiUsageRecorder::class)
            ->record(
                tenant: $tenant,
                provider: 'openai',
                model: 'gpt-example',
                inputTokens: -1,
                outputTokens: 10,
            );
    }

    public function test_provider_is_required(): void
    {
        $tenant = $this->tenant('ai-provider-required');

        $this->expectException(
            InvalidArgumentException::class
        );

        app(AiUsageRecorder::class)
            ->record(
                tenant: $tenant,
                provider: ' ',
                model: 'gpt-example',
                inputTokens: 1,
                outputTokens: 1,
            );
    }

    public function test_model_is_required(): void
    {
        $tenant = $this->tenant('ai-model-required');

        $this->expectException(
            InvalidArgumentException::class
        );

        app(AiUsageRecorder::class)
            ->record(
                tenant: $tenant,
                provider: 'openai',
                model: ' ',
                inputTokens: 1,
                outputTokens: 1,
            );
    }

    public function test_records_are_assigned_to_explicit_tenant(): void
    {
        $first = $this->tenant('ai-tenant-first');
        $second = $this->tenant('ai-tenant-second');

        $recorder = app(AiUsageRecorder::class);

        $recorder->record(
            tenant: $first,
            provider: 'openai',
            model: 'gpt-example',
            inputTokens: 10,
            outputTokens: 20,
        );

        $recorder->record(
            tenant: $second,
            provider: 'openai',
            model: 'gpt-example',
            inputTokens: 100,
            outputTokens: 200,
        );

        $this->assertSame(
            1,
            AiUsageRecord::query()
                ->where('tenant_id', $first->id)
                ->count()
        );

        $this->assertSame(
            1,
            AiUsageRecord::query()
                ->where('tenant_id', $second->id)
                ->count()
        );
    }

    private function tenant(string $slug): Tenant
    {
        return Tenant::query()->create([
            'name' => ucfirst($slug),
            'slug' => $slug,
            'status' => 'active',
        ]);
    }
}