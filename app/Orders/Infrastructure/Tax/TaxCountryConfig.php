<?php

namespace App\Orders\Infrastructure\Tax;

use App\Orders\Domain\TaxCalculatorInterface;
use Illuminate\Support\Facades\Cache;

final class TaxCountryConfig
{
    public const CACHE_KEY = 'resto_tax_country';

    /** @return list<array{country: string, currency: string, name: string}> */
    public static function options(): array
    {
        return [
            ['country' => 'es', 'currency' => 'EUR', 'name' => 'España — Madrid'],
            ['country' => 'mx', 'currency' => 'MXN', 'name' => 'México — CDMX'],
            ['country' => 'ar', 'currency' => 'ARS', 'name' => 'Argentina — Buenos Aires'],
            ['country' => 'cl', 'currency' => 'CLP', 'name' => 'Chile — Santiago'],
            ['country' => 'co', 'currency' => 'COP', 'name' => 'Colombia — Bogotá'],
            ['country' => 'pe', 'currency' => 'PEN', 'name' => 'Perú — Lima'],
        ];
    }

    public static function normalize(?string $country): string
    {
        return match (strtolower((string) $country)) {
            'mx', 'mexico' => 'mx',
            'ar', 'argentina' => 'ar',
            'cl', 'chile' => 'cl',
            'co', 'colombia' => 'co',
            'pe', 'peru' => 'pe',
            default => 'es',
        };
    }

    public static function currentCountry(): string
    {
        return self::normalize(
            Cache::get(self::CACHE_KEY, env('TAX_COUNTRY', 'es'))
        );
    }

    public static function setCountry(string $country): void
    {
        Cache::forever(self::CACHE_KEY, self::normalize($country));
    }

    public static function currency(?string $country = null): string
    {
        return match (self::normalize($country ?? self::currentCountry())) {
            'mx' => 'MXN',
            'ar' => 'ARS',
            'cl' => 'CLP',
            'co' => 'COP',
            'pe' => 'PEN',
            default => 'EUR',
        };
    }

    public static function establishmentName(?string $country = null): string
    {
        $code = self::normalize($country ?? self::currentCountry());
        foreach (self::options() as $option) {
            if ($option['country'] === $code) {
                return $option['name'];
            }
        }

        return 'España — Madrid';
    }

    public static function calculator(?string $country = null): TaxCalculatorInterface
    {
        return match (self::normalize($country ?? self::currentCountry())) {
            'mx' => new MexicoTaxCalculator(),
            'ar' => new ArgentinaTaxCalculator(),
            'cl' => new ChileTaxCalculator(),
            'co' => new ColombiaTaxCalculator(),
            'pe' => new PeruTaxCalculator(),
            default => new SpainTaxCalculator(),
        };
    }
}
