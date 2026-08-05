<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ExpectedVolumeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $expectedVolumes = [
            ['name' => '😌 Tranquilo (até 20 pedidos)', 'slug' => 'tranquilo', 'order' => 1],
            ['name' => '⚡ Moderado (20–40 pedidos)', 'slug' => 'moderado', 'order' => 2],
            ['name' => '🔥 Pesado (40+ pedidos)', 'slug' => 'pesado', 'order' => 3],
        ];

        foreach ($expectedVolumes as $expectedVolume) {
            DB::table('expected_volumes')->insert($expectedVolume + [
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
