<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserAdminSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $admins = [
            [
                'name' => 'Fabio Melo',
                'email' => 'fabio.melo@giromoto.com.br',
                'password' => '123@giromoto456',
            ],
            [
                'name' => 'Gabriel Vinicius',
                'email' => 'gabriel.vinicius@giromoto.com.br',
                'password' => '123@giromoto456',
            ],
        ];

        foreach ($admins as $admin) {
            User::updateOrCreate(
                ['email' => $admin['email']],
                [
                    'name' => $admin['name'],
                    'password' => $admin['password'],
                    'is_admin' => true,
                    'email_verified_at' => now(),
                ],
            );
        }
    }
}
