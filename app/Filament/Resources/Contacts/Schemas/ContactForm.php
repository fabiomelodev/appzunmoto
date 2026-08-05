<?php

namespace App\Filament\Resources\Contacts\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ContactForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Section::make('Conteúdo')
                    ->description('Informações exibidas em "Falar com o suporte" na tela de Ajuda.')
                    ->columnSpan(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('Nome')
                            ->helperText('Texto exibido no item. Ex.: suporte@giromoto.com.br.')
                            ->required(),
                        Select::make('type')
                            ->label('Tipo')
                            ->options(['email' => 'E-mail', 'phone' => 'Telefone', 'chat' => 'Chat'])
                            ->default('email')
                            ->required(),
                        TextInput::make('link')
                            ->label('Link')
                            ->helperText('URL de destino ao tocar no item. Ex.: mailto:suporte@giromoto.com.br, tel:+551140004000 ou https://wa.me/55...')
                            ->required(),
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
                            ->helperText('Ordem de exibição na lista (menor aparece primeiro).')
                            ->required()
                            ->numeric()
                            ->default(0),
                    ]),
            ]);
    }
}
