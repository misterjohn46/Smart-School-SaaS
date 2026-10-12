<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Concerns\HasSchoolAccessOnly;
use App\Filament\Exports\AttendanceExporter;
use App\Filament\Resources\AttendanceResource\Pages;
use App\Models\Attendance;
use Filament\Actions;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class AttendanceResource extends Resource
{
    use HasSchoolAccessOnly;

    protected static ?string $model = Attendance::class;

    protected static string|UnitEnum|null $navigationGroup = 'Presensi & IoT RFID';

    protected static ?string $navigationLabel = 'Log Rekapitulasi Presensi';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Select::make('user_id')
                    ->label('Pengguna (Siswa / Guru)')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->required(),
                Select::make('classroom_id')
                    ->label('Rombel Kelas')
                    ->relationship('classroom', 'name')
                    ->searchable(),
                DatePicker::make('date')
                    ->label('Tanggal')
                    ->default(now())
                    ->required(),
                TimePicker::make('time_in')
                    ->label('Jam Masuk')
                    ->seconds(false),
                TimePicker::make('time_out')
                    ->label('Jam Pulang')
                    ->seconds(false),
                Select::make('status')
                    ->label('Status Kehadiran')
                    ->options([
                        'hadir' => 'Hadir',
                        'terlambat' => 'Terlambat',
                        'izin' => 'Izin',
                        'sakit' => 'Sakit',
                        'alpa' => 'Alpa (Tanpa Keterangan)',
                    ])
                    ->default('hadir')
                    ->required(),
                Select::make('method')
                    ->label('Metode Verifikasi')
                    ->options([
                        'rfid' => 'IoT RFID Standalone (ESP32)',
                        'qrcode' => 'Scan QR Code Digital',
                        'face_recognition' => 'AI Face Recognition',
                        'gps_mobile' => 'GPS Mobile Geofencing',
                        'manual' => 'Input Manual Guru / TU',
                    ])
                    ->default('rfid')
                    ->required(),
                TextInput::make('notes')
                    ->label('Catatan Keterangan'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('date', 'desc')
            ->columns([
                TextColumn::make('date')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),
                TextColumn::make('user.name')
                    ->label('Nama Pengguna')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('classroom.name')
                    ->label('Kelas')
                    ->placeholder('-')
                    ->badge()
                    ->color('primary')
                    ->sortable(),
                TextColumn::make('time_in')
                    ->label('Masuk')
                    ->time('H:i')
                    ->placeholder('-')
                    ->sortable(),
                TextColumn::make('time_out')
                    ->label('Pulang')
                    ->time('H:i')
                    ->placeholder('-')
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'hadir' => 'success',
                        'terlambat' => 'warning',
                        'izin' => 'info',
                        'sakit' => 'danger',
                        'alpa' => 'gray',
                        default => 'secondary',
                    })
                    ->formatStateUsing(fn (string $state): string => strtoupper($state)),
                TextColumn::make('method')
                    ->label('Metode')
                    ->badge()
                    ->color('gray')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'rfid' => 'IoT RFID',
                        'face_recognition' => 'AI Face',
                        'gps_mobile' => 'GPS Mobile',
                        'qrcode' => 'QR Code',
                        default => 'Manual',
                    }),
                TextColumn::make('device_name')
                    ->label('Pos / Alat')
                    ->placeholder('Aplikasi Mobile')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([])
            ->actions([
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    // Ekspor hanya baris yang dipilih (mis. satu rombel / satu bulan).
                    Actions\ExportBulkAction::make()
                        ->label('Ekspor Terpilih')
                        ->exporter(AttendanceExporter::class),
                    Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageAttendances::route('/'),
        ];
    }
}
