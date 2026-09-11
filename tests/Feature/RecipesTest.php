<?php

use App\Orders\Domain\RecipeRepositoryInterface;
use App\Orders\Infrastructure\Persistence\EloquentRecipeRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->app->bind(RecipeRepositoryInterface::class, EloquentRecipeRepository::class);

    DB::table('products')->insert([
        ['id' => 2, 'name' => 'Tostada', 'price' => 3.00, 'stock' => 50, 'created_at' => now(), 'updated_at' => now()],
        ['id' => 5, 'name' => 'Hamburguesa', 'price' => 12.00, 'stock' => 30, 'created_at' => now(), 'updated_at' => now()],
    ]);

    DB::table('recipes')->insert([
        ['id' => 1, 'product_id' => 2, 'name' => 'Tostada clasica', 'created_at' => now(), 'updated_at' => now()],
    ]);

    DB::table('recipe_items')->insert([
        ['recipe_id' => 1, 'ingredient_name' => 'Pan', 'quantity' => 2, 'unit' => 'ud', 'unit_cost' => 0.15, 'created_at' => now(), 'updated_at' => now()],
        ['recipe_id' => 1, 'ingredient_name' => 'Aceite', 'quantity' => 0.01, 'unit' => 'l', 'unit_cost' => 8.00, 'created_at' => now(), 'updated_at' => now()],
    ]);
});

it('GET /api/recipes lista escandallos con coste y margen', function () {
    $this->getJson('/api/recipes')
        ->assertOk()
        ->assertJsonCount(1)
        ->assertJsonPath('0.name', 'Tostada clasica')
        ->assertJsonPath('0.theoreticalCost', 0.38)
        ->assertJsonPath('0.margin', 2.62)
        ->assertJsonPath('0.items.0.ingredientName', 'Pan');
});

it('GET /api/recipes/{id} devuelve detalle', function () {
    $this->getJson('/api/recipes/1')
        ->assertOk()
        ->assertJsonPath('id', 1)
        ->assertJsonPath('salePrice', 3);
});

it('GET /api/recipes/{id} lanza 422 si no existe', function () {
    $this->getJson('/api/recipes/99')
        ->assertStatus(422)
        ->assertJsonPath('message', 'Receta no encontrada');
});
