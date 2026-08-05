<?php

namespace App\Filament\Resources\VenueTypes;

use App\Filament\Resources\VenueTypes\Pages\CreateVenueType;
use App\Filament\Resources\VenueTypes\Pages\EditVenueType;
use App\Filament\Resources\VenueTypes\Pages\ListVenueTypes;
use App\Filament\Resources\VenueTypes\Schemas\VenueTypeForm;
use App\Filament\Resources\VenueTypes\Tables\VenueTypesTable;
use App\Models\VenueType;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class VenueTypeResource extends Resource
{
    protected static ?string $model = VenueType::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingStorefront;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $label = 'Tipo do Local';

    protected static ?string $pluralLabel = 'Tipos do Local';

    public static function form(Schema $schema): Schema
    {
        return VenueTypeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return VenueTypesTable::configure($table);
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
            'index' => ListVenueTypes::route('/'),
            'create' => CreateVenueType::route('/create'),
            'edit' => EditVenueType::route('/{record}/edit'),
        ];
    }
}
