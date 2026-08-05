<?php

namespace App\Filament\Resources\VenueTypes\Pages;

use App\Filament\Resources\VenueTypes\VenueTypeResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditVenueType extends EditRecord
{
    protected static string $resource = VenueTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
