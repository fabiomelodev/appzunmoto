<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class VenueTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $venueTypes = [
            ['name' => 'Pizzaria', 'slug' => 'pizzaria', 'order' => 1],
            ['name' => 'Hambúrguer', 'slug' => 'hamburguer', 'order' => 2],
            ['name' => 'Japonês', 'slug' => 'japones', 'order' => 3],
            ['name' => 'Mercado', 'slug' => 'mercado', 'order' => 4],
            ['name' => 'Farmácia', 'slug' => 'farmacia', 'order' => 5],
            ['name' => 'Outro', 'slug' => 'outro', 'order' => 6],
        ];

        foreach ($venueTypes as $venueType) {
            DB::table('venue_types')->insert($venueType + [
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
