<?php

namespace App\Orders\Domain;

interface ProductRepositoryInterface
{
    /** @return list<array<string, mixed>> */
    public function all(): array;
}