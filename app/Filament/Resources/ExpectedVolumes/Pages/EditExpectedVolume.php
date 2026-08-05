<?php

namespace App\Filament\Resources\ExpectedVolumes\Pages;

use App\Filament\Resources\ExpectedVolumes\ExpectedVolumeResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditExpectedVolume extends EditRecord
{
    protected static string $resource = ExpectedVolumeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
