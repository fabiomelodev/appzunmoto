<?php

namespace App\Filament\Resources\Users\Tables;

use App\Models\AcquisitionChannel;
use App\Support\Acquisition;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->withCount('addresses')->with('acquisitionChannel'))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('name')
                    ->label('Nome')
                    ->searchable(),
                TextColumn::make('email')
                    ->label('E-mail')
                    ->searchable(),
                TextColumn::make('profile.role')
                    ->label('Perfil')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'courier' => 'Motoboy',
                        'business' => 'Estabelecimento',
                        default => $state,
                    })
                    ->colors([
                        'primary' => 'courier',
                        'success' => 'business',
                    ]),
                IconColumn::make('addresses_count')
                    ->label('Estabelecimento')
                    ->boolean()
                    ->getStateUsing(fn ($record) => $record->addresses_count > 0)
                    ->tooltip('Já tem algum estabelecimento cadastrado, independente do perfil ativo agora'),
                TextColumn::make('profile.phone')
                    ->label('Telefone')
                    ->placeholder('—'),
                TextColumn::make('profile.city')
                    ->label('Cidade')
                    ->placeholder('—'),
                TextColumn::make('acquisition')
                    ->label('Canal')
                    ->getStateUsing(fn ($record) => Acquisition::label($record->utm_source, $record->acquisitionChannel?->name))
                    ->badge()
                    ->color(fn ($state) => $state === 'Direto / não informado' ? 'gray' : 'info')
                    ->tooltip('De onde o usuário veio (link ou QR code com utm_source)'),
                TextColumn::make('heard_from')
                    ->label('Como conheceu')
                    ->formatStateUsing(fn ($state) => Acquisition::HEARD_FROM[$state] ?? $state)
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label('Cadastrado em')
                    ->dateTime('d/m/Y')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('channel')
                    ->label('Canal')
                    ->options(fn () => AcquisitionChannel::orderBy('name')->pluck('name', 'id')->all() + ['direct' => 'Direto / não informado'])
                    ->query(function (Builder $query, array $data): Builder {
                        $value = $data['value'] ?? null;

                        return match (true) {
                            $value === 'direct' => $query->whereNull('utm_source'),
                            filled($value) => $query->where('acquisition_channel_id', $value),
                            default => $query,
                        };
                    }),
                SelectFilter::make('role')
                    ->label('Perfil')
                    ->options([
                        'courier' => 'Motoboy',
                        'business' => 'Estabelecimento',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['value'] ?? null,
                            fn (Builder $q, $value) => $q->whereHas('profile', fn ($p) => $p->where('role', $value)),
                        );
                    }),
                TernaryFilter::make('has_establishment')
                    ->label('Tem estabelecimento')
                    ->placeholder('Todos')
                    ->trueLabel('Sim')
                    ->falseLabel('Não')
                    ->queries(
                        true: fn (Builder $query) => $query->whereHas('addresses'),
                        false: fn (Builder $query) => $query->whereDoesntHave('addresses'),
                    ),
            ])
            ->recordActions([
                ViewAction::make()
                    ->iconButton(),
            ]);
    }
}
