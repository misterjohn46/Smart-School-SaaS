<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\StudentWalletResource\Pages;
use App\Models\StudentWallet;
use App\Services\WalletService;
use Filament\Actions;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class StudentWalletResource extends Resource
{
    use \App\Filament\Concerns\HasSchoolAccessOnly;

    protected static ?string $model = StudentWallet::class;

    protected static string | UnitEnum | null $navigationGroup = 'Tabungan & E-Wallet';
    protected static ?string $navigationLabel = 'Rekening Tabungan Siswa';
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-wallet';
    protected static ?int $navigationSort = 1;

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('balance', 'desc')
            ->columns([
                TextColumn::make('account_number')
                    ->label('No. Rekening')
                    ->searchable()
                    ->sortable()
                    ->fontFamily('mono')
                    ->color('primary'),
                TextColumn::make('studentProfile.full_name')
                    ->label('Nama Siswa')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('studentProfile.classroom.name')
                    ->label('Kelas')
                    ->badge()
                    ->color('gray')
                    ->placeholder('-'),
                TextColumn::make('balance')
                    ->label('Saldo Tabungan')
                    ->money('IDR', locale: 'id')
                    ->sortable()
                    ->weight('bold')
                    ->color('success'),
                TextColumn::make('daily_limit')
                    ->label('Batas Jajan Harian')
                    ->money('IDR', locale: 'id')
                    ->sortable(),
                TextColumn::make('spent_today')
                    ->label('Terpakai Hari Ini')
                    ->money('IDR', locale: 'id'),
                IconColumn::make('is_active')
                    ->label('Status Aktif')
                    ->boolean(),
            ])
            ->actions([
                // Aksi Setor Tunai (Top Up)
                Actions\Action::make('topup')
                    ->label('Setor Tunai')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->form([
                        TextInput::make('amount')
                            ->label('Nominal Setoran (Rp)')
                            ->numeric()
                            ->prefix('Rp')
                            ->required()
                            ->minValue(5000),
                        TextInput::make('description')
                            ->label('Keterangan Setor')
                            ->default('Setor Tunai Loket Koperasi / TU')
                            ->required(),
                    ])
                    ->action(function (StudentWallet $record, array $data, WalletService $walletService) {
                        $walletService->topup(
                            $record->id,
                            (float) $data['amount'],
                            auth()->id(),
                            $data['description']
                        );
                        Notification::make()->title('Setor tunai berhasil dicatat!')->success()->send();
                    }),

                // Aksi Tarik Tunai
                Actions\Action::make('withdraw')
                    ->label('Tarik Tunai')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('warning')
                    ->form([
                        TextInput::make('amount')
                            ->label('Nominal Penarikan (Rp)')
                            ->numeric()
                            ->prefix('Rp')
                            ->required()
                            ->minValue(5000),
                        TextInput::make('pin')
                            ->label('PIN Otorisasi 6-Digit Siswa')
                            ->password()
                            ->length(6)
                            ->required(),
                        TextInput::make('description')
                            ->label('Keterangan Tarik')
                            ->default('Tarik Tunai Loket Koperasi')
                            ->required(),
                    ])
                    ->action(function (StudentWallet $record, array $data, WalletService $walletService) {
                        try {
                            $walletService->withdraw(
                                $record->id,
                                (float) $data['amount'],
                                auth()->id(),
                                $data['pin'],
                                $data['description']
                            );
                            Notification::make()->title('Tarik tunai berhasil diproses!')->success()->send();
                        } catch (\Exception $e) {
                            Notification::make()->title($e->getMessage())->danger()->send();
                        }
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageStudentWallets::route('/'),
        ];
    }
}
