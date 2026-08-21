<?php

namespace App\Orders\Domain;

use DomainException;

class InsufficientStockException extends DomainException
{
    public static function forProduct(string $name, int $available): self
    {
        return new self("Stock insuficiente para {$name} (disponible: {$available})");
    }
}
