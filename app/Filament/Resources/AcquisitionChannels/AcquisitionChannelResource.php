<?php

namespace App\Filament\Resources\AcquisitionChannels;

use App\Filament\Resources\AcquisitionChannels\Pages\CreateAcquisitionChannel;
use App\Filament\Resources\AcquisitionChannels\Pages\EditAcquisitionChannel;
use App\Filament\Resources\AcquisitionChannels\Pages\ListAcquisitionChannels;
use App\Filament\Resources\AcquisitionChannels\Schemas\AcquisitionChannelForm;
use App\Filament\Resources\AcquisitionChannels\Tables\AcquisitionChannelsTable;
use App\Models\AcquisitionChannel;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class AcquisitionChannelResource extends Resource
{
    protected static ?string $model = AcquisitionChannel::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMegaphone;

    protected static string|UnitEnum|null $navigationGroup = 'Usuários';

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $label = 'Canal de aquisição';

    protected static ?string $pluralLabel = 'Canais de aquisição';

    public static function form(Schema $schema): Schema
    {
        return AcquisitionChannelForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AcquisitionChannelsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAcquisitionChannels::route('/'),
            'create' => CreateAcquisitionChannel::route('/create'),
            'edit' => EditAcquisitionChannel::route('/{record}/edit'),
        ];
    }
}
