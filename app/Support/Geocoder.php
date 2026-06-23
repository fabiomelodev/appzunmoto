<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;

/**
 * Best-effort address → coordinates lookup via OpenStreetMap Nominatim.
 * Always returns null on any failure (network, rate limit, not found), so
 * callers can store null lat/lng without breaking the flow.
 *
 * forAddress() tries a chain of queries from most precise (full street) to
 * coarsest (city centre), returning the first hit. In Brazil a full street +
 * house number frequently has no OSM match in smaller cities, while the CEP
 * and city queries almost always resolve — so the fallback chain is what makes
 * geocoding actually land coordinates instead of silently giving up.
 */
class Geocoder
{
    /** UF → full state name, used to disambiguate generic city names. */
    private const STATES = [
        'AC' => 'Acre', 'AL' => 'Alagoas', 'AP' => 'Amapá', 'AM' => 'Amazonas',
        'BA' => 'Bahia', 'CE' => 'Ceará', 'DF' => 'Distrito Federal', 'ES' => 'Espírito Santo',
        'GO' => 'Goiás', 'MA' => 'Maranhão', 'MT' => 'Mato Grosso', 'MS' => 'Mato Grosso do Sul',
        'MG' => 'Minas Gerais', 'PA' => 'Pará', 'PB' => 'Paraíba', 'PR' => 'Paraná',
        'PE' => 'Pernambuco', 'PI' => 'Piauí', 'RJ' => 'Rio de Janeiro', 'RN' => 'Rio Grande do Norte',
        'RS' => 'Rio Grande do Sul', 'RO' => 'Rondônia', 'RR' => 'Roraima', 'SC' => 'Santa Catarina',
        'SP' => 'São Paulo', 'SE' => 'Sergipe', 'TO' => 'Tocantins',
    ];

    /** Single free-form query against Nominatim. Returns null on any failure. */
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
                    'countrycodes' => 'br',
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

    /**
     * Resolve coordinates from structured address fields, trying progressively
     * looser queries. Returns the first match, or null if every attempt fails.
     */
    public static function forAddress(?string $street, ?string $number, ?string $district, ?string $city, ?string $cep): ?array
    {
        $cityName = self::cityName($city);
        $state = self::stateName($city);
        $cepFmt = self::formatCep($cep);
        $street = trim((string) $street);
        $number = trim((string) $number);

        return self::firstMatch([
            self::join([trim("$street $number"), $district, $cityName, $state, 'Brasil']),
            self::join([$street, $cityName, $state, 'Brasil']),
            $cepFmt ? "$cepFmt, Brasil" : '',
            self::join([$district, $cityName, $state, 'Brasil']),
            self::join([$cityName, $state, 'Brasil']),
        ]);
    }

    /**
     * Resolve coordinates from the loose fields a Shift stores (a one-line
     * address, a region/district, and an optional CEP). Used when backfilling
     * shifts that were created before geocoding worked.
     */
    public static function forShift(?string $addressLine, ?string $region, ?string $city, ?string $cep): ?array
    {
        $cityName = self::cityName($city);
        $state = self::stateName($city);
        $cepFmt = self::formatCep($cep);

        return self::firstMatch([
            self::join([$addressLine, $cityName, $state, 'Brasil']),
            $cepFmt ? "$cepFmt, Brasil" : '',
            self::join([$region, $cityName, $state, 'Brasil']),
            self::join([$cityName, $state, 'Brasil']),
        ]);
    }

    /** Try each query in order, returning the first that resolves. */
    private static function firstMatch(array $queries): ?array
    {
        foreach ($queries as $q) {
            $q = trim((string) $q);
            if ($q === '') {
                continue;
            }
            $coords = self::coordinates($q);
            if ($coords) {
                return $coords;
            }
        }

        return null;
    }

    /** Join non-empty parts with ", ". */
    private static function join(array $parts): string
    {
        return collect($parts)->map(fn ($p) => trim((string) $p))->filter()->join(', ');
    }

    /** "Mogi das Cruzes - SP" → "Mogi das Cruzes". */
    private static function cityName(?string $city): string
    {
        $city = trim((string) $city);
        if ($city === '') {
            return '';
        }

        // Strip a trailing " - UF" / " / UF" / ", UF" suffix.
        return trim(preg_replace('/[\s,\/-]+[A-Za-z]{2}\s*$/u', '', $city)) ?: $city;
    }

    /** "Mogi das Cruzes - SP" → "São Paulo" (empty when no UF is present). */
    private static function stateName(?string $city): string
    {
        if (preg_match('/([A-Za-z]{2})\s*$/u', trim((string) $city), $m)) {
            return self::STATES[strtoupper($m[1])] ?? '';
        }

        return '';
    }

    /** "08744090" → "08744-090" (returns '' when not 8 digits). */
    private static function formatCep(?string $cep): string
    {
        $digits = preg_replace('/\D/', '', (string) $cep);

        return strlen($digits) === 8 ? substr($digits, 0, 5).'-'.substr($digits, 5) : '';
    }
}
