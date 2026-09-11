<?php

namespace App\Orders\Infrastructure\Persistence;

use App\Orders\Domain\RecipeCost;
use App\Orders\Domain\RecipeRepositoryInterface;
use Illuminate\Support\Facades\DB;

class EloquentRecipeRepository implements RecipeRepositoryInterface
{
    public function all(): array
    {
        $recipes = DB::table('recipes')->orderBy('id')->get();

        return $recipes->map(fn ($row) => $this->mapRecipe($row))->all();
    }

    public function findById(int $id): ?array
    {
        $row = DB::table('recipes')->where('id', $id)->first();

        return $row ? $this->mapRecipe($row) : null;
    }

    private function mapRecipe(object $row): array
    {
        $items = DB::table('recipe_items')
            ->where('recipe_id', $row->id)
            ->orderBy('id')
            ->get()
            ->map(fn ($item) => [
                'id' => (int) $item->id,
                'ingredientName' => $item->ingredient_name,
                'quantity' => (float) $item->quantity,
                'unit' => $item->unit,
                'unitCost' => (float) $item->unit_cost,
            ])
            ->all();

        $costItems = array_map(
            fn (array $item) => ['quantity' => $item['quantity'], 'unit_cost' => $item['unitCost']],
            $items
        );

        $productPrice = (float) DB::table('products')->where('id', $row->product_id)->value('price');
        $theoreticalCost = RecipeCost::theoretical($costItems);

        return [
            'id' => (int) $row->id,
            'productId' => (int) $row->product_id,
            'name' => $row->name,
            'salePrice' => $productPrice,
            'theoreticalCost' => $theoreticalCost,
            'margin' => RecipeCost::margin($productPrice, $theoreticalCost),
            'items' => $items,
        ];
    }
}
