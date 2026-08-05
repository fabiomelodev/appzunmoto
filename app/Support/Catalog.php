<?php

namespace App\Support;

use App\Models\Benefit;
use App\Models\ExpectedVolume;
use App\Models\VenueType;

/**
 * Domain taxonomy and Portuguese labels (ported from the React `lib/types.ts`).
 * Data slugs are kept identical to the original app; only code is in English.
 *
 * Vehicle and contact-type taxonomy stay static (fixed by the domain). Venue
 * type, expected volume and benefits are admin-managed (see the matching
 * models/Filament resources) — their accessors below query the database.
 */
class Catalog
{
    public const VEHICLE_OPTIONS = ['moto', 'bike-eletrica', 'bike'];

    public const VEHICLE_LABEL = [
        'moto' => 'Moto',
        'bike-eletrica' => 'Moto Elétrica / Bicicleta Motorizada',
        'bike' => 'Bicicleta Convencional',
    ];

    public const VEHICLE_LABEL_SHORT = [
        'moto' => 'Moto',
        'bike-eletrica' => 'M. Elétrica / Bike M.',
        'bike' => 'Bike Convencional',
    ];

    public const VEHICLE_HINT = [
        'moto' => 'Combustão · maior alcance',
        'bike-eletrica' => 'Bateria/Combustão · médio alcance',
        'bike' => 'Sem motor · entregas próximas',
    ];

    /** Maps a vehicle slug to a Lucide icon name (see <x-ui.icon>). */
    public const VEHICLE_ICON = [
        'moto' => 'bike',
        'bike-eletrica' => 'zap',
        'bike' => 'bike',
    ];

    /** Maps a contact type (`contacts.type`) to a Lucide icon name (see <x-ui.icon>). */
    public const CONTACT_TYPE_ICON = [
        'email' => 'mail',
        'phone' => 'phone',
        'chat' => 'message-circle',
    ];

    /** Human label for a set of accepted vehicles. */
    public static function vehiclesLabel(array $vehicles): string
    {
        $vehicles = array_values($vehicles);

        if (empty($vehicles) || count($vehicles) === 3) {
            return 'Todos os veículos';
        }
        if (count($vehicles) === 1) {
            return 'Apenas '.(self::VEHICLE_LABEL[$vehicles[0]] ?? $vehicles[0]);
        }

        return implode(' + ', array_map(fn ($v) => self::VEHICLE_LABEL[$v] ?? $v, $vehicles));
    }

    /** Active venue types (slug => name), ordered — the selectable options in the shift form. */
    public static function venueTypes(): array
    {
        return VenueType::active()->orderBy('order')->pluck('name', 'slug')->all();
    }

    /** All venue type labels (slug => name), regardless of status — so old shifts keep displaying correctly. */
    public static function allVenueTypeLabels(): array
    {
        return VenueType::pluck('name', 'slug')->all();
    }

    /** Label for a venue type slug, regardless of its current active status. */
    public static function venueTypeLabel(?string $slug): ?string
    {
        return $slug ? (self::allVenueTypeLabels()[$slug] ?? $slug) : null;
    }

    /** Active expected-volume options (slug => name), ordered — the selectable options in the shift form. */
    public static function expectedVolumes(): array
    {
        return ExpectedVolume::active()->orderBy('order')->pluck('name', 'slug')->all();
    }

    /** All expected-volume labels (slug => name), regardless of status. */
    public static function allExpectedVolumeLabels(): array
    {
        return ExpectedVolume::pluck('name', 'slug')->all();
    }

    /** Label for an expected-volume slug, regardless of its current active status. */
    public static function volumeLabel(?string $slug): ?string
    {
        return $slug ? (self::allExpectedVolumeLabels()[$slug] ?? $slug) : null;
    }

    /** Active benefits, ordered — each as ['slug' => ..., 'name' => ..., 'icon' => ...]. */
    public static function benefits(): array
    {
        return Benefit::active()->orderBy('order')->get(['slug', 'name', 'icon'])
            ->map(fn (Benefit $b) => ['slug' => $b->slug, 'name' => $b->name, 'icon' => $b->icon])
            ->all();
    }

    /** Active benefit slugs only — used to sanitize form/filter submissions. */
    public static function benefitOptions(): array
    {
        return Benefit::active()->pluck('slug')->all();
    }

    /** All benefits (slug => ['name' => ..., 'icon' => ...]), regardless of status. */
    public static function allBenefitMeta(): array
    {
        return Benefit::get(['slug', 'name', 'icon'])
            ->keyBy('slug')
            ->map(fn (Benefit $b) => ['name' => $b->name, 'icon' => $b->icon])
            ->all();
    }

    /** Label for a benefit slug, regardless of its current active status. */
    public static function benefitLabel(string $slug): string
    {
        return self::allBenefitMeta()[$slug]['name'] ?? $slug;
    }

    /** Icon for a benefit slug, regardless of its current active status. */
    public static function benefitIcon(string $slug): string
    {
        return self::allBenefitMeta()[$slug]['icon'] ?? 'check';
    }
}
