<?php

namespace App\Orders\Infrastructure\Persistence;

use App\Orders\Domain\TableRepositoryInterface;
use Illuminate\Support\Facades\DB;

class EloquentTableRepository implements TableRepositoryInterface
{
    public function all(): array
    {
        return DB::table('tables')->get()->map(fn ($row) => (array) $row)->all();
    }
}