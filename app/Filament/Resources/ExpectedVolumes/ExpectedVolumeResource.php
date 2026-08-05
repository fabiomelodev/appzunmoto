<?php

namespace App\Filament\Resources\ExpectedVolumes;

use App\Filament\Resources\ExpectedVolumes\Pages\CreateExpectedVolume;
use App\Filament\Resources\ExpectedVolumes\Pages\EditExpectedVolume;
use App\Filament\Resources\ExpectedVolumes\Pages\ListExpectedVolumes;
use App\Filament\Resources\ExpectedVolumes\Schemas\ExpectedVolumeForm;
use App\Filament\Resources\ExpectedVolumes\Tables\ExpectedVolumesTable;
use App\Models\ExpectedVolume;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ExpectedVolumeResource extends Resource
{
    protected static ?string $model = ExpectedVolume::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $label = 'Movimento Esperado';

    protected static ?string $pluralLabel = 'Movimentos Esperados';

    public static function form(Schema $schema): Schema
    {
        return ExpectedVolumeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ExpectedVolumesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListExpectedVolumes::route('/'),
            'create' => CreateExpectedVolume::route('/create'),
            'edit' => EditExpectedVolume::route('/{record}/edit'),
        ];
    }
}
