<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Concerns\HasSchoolAccessOnly;
use App\Filament\Resources\CanteenMenuResource\Pages;
use App\Models\CanteenMenu;
use Filament\Actions;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class CanteenMenuResource extends Resource
{
    use HasSchoolAccessOnly;

    protected static ?string $model = CanteenMenu::class;

    protected static string|UnitEnum|null $navigationGroup = 'E-Kantin Sekolah';

    protected static ?string $navigationLabel = 'Katalog Menu Kantin';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cake';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Select::make('canteen_stand_id')
                    ->label('Stand Kantin')
                    ->relationship('stand', 'name')
                    ->searchable()
                    ->required(),
                TextInput::make('name')
                    ->label('Nama Menu Makanan / Minuman')
                    ->placeholder('Contoh: Nasi Goreng Spesial Telur')
                    ->required()
                    ->maxLength(100),
                Select::make('category')
                    ->label('Kategori')
                    ->options([
                        'food' => 'Makanan Utama',
                        'drink' => 'Minuman',
                        'snack' => 'Camilan / Snack',
                        'dessert' => 'Dessert / Buah',
                    ])
                    ->default('food')
                    ->required(),
                TextInput::make('price')
                    ->label('Harga Jual (Rp)')
                    ->numeric()
                    ->prefix('Rp')
                    ->required(),
                TextInput::make('cost_price')
                    ->label('Harga Modal / Pokok (Rp)')
                    ->numeric()
                    ->prefix('Rp')
                    ->default(0),
                TextInput::make('stock')
                    ->label('Jumlah Stok Harian')
                    ->numeric()
                    ->default(50)
                    ->required(),
                Toggle::make('is_available')
                    ->label('Tersedia Dijual')
                    ->default(true),
                Textarea::make('description')
                    ->label('Komposisi / Catatan Menu')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('stand.name')
                    ->label('Stand Kantin')
                    ->badge()
                    ->color('primary')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('name')
                    ->label('Nama Menu')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('category')
                    ->label('Kategori')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'food' => 'warning',
                        'drink' => 'info',
                        'snack' => 'success',
                        'dessert' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'food' => 'Makanan',
                        'drink' => 'Minuman',
                        'snack' => 'Snack',
                        'dessert' => 'Dessert',
                        default => ucfirst($state),
                    }),
                TextColumn::make('price')
                    ->label('Harga')
                    ->money('IDR', locale: 'id')
                    ->sortable(),
                TextColumn::make('stock')
                    ->label('Stok')
                    ->badge()
                    ->color(fn (int $state): string => $state > 5 ? 'success' : 'danger')
                    ->sortable(),
                IconColumn::make('is_available')
                    ->label('Tersedia')
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('canteen_stand_id')
                    ->label('Stand Kantin')
                    ->relationship('stand', 'name'),
                SelectFilter::make('category')
                    ->label('Kategori Menu')
                    ->options([
                        'food' => 'Makanan',
                        'drink' => 'Minuman',
                        'snack' => 'Snack',
                        'dessert' => 'Dessert',
                    ]),
            ])
            ->actions([
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageCanteenMenus::route('/'),
        ];
    }
}
