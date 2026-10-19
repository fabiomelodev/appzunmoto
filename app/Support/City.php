<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Cities are typed by people ("Mogi das Cruzes - SP", "mogi das cruzes", "Mogi das Cruzes/SP"),
 * so shifts and profiles are compared through a normalised name and a lookup key.
 */
class City
{
    /** "Mogi das Cruzes - SP" → "Mogi das Cruzes" (drops a trailing UF; keeps the typed capitalisation). */
    public static function normalize(?string $city): string
    {
        $city = trim((string) $city);
        if ($city === '') {
            return '';
        }

        $name = trim(preg_replace('/[\s,\/-]+[A-Za-z]{2}\s*$/u', '', $city));

        return $name !== '' ? $name : $city;
    }

    /** Case- and accent-insensitive key for comparing two spellings of the same city. */
    public static function key(?string $city): string
    {
        return Str::lower(Str::ascii(self::normalize($city)));
    }

    public static function same(?string $a, ?string $b): bool
    {
        $ka = self::key($a);

        return $ka !== '' && $ka === self::key($b);
    }
}
