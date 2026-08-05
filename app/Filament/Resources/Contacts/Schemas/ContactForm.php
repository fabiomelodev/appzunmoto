<?php

namespace App\Filament\Resources\Contacts\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ContactForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('link')
                    ->label('Link')
                    ->helperText('URL de destino ao tocar no item. Ex.: mailto:suporte@giromoto.com.br, tel:+551140004000 ou https://wa.me/55...')
                    ->required(),
                Select::make('type')
                    ->options(['email' => 'Email', 'phone' => 'Phone', 'chat' => 'Chat'])
                    ->default('email')
                    ->required(),
                Select::make('status')
                    ->options(['active' => 'Active', 'inactive' => 'Inactive'])
                    ->default('active')
                    ->required(),
                TextInput::make('order')
                    ->required()
                    ->numeric()
                    ->default(0),
            ]);
    }
}
