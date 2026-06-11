<?php

namespace App\Filament\Resources\Faqs\Schemas;

use Filament\Forms\Components\{DatePicker, RichEditor, Select, TextInput};
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class FaqForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(12)
            ->components([
                Section::make()
                    ->columnSpan(9)
                    ->schema([
                        TextInput::make('name')
                            ->label('Nome')
                            ->required(),
                        RichEditor::make('description')
                            ->label('Descrição')
                            ->required()
                            ->columnSpanFull(),
                    ]),
                Section::make()
                    ->columnSpan(3)
                    ->schema([
                        Select::make('status')
                            ->options(['active' => 'Ativo', 'inactive' => 'Inativo'])
                            ->default('active')
                            ->required(),
                        DatePicker::make('created_at')
                            ->label('Criado Em')
                            ->disabled()
                            ->visibleOn('edit'),
                    ])
            ]);
    }
}
