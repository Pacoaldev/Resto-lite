<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Limpiar datos
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
