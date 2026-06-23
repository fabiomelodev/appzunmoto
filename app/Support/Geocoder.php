<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;

/**
 * Best-effort address → coordinates lookup via OpenStreetMap Nominatim.
 * Always returns null on any failure (network, rate limit, not found), so
 * callers can store null lat/lng without breaking the flow.
 */
class Geocoder
{
    public static function coordinates(string $query): ?array
    {
        if (trim($query) === '') {
            return null;
        }

        try {
            $response = Http::withHeaders(['User-Agent' => 'MotoReserva/1.0 (contato@motoreserva.app)'])
                ->timeout(8)
                ->get('https://nominatim.openstreetmap.org/search', [
                    'format' => 'jsonv2',
                    'limit' => 1,
                    'q' => $query,
                ]);

            $data = $response->json();
            if (is_array($data) && isset($data[0]['lat'], $data[0]['lon'])) {
                return ['lat' => (float) $data[0]['lat'], 'lng' => (float) $data[0]['lon']];
            }
        } catch (\Throwable $e) {
            // best-effort: ignore and return null
        }

        return null;
    }

    public static function forAddress(?string $street, ?string $number, ?string $district, ?string $city, ?string $cep): ?array
    {
        $query = collect([
            trim(($street ?? '').' '.($number ?? '')),
            $district,
            $city,
            $cep,
            'Brasil',
        ])->filter()->join(', ');

        return self::coordinates($query);
    }
}
