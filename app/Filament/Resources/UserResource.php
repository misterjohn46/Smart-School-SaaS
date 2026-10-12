<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
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
use Illuminate\Database\Eloquent\Builder;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Manajemen Pengguna';

    protected static ?string $navigationLabel = 'Data Pengguna & Staf';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-users';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                TextInput::make('name')
                    ->label('Nama Lengkap')
                    ->required()
                    ->maxLength(255),
                TextInput::make('username')
                    ->label('Username')
                    ->maxLength(50)
                    ->unique(ignoreRecord: true),
                TextInput::make('email')
                    ->label('Alamat Email')
                    ->email()
                    ->required()
                    ->maxLength(255)
                    ->dehydrateStateUsing(fn (string $state): string => strtolower($state))
                    ->unique(ignoreRecord: true),
                TextInput::make('nisn')
                    ->label('NISN (Khusus Siswa)')
                    ->maxLength(20)
                    ->unique(ignoreRecord: true),
                TextInput::make('phone_number')
                    ->label('No. WhatsApp / HP')
                    ->tel()
                    ->maxLength(25),
                TextInput::make('password')
                    ->label('Kata Sandi')
                    ->password()
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->minLength(8)
                    ->same('password_confirmation')
                    ->dehydrated(fn (?string $state): bool => filled($state)),
                TextInput::make('password_confirmation')
                    ->label('Konfirmasi Sandi')
                    ->password()
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->minLength(8)
                    ->same('password')
                    ->dehydrated(false),
                Select::make('roles')
                    ->label('Role Akses (Hak Pengguna)')
                    // Hanya Super Admin boleh memberikan peran super_admin. Tanpa filter ini
                    // admin sekolah dapat mengangkat dirinya sendiri menjadi super_admin.
                    ->relationship(
                        name: 'roles',
                        titleAttribute: 'name',
                        modifyQueryUsing: fn (Builder $query): Builder => auth()->user()?->hasRole('super_admin')
                            ? $query
                            : $query->where('name', '!=', 'super_admin'),
                    )
                    ->preload()
                    ->disabled(fn (?User $record): bool => $record?->is(auth()->user()) ?? false)
                    ->dehydrated(fn (?User $record): bool => ! ($record?->is(auth()->user()) ?? false))
                    ->required(),
                Toggle::make('is_active')
                    ->label('Status Akun Aktif')
                    ->default(true),
                Toggle::make('is_locked')
                    ->label('Akun Terkunci (Lockout)')
                    ->default(false),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nama Pengguna')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('username')
                    ->label('Username')
                    ->searchable()
                    ->placeholder('-'),
                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('nisn')
                    ->label('NISN')
                    ->searchable()
                    ->placeholder('-'),
                TextColumn::make('roles.name')
                    ->label('Role')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'super_admin' => 'danger',
                        'admin' => 'primary',
                        'guru' => 'success',
                        'guru_bk' => 'info',
                        'bendahara' => 'warning',
                        'siswa' => 'amber',
                        'orang_tua' => 'purple',
                        default => 'gray',
                    }),
                IconColumn::make('is_locked')
                    ->label('Terkunci')
                    ->boolean()
                    ->trueIcon('heroicon-o-lock-closed')
                    ->falseIcon('heroicon-o-lock-open')
                    ->trueColor('danger')
                    ->falseColor('success'),
                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->label('Terdaftar')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([])
            ->actions([
                Actions\Action::make('unlock')
                    ->label('Buka Kunci')
                    ->icon('heroicon-o-lock-open')
                    ->color('success')
                    ->visible(fn (User $record): bool => (bool) $record->is_locked)
                    ->requiresConfirmation()
                    ->modalHeading('Buka Kunci Akun')
                    ->modalDescription('Apakah Anda yakin ingin membuka kunci akun ini agar pengguna dapat login kembali?')
                    ->action(function (User $record): void {
                        $record->unlockAccount();
                        Notification::make()
                            ->title('Akun berhasil dibuka kuncinya')
                            ->success()
                            ->send();
                    }),
                Actions\Action::make('lock')
                    ->label('Kunci')
                    ->icon('heroicon-o-lock-closed')
                    ->color('danger')
                    ->visible(fn (User $record): bool => ! $record->is_locked && ! $record->is(auth()->user()))
                    ->requiresConfirmation()
                    ->modalHeading('Kunci Akun Pengguna')
                    ->modalDescription('Akun yang dikunci tidak akan bisa login selama 30 menit atau sampai dibuka kuncinya.')
                    ->action(function (User $record): void {
                        $record->lockAccount('Dikunci manual oleh administrator');
                        Notification::make()
                            ->title('Akun berhasil dikunci')
                            ->warning()
                            ->send();
                    }),
                Actions\EditAction::make(),
                Actions\DeleteAction::make()
                    ->hidden(fn (User $record): bool => $record->is(auth()->user())),
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
