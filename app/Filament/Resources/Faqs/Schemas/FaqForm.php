<?php

namespace App\Filament\Resources\Faqs\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class FaqForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Section::make('Conteúdo')
                    ->description('Pergunta e resposta exibidas em "Perguntas frequentes" na tela de Ajuda.')
                    ->columnSpan(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('Pergunta')
                            ->required(),
                        RichEditor::make('description')
                            ->label('Resposta')
                            ->required(),
                    ]),
                Section::make('Publicação')
                    ->description('Visibilidade da pergunta.')
                    ->columnSpan(1)
                    ->schema([
                        Select::make('status')
                            ->label('Status')
                            ->options(['active' => 'Ativo', 'inactive' => 'Inativo'])
                            ->default('active')
                            ->required(),
                        DateTimePicker::make('created_at')
                            ->label('Criado Em')
                            ->disabled()
                            ->visibleOn('edit'),
                    ]),
            ]);
    }
}
