<?php

namespace Tests\Feature;

use App\Enums\ImportStatus;
use App\Enums\UsageMetric;
use App\Models\Import;
use App\Models\EmailMessage;
use App\Models\WhatsAppMessage;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantUsageService;
use App\Services\AiUsageRecorder;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantUsageServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_usage_is_counted_for_tenant(): void
    {
        $tenant = $this->tenant('usage-users');

        app(TenantContext::class)->set($tenant);

        User::query()->create([
            'name' => 'One',
            'email' => 'one@example.test',
            'password' => 'password',
            'role' => 'user',
        ]);

        User::query()->create([
            'name' => 'Two',
            'email' => 'two@example.test',
            'password' => 'password',
            'role' => 'user',
        ]);

        $this->assertSame(
            2,
            app(TenantUsageService::class)->value(
                $tenant,
                UsageMetric::USERS
            )
        );
    }

    public function test_storage_usage_reuses_storage_service(): void
    {
        $tenant = $this->tenant('usage-storage');

        Import::query()
            ->withoutGlobalScopes()
            ->create([
                'tenant_id' => $tenant->id,
                'user_id' => null,
                'target' => null,
                'original_name' => 'usage.csv',
                'stored_path' =>
                    'tenant-imports/'
                    . $tenant->id
                    . '/usage.csv',
                'mime_type' => 'text/csv',
                'size' => 640,
                'status' => ImportStatus::UPLOADED,
                'delimiter' => ',',
                'encoding' => 'UTF-8',
            ]);

        $this->assertSame(
            640,
            app(TenantUsageService::class)->value(
                $tenant,
                UsageMetric::STORAGE_BYTES
            )
        );
    }

    public function test_message_usage_combines_email_and_whatsapp(): void
    {
        $tenant = $this->tenant('usage-messages');

        EmailMessage::query()
            ->withoutGlobalScopes()
            ->forceCreate([
                'tenant_id' => $tenant->id,
                'to_email' => 'one@example.test',
                'subject' => 'One',
                'body' => 'Message one',
                'status' => 'pending',
            ]);

        WhatsAppMessage::query()
            ->withoutGlobalScopes()
            ->forceCreate([
                'tenant_id' => $tenant->id,
                'phone' => '5511999999999',
                'body' => 'Message two',
                'status' => 'pending',
                'direction' => 'outbound',
            ]);

        WhatsAppMessage::query()
            ->withoutGlobalScopes()
            ->forceCreate([
                'tenant_id' => $tenant->id,
                'phone' => '5511888888888',
                'body' => 'Message three',
                'status' => 'pending',
                'direction' => 'outbound',
            ]);

        $this->assertSame(
            3,
            app(TenantUsageService::class)->value(
                $tenant,
                UsageMetric::MESSAGES
            )
        );
    }
    public function test_inbound_whatsapp_does_not_consume_message_usage(): void
    {
        $tenant = $this->tenant(
            'usage-inbound-whatsapp'
        );

        WhatsAppMessage::query()
            ->withoutGlobalScopes()
            ->forceCreate([
                'tenant_id' => $tenant->id,
                'phone' => '5511999999999',
                'body' => 'Outbound message',
                'status' => 'pending',
                'direction' => 'outbound',
            ]);

        WhatsAppMessage::query()
            ->withoutGlobalScopes()
            ->forceCreate([
                'tenant_id' => $tenant->id,
                'phone' => '5511888888888',
                'body' => 'Inbound message',
                'status' => 'received',
                'direction' => 'inbound',
            ]);

        $this->assertSame(
            1,
            app(TenantUsageService::class)->value(
                $tenant,
                UsageMetric::MESSAGES
            )
        );
    }
    public function test_ai_token_usage_is_summed_for_tenant(): void
    {
        $tenant = $this->tenant('usage-ai');

        $recorder = app(AiUsageRecorder::class);

        $recorder->record(
            tenant: $tenant,
            provider: 'openai',
            model: 'gpt-example',
            inputTokens: 100,
            outputTokens: 50,
        );

        $recorder->record(
            tenant: $tenant,
            provider: 'openai',
            model: 'gpt-example',
            inputTokens: 200,
            outputTokens: 100,
        );

        $this->assertSame(
            450,
            app(TenantUsageService::class)->value(
                $tenant,
                UsageMetric::AI_TOKENS
            )
        );
    }

    public function test_ai_token_usage_is_isolated_between_tenants(): void
    {
        $first = $this->tenant('usage-ai-first');
        $second = $this->tenant('usage-ai-second');

        $recorder = app(AiUsageRecorder::class);

        $recorder->record(
            tenant: $first,
            provider: 'openai',
            model: 'gpt-example',
            inputTokens: 20,
            outputTokens: 30,
        );

        $recorder->record(
            tenant: $second,
            provider: 'openai',
            model: 'gpt-example',
            inputTokens: 400,
            outputTokens: 500,
        );

        $service = app(TenantUsageService::class);

        $this->assertSame(
            50,
            $service->value(
                $first,
                UsageMetric::AI_TOKENS
            )
        );

        $this->assertSame(
            900,
            $service->value(
                $second,
                UsageMetric::AI_TOKENS
            )
        );
    }
    public function test_usage_is_isolated_between_tenants(): void
    {
        $first = $this->tenant('usage-first');
        $second = $this->tenant('usage-second');

        Import::query()
            ->withoutGlobalScopes()
            ->create([
                'tenant_id' => $first->id,
                'user_id' => null,
                'target' => null,
                'original_name' => 'first.csv',
                'stored_path' => 'first.csv',
                'mime_type' => 'text/csv',
                'size' => 100,
                'status' => ImportStatus::UPLOADED,
                'delimiter' => ',',
                'encoding' => 'UTF-8',
            ]);

        Import::query()
            ->withoutGlobalScopes()
            ->create([
                'tenant_id' => $second->id,
                'user_id' => null,
                'target' => null,
                'original_name' => 'second.csv',
                'stored_path' => 'second.csv',
                'mime_type' => 'text/csv',
                'size' => 900,
                'status' => ImportStatus::UPLOADED,
                'delimiter' => ',',
                'encoding' => 'UTF-8',
            ]);

        $service = app(TenantUsageService::class);

        $this->assertSame(
            100,
            $service->value(
                $first,
                UsageMetric::STORAGE_BYTES
            )
        );

        $this->assertSame(
            900,
            $service->value(
                $second,
                UsageMetric::STORAGE_BYTES
            )
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