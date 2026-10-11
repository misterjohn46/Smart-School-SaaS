<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Enums\RoleName;
use App\Filament\Resources\LessonScheduleResource\Pages;
use App\Models\AcademicYear;
use App\Models\LessonSchedule;
use App\Models\User;
use Filament\Actions;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class LessonScheduleResource extends Resource
{
    use \App\Filament\Concerns\HasSchoolAccessOnly;

    protected static ?string $model = LessonSchedule::class;

    protected static string | UnitEnum | null $navigationGroup = 'Master Data Akademik';
    protected static ?string $navigationLabel = 'Jadwal Mengajar';
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?int $navigationSort = 5;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Select::make('academic_year_id')
                    ->label('Tahun Ajaran & Semester')
                    ->relationship('academicYear', 'name')
                    ->default(fn () => AcademicYear::where('is_active', true)->first()?->id)
                    ->required()
                    ->searchable()
                    ->preload(),

                Select::make('classroom_id')
                    ->label('Rombongan Belajar (Kelas)')
                    ->relationship('classroom', 'name')
                    ->required()
                    ->searchable()
                    ->preload(),

                Select::make('subject_id')
                    ->label('Mata Pelajaran')
                    ->relationship('subject', 'name')
                    ->required()
                    ->searchable()
                    ->preload(),

                Select::make('teacher_user_id')
                    ->label('Guru Pengajar')
                    ->options(fn () => User::role(RoleName::Teacher->value)->pluck('name', 'id'))
                    ->required()
                    ->searchable()
                    ->preload(),

                Select::make('day_of_week')
                    ->label('Hari Pelajaran')
                    ->options([
                        'senin' => 'Senin',
                        'selasa' => 'Selasa',
                        'rabu' => 'Rabu',
                        'kamis' => 'Kamis',
                        'jumat' => 'Jumat',
                        'sabtu' => 'Sabtu',
                    ])
                    ->default('senin')
                    ->required(),

                TimePicker::make('start_time')
                    ->label('Jam Mulai')
                    ->seconds(false)
                    ->default('07:15')
                    ->required(),

                TimePicker::make('end_time')
                    ->label('Jam Selesai')
                    ->seconds(false)
                    ->default('08:45')
                    ->required(),

                TextInput::make('room')
                    ->label('Ruangan / Laboratorium')
                    ->placeholder('Contoh: Lab Komputer 1, Ruang 102')
                    ->maxLength(50),

                TextInput::make('order')
                    ->label('Jam Ke-')
                    ->numeric()
                    ->default(1)
                    ->minValue(1)
                    ->maxValue(12),

                Toggle::make('is_active')
                    ->label('Jadwal Aktif')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('day_of_week')
                    ->label('Hari')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'senin' => 'primary',
                        'selasa' => 'success',
                        'rabu' => 'info',
                        'kamis' => 'warning',
                        'jumat' => 'danger',
                        'sabtu' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                    ->sortable(),

                TextColumn::make('time_slot')
                    ->label('Jam Pelajaran')
                    ->badge()
                    ->color('gray'),

                TextColumn::make('classroom.name')
                    ->label('Kelas')
                    ->badge()
                    ->color('info')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('subject.name')
                    ->label('Mata Pelajaran')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('teacher.name')
                    ->label('Guru Pengajar')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('room')
                    ->label('Ruangan')
                    ->placeholder('-')
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),
            ])
            ->defaultSort('day_of_week')
            ->filters([
                SelectFilter::make('day_of_week')
                    ->label('Filter Hari')
                    ->options([
                        'senin' => 'Senin',
                        'selasa' => 'Selasa',
                        'rabu' => 'Rabu',
                        'kamis' => 'Kamis',
                        'jumat' => 'Jumat',
                        'sabtu' => 'Sabtu',
                    ]),

                SelectFilter::make('classroom_id')
                    ->label('Filter Kelas')
                    ->relationship('classroom', 'name'),

                SelectFilter::make('subject_id')
                    ->label('Filter Mapel')
                    ->relationship('subject', 'name'),
            ])
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
            'index' => Pages\ManageLessonSchedules::route('/'),
        ];
    }
}
