<?php

namespace App\Orders\Infrastructure\Tax;

use App\Orders\Domain\TaxCalculatorInterface;

class ChileTaxCalculator implements TaxCalculatorInterface
{
    public function calculate(float $subtotal): float
    {
        return round($subtotal * $this->getRate(), 2);
    }

    public function getLabel(): string
    {
        return 'IVA';
    }

    public function getRate(): float
    {
        return 0.19;
    }
}
