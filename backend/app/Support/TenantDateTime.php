<?php

namespace App\Support;

use App\Services\TenantContext;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use DateTimeZone;
use InvalidArgumentException;

final class TenantDateTime
{
    public function __construct(
        private readonly TenantContext $tenantContext,
    ) {
    }

    public function timezone(): DateTimeZone
    {
        return new DateTimeZone(
            $this->tenantContext->get()->timezone
        );
    }

    public function timezoneName(): string
    {
        return $this->timezone()->getName();
    }

    public function utcToTenant(
        DateTimeInterface|string $instant
    ): CarbonImmutable {
        if ($instant instanceof DateTimeInterface) {
            return CarbonImmutable::instance($instant)
                ->setTimezone($this->timezone());
        }

        return CarbonImmutable::parse(
            $instant,
            'UTC'
        )->setTimezone(
            $this->timezone()
        );
    }

    public function tenantToUtc(
        string $localDateTime,
        string $format = 'Y-m-d H:i:s'
    ): CarbonImmutable {
        $value = CarbonImmutable::createFromFormat(
            $format,
            $localDateTime,
            $this->timezone()
        );

        if (
            $value === false
            || $value->format($format) !== $localDateTime
        ) {
            throw new InvalidArgumentException(
                'Invalid tenant local date/time.'
            );
        }

        return $value->utc();
    }

    /**
     * Returns [inclusive start UTC, exclusive end UTC].
     */
    public function localDayUtcBounds(
        string $date
    ): array {
        $start = CarbonImmutable::createFromFormat(
            'Y-m-d H:i:s',
            $date . ' 00:00:00',
            $this->timezone()
        );

        if (
            $start === false
            || $start->format('Y-m-d') !== $date
        ) {
            throw new InvalidArgumentException(
                'Invalid tenant local date.'
            );
        }

        $end = $start->addDay();

        return [
            $start->utc(),
            $end->utc(),
        ];
    }
    public function formatForTenant(
        DateTimeInterface|string $instant,
        string $format = 'd/m/Y H:i:s'
    ): string {
        return $this->utcToTenant($instant)
            ->format($format);
    }
}