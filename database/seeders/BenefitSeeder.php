<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BenefitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $benefits = [
            ['name' => 'Lanche', 'slug' => 'lanche', 'icon' => 'sandwich', 'order' => 1],
            ['name' => 'Almoço', 'slug' => 'almoco', 'icon' => 'utensils', 'order' => 2],
            ['name' => 'Janta', 'slug' => 'janta', 'icon' => 'drumstick', 'order' => 3],
            ['name' => 'Combustível', 'slug' => 'combustivel', 'icon' => 'fuel', 'order' => 4],
        ];

        foreach ($benefits as $benefit) {
            DB::table('benefits')->insert($benefit + [
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
