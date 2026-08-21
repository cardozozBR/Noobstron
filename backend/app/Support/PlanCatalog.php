<?php

namespace App\Support;

final class PlanCatalog
{
    /**
     * @return array<
     *     string,
     *     array{
     *         code: string,
     *         name: string
     *     }
     * >
     */
    public static function definitions(): array
    {
        return [
            'start' => [
                'code' => 'start',
                'name' => 'Start',
            ],
            'pro' => [
                'code' => 'pro',
                'name' => 'Pro',
            ],
            'business' => [
                'code' => 'business',
                'name' => 'Business',
            ],
            'enterprise' => [
                'code' => 'enterprise',
                'name' => 'Enterprise',
            ],
        ];
    }

    /**
     * @return list<string>
     */
    public static function codes(): array
    {
        return array_keys(
            self::definitions()
        );
    }
}