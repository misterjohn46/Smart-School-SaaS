<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\AuditTrailResource\Pages;
use App\Models\AuditTrail;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Model;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class AuditTrailResource extends Resource
{
    protected static ?string $model = AuditTrail::class;

    /**
     * Log keamanan memuat IP dan identitas yang dicoba saat login, sehingga hanya
     * boleh dibuka pemegang kewenangan keamanan. Sebelumnya resource ini tidak
     * memakai concern maupun policy apa pun, jadi terbuka untuk semua pengguna panel.
     */
    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'admin', 'kepala_sekolah']) ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    // Audit trail bersifat append-only: tidak boleh dibuat, diubah, atau dihapus dari panel.
    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    protected static string | UnitEnum | null $navigationGroup = 'Sistem & Keamanan';
    protected static ?string $navigationLabel = 'Audit Trail & Log Keamanan';
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-shield-check';
    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')
                    ->label('Waktu Kejadian')
                    ->dateTime('d M Y, H:i:s')
                    ->sortable(),
                TextColumn::make('action')
                    ->label('Aktivitas')
                    ->badge()
                    ->color('gray')
                    ->sortable(),
                TextColumn::make('user.name')
                    ->label('Pengguna')
                    ->placeholder('Tamu / Tidak Ditemukan')
                    ->searchable(),
                TextColumn::make('identifier_attempted')
                    ->label('Identitas Diinput')
                    ->searchable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'success' => 'success',
                        'failed' => 'danger',
                        'locked' => 'warning',
                        'disabled' => 'gray',
                        default => 'info',
                    })
                    ->formatStateUsing(fn (string $state): string => strtoupper($state)),
                TextColumn::make('ip_address')
                    ->label('Alamat IP')
                    ->fontFamily('mono')
                    ->searchable(),
                TextColumn::make('failure_reason')
                    ->label('Keterangan / Alasan Gagal')
                    ->placeholder('-')
                    ->wrap(),
            ])
            ->filters([])
            ->actions([])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageAuditTrails::route('/'),
        ];
    }
}
