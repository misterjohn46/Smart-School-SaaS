<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\AttendanceLeaveResource\Pages;
use App\Models\AttendanceLeave;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class AttendanceLeaveResource extends Resource
{
    use \App\Filament\Concerns\HasSchoolAccessOnly;

    protected static ?string $model = AttendanceLeave::class;

    protected static string | UnitEnum | null $navigationGroup = 'Presensi & IoT RFID';
    protected static ?string $navigationLabel = 'Persetujuan Izin & Sakit';
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-document-text';
    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('user.name')
                    ->label('Nama Pengaju')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('type')
                    ->label('Jenis')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'sakit' => 'danger',
                        'izin' => 'warning',
                        'tugas_luar' => 'info',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst(str_replace('_', ' ', $state))),
                TextColumn::make('start_date')
                    ->label('Dari')
                    ->date('d M Y')
                    ->sortable(),
                TextColumn::make('end_date')
                    ->label('Sampai')
                    ->date('d M Y')
                    ->sortable(),
                TextColumn::make('reason')
                    ->label('Alasan Permohonan')
                    ->wrap(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'approved' => 'success',
                        'rejected' => 'danger',
                        default => 'warning',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'approved' => 'Disetujui',
                        'rejected' => 'Ditolak',
                        default => 'Menunggu Review',
                    }),
                TextColumn::make('approver.name')
                    ->label('Disetujui Oleh')
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([])
            ->actions([
                Actions\Action::make('approve')
                    ->label('Setujui')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (AttendanceLeave $record): bool => $record->status === 'pending')
                    ->requiresConfirmation()
                    ->modalHeading('Setujui Permohonan Izin / Sakit')
                    ->action(function (AttendanceLeave $record): void {
                        $record->update([
                            'status' => 'approved',
                            'approved_by' => auth()->id(),
                        ]);
                        Notification::make()->title('Izin berhasil disetujui')->success()->send();
                    }),
                Actions\Action::make('reject')
                    ->label('Tolak')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (AttendanceLeave $record): bool => $record->status === 'pending')
                    ->requiresConfirmation()
                    ->modalHeading('Tolak Permohonan Izin / Sakit')
                    ->action(function (AttendanceLeave $record): void {
                        $record->update([
                            'status' => 'rejected',
                            'approved_by' => auth()->id(),
                        ]);
                        Notification::make()->title('Izin ditolak')->warning()->send();
                    }),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageAttendanceLeaves::route('/'),
        ];
    }
}
