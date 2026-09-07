<?php

namespace App\Orders\Domain;

use DomainException;

class OrderNotFoundException extends DomainException
{
    public static function withId(int $id): self
    {
        return new self("Pedido {$id} no encontrado");
    }
}