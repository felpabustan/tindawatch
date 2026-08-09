<?php

namespace App\Support;

use Illuminate\Support\Collection;

class EwalletProviders
{
    /**
     * Catalog of providers that stores may enable.
     * Add new entries in config/tindawatch.php (and a logo asset) to expand.
     *
     * @return Collection<int, array{name: string, slug: string, logo: string}>
     */
    public static function catalog(): Collection
    {
        /** @var array<int, array{name: string, slug: string, logo: string}> $providers */
        $providers = config('tindawatch.ewallet_providers', []);

        return collect($providers)
            ->map(fn (array $provider) => [
                'name' => (string) $provider['name'],
                'slug' => (string) $provider['slug'],
                'logo' => (string) $provider['logo'],
            ])
            ->values();
    }

    /**
     * @return array<int, string>
     */
    public static function names(): array
    {
        return self::catalog()->map(fn (array $provider) => $provider['name'])->all();
    }

    public static function isAllowed(string $name): bool
    {
        return in_array($name, self::names(), true);
    }

    public static function logoFor(?string $name): ?string
    {
        if ($name === null) {
            return null;
        }

        $match = self::catalog()->firstWhere('name', $name);

        return $match['logo'] ?? null;
    }

    public static function sortOrder(string $name): int
    {
        $index = self::catalog()->search(fn (array $provider) => $provider['name'] === $name);

        return $index === false ? 999 : $index;
    }
}
