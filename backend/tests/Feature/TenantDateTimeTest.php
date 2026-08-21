<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Services\TenantContext;
use App\Support\TenantDateTime;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class TenantDateTimeTest extends TestCase
{
    use RefreshDatabase;

    private function setTenantTimezone(
        string $timezone,
        string $slug
    ): TenantDateTime {
        $tenant = Tenant::create([
            'name' => 'Tenant Time',
            'slug' => $slug,
            'status' => 'active',
            'country_code' => 'US',
            'locale' => 'en',
            'timezone' => $timezone,
            'currency' => 'USD',
        ]);

        app(TenantContext::class)->set($tenant);

        return app(TenantDateTime::class);
    }

    public function test_utc_instant_is_displayed_in_tenant_timezone(): void
    {
        $time = $this->setTenantTimezone(
            'America/Fortaleza',
            'time-fortaleza'
        );

        $local = $time->utcToTenant(
            '2026-08-16 00:30:00'
        );

        $this->assertSame(
            '2026-08-15 21:30:00',
            $local->format('Y-m-d H:i:s')
        );

        $this->assertSame(
            'America/Fortaleza',
            $local->timezoneName
        );
    }

    public function test_tokyo_uses_same_utc_instant_with_different_local_time(): void
    {
        $time = $this->setTenantTimezone(
            'Asia/Tokyo',
            'time-tokyo'
        );

        $local = $time->utcToTenant(
            '2026-08-16 00:30:00'
        );

        $this->assertSame(
            '2026-08-16 09:30:00',
            $local->format('Y-m-d H:i:s')
        );
    }

    public function test_tenant_local_datetime_can_be_converted_to_utc(): void
    {
        $time = $this->setTenantTimezone(
            'America/Fortaleza',
            'time-to-utc'
        );

        $utc = $time->tenantToUtc(
            '2026-08-15 21:30:00'
        );

        $this->assertSame(
            '2026-08-16 00:30:00',
            $utc->format('Y-m-d H:i:s')
        );

        $this->assertSame(
            'UTC',
            $utc->timezoneName
        );
    }

    public function test_local_day_is_converted_to_utc_range(): void
    {
        $time = $this->setTenantTimezone(
            'Asia/Tokyo',
            'time-day-tokyo'
        );

        [$start, $end] = $time->localDayUtcBounds(
            '2026-08-16'
        );

        $this->assertSame(
            '2026-08-15 15:00:00',
            $start->format('Y-m-d H:i:s')
        );

        $this->assertSame(
            '2026-08-16 15:00:00',
            $end->format('Y-m-d H:i:s')
        );
    }

    public function test_dst_day_uses_real_calendar_day_length(): void
    {
        $time = $this->setTenantTimezone(
            'America/New_York',
            'time-dst-new-york'
        );

        [$start, $end] = $time->localDayUtcBounds(
            '2026-03-08'
        );

        $this->assertSame(
            23 * 60 * 60,
            $end->timestamp - $start->timestamp
        );
    }

    public function test_invalid_local_date_is_rejected(): void
    {
        $time = $this->setTenantTimezone(
            'America/Fortaleza',
            'time-invalid-date'
        );

        $this->expectException(
            InvalidArgumentException::class
        );

        $time->localDayUtcBounds(
            '2026-02-31'
        );
    }
    public function test_datetime_is_formatted_in_tenant_timezone(): void
    {
        $time = $this->setTenantTimezone(
            'Asia/Tokyo',
            'time-format-tokyo'
        );

        $formatted = $time->formatForTenant(
            '2026-08-16 00:30:00'
        );

        $this->assertSame(
            '16/08/2026 09:30:00',
            $formatted
        );
    }
}