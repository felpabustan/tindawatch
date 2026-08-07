<?php

namespace App\Support;

class Money
{
    public static function toCentavos(int|float|string $pesos): int
    {
        return (int) round(((float) $pesos) * 100);
    }

    public static function toPesos(int $centavos): float
    {
        return round($centavos / 100, 2);
    }

    public static function format(int $centavos): string
    {
        return '₱'.number_format(self::toPesos($centavos), 2);
    }
}
