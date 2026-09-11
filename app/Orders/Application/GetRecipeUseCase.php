<?php

namespace App\Orders\Application;

use App\Orders\Domain\RecipeRepositoryInterface;

class GetRecipeUseCase
{
    public function __construct(
        private RecipeRepositoryInterface $recipeRepository
    ) {
    }

    public function execute(int $id): array
    {
        $recipe = $this->recipeRepository->findById($id);

        if ($recipe === null) {
            throw new \DomainException('Receta no encontrada');
        }

        return $recipe;
    }
}
