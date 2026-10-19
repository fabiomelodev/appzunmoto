<?php

namespace App\Filament\Resources\AcquisitionChannels\Pages;

use App\Filament\Resources\AcquisitionChannels\AcquisitionChannelResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAcquisitionChannels extends ListRecords
{
    protected static string $resource = AcquisitionChannelResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
