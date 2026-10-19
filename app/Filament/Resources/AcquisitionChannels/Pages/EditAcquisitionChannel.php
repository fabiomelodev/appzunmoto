<?php

namespace App\Filament\Resources\AcquisitionChannels\Pages;

use App\Filament\Resources\AcquisitionChannels\AcquisitionChannelResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditAcquisitionChannel extends EditRecord
{
    protected static string $resource = AcquisitionChannelResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
