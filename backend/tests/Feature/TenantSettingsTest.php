<?php

namespace Tests\Feature;

use App\Enums\Feature;
use App\Enums\Permission as PermissionEnum;
use App\Models\AuditLog;
use App\Models\Permission;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantContext;
use App\Support\TenantCapabilities;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TenantSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);
    }

    private function tenant(
        string $slug,
        string $name = 'Tenant Teste'
    ): Tenant {
        $tenant = Tenant::create([
            'name' => $name,
            'slug' => $slug,
            'status' => 'active',
            'country_code' => 'BR',
            'locale' => 'pt-BR',
            'timezone' => 'America/Fortaleza',
            'currency' => 'BRL',
        ]);

        app(TenantCapabilities::class)->set(
            $tenant,
            Feature::BRANDING,
            true
        );

        return $tenant;
    }

    private function user(
        Tenant $tenant,
        string $email,
        string $role = 'user'
    ): User {
        app(TenantContext::class)->set($tenant);

        return User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Usuário Teste',
            'email' => $email,
            'password' => 'TesteSenha123',
            'role' => $role,
        ]);
    }

    private function grantSettingsPermission(
        User $user
    ): void {
        $permission = Permission::where(
            'name',
            PermissionEnum::SETTINGS_UPDATE->value
        )->firstOrFail();

        $user->permissions()->syncWithoutDetaching(
            $permission->id
        );
    }

    public function test_user_with_permission_can_view_settings(): void
    {
        $tenant = $this->tenant('tenant-settings');

        $user = $this->user(
            $tenant,
            'settings@tenant.local'
        );

        $this->grantSettingsPermission($user);

        $response = $this
            ->actingAs($user)
            ->get('http://tenant-settings.localhost/settings');

        $response->assertOk();
        $response->assertSee($tenant->name);
    }

    public function test_user_without_permission_gets_forbidden(): void
    {
        $tenant = $this->tenant('tenant-settings-denied');

        $user = $this->user(
            $tenant,
            'denied@tenant.local'
        );

        $response = $this
            ->actingAs($user)
            ->get('http://tenant-settings-denied.localhost/settings');

        $response->assertForbidden();
    }

    public function test_admin_can_access_settings(): void
    {
        $tenant = $this->tenant('tenant-settings-admin');

        $admin = $this->user(
            $tenant,
            'admin@tenant.local',
            'admin'
        );

        $response = $this
            ->actingAs($admin)
            ->get('http://tenant-settings-admin.localhost/settings');

        $response->assertOk();
    }

    public function test_settings_update_changes_current_tenant_only(): void
    {
        $tenantA = $this->tenant(
            'tenant-settings-a',
            'Tenant A'
        );

        $tenantB = $this->tenant(
            'tenant-settings-b',
            'Tenant B'
        );

        $user = $this->user(
            $tenantA,
            'settings-a@tenant.local'
        );

        $this->grantSettingsPermission($user);

        $response = $this
            ->actingAs($user)
            ->put(
                'http://tenant-settings-a.localhost/settings',
                [
                    'name' => 'Marca Atualizada',
                    'brand_primary_color' => '#abcdef',
                ]
            );

        $response->assertRedirect(
            'http://tenant-settings-a.localhost/settings'
        );

        $tenantA->refresh();
        $tenantB->refresh();

        $this->assertSame(
            'Marca Atualizada',
            $tenantA->name
        );

        $this->assertSame(
            '#ABCDEF',
            $tenantA->brand_primary_color
        );

        $this->assertSame(
            'Tenant B',
            $tenantB->name
        );

        $this->assertNull(
            $tenantB->brand_primary_color
        );
    }

    public function test_invalid_color_is_rejected(): void
    {
        $tenant = $this->tenant(
            'tenant-settings-invalid'
        );

        $user = $this->user(
            $tenant,
            'invalid@tenant.local'
        );

        $this->grantSettingsPermission($user);

        $response = $this
            ->actingAs($user)
            ->from('http://tenant-settings-invalid.localhost/settings')
            ->put(
                'http://tenant-settings-invalid.localhost/settings',
                [
                    'name' => 'Tenant Teste',
                    'brand_primary_color' => 'blue',
                ]
            );

        $response->assertSessionHasErrors(
            'brand_primary_color'
        );
    }

    public function test_settings_update_creates_audit_log(): void
    {
        $tenant = $this->tenant(
            'tenant-settings-audit'
        );

        $user = $this->user(
            $tenant,
            'audit-settings@tenant.local'
        );

        $this->grantSettingsPermission($user);

        $this
            ->actingAs($user)
            ->put(
                'http://tenant-settings-audit.localhost/settings',
                [
                    'name' => 'Tenant Atualizado',
                    'brand_primary_color' => '#112233',
                ]
            );

        $this->assertDatabaseHas('audit_logs', [
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'action' => 'tenant.settings.updated',
        ]);
    }
    public function test_settings_view_uses_tenant_locale(): void
    {
        $tenant = Tenant::create([
            'name' => 'Tenant Japan Settings',
            'slug' => 'tenant-japan-settings',
            'status' => 'active',
            'country_code' => 'JP',
            'locale' => 'ja',
            'timezone' => 'Asia/Tokyo',
            'currency' => 'JPY',
        ]);

        app(TenantCapabilities::class)->set(
            $tenant,
            Feature::BRANDING,
            true
        );

        $admin = $this->user(
            $tenant,
            'admin@tenant-japan-settings.local',
            'admin'
        );

        $response = $this
            ->actingAs($admin)
            ->get(
                'http://tenant-japan-settings.localhost/settings'
            );

        $response->assertOk();

        $response->assertSee(
            'lang="ja"',
            false
        );

        $response->assertSee(
            '設定',
            false
        );

        $response->assertSee(
            'メインカラー',
            false
        );
    }
    private function fakePng(
        string $name = 'logo.png'
    ): UploadedFile {
        $png = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9Y9Z1aAAAAAASUVORK5CYII='
        );

        return UploadedFile::fake()->createWithContent(
            $name,
            $png
        );
    }

    public function test_logo_can_be_uploaded(): void
    {
        Storage::fake('public');

        $tenant = $this->tenant(
            'tenant-settings-logo'
        );

        $user = $this->user(
            $tenant,
            'logo@tenant.local'
        );

        $this->grantSettingsPermission($user);

        $response = $this
            ->actingAs($user)
            ->put(
                'http://tenant-settings-logo.localhost/settings',
                [
                    'name' => $tenant->name,
                    'brand_primary_color' => null,
                    'logo' => $this->fakePng(),
                ]
            );

        $response->assertSessionHasNoErrors();

        $tenant->refresh();

        $this->assertNotNull(
            $tenant->logo_path
        );

        $this->assertStringStartsWith(
            'tenant-branding/' . $tenant->id . '/',
            $tenant->logo_path
        );

        Storage::disk('public')->assertExists(
            $tenant->logo_path
        );
    }

    public function test_replacing_logo_deletes_previous_file(): void
    {
        Storage::fake('public');

        $tenant = $this->tenant(
            'tenant-settings-logo-replace'
        );

        $user = $this->user(
            $tenant,
            'replace-logo@tenant.local'
        );

        $this->grantSettingsPermission($user);

        $oldPath = 'tenant-branding/'
            . $tenant->id
            . '/old-logo.png';

        Storage::disk('public')->put(
            $oldPath,
            'old'
        );

        $tenant->logo_path = $oldPath;
        $tenant->save();

        $this
            ->actingAs($user)
            ->put(
                'http://tenant-settings-logo-replace.localhost/settings',
                [
                    'name' => $tenant->name,
                    'brand_primary_color' => null,
                    'logo' => $this->fakePng(
                        'new-logo.png'
                    ),
                ]
            )
            ->assertSessionHasNoErrors();

        $tenant->refresh();

        $this->assertNotSame(
            $oldPath,
            $tenant->logo_path
        );

        Storage::disk('public')->assertMissing(
            $oldPath
        );

        Storage::disk('public')->assertExists(
            $tenant->logo_path
        );
    }

    public function test_logo_can_be_removed(): void
    {
        Storage::fake('public');

        $tenant = $this->tenant(
            'tenant-settings-logo-remove'
        );

        $user = $this->user(
            $tenant,
            'remove-logo@tenant.local'
        );

        $this->grantSettingsPermission($user);

        $path = 'tenant-branding/'
            . $tenant->id
            . '/logo.png';

        Storage::disk('public')->put(
            $path,
            'logo'
        );

        $tenant->logo_path = $path;
        $tenant->save();

        $this
            ->actingAs($user)
            ->put(
                'http://tenant-settings-logo-remove.localhost/settings',
                [
                    'name' => $tenant->name,
                    'brand_primary_color' => null,
                    'remove_logo' => '1',
                ]
            )
            ->assertSessionHasNoErrors();

        $tenant->refresh();

        $this->assertNull(
            $tenant->logo_path
        );

        Storage::disk('public')->assertMissing(
            $path
        );
    }

    public function test_invalid_logo_is_rejected(): void
    {
        Storage::fake('public');

        $tenant = $this->tenant(
            'tenant-settings-logo-invalid'
        );

        $user = $this->user(
            $tenant,
            'invalid-logo@tenant.local'
        );

        $this->grantSettingsPermission($user);

        $file = UploadedFile::fake()
            ->createWithContent(
                'logo.txt',
                'not-an-image'
            );

        $response = $this
            ->actingAs($user)
            ->from(
                'http://tenant-settings-logo-invalid.localhost/settings'
            )
            ->put(
                'http://tenant-settings-logo-invalid.localhost/settings',
                [
                    'name' => $tenant->name,
                    'brand_primary_color' => null,
                    'logo' => $file,
                ]
            );

        $response->assertSessionHasErrors(
            'logo'
        );

        $tenant->refresh();

        $this->assertNull(
            $tenant->logo_path
        );
    }

    public function test_tenant_logo_storage_is_isolated(): void
    {
        Storage::fake('public');

        $tenantA = $this->tenant(
            'tenant-settings-logo-a',
            'Tenant A'
        );

        $tenantB = $this->tenant(
            'tenant-settings-logo-b',
            'Tenant B'
        );

        $user = $this->user(
            $tenantA,
            'logo-a@tenant.local'
        );

        $this->grantSettingsPermission($user);

        $tenantBPath = 'tenant-branding/'
            . $tenantB->id
            . '/logo.png';

        Storage::disk('public')->put(
            $tenantBPath,
            'tenant-b-logo'
        );

        $tenantB->logo_path = $tenantBPath;
        $tenantB->save();

        $this
            ->actingAs($user)
            ->put(
                'http://tenant-settings-logo-a.localhost/settings',
                [
                    'name' => $tenantA->name,
                    'brand_primary_color' => null,
                    'logo' => $this->fakePng(),
                ]
            )
            ->assertSessionHasNoErrors();

        $tenantA->refresh();
        $tenantB->refresh();

        $this->assertStringStartsWith(
            'tenant-branding/' . $tenantA->id . '/',
            $tenantA->logo_path
        );

        $this->assertSame(
            $tenantBPath,
            $tenantB->logo_path
        );

        Storage::disk('public')->assertExists(
            $tenantBPath
        );
    }
    public function test_existing_logo_is_rendered_in_settings(): void
    {
        Storage::fake('public');

        $tenant = $this->tenant(
            'tenant-settings-logo-view'
        );

        $user = $this->user(
            $tenant,
            'logo-view@tenant.local'
        );

        $this->grantSettingsPermission($user);

        $path = 'tenant-branding/'
            . $tenant->id
            . '/logo.png';

        Storage::disk('public')->put(
            $path,
            'logo'
        );

        $tenant->logo_path = $path;
        $tenant->save();

        $response = $this
            ->actingAs($user)
            ->get(
                'http://tenant-settings-logo-view.localhost/settings'
            );

        $response->assertOk();

        $response->assertSee(
            'storage/' . $path,
            false
        );

        $response->assertSee(
            __('ui.settings.remove_logo'),
            false
        );
    }

    public function test_user_with_permission_is_blocked_when_branding_feature_is_disabled(): void
    {
        $tenant = $this->tenant(
            'tenant-settings-feature-off'
        );

        $user = $this->user(
            $tenant,
            'feature-off@tenant.local'
        );

        $this->grantSettingsPermission(
            $user
        );

        app(TenantCapabilities::class)->set(
            $tenant,
            Feature::BRANDING,
            false
        );

        $response = $this
            ->actingAs($user)
            ->get(
                'http://tenant-settings-feature-off.localhost/settings'
            );

        $response->assertForbidden();
    }
}