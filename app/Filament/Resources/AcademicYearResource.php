<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\AcademicYearResource\Pages;
use App\Models\AcademicYear;
use Filament\Actions;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class AcademicYearResource extends Resource
{
    use \App\Filament\Concerns\HasSchoolAccessOnly;

    protected static ?string $model = AcademicYear::class;

    protected static string | UnitEnum | null $navigationGroup = 'Master Data Akademik';
    protected static ?string $navigationLabel = 'Tahun Ajaran & Semester';
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                TextInput::make('name')
                    ->label('Tahun Ajaran')
                    ->placeholder('Contoh: 2024/2025')
                    ->required()
                    ->maxLength(50),
                Select::make('semester')
                    ->label('Semester')
                    ->options([
                        'ganjil' => 'Semester Ganjil',
                        'genap' => 'Semester Genap',
                    ])
                    ->required(),
                DatePicker::make('start_date')
                    ->label('Tanggal Mulai')
                    ->native(false),
                DatePicker::make('end_date')
                    ->label('Tanggal Selesai')
                    ->native(false),
                Toggle::make('is_active')
                    ->label('Tahun Ajaran Aktif Saat Ini')
                    ->default(false),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Tahun Ajaran')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('semester')
                    ->label('Semester')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'ganjil' ? 'info' : 'warning')
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),
                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),
                TextColumn::make('start_date')
                    ->label('Mulai')
                    ->date('d M Y')
                    ->sortable(),
                TextColumn::make('end_date')
                    ->label('Selesai')
                    ->date('d M Y')
                    ->sortable(),
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
            'index' => Pages\ManageAcademicYears::route('/'),
        ];
    }
}
