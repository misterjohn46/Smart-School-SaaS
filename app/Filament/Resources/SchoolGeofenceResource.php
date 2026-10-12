<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Concerns\HasSchoolAccessOnly;
use App\Filament\Resources\SchoolGeofenceResource\Pages;
use App\Models\SchoolGeofence;
use Filament\Actions;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class SchoolGeofenceResource extends Resource
{
    use HasSchoolAccessOnly;

    protected static ?string $model = SchoolGeofence::class;

    protected static string|UnitEnum|null $navigationGroup = 'Presensi & IoT RFID';

    protected static ?string $navigationLabel = 'Geofencing GPS Sekolah';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-map-pin';

    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                TextInput::make('name')
                    ->label('Nama Titik Lokasi')
                    ->placeholder('Contoh: Kampus Utama SMK')
                    ->required()
                    ->maxLength(100),
                TextInput::make('latitude')
                    ->label('Garis Lintang (Latitude)')
                    ->numeric()
                    ->placeholder('-6.2088000')
                    ->required(),
                TextInput::make('longitude')
                    ->label('Garis Bujur (Longitude)')
                    ->numeric()
                    ->placeholder('106.8456000')
                    ->required(),
                TextInput::make('radius_meters')
                    ->label('Radius Jangkauan (Meter)')
                    ->numeric()
                    ->default(100)
                    ->suffix('Meter')
                    ->required(),
                Toggle::make('is_active')
                    ->label('Titik Lokasi Aktif')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nama Lokasi')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('latitude')
                    ->label('Latitude')
                    ->fontFamily('mono'),
                TextColumn::make('longitude')
                    ->label('Longitude')
                    ->fontFamily('mono'),
                TextColumn::make('radius_meters')
                    ->label('Radius')
                    ->suffix(' Meter')
                    ->badge()
                    ->color('info')
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),
            ])
            ->filters([])
            ->actions([
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageSchoolGeofences::route('/'),
        ];
    }
}
