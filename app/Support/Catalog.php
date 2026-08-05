<?php

namespace App\Support;

/**
 * Domain taxonomy and Portuguese labels (ported from the React `lib/types.ts`).
 * Data slugs are kept identical to the original app; only code is in English.
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

    public const BENEFIT_OPTIONS = ['lanche', 'almoco', 'janta', 'combustivel'];

    public const BENEFIT_LABEL = [
        'lanche' => 'Lanche',
        'almoco' => 'Almoço',
        'janta' => 'Janta',
        'combustivel' => 'Combustível',
    ];

    public const BENEFIT_ICON = [
        'lanche' => 'sandwich',
        'almoco' => 'utensils',
        'janta' => 'drumstick',
        'combustivel' => 'fuel',
    ];

    public const VENUE_TYPE_LABEL = [
        'pizzaria' => 'Pizzaria',
        'hamburguer' => 'Hambúrguer',
        'japones' => 'Japonês',
        'mercado' => 'Mercado',
        'farmacia' => 'Farmácia',
        'outro' => 'Outro',
    ];

    public const VOLUME_LABEL = [
        'tranquilo' => '😌 Tranquilo (até 20 pedidos)',
        'moderado' => '⚡ Moderado (20–40 pedidos)',
        'pesado' => '🔥 Pesado (40+ pedidos)',
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
}
