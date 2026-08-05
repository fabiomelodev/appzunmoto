<?php

namespace App\Filament\Resources\Benefits\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class BenefitForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Section::make('Conteúdo')
                    ->description('Usado no formulário de nova vaga, ao escolher os benefícios oferecidos.')
                    ->columnSpan(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('Nome')
                            ->helperText('Rótulo exibido para o usuário. Ex.: Lanche, Almoço, Combustível.')
                            ->required(),
                        TextInput::make('slug')
                            ->label('Slug')
                            ->helperText('Identificador estável usado internamente (sem espaços/acentos). Ex.: lanche.')
                            ->unique(ignoreRecord: true)
                            ->required(),
                        TextInput::make('icon')
                            ->label('Ícone')
                            ->helperText('Nome do ícone Lucide (lucide.dev) usado no app. Ex.: sandwich, utensils, fuel.'),
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
