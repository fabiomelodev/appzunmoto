<?php

namespace App\Filament\Resources\ExpectedVolumes\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ExpectedVolumeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Section::make('Conteúdo')
                    ->description('Usado no formulário de nova vaga, ao escolher o movimento esperado.')
                    ->columnSpan(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('Nome')
                            ->helperText('Rótulo exibido para o usuário. Ex.: 😌 Tranquilo (até 20 pedidos).')
                            ->required(),
                        TextInput::make('slug')
                            ->label('Slug')
                            ->helperText('Identificador estável usado internamente (sem espaços/acentos). Ex.: tranquilo.')
                            ->unique(ignoreRecord: true)
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
