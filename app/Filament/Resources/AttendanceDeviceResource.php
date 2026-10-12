<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Concerns\HasSchoolAccessOnly;
use App\Filament\Resources\AttendanceDeviceResource\Pages;
use App\Models\AttendanceDevice;
use Filament\Actions;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class AttendanceDeviceResource extends Resource
{
    use HasSchoolAccessOnly;

    protected static ?string $model = AttendanceDevice::class;

    protected static string|UnitEnum|null $navigationGroup = 'Presensi & IoT RFID';

    protected static ?string $navigationLabel = 'Perangkat IoT ESP32';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cpu-chip';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                TextInput::make('device_name')
                    ->label('Nama Alat / Pos')
                    ->placeholder('Contoh: Gerbang Masuk Utama Siswa')
                    ->required()
                    ->maxLength(100),
                TextInput::make('location')
                    ->label('Lokasi Penempatan')
                    ->placeholder('Contoh: Lobby Gedung A Depan')
                    ->required()
                    ->maxLength(100),
                Select::make('device_type')
                    ->label('Tipe Operasi Gerbang')
                    ->options([
                        'two_way' => 'Dua Arah (Masuk & Pulang)',
                        'gate_in' => 'Khusus Masuk (Check-In Only)',
                        'gate_out' => 'Khusus Pulang (Check-Out Only)',
                        'canteen' => 'Mesin Pembayaran Kantin',
                    ])
                    ->default('two_way')
                    ->required(),
                TextInput::make('device_token_preview')
                    ->label('Kunci Rahasia (X-Device-Token)')
                    ->helperText('Token disimpan sebagai hash dan tidak dapat dilihat kembali. Gunakan aksi "Terbitkan Token Baru" bila token hilang.')
                    ->disabled()
                    ->dehydrated(false)
                    ->visible(fn ($record) => filled($record)),
                Toggle::make('is_active')
                    ->label('Perangkat Aktif & Menerima Request')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('device_name')
                    ->label('Nama Perangkat')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('location')
                    ->label('Lokasi')
                    ->searchable(),
                TextColumn::make('device_type')
                    ->label('Tipe')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'gate_in' => 'success',
                        'gate_out' => 'warning',
                        'two_way' => 'primary',
                        'canteen' => 'amber',
                        default => 'gray',
                    }),
                TextColumn::make('device_token_preview')
                    ->label('Device Token')
                    ->fontFamily('mono')
                    ->placeholder('Tersembunyi'),
                TextColumn::make('last_ping_at')
                    ->label('Koneksi Terakhir')
                    ->since()
                    ->placeholder('Belum Pernah Terhubung')
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label('Status')
                    ->boolean(),
            ])
            ->filters([])
            ->actions([
                Actions\Action::make('regenerateToken')
                    ->label('Terbitkan Token Baru')
                    ->icon('heroicon-o-key')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Terbitkan Token Perangkat Baru')
                    ->modalDescription('Token lama langsung tidak berlaku. Perangkat ESP32 harus diflash ulang dengan token baru.')
                    ->action(function (AttendanceDevice $record): void {
                        $token = $record->regenerateToken();

                        Notification::make()
                            ->title('Token baru berhasil diterbitkan')
                            ->body('Salin sekarang, token ini tidak akan ditampilkan lagi: '.$token)
                            ->persistent()
                            ->success()
                            ->send();
                    }),
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
            'index' => Pages\ManageAttendanceDevices::route('/'),
        ];
    }
}
