<?php

namespace App\Filament\Resources\AcquisitionChannels\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AcquisitionChannelsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->withCount('users'))
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')->label('Nome')->searchable(),
                TextColumn::make('slug')->label('utm_source')->copyable()->searchable(),
                TextColumn::make('url')
                    ->label('Link')
                    ->getStateUsing(fn ($record) => $record->url())
                    ->copyable()
                    ->copyMessage('Link copiado')
                    ->toggleable(),
                TextColumn::make('users_count')->label('Usuários')->numeric()->sortable(),
                IconColumn::make('active')->label('Ativo')->boolean(),
            ])
            ->recordActions([
                EditAction::make()->iconButton(),
                DeleteAction::make()->iconButton(),
            ]);
    }
}
