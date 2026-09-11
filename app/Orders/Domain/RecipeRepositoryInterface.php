<?php

namespace App\Orders\Domain;

interface RecipeRepositoryInterface
{
    /** @return list<array<string, mixed>> */
    public function all(): array;

    /** @return array<string, mixed>|null */
    public function findById(int $id): ?array;
}
