<?php

namespace App\Console\Commands;

use App\Models\Shift;
use App\Models\UserAddress;
use App\Support\City;
use App\Support\Geocoder;
use Illuminate\Console\Command;

/**
 * Fills in `shifts.city` for shifts created before shifts stored one. First tries the publisher's
 * own saved addresses (same coordinates → that address's city, no network); anything left is
 * located by reverse geocoding the shift's coordinates. Shifts with no usable coordinates stay
 * without a city. Safe to re-run: only shifts still without a city are touched.
 */
class BackfillShiftCities extends Command
{
    protected $signature = 'shifts:backfill-city {--limit=0 : Stop after this many shifts (0 = all)}';

    protected $description = 'Fill the city of shifts that do not have one yet (from the publisher\'s addresses, then reverse geocoding)';

    public function handle(): int
    {
        $query = Shift::whereNull('city')->whereNotNull('lat')->where('lat', '!=', 0)->orderBy('created_at');
        if ($limit = (int) $this->option('limit')) {
            $query->limit($limit);
        }

        $fromAddresses = 0;
        $fromMap = 0;
        $missed = 0;
        $cache = []; // rounded coordinate → city (shifts of the same venue repeat a lot)

        foreach ($query->get() as $shift) {
            $city = $this->cityFromPublisherAddresses($shift);
            if ($city) {
                $fromAddresses++;
            } else {
                $key = round((float) $shift->lat, 3).','.round((float) $shift->lng, 3);
                if (! array_key_exists($key, $cache)) {
                    $cache[$key] = Geocoder::reverseCity((float) $shift->lat, (float) $shift->lng);
                    usleep(1_100_000); // Nominatim: ~1 request/second
                }
                $city = $cache[$key];
                $city ? $fromMap++ : $missed++;
            }

            if ($city) {
                $shift->forceFill(['city' => $city])->saveQuietly();
            }
        }

        $this->info("Pelo endereço de quem publicou: {$fromAddresses} · pelo mapa: {$fromMap} · sem resultado: {$missed}");

        return self::SUCCESS;
    }

    private function cityFromPublisherAddresses(Shift $shift): ?string
    {
        $match = UserAddress::where('user_id', $shift->creator_id)
            ->whereNotNull('lat')
            ->whereBetween('lat', [(float) $shift->lat - 0.0005, (float) $shift->lat + 0.0005])
            ->whereBetween('lng', [(float) $shift->lng - 0.0005, (float) $shift->lng + 0.0005])
            ->first();

        $city = City::normalize($match?->city);

        return $city !== '' ? $city : null;
    }
}
