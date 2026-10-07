<?php

namespace App\Console\Commands;

use App\Models\Profile;
use Illuminate\Console\Command;

/**
 * Locates couriers that signed up before the search radius existed (district/city only,
 * no coordinates), so "Vagas perto de mim" can apply to them. Run once after deploying;
 * safe to re-run — it only touches profiles that still have no base location.
 */
class GeocodeCourierBases extends Command
{
    protected $signature = 'profiles:geocode-base {--limit=0 : Stop after this many profiles (0 = all)}';

    protected $description = 'Geocode the base location (district/city) of couriers that have none yet';

    public function handle(): int
    {
        $query = Profile::where('role', 'courier')
            ->whereNull('base_lat')
            ->whereNotNull('city')->where('city', '!=', '');

        if ($limit = (int) $this->option('limit')) {
            $query->limit($limit);
        }

        $done = 0;
        $missed = 0;
        foreach ($query->get() as $profile) {
            $profile->refreshBaseLocation();
            $profile->refresh()->base_lat !== null ? $done++ : $missed++;
            usleep(1_100_000); // Nominatim: ~1 request/second
        }

        $this->info("Localizados: {$done} · sem resultado: {$missed}");

        return self::SUCCESS;
    }
}
