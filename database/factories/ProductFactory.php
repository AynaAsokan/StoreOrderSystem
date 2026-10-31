<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition()
    {
        return [
            'name' => $this->faker->word,
            'code' => $this->faker->unique()->bothify('PRD-####'),
            'price' => 1000,
            'tax_percentage' => 18,
            'stock' => 10,
        ];
    }
}