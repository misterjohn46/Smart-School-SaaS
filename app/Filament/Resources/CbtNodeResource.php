<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\CbtNodeResource\Pages;
use App\Models\CbtNode;
use Filament\Actions;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class CbtNodeResource extends Resource
{
    use \App\Filament\Concerns\HasSuperAdminAccessOnly;

    protected static ?string $model = CbtNode::class;

    protected static string | UnitEnum | null $navigationGroup = 'SaaS Platform & Multi-Tenant';
    protected static ?string $navigationLabel = 'Node Server CBT ANBK';
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-server-stack';
    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                TextInput::make('node_code')
                    ->label('Kode Node Server')
                    ->placeholder('Contoh: CBT-SMK-001')
                    ->required()
                    ->maxLength(50),
                TextInput::make('node_name')
                    ->label('Nama Server Node')
                    ->placeholder('Contoh: Server Lab RPL Utama')
                    ->required()
                    ->maxLength(100),
                TextInput::make('school_name')
                    ->label('Nama Sekolah Penempatan')
                    ->required()
                    ->maxLength(150),
                TextInput::make('ip_address')
                    ->label('IP Address Lokal / VPN')
                    ->placeholder('192.168.1.100')
                    ->maxLength(45),
                Select::make('status')
                    ->label('Status Server')
                    ->options([
                        'online' => 'Online & Siap Ujian',
                        'offline' => 'Offline',
                        'syncing' => 'Sedang Sinkronisasi Soal',
                        'maintenance' => 'Maintenance',
                    ])
                    ->default('online')
                    ->required(),
                Select::make('sync_status')
                    ->label('Status Sinkronisasi Bank Soal')
                    ->options([
                        'synchronized' => 'Tersinkronisasi Penuh (100%)',
                        'pending' => 'Menunggu Sinkronisasi',
                        'syncing' => 'Proses Unduh Soal',
                        'failed' => 'Gagal Sinkron',
                    ])
                    ->default('synchronized')
                    ->required(),
                TextInput::make('max_concurrent_students')
                    ->label('Kapasitas Klien Siswa')
                    ->numeric()
                    ->default(100)
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('node_code')
                    ->label('Kode Node')
                    ->badge()
                    ->color('primary')
                    ->searchable(),
                TextColumn::make('node_name')
                    ->label('Nama Server Node')
                    ->searchable()
                    ->weight('bold'),
                TextColumn::make('school_name')
                    ->label('Lokasi Sekolah')
                    ->searchable(),
                TextColumn::make('status')
                    ->label('Kondisi')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'online' => 'success',
                        'syncing' => 'info',
                        'maintenance' => 'warning',
                        default => 'danger',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'online' => 'Online',
                        'offline' => 'Offline',
                        'syncing' => 'Sinkronisasi',
                        'maintenance' => 'Maintenance',
                        default => ucfirst($state),
                    }),
                TextColumn::make('sync_status')
                    ->label('Bank Soal')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'synchronized' => 'success',
                        'syncing' => 'warning',
                        default => 'danger',
                    }),
                TextColumn::make('max_concurrent_students')
                    ->label('Kapasitas Klien')
                    ->numeric(),
                TextColumn::make('last_heartbeat_at')
                    ->label('Heartbeat')
                    ->since()
                    ->placeholder('Baru Saja'),
            ])
            ->actions([
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageCbtNodes::route('/'),
        ];
    }
}
