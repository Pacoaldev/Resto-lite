<?php

namespace App\Orders\Domain;

final class RecipeCost
{
    /**
     * @param list<array{quantity: float|int|string, unit_cost: float|int|string}> $items
     */
    public static function theoretical(array $items): float
    {
        $total = 0.0;
        foreach ($items as $item) {
            $total += (float) $item['quantity'] * (float) $item['unit_cost'];
        }

        return round($total, 2);
    }

    public static function margin(float $salePrice, float $theoreticalCost): float
    {
        return round($salePrice - $theoreticalCost, 2);
    }
}
