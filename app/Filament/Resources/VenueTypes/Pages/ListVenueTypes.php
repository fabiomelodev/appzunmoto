<?php

namespace App\Filament\Resources\VenueTypes\Pages;

use App\Filament\Resources\VenueTypes\VenueTypeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListVenueTypes extends ListRecords
{
    protected static string $resource = VenueTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
