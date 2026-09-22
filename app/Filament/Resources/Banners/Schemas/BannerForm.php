<?php

namespace App\Filament\Resources\Banners\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class BannerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Section::make('Conteúdo')
                    ->description('Exibido no carrossel da tela de Vagas, no lugar do texto de boas-vindas.')
                    ->columnSpan(2)
                    ->schema([
                        FileUpload::make('image')
                            ->label('Imagem')
                            ->helperText('Recomendado: proporção larga (ex.: 16:9), até 4 MB.')
                            ->image()
                            ->disk('public')
                            ->directory('banners')
                            ->visibility('public')
                            ->required(),
                        TextInput::make('title')
                            ->label('Título (opcional)')
                            ->helperText('Usado como texto alternativo da imagem — não é exibido no carrossel.')
                            ->maxLength(255),
                        TextInput::make('link_url')
                            ->label('Link (opcional)')
                            ->helperText('Para onde o usuário vai ao tocar no banner. Deixe em branco para um banner sem link.')
                            ->url()
                            ->maxLength(2048)
                            ->live(),
                        Toggle::make('open_in_new_tab')
                            ->label('Abrir em nova aba')
                            ->helperText('Se desligado, substitui a tela atual. Em apps instalados (PWA), links "em nova aba" tendem a abrir no navegador do aparelho em vez de dentro do app.')
                            ->default(false)
                            ->visible(fn ($get) => filled($get('link_url'))),
                    ]),
                Section::make('Publicação')
                    ->description('Visibilidade e ordem de exibição.')
                    ->columnSpan(1)
                    ->schema([
                        Select::make('status')
                            ->label('Status')
                            ->options(['active' => 'Ativo', 'inactive' => 'Inativo'])
                            ->default('active')
                            ->required(),
                        TextInput::make('order')
                            ->label('Ordem')
                            ->helperText('Ordem de exibição no carrossel (menor aparece primeiro).')
                            ->required()
                            ->numeric()
                            ->default(0),
                    ]),
            ]);
    }
}
