<?php

namespace App\Orders\Domain;

interface TableRepositoryInterface
{
    /** @return list<array<string, mixed>> */
    public function all(): array;
}