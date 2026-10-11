<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\TenantResource\Pages;
use App\Models\Tenant;
use App\Models\User;
use Filament\Actions;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use UnitEnum;

class TenantResource extends Resource
{
    use \App\Filament\Concerns\HasSuperAdminAccessOnly;

    protected static ?string $model = Tenant::class;

    protected static string | UnitEnum | null $navigationGroup = 'SaaS Platform & Multi-Tenant';
    protected static ?string $navigationLabel = 'Sekolah Mitra (Tenants)';
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-building-office-2';
    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                TextInput::make('school_name')
                    ->label('Nama Sekolah Instansi')
                    ->required()
                    ->maxLength(150),
                TextInput::make('npsn')
                    ->label('NPSN Sekolah')
                    ->required()
                    ->maxLength(20),
                TextInput::make('admin_name')
                    ->label('Nama Penanggung Jawab / Admin')
                    ->required()
                    ->maxLength(100),
                TextInput::make('admin_email')
                    ->label('Email Kontak Admin (Untuk Login)')
                    ->email()
                    ->required()
                    ->maxLength(100)
                    ->helperText('Akun Administrator Sekolah (Role: admin) dengan kata sandi awal: password akan otomatis dibuatkan.'),
                TextInput::make('admin_phone')
                    ->label('Nomor Telepon / WhatsApp')
                    ->tel()
                    ->maxLength(30),
                DatePicker::make('trial_ends_at')
                    ->label('Masa Berlaku Trial')
                    ->default(now()->addDays(14)),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('school_name')
                    ->label('Nama Sekolah')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('npsn')
                    ->label('NPSN')
                    ->badge()
                    ->color('primary')
                    ->searchable(),
                TextColumn::make('domains.domain')
                    ->label('Domain / Subdomain')
                    ->badge()
                    ->color('info')
                    ->placeholder('-'),
                TextColumn::make('admin_name')
                    ->label('Admin Sekolah')
                    ->searchable(),
                TextColumn::make('admin_email')
                    ->label('Email Login')
                    ->searchable(),
                TextColumn::make('trial_ends_at')
                    ->label('Batas Trial')
                    ->date('d M Y')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Terdaftar')
                    ->dateTime('d M Y')
                    ->sortable(),
            ])
            ->actions([
                Actions\Action::make('credentials')
                    ->label('Kredensial')
                    ->icon('heroicon-o-key')
                    ->color('success')
                    ->modalHeading(fn (Tenant $record): string => "Kredensial Login: {$record->school_name}")
                    ->modalDescription('Gunakan informasi akun berikut untuk masuk sebagai Administrator Sekolah:')
                    ->infolist([
                        \Filament\Infolists\Components\TextEntry::make('school_name')
                            ->label('Nama Sekolah')
                            ->weight('bold'),
                        \Filament\Infolists\Components\TextEntry::make('npsn')
                            ->label('NPSN Sekolah')
                            ->badge()
                            ->color('gray'),
                        \Filament\Infolists\Components\TextEntry::make('admin_name')
                            ->label('Nama Penanggung Jawab'),
                        \Filament\Infolists\Components\TextEntry::make('admin_email')
                            ->label('Email Login Admin')
                            ->copyable()
                            ->badge()
                            ->color('primary'),
                        \Filament\Infolists\Components\TextEntry::make('admin_password')
                            ->label('Kata Sandi Default')
                            ->state('password')
                            ->copyable()
                            ->badge()
                            ->color('warning'),
                        \Filament\Infolists\Components\TextEntry::make('login_url')
                            ->label('Tautan Halaman Login')
                            ->state(fn (): string => url('/login'))
                            ->copyable()
                            ->url(fn (): string => url('/login'), shouldOpenInNewTab: true)
                            ->color('info'),
                        \Filament\Infolists\Components\TextEntry::make('role')
                            ->label('Hak Akses / Role')
                            ->state('Administrator Sekolah (Admin TU)')
                            ->badge()
                            ->color('success'),
                    ])
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup'),
                Actions\Action::make('sync_admin')
                    ->label('Sinkron Akun')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Sinkronisasi Akun Admin Sekolah')
                    ->modalDescription('Apakah Anda ingin memastikan akun pengguna untuk email ini aktif dengan role admin?')
                    ->action(function (Tenant $record): void {
                        $role = Role::firstOrCreate(['name' => 'admin']);
                        $user = User::updateOrCreate(
                            ['email' => $record->admin_email],
                            [
                                'name' => $record->admin_name ?: 'Admin ' . $record->school_name,
                                'phone_number' => $record->admin_phone,
                                'password' => Hash::make('password'),
                                'is_active' => true,
                                'email_verified_at' => now(),
                            ]
                        );
                        $user->syncRoles([$role]);

                        Notification::make()
                            ->title('Akun admin berhasil disinkronisasi!')
                            ->body("Email: {$record->admin_email} | Sandi: password")
                            ->success()
                            ->send();
                    }),
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageTenants::route('/'),
        ];
    }
}
