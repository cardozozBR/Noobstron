<?php

namespace App\Http\Controllers;

use App\Services\AuditService;
use App\Services\TenantContext;
use App\Support\TenantBrandingSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class TenantSettingsController extends Controller
{
    public function edit()
    {
        return view('settings.edit', [
            'tenant' => app(TenantContext::class)->get(),
        ]);
    }

    public function update(
        Request $request,
        AuditService $audits
    ) {
        $tenant = app(TenantContext::class)->get();

        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'brand_primary_color' => [
                'nullable',
                'string',
                'max:7',
            ],

            'logo' => [
                'nullable',
                'image',
                'mimes:png,jpg,jpeg,webp',
                'max:2048',
            ],

            'remove_logo' => [
                'nullable',
                'boolean',
            ],
        ]);

        try {
            $color = TenantBrandingSettings::normalizePrimaryColor(
                $data['brand_primary_color'] ?? null
            );
        } catch (\InvalidArgumentException) {
            throw ValidationException::withMessages([
                'brand_primary_color' => __('ui.settings.invalid_color'),
            ]);
        }

        $changes = [];

        if ($tenant->name !== $data['name']) {
            $changes[] = 'nome comercial';
        }

        if ($tenant->brand_primary_color !== $color) {
            $changes[] = 'cor principal';
        }

        $oldLogoPath = $tenant->logo_path;
        $newLogoPath = $oldLogoPath;

        if ($request->boolean('remove_logo') && $oldLogoPath) {
            $newLogoPath = null;
            $changes[] = 'logo removido';
        }

        if ($request->hasFile('logo')) {
            app(
                \App\Services\TenantBrandingStorageGuard::class
            )->assertCanStoreLogo(
                $tenant,
                $request->file('logo')
            );
            $newLogoPath = $request
                ->file('logo')
                ->store(
                    'tenant-branding/' . $tenant->id,
                    'public'
                );

            $changes[] = 'logo';
        }

        $tenant->name = $data['name'];
        $tenant->brand_primary_color = $color;
        $tenant->logo_path = $newLogoPath;
        $tenant->save();

        if (
            $oldLogoPath
            && $oldLogoPath !== $newLogoPath
        ) {
            Storage::disk('public')->delete($oldLogoPath);
        }

        $description = empty($changes)
            ? 'Configurações do tenant atualizadas sem alterações.'
            : 'Configurações do tenant atualizadas. Alterações: '
                . implode(', ', $changes)
                . '.';

        $audits->log(
            'tenant.settings.updated',
            $description
        );

        return redirect()
            ->route('settings.edit')
            ->with(
                'success',
                __('ui.settings.success')
            );
    }
}