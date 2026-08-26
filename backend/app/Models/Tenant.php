<?php

namespace App\Models;

use App\Support\TenantBrandingSettings;
use App\Support\TenantGlobalSettings;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Tenant extends Model
{
    protected $casts = [
        'trial_started_at' => 'immutable_datetime',
        'trial_ends_at' => 'immutable_datetime',
    ];

    protected $fillable = [
        'name',
        'slug',
        'status',
        'trial_started_at',
        'trial_ends_at',
        'country_code',
        'locale',
        'segment',
        'timezone',
        'currency',
        'brand_primary_color',
        'logo_path',
    ];

    protected static function booted(): void
    {
        static::saving(function (Tenant $tenant): void {
            $defaults = TenantGlobalSettings::defaults();

            $tenant->country_code = strtoupper(
                trim(
                    (string) (
                        $tenant->country_code
                        ?: ($defaults['country_code'] ?? 'BR')
                    )
                )
            );

            $tenant->locale = trim(
                (string) (
                    $tenant->locale
                    ?: ($defaults['locale'] ?? 'pt-BR')
                )
            );

            $tenant->timezone = trim(
                (string) (
                    $tenant->timezone
                    ?: ($defaults['timezone'] ?? 'America/Fortaleza')
                )
            );

            $tenant->currency = strtoupper(
                trim(
                    (string) (
                        $tenant->currency
                        ?: ($defaults['currency'] ?? 'BRL')
                    )
                )
            );

            TenantGlobalSettings::assertValid(
                $tenant->country_code,
                $tenant->locale,
                $tenant->timezone,
                $tenant->currency
            );

            $tenant->brand_primary_color =
                TenantBrandingSettings::normalizePrimaryColor(
                    $tenant->brand_primary_color
                );

            $tenant->logo_path =
                TenantBrandingSettings::normalizeLogoPath(
                    $tenant->logo_path
                );
        });
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(
            Subscription::class
        );
    }

    public function latestSubscription(): HasOne
    {
        return $this->hasOne(
            Subscription::class
        )->latestOfMany('id');
    }

    public function effectiveBrandPrimaryColor(): string
    {
        return TenantBrandingSettings::primaryColor(
            $this->brand_primary_color
        );
    }

    public function hasLogo(): bool
    {
        return $this->logo_path !== null
            && trim($this->logo_path) !== '';
    }
    public function features(): HasMany
    {
        return $this->hasMany(TenantFeature::class);
    }

    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class);
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    public function imports()
    {
        return $this->hasMany(
            Import::class
        );
    }

    public function pipelines(): HasMany
    {
        return $this->hasMany(
            Pipeline::class
        );
    }

public function opportunities(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(
            Opportunity::class
        );
    }

    public function activities(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Activity::class);
    }

    public function catalogItems(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(
            CatalogItem::class
        );
    }
    public function proposals(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(
            Proposal::class
        );
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }
    public function receivables(): HasMany
    {
        return $this->hasMany(
            Receivable::class
        );
    }

    public function charges(): HasMany
    {
        return $this->hasMany(
            Charge::class
        );
    }

    public function chargeRecurrences(): HasMany
    {
        return $this->hasMany(
            ChargeRecurrence::class
        );
    }

    public function emailMessages(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(
            EmailMessage::class
        );
    }

    public function emailTemplates(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(
            EmailTemplate::class
        );
    }

    public function whatsAppMessages()
    {
        return $this->hasMany(
            WhatsAppMessage::class
        );
    }

    public function whatsAppTemplates()
    {
        return $this->hasMany(
            WhatsAppTemplate::class
        );
    }

    public function whatsAppProviderConfigs()
    {
        return $this->hasMany(
            WhatsAppProviderConfig::class
        );
    }

    public function conversations()
    {
        return $this->hasMany(
            Conversation::class
        );
    }
}
