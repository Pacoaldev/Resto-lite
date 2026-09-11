<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Limpiar datos (orden por FKs)
        if (Schema::hasTable('stock_movements')) {
            DB::table('stock_movements')->truncate();
        }
        if (Schema::hasTable('recipe_items')) {
            DB::table('recipe_items')->delete();
        }
        if (Schema::hasTable('recipes')) {
            DB::table('recipes')->delete();
        }
        DB::table('orders')->truncate();
        DB::table('tables')->delete();
        DB::table('products')->delete();

        // Población de Mesas (status must match seeded orders below)
        DB::table('tables')->insert([
            ['id' => 1, 'name' => 'Mesa 1', 'status' => 'free', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'name' => 'Mesa 2', 'status' => 'occupied', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'name' => 'Mesa 3', 'status' => 'free', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 4, 'name' => 'Mesa 4', 'status' => 'billRequested', 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Población de Productos/Menú
        DB::table('products')->insert([
            ['id' => 1, 'name' => 'Café', 'price' => 2.50, 'stock' => 99, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'name' => 'Tostada', 'price' => 3.00, 'stock' => 50, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'name' => 'Zumo natural', 'price' => 4.00, 'stock' => 20, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 4, 'name' => 'Cerveza', 'price' => 2.80, 'stock' => 150, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'Hamburguesa', 'price' => 12.00, 'stock' => 30, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Escandallos básicos
        DB::table('recipes')->insert([
            ['id' => 1, 'product_id' => 2, 'name' => 'Tostada clásica', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'product_id' => 5, 'name' => 'Hamburguesa casa', 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('recipe_items')->insert([
            ['recipe_id' => 1, 'ingredient_name' => 'Pan de molde', 'quantity' => 2, 'unit' => 'rebanada', 'unit_cost' => 0.15, 'created_at' => now(), 'updated_at' => now()],
            ['recipe_id' => 1, 'ingredient_name' => 'Aceite de oliva', 'quantity' => 0.01, 'unit' => 'l', 'unit_cost' => 8.00, 'created_at' => now(), 'updated_at' => now()],
            ['recipe_id' => 1, 'ingredient_name' => 'Tomate', 'quantity' => 0.05, 'unit' => 'kg', 'unit_cost' => 2.50, 'created_at' => now(), 'updated_at' => now()],
            ['recipe_id' => 2, 'ingredient_name' => 'Pan burger', 'quantity' => 1, 'unit' => 'ud', 'unit_cost' => 0.40, 'created_at' => now(), 'updated_at' => now()],
            ['recipe_id' => 2, 'ingredient_name' => 'Carne vacuno', 'quantity' => 0.15, 'unit' => 'kg', 'unit_cost' => 12.00, 'created_at' => now(), 'updated_at' => now()],
            ['recipe_id' => 2, 'ingredient_name' => 'Queso cheddar', 'quantity' => 0.03, 'unit' => 'kg', 'unit_cost' => 9.00, 'created_at' => now(), 'updated_at' => now()],
            ['recipe_id' => 2, 'ingredient_name' => 'Lechuga', 'quantity' => 0.02, 'unit' => 'kg', 'unit_cost' => 3.00, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Demo orders so occupied / billRequested tables are actionable
        DB::table('orders')->insert([
            [
                'tableId' => 2,
                'status' => 'open',
                'items' => json_encode([
                    ['name' => 'Café', 'price' => 2.50, 'quantity' => 2],
                    ['name' => 'Tostada', 'price' => 3.00, 'quantity' => 1],
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'tableId' => 4,
                'status' => 'sent',
                'items' => json_encode([
                    ['name' => 'Hamburguesa', 'price' => 12.00, 'quantity' => 1],
                    ['name' => 'Cerveza', 'price' => 2.80, 'quantity' => 2],
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
