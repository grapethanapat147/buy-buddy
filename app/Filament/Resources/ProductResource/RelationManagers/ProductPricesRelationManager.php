<?php

namespace App\Filament\Resources\ProductResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ProductPricesRelationManager extends RelationManager
{
    protected static string $relationship = 'prices';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('platform')->required()->maxLength(255),
            Forms\Components\TextInput::make('price')->numeric()->required()->prefix('฿'),
            Forms\Components\TextInput::make('url')->url()->maxLength(255),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('platform')
            ->columns([
                Tables\Columns\TextColumn::make('platform'),
                Tables\Columns\TextColumn::make('price')->money('THB'),
                Tables\Columns\TextColumn::make('url')->limit(30),
            ])
            ->headerActions([Tables\Actions\CreateAction::make()])
            ->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()]);
    }
}
