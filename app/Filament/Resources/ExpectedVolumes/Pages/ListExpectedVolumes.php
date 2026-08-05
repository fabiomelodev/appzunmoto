<?php

namespace App\Filament\Resources\ExpectedVolumes\Pages;

use App\Filament\Resources\ExpectedVolumes\ExpectedVolumeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListExpectedVolumes extends ListRecords
{
    protected static string $resource = ExpectedVolumeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
