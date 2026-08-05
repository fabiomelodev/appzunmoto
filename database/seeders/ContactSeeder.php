<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ContactSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('contacts')->insert([
            [
                'name' => 'Iniciar chat com o suporte',
                'link' => 'https://google.com',
                'type' => 'chat',
                'status' => 'active',
                'order' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            [
                'name' => 'suporte@giromoto.com.br',
                'link' => 'mailto:suporte@giromoto.com.br',
                'type' => 'email',
                'status' => 'active',
                'order' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            [
                'name' => '(11) 4000-4000',
                'link' => 'tel:+551140004000',
                'type' => 'phone',
                'status' => 'active',
                'order' => 3,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
