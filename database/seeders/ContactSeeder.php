<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
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
                'name' => 'suporte@motoreserva.com.br',
                'link' => 'https://google.com',
                'type' => 'email',
                'status' => 'active',
                'order' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            [
                'name' => '(xx) 99999-9999',
                'link' => 'https://google.com',
                'type' => 'phone',
                'status' => 'active',
                'order' => 3,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
