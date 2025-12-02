<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Product;


class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        Product::create([
            'name' => 'Limited Edition Flash Sale Item',
            'price' => 99.99,
            'stock' => 100,
        ]);

        $this->command->info('Product seeded with 100 stock');

    }
}
