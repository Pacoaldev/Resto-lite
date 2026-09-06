<?php

namespace App\Orders\Application;

use App\Orders\Domain\TableRepositoryInterface;

class ListTablesUseCase
{
    public function __construct(
        private TableRepositoryInterface $tableRepository
    ) {
    }

    public function execute(): array
    {
        return $this->tableRepository->all();
    }
}