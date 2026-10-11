<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\PlanResource\Pages;
use App\Models\Plan;
use Filament\Actions;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class PlanResource extends Resource
{
    use \App\Filament\Concerns\HasSuperAdminAccessOnly;

    protected static ?string $model = Plan::class;

    protected static string | UnitEnum | null $navigationGroup = 'SaaS Platform & Multi-Tenant';
    protected static ?string $navigationLabel = 'Paket Langganan (Plans)';
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-sparkles';
    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                TextInput::make('name')
                    ->label('Nama Paket')
                    ->placeholder('Contoh: Starter Plan, Pro Plan')
                    ->required()
                    ->maxLength(100),
                TextInput::make('code')
                    ->label('Kode Paket')
                    ->placeholder('Contoh: starter, pro, enterprise')
                    ->required()
                    ->maxLength(50),
                TextInput::make('price_monthly')
                    ->label('Tarif Bulanan (Rp)')
                    ->numeric()
                    ->prefix('Rp')
                    ->required(),
                TextInput::make('price_yearly')
                    ->label('Tarif Tahunan (Rp)')
                    ->numeric()
                    ->prefix('Rp')
                    ->required(),
                TextInput::make('max_students')
                    ->label('Batas Maksimal Siswa')
                    ->numeric()
                    ->default(500)
                    ->required(),
                Toggle::make('is_active')
                    ->label('Status Aktif Dijual')
                    ->default(true),
                Textarea::make('description')
                    ->label('Deskripsi Paket')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nama Paket')
                    ->searchable()
                    ->weight('bold'),
                TextColumn::make('code')
                    ->label('Kode')
                    ->badge()
                    ->color('primary'),
                TextColumn::make('price_monthly')
                    ->label('Tarif / Bulan')
                    ->money('IDR', locale: 'id')
                    ->sortable(),
                TextColumn::make('price_yearly')
                    ->label('Tarif / Tahun')
                    ->money('IDR', locale: 'id')
                    ->sortable(),
                TextColumn::make('max_students')
                    ->label('Kuota Siswa')
                    ->numeric()
                    ->sortable(),
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
            'index' => Pages\ManagePlans::route('/'),
        ];
    }
}
