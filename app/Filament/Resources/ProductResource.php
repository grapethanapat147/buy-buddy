<?php

namespace App\Filament\Resources;

use App\Enums\ProductMode;
use App\Enums\ProductTier;
use App\Filament\Resources\ProductResource\Pages;
use App\Filament\Resources\ProductResource\RelationManagers;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('category_id')
                ->relationship('category', 'name')
                ->required(),
            Forms\Components\TextInput::make('name')->required()->maxLength(255),
            Forms\Components\TextInput::make('slug')->required()->maxLength(255)->unique(ignoreRecord: true),
            Forms\Components\Select::make('tier')
                ->options(collect(ProductTier::cases())->mapWithKeys(fn ($t) => [$t->value => $t->name]))
                ->required(),
            Forms\Components\Select::make('mode')
                ->options(collect(ProductMode::cases())->mapWithKeys(fn ($m) => [$m->value => $m->name]))
                ->required(),
            Forms\Components\TextInput::make('ref_price')->numeric()->required()->prefix('฿'),
            Forms\Components\TextInput::make('restock_cadence')->maxLength(255)
                ->helperText('e.g. weekly / monthly (restock items only)'),
            Forms\Components\TextInput::make('qty_scales_by')->maxLength(255)
                ->helperText('e.g. occupants (leave blank for fixed quantity)'),
            Forms\Components\Select::make('pairedProducts')
                ->label('Smart Bundle — often bought with')
                ->relationship('pairedProducts', 'name')
                ->multiple()
                ->preload(),
            Forms\Components\Repeater::make('triggers')
                ->schema([
                    Forms\Components\Select::make('field')
                        ->options(['cooking' => 'cooking', 'occupants' => 'occupants', 'room_type' => 'room_type'])
                        ->required(),
                    Forms\Components\Select::make('op')
                        ->options(['=' => '=', '>=' => '>=', 'in' => 'in'])
                        ->required(),
                    Forms\Components\TextInput::make('value')->required()
                        ->helperText('For "in", comma-separate (e.g. sometimes,often)'),
                ])
                ->helperText('Rules that make this product appear (ALL must match). Leave empty to always show.')
                ->default([])
                ->columns(3),
        ]);
    }

    public static function mutateTriggers(array $data): array
    {
        $data['triggers'] = collect($data['triggers'] ?? [])->map(function (array $rule) {
            if ($rule['op'] === 'in') {
                $rule['value'] = array_map('trim', explode(',', (string) $rule['value']));
            } elseif ($rule['field'] === 'occupants') {
                $rule['value'] = (int) $rule['value'];
            }

            return $rule;
        })->all();

        return $data;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('category.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('slug')
                    ->searchable(),
                Tables\Columns\TextColumn::make('tier')
                    ->searchable(),
                Tables\Columns\TextColumn::make('mode')
                    ->searchable(),
                Tables\Columns\TextColumn::make('ref_price')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('restock_cadence')
                    ->searchable(),
                Tables\Columns\TextColumn::make('qty_scales_by')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
