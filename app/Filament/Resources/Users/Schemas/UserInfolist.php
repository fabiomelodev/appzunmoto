<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Support\Catalog;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UserInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Dados da conta')
                ->columns(2)
                ->schema([
                    TextEntry::make('name')
                        ->label('Nome'),
                    TextEntry::make('email')
                        ->label('E-mail'),
                    TextEntry::make('profile.role')
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
                    TextEntry::make('profile.phone')
                        ->label('Telefone')
                        ->placeholder('—'),
                    TextEntry::make('acquisition')
                        ->label('Canal de origem')
                        ->state(fn ($record) => \App\Support\Acquisition::label($record->utm_source, $record->acquisitionChannel?->name))
                        ->badge()
                        ->color('info'),
                    TextEntry::make('heard_from')
                        ->label('Como conheceu')
                        ->formatStateUsing(fn ($state) => \App\Support\Acquisition::HEARD_FROM[$state] ?? $state)
                        ->placeholder('—'),
                    TextEntry::make('profile.birth_date')
                        ->label('Data de nascimento')
                        ->date('d/m/Y')
                        ->placeholder('—'),
                    TextEntry::make('profile.vehicle')
                        ->label('Veículo')
                        ->formatStateUsing(fn ($state) => $state ? (Catalog::VEHICLE_LABEL[$state] ?? $state) : '—'),
                    TextEntry::make('profile.city')
                        ->label('Cidade base')
                        ->placeholder('—'),
                    TextEntry::make('created_at')
                        ->label('Cadastrado em')
                        ->dateTime('d/m/Y H:i'),
                ]),
            Section::make('Estabelecimentos')
                ->description('Endereços cadastrados por esta conta, independente do perfil ativo agora.')
                ->schema([
                    RepeatableEntry::make('addresses')
                        ->label('Endereços')
                        ->columns(2)
                        ->schema([
                            TextEntry::make('label')
                                ->label('Apelido'),
                            TextEntry::make('city')
                                ->label('Endereço')
                                ->formatStateUsing(fn ($state, $record) => trim("{$record->street}, {$record->number} — {$record->district}, {$state}")),
                        ]),
                ])
                ->visible(fn ($record) => $record->addresses()->exists()),
        ]);
    }
}
