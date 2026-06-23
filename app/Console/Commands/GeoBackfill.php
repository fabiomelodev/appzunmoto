<?php

namespace App\Console\Commands;

use App\Models\Shift;
use App\Models\UserAddress;
use App\Support\Geocoder;
use Illuminate\Console\Command;

class GeoBackfill extends Command
{
    protected $signature = 'geo:backfill {--force : Re-geocode rows that already have coordinates}';

    protected $description = 'Fill missing lat/lng on saved addresses and shifts via Nominatim';

    public function handle(): int
    {
        $this->backfillAddresses();
        $this->backfillShifts();

        $this->info('Done.');

        return self::SUCCESS;
    }

    private function backfillAddresses(): void
    {
        $query = UserAddress::query()
            ->when(! $this->option('force'), fn ($q) => $q->where(fn ($w) => $w->whereNull('lat')->orWhere('lat', 0)));

        $rows = $query->get();
        $this->line("Endereços a processar: {$rows->count()}");

        foreach ($rows as $a) {
            $coords = Geocoder::forAddress($a->street, $a->number, $a->district, $a->city, $a->postal_code);
            if ($coords) {
                $a->update(['lat' => $coords['lat'], 'lng' => $coords['lng']]);
                $this->info("  ✓ {$a->label}: {$coords['lat']}, {$coords['lng']}");
            } else {
                $this->warn("  ✗ {$a->label}: não resolvido");
            }
            usleep(1_100_000); // respect Nominatim's 1 req/s policy
        }
    }

    private function backfillShifts(): void
    {
        $query = Shift::query()
            ->when(! $this->option('force'), fn ($q) => $q->where(fn ($w) => $w->whereNull('lat')->orWhere('lat', 0)));

        $rows = $query->get();
        $this->line("Vagas a processar: {$rows->count()}");

        foreach ($rows as $s) {
            $coords = Geocoder::forShift($s->address, $s->region, null, $s->postal_code);
            if ($coords) {
                $s->update(['lat' => $coords['lat'], 'lng' => $coords['lng']]);
                $this->info("  ✓ {$s->venue}: {$coords['lat']}, {$coords['lng']}");
            } else {
                $this->warn("  ✗ {$s->venue}: não resolvido");
            }
            usleep(1_100_000); // respect Nominatim's 1 req/s policy
        }
    }
}
