<?php

namespace App\Orders\Infrastructure\Tax;

use App\Orders\Domain\TaxCalculatorInterface;

class PeruTaxCalculator implements TaxCalculatorInterface
{
    public function calculate(float $subtotal): float
    {
        return round($subtotal * $this->getRate(), 2);
    }

    public function getLabel(): string
    {
        return 'IGV';
    }

    public function getRate(): float
    {
        return 0.18;
    }
}
