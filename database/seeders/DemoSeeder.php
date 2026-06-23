<?php

namespace Database\Seeders;

use App\Models\Shift;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        // Two businesses and a courier (profiles are auto-created by UserObserver).
        $bella = $this->business('Pizzaria Bella', 'bella@demo.test', 'Centro', 'Rua Augusta, 1200');
        $sushi = $this->business('Sushi Yama', 'yama@demo.test', 'Pinheiros', 'Av. Faria Lima, 500');
        $burger = $this->business('Burger House', 'burger@demo.test', 'Zona Sul', 'Av. Santo Amaro, 900');

        $carlos = User::create(['name' => 'Carlos Entregas', 'email' => 'carlos@demo.test', 'password' => 'secret123']);
        $carlos->profile->update(['role' => 'courier', 'vehicle' => 'moto', 'city' => 'São Paulo']);

        $today = Carbon::today();

        $shifts = [
            [$bella, 'Pizzaria Bella', 'Centro', 'pizzaria', 'pesado', 160, [12, 18], ['moto'], ['lanche', 'combustivel'], true, 2, $today],
            [$sushi, 'Sushi Yama', 'Pinheiros', 'japones', 'moderado', 180, [10, 16], ['moto', 'bike-eletrica'], ['janta'], false, 1, $today],
            [$burger, 'Burger House', 'Zona Sul', 'hamburguer', 'pesado', 150, [9, 14], ['moto'], ['lanche', 'almoco'], true, 3, $today->copy()->addDay()],
            [$bella, 'Bella Express (Noturno)', 'Centro', 'pizzaria', 'moderado', 140, [8, 12], ['moto', 'bike-eletrica', 'bike'], ['lanche'], false, 1, $today->copy()->addDays(2)],
        ];

        foreach ($shifts as [$owner, $venue, $region, $type, $volume, $daily, $fee, $vehicles, $benefits, $bag, $needed, $date]) {
            Shift::create([
                'creator_id' => $owner->id,
                'creator_role' => 'business',
                'venue' => $venue,
                'region' => $region,
                'address' => $owner->profile->street ?? 'São Paulo',
                'date' => $date->toDateString(),
                'start_time' => '18:00',
                'end_time' => '23:00',
                'daily_rate' => $daily,
                'delivery_fee' => $fee[0],
                'delivery_fee_min' => $fee[0],
                'delivery_fee_max' => $fee[1],
                'venue_type' => $type,
                'expected_volume' => $volume,
                'benefits' => $benefits,
                'accepted_vehicles' => $vehicles,
                'requires_own_bag' => $bag,
                'couriers_needed' => $needed,
                'status' => 'available',
                'lat' => 0,
                'lng' => 0,
            ]);
        }

        // A courier-posted "shift coverage" request.
        Shift::create([
            'creator_id' => $carlos->id,
            'creator_role' => 'courier',
            'venue' => 'Cobertura — Carlos',
            'region' => 'Zona Sul',
            'address' => 'São Paulo',
            'date' => $today->copy()->addDay()->toDateString(),
            'start_time' => '19:00',
            'end_time' => '23:30',
            'daily_rate' => 120,
            'delivery_fee' => 9,
            'delivery_fee_min' => 9,
            'delivery_fee_max' => 14,
            'venue_type' => 'outro',
            'expected_volume' => 'tranquilo',
            'benefits' => [],
            'accepted_vehicles' => ['moto', 'bike-eletrica'],
            'requires_own_bag' => false,
            'couriers_needed' => 1,
            'status' => 'available',
            'lat' => 0,
            'lng' => 0,
        ]);
    }

    protected function business(string $name, string $email, string $city, string $street): User
    {
        $user = User::create(['name' => $name, 'email' => $email, 'password' => 'secret123']);
        $user->profile->update(['role' => 'business', 'city' => $city, 'street' => $street]);

        return $user;
    }
}
