<?php

namespace App\Orders\Infrastructure\Http;

use App\Orders\Application\GetRecipeUseCase;
use App\Orders\Application\ListRecipesUseCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class RecipeController extends Controller
{
    public function __construct(
        private ListRecipesUseCase $listRecipesUseCase,
        private GetRecipeUseCase $getRecipeUseCase
    ) {
    }

    public function index(): JsonResponse
    {
        return response()->json($this->listRecipesUseCase->execute());
    }

    public function show(int $id): JsonResponse
    {
        return response()->json($this->getRecipeUseCase->execute($id));
    }
}
