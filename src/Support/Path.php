<?php

namespace Sarmadict\FilamentMedia\Support;

use InvalidArgumentException;

final class Path
{
    public static function normalize(?string $path): string
    {
        $path = str_replace('\\', '/', trim((string) $path));
        $path = trim($path, '/');

        if ($path === '') {
            return '';
        }

        $segments = array_values(array_filter(explode('/', $path), fn (string $segment): bool => $segment !== ''));

        foreach ($segments as $segment) {
            if ($segment === '.' || $segment === '..') {
                throw new InvalidArgumentException('Relative path segments are not allowed.');
            }
        }

        return implode('/', $segments);
    }

    public static function join(string ...$parts): string
    {
        return self::normalize(implode('/', array_filter($parts, fn (string $part): bool => $part !== '')));
    }

    public static function parent(string $path): string
    {
        $path = self::normalize($path);

        if ($path === '' || ! str_contains($path, '/')) {
            return '';
        }

        return self::normalize(dirname($path));
    }

    /**
     * @return list<array{label: string, path: string}>
     */
    public static function breadcrumbs(string $path): array
    {
        $path = self::normalize($path);
        $breadcrumbs = [['label' => '/', 'path' => '']];

        if ($path === '') {
            return $breadcrumbs;
        }

        $running = '';

        foreach (explode('/', $path) as $segment) {
            $running = self::join($running, $segment);
            $breadcrumbs[] = [
                'label' => $segment,
                'path' => $running,
            ];
        }

        return $breadcrumbs;
    }
}
