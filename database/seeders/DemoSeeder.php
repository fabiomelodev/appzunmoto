<?php

namespace Database\Seeders;

use App\Models\Application;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        // Businesses + couriers (profiles auto-created by UserObserver).
        $bella = $this->business('Pizzaria Bella', 'bella@demo.test', 'Centro', 'Rua Augusta, 1200');
        $sushi = $this->business('Sushi Yama', 'yama@demo.test', 'Pinheiros', 'Av. Faria Lima, 500');
        $burger = $this->business('Burger House', 'burger@demo.test', 'Zona Sul', 'Av. Santo Amaro, 900');

        $carlos = $this->courier('Carlos Entregas', 'carlos@demo.test');
        $ana = $this->courier('Ana Rápida', 'ana@demo.test');

        $today = Carbon::today();

        // [owner, venue, region, type, volume, daily, [feeMin,feeMax], vehicles, benefits, bag, needed, date, [lat,lng]]
        $shifts = [
            [$bella, 'Pizzaria Bella', 'Centro', 'pizzaria', 'pesado', 160, [12, 18], ['moto'], ['lanche', 'combustivel'], true, 2, $today, [-23.5505, -46.6420]],
            [$sushi, 'Sushi Yama', 'Pinheiros', 'japones', 'moderado', 180, [10, 16], ['moto', 'bike-eletrica'], ['janta'], false, 1, $today, [-23.5670, -46.6920]],
            [$burger, 'Burger House', 'Zona Sul', 'hamburguer', 'pesado', 150, [9, 14], ['moto'], ['lanche', 'almoco'], true, 3, $today->copy()->addDay(), [-23.6500, -46.7100]],
            [$bella, 'Bella Express (Noturno)', 'Centro', 'pizzaria', 'moderado', 140, [8, 12], ['moto', 'bike-eletrica', 'bike'], ['lanche'], false, 1, $today->copy()->addDays(2), [-23.5480, -46.6390]],
        ];

        $created = [];
        foreach ($shifts as [$owner, $venue, $region, $type, $volume, $daily, $fee, $vehicles, $benefits, $bag, $needed, $date, $coords]) {
            $created[] = Shift::create([
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
                'lat' => $coords[0],
                'lng' => $coords[1],
            ]);
        }

        // A courier-posted "shift coverage" request (also geolocated).
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
            'lat' => -23.6480,
            'lng' => -46.7080,
        ]);

        // Some interest so the "Parcerias" tab and notifications have data.
        Application::create(['shift_id' => $created[0]->id, 'user_id' => $carlos->id, 'status' => 'interested']);
        Application::create(['shift_id' => $created[0]->id, 'user_id' => $ana->id, 'status' => 'interested']);
        Application::create(['shift_id' => $created[1]->id, 'user_id' => $carlos->id, 'status' => 'interested']);
    }

    protected function business(string $name, string $email, string $city, string $street): User
    {
        $user = User::create(['name' => $name, 'email' => $email, 'password' => 'secret123']);
        $user->profile->update(['role' => 'business', 'city' => $city, 'street' => $street]);

        return $user;
    }

    protected function courier(string $name, string $email): User
    {
        $user = User::create(['name' => $name, 'email' => $email, 'password' => 'secret123']);
        $user->profile->update(['role' => 'courier', 'vehicle' => 'moto', 'city' => 'São Paulo', 'has_bag' => true]);

        return $user;
    }
}
