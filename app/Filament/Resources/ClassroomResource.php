<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Enums\RoleName;
use App\Filament\Concerns\HasSchoolAccessOnly;
use App\Filament\Resources\ClassroomResource\Pages;
use App\Models\Classroom;
use App\Models\User;
use Filament\Actions;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class ClassroomResource extends Resource
{
    use HasSchoolAccessOnly;

    protected static ?string $model = Classroom::class;

    protected static string|UnitEnum|null $navigationGroup = 'Master Data Akademik';

    protected static ?string $navigationLabel = 'Rombongan Belajar (Kelas)';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                TextInput::make('name')
                    ->label('Nama Kelas')
                    ->placeholder('Contoh: X - RPL 1')
                    ->required()
                    ->maxLength(50),
                Select::make('academic_year_id')
                    ->label('Tahun Ajaran')
                    ->relationship('academicYear', 'name')
                    ->required()
                    ->searchable()
                    ->preload(),
                Select::make('grade_level_id')
                    ->label('Tingkat Kelas')
                    ->relationship('gradeLevel', 'name')
                    ->required()
                    ->preload(),
                Select::make('department_id')
                    ->label('Jurusan')
                    ->relationship('department', 'name')
                    ->searchable()
                    ->preload(),
                Select::make('homeroom_teacher_id')
                    ->label('Wali Kelas (Guru)')
                    ->options(fn () => User::role(RoleName::Teacher->value)->pluck('name', 'id'))
                    ->searchable()
                    ->preload(),
                TextInput::make('capacity')
                    ->label('Kapasitas Siswa')
                    ->numeric()
                    ->default(36)
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nama Kelas')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('gradeLevel.name')
                    ->label('Tingkat')
                    ->badge()
                    ->color('info')
                    ->sortable(),
                TextColumn::make('department.code')
                    ->label('Jurusan')
                    ->badge()
                    ->color('primary')
                    ->sortable(),
                TextColumn::make('homeroomTeacher.name')
                    ->label('Wali Kelas')
                    ->placeholder('Belum Ditentukan')
                    ->searchable(),
                TextColumn::make('capacity')
                    ->label('Kapasitas')
                    ->suffix(' Siswa')
                    ->sortable(),
                TextColumn::make('academicYear.name')
                    ->label('Tahun Ajaran')
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
            'index' => Pages\ManageClassrooms::route('/'),
        ];
    }
}
