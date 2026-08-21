<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PublicLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $supported = array_keys(config('global.locales', []));
        $fallback = (string) config('app.fallback_locale', 'pt-BR');

        if (!in_array($fallback, $supported, true)) {
            $fallback = 'pt-BR';
        }

        $locale = $request->cookie('public_locale');

        if (!is_string($locale) || !in_array($locale, $supported, true)) {
            $locale = $this->detect(
                (string) $request->header('Accept-Language', ''),
                $supported,
                $fallback
            );
        }

        app()->setLocale($locale);

        return $next($request);
    }

    private function detect(string $header, array $supported, string $fallback): string
    {
        $header = trim($header);

        if ($header === '') {
            return $fallback;
        }

        $items = [];

        foreach (explode(',', $header) as $position => $item) {
            $parts = array_map('trim', explode(';', $item));
            $tag = strtolower(str_replace('_', '-', $parts[0] ?? ''));

            if ($tag === '') {
                continue;
            }

            $quality = 1.0;

            foreach (array_slice($parts, 1) as $parameter) {
                if (str_starts_with($parameter, 'q=')) {
                    $quality = (float) substr($parameter, 2);
                }
            }

            $items[] = compact('tag', 'quality', 'position');
        }

        usort($items, static function (array $a, array $b): int {
            $quality = $b['quality'] <=> $a['quality'];

            return $quality !== 0
                ? $quality
                : $a['position'] <=> $b['position'];
        });

        foreach ($items as $item) {
            $language = explode('-', $item['tag'])[0];

            $mapped = match ($language) {
                'pt' => 'pt-BR',
                'en' => 'en',
                'es' => 'es',
                'zh' => 'zh-CN',
                'ja' => 'ja',
                default => null,
            };

            if ($mapped !== null && in_array($mapped, $supported, true)) {
                return $mapped;
            }
        }

        return $fallback;
    }
}