<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Concerns\HasFinanceAccessOnly;
use App\Filament\Resources\PaymentResource\Pages;
use App\Models\Payment;
use App\Services\FinanceService;
use Filament\Actions;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class PaymentResource extends Resource
{
    use HasFinanceAccessOnly;

    protected static ?string $model = Payment::class;

    protected static string|UnitEnum|null $navigationGroup = 'Keuangan & Billing';

    protected static ?string $navigationLabel = 'Penerimaan Kas & Pembayaran';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';

    protected static ?int $navigationSort = 3;

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('payment_number')
                    ->label('No. Kuitansi')
                    ->searchable()
                    ->sortable()
                    ->fontFamily('mono')
                    ->color('primary'),
                TextColumn::make('studentProfile.full_name')
                    ->label('Nama Siswa')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('amount')
                    ->label('Nominal Bayar')
                    ->money('IDR', locale: 'id')
                    ->sortable(),
                TextColumn::make('payment_method')
                    ->label('Metode')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'cash' => 'success',
                        'wallet' => 'info',
                        'bank_transfer' => 'warning',
                        'qris' => 'primary',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'cash' => 'Tunai (Kasir)',
                        'wallet' => 'Dompet KTS',
                        'bank_transfer' => 'Transfer Bank',
                        'qris' => 'QRIS',
                        default => ucfirst($state),
                    }),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'verified' => 'success',
                        'pending' => 'warning',
                        'rejected' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'verified' => 'Terverifikasi',
                        'pending' => 'Menunggu Verifikasi',
                        'rejected' => 'Ditolak',
                        default => ucfirst($state),
                    }),
                TextColumn::make('payment_date')
                    ->label('Waktu Bayar')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
                TextColumn::make('reference_number')
                    ->label('Ref/Bank')
                    ->placeholder('-'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status Verifikasi')
                    ->options([
                        'verified' => 'Terverifikasi (Lunas)',
                        'pending' => 'Menunggu Verifikasi',
                        'rejected' => 'Ditolak',
                    ]),
                SelectFilter::make('payment_method')
                    ->label('Metode Bayar')
                    ->options([
                        'cash' => 'Tunai Kasir',
                        'wallet' => 'Dompet KTS',
                        'bank_transfer' => 'Transfer Bank',
                        'qris' => 'QRIS',
                    ]),
            ])
            ->actions([
                // Verifikasi Bukti Transfer Bank
                Actions\Action::make('verify')
                    ->label('Setujui (Approve)')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (Payment $record): bool => $record->status === 'pending')
                    ->requiresConfirmation()
                    ->action(function (Payment $record, FinanceService $financeService) {
                        $financeService->verifyBankTransfer($record->id, true, auth()->id());
                        Notification::make()->title('Pembayaran berhasil diverifikasi')->success()->send();
                    }),

                // Tolak Bukti Transfer
                Actions\Action::make('reject')
                    ->label('Tolak (Reject)')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (Payment $record): bool => $record->status === 'pending')
                    ->form([
                        Textarea::make('reason')
                            ->label('Alasan Penolakan')
                            ->placeholder('Contoh: Mutasi bank tidak ditemukan / nominal kurang')
                            ->required(),
                    ])
                    ->action(function (Payment $record, array $data, FinanceService $financeService) {
                        $financeService->verifyBankTransfer($record->id, false, auth()->id(), $data['reason']);
                        Notification::make()->title('Pembayaran ditolak')->warning()->send();
                    }),

                // Lihat Bukti Transfer (disajikan dari disk privat lewat rute bergerbang)
                Actions\Action::make('viewProof')
                    ->label('Bukti Transfer')
                    ->icon('heroicon-o-paper-clip')
                    ->color('warning')
                    ->visible(fn (Payment $record): bool => filled($record->proof_document))
                    ->url(fn (Payment $record): string => route('finance.proof', $record->hash_id))
                    ->openUrlInNewTab(),

                // Cetak Kuitansi Resmi
                Actions\Action::make('print')
                    ->label('Kuitansi')
                    ->icon('heroicon-o-printer')
                    ->color('gray')
                    ->url(fn (Payment $record): string => route('finance.receipt', $record->hash_id))
                    ->openUrlInNewTab(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManagePayments::route('/'),
        ];
    }
}
