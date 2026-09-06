<?php

namespace App\Orders\Application;

use App\Orders\Domain\ProductRepositoryInterface;

class ListProductsUseCase
{
    public function __construct(
        private ProductRepositoryInterface $productRepository
    ) {
    }

    public function execute(): array
    {
        return $this->productRepository->all();
    }
}