<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;

class ProductSeeder extends Seeder
{
    public function run()
    {
        Product::insert([
            [
                'name' => 'Laptop',
                'code' => 'PRD001',
                'price' => 50000,
                'tax_percentage' => 18,
                'stock' => 10,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Mouse',
                'code' => 'PRD002',
                'price' => 500,
                'tax_percentage' => 18,
                'stock' => 20,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Keyboard',
                'code' => 'PRD003',
                'price' => 1000,
                'tax_percentage' => 18,
                'stock' => 15,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}