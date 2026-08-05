<?php

namespace App\Filament\Resources\Benefits\Pages;

use App\Filament\Resources\Benefits\BenefitResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBenefits extends ListRecords
{
    protected static string $resource = BenefitResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
