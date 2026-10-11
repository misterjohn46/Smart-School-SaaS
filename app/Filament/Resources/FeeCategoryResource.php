<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\FeeCategoryResource\Pages;
use App\Models\FeeCategory;
use Filament\Actions;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class FeeCategoryResource extends Resource
{
    use \App\Filament\Concerns\HasFinanceAccessOnly;

    protected static ?string $model = FeeCategory::class;

    protected static string | UnitEnum | null $navigationGroup = 'Keuangan & Billing';
    protected static ?string $navigationLabel = 'Kategori Biaya Sekolah';
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-tag';
    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                TextInput::make('code')
                    ->label('Kode Biaya')
                    ->placeholder('Contoh: SPP-2026, DSP-X')
                    ->required()
                    ->maxLength(50),
                TextInput::make('name')
                    ->label('Nama Kategori Biaya')
                    ->placeholder('Contoh: SPP Bulanan Kelas X')
                    ->required()
                    ->maxLength(100),
                Select::make('academic_year_id')
                    ->label('Tahun Ajaran Terkait')
                    ->relationship('academicYear', 'name')
                    ->searchable()
                    ->nullable(),
                TextInput::make('default_amount')
                    ->label('Tarif Standar (Rp)')
                    ->numeric()
                    ->prefix('Rp')
                    ->default(0)
                    ->required(),
                Toggle::make('is_recurring')
                    ->label('Tagihan Rutin Bulanan')
                    ->default(true),
                Toggle::make('is_active')
                    ->label('Status Aktif')
                    ->default(true),
                Textarea::make('description')
                    ->label('Keterangan Pos Biaya')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->label('Kode')
                    ->badge()
                    ->color('primary')
                    ->searchable(),
                TextColumn::make('name')
                    ->label('Nama Kategori')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('default_amount')
                    ->label('Tarif Standar')
                    ->money('IDR', locale: 'id')
                    ->sortable(),
                TextColumn::make('academicYear.name')
                    ->label('Tahun Ajaran')
                    ->placeholder('-'),
                IconColumn::make('is_recurring')
                    ->label('Rutin')
                    ->boolean(),
                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),
            ])
            ->actions([
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageFeeCategories::route('/'),
        ];
    }
}
