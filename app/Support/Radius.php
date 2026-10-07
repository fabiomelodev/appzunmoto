<?php

namespace App\Support;

use App\Models\Profile;
use App\Models\Shift;

/**
 * "Vagas perto de mim": straight-line distance between a courier's base location
 * (district/city, geocoded) and a shift, against the courier's chosen radius.
 *
 * Deliberately lenient — a shift is hidden ONLY when both ends have usable
 * coordinates and are farther apart than the radius. Missing/zeroed coordinates
 * (failed geocoding) never hide anything.
 */
class Radius
{
    public const DEFAULT_KM = 15;

    public const MIN_KM = 5;

    public const MAX_KM = 20;

    public static function clamp(int $km): int
    {
        return max(self::MIN_KM, min(self::MAX_KM, $km));
    }

    /** Great-circle distance in km (haversine). */
    public static function distanceKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $toRad = fn (float $d) => $d * M_PI / 180;
        $dLat = $toRad($lat2 - $lat1);
        $dLng = $toRad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2 + cos($toRad($lat1)) * cos($toRad($lat2)) * sin($dLng / 2) ** 2;

        return 6371 * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    /** Whether the radius filter applies to this profile at all (courier with a located base). */
    public static function applies(?Profile $profile): bool
    {
        return $profile
            && $profile->isCourier()
            && $profile->base_lat !== null
            && $profile->base_lng !== null
            && (float) $profile->base_lat !== 0.0;
    }

    /** True when the shift is inside the profile's radius, or can't be judged (no coordinates). */
    public static function includes(Profile $profile, Shift $shift): bool
    {
        if (! self::hasCoordinates($shift->lat, $shift->lng)) {
            return true;
        }

        return self::distanceKm((float) $profile->base_lat, (float) $profile->base_lng, (float) $shift->lat, (float) $shift->lng)
            <= (int) ($profile->radius_km ?: self::DEFAULT_KM);
    }

    public static function hasCoordinates($lat, $lng): bool
    {
        return $lat !== null && $lng !== null && ((float) $lat !== 0.0 || (float) $lng !== 0.0);
    }
}
