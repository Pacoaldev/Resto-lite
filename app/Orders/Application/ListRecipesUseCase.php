<?php

namespace App\Orders\Application;

use App\Orders\Domain\RecipeRepositoryInterface;

class ListRecipesUseCase
{
    public function __construct(
        private RecipeRepositoryInterface $recipeRepository
    ) {
    }

    public function execute(): array
    {
        return $this->recipeRepository->all();
    }
}
