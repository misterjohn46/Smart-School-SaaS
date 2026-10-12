<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Concerns\HasSchoolAccessOnly;
use App\Filament\Resources\SubjectResource\Pages;
use App\Models\Subject;
use Filament\Actions;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class SubjectResource extends Resource
{
    use HasSchoolAccessOnly;

    protected static ?string $model = Subject::class;

    protected static string|UnitEnum|null $navigationGroup = 'Master Data Akademik';

    protected static ?string $navigationLabel = 'Mata Pelajaran';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-book-open';

    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                TextInput::make('code')
                    ->label('Kode Mapel')
                    ->placeholder('Contoh: RPL-01, MAT-01')
                    ->required()
                    ->maxLength(20)
                    ->unique(ignoreRecord: true),
                TextInput::make('name')
                    ->label('Nama Mata Pelajaran')
                    ->placeholder('Contoh: Pemrograman Berorientasi Objek')
                    ->required()
                    ->maxLength(100),
                TextInput::make('kkm')
                    ->label('Kriteria Ketuntasan Minimal (KKM)')
                    ->numeric()
                    ->default(75)
                    ->required(),
                Select::make('type')
                    ->label('Kategori Pelajaran')
                    ->options([
                        'umum' => 'Muatan Nasional / Umum',
                        'kejuruan' => 'Peminatan Kejuruan',
                        'mulok' => 'Muatan Lokal',
                    ])
                    ->default('umum')
                    ->required(),
                Toggle::make('is_active')
                    ->label('Mata Pelajaran Aktif')
                    ->default(true),
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
                    ->sortable()
                    ->searchable(),
                TextColumn::make('name')
                    ->label('Nama Mata Pelajaran')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('kkm')
                    ->label('KKM')
                    ->badge()
                    ->color('success')
                    ->sortable(),
                TextColumn::make('type')
                    ->label('Kategori')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'kejuruan' => 'info',
                        'mulok' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),
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
            'index' => Pages\ManageSubjects::route('/'),
        ];
    }
}
