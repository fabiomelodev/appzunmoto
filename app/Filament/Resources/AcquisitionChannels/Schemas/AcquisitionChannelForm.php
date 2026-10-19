<?php

namespace App\Filament\Resources\AcquisitionChannels\Schemas;

use App\Support\Acquisition;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AcquisitionChannelForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Canal')
                ->description('De onde vêm os usuários: cada canal tem um link (e QR code) com o seu código em ?utm_source=.')
                ->schema([
                    TextInput::make('name')
                        ->label('Nome')
                        ->helperText('Como aparece nos relatórios (ex.: TV, Panfleto).')
                        ->required()
                        ->maxLength(100),
                    TextInput::make('slug')
                        ->label('Código no link (utm_source)')
                        ->helperText('Minúsculas, sem espaços (letras, números, - e _). Depois de impresso em QR codes, não mude.')
                        ->required()
                        ->maxLength(64)
                        ->unique(ignoreRecord: true)
                        ->dehydrateStateUsing(fn (?string $state) => Acquisition::normalize($state))
                        ->live(onBlur: true),
                    TextInput::make('link')
                        ->label('Link para o QR code')
                        ->helperText('Use este link nos panfletos, vídeos e anúncios deste canal.')
                        ->disabled()
                        ->dehydrated(false)
                        ->formatStateUsing(fn ($record) => $record?->url())
                        ->placeholder(fn ($get) => rtrim((string) config('app.url'), '/').'?utm_source='.(Acquisition::normalize((string) $get('slug')) ?: 'codigo'))
                        ->visibleOn(['edit', 'create']),
                    Toggle::make('active')
                        ->label('Ativo')
                        ->helperText('Canais desativados deixam de ser reconhecidos em novos cadastros (aparecem como "Outro").')
                        ->default(true),
                ]),
        ]);
    }
}
