<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\CanteenOrder;
use App\Models\StudentProfile;
use App\Models\StudentWallet;
use App\Models\WalletTransaction;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class WalletService
{
    /**
     * Dapatkan rekening dompet siswa atau buat baru jika belum ada.
     */
    public function getOrCreateWallet(int $studentProfileId): StudentWallet
    {
        $wallet = StudentWallet::where('student_profile_id', $studentProfileId)->first();

        if (! $wallet) {
            $student = StudentProfile::findOrFail($studentProfileId);
            $accountNumber = 'WAL-' . ($student->nisn ?? str_pad((string) $student->id, 8, '0', STR_PAD_LEFT));

            $wallet = StudentWallet::create([
                'student_profile_id' => $student->id,
                'account_number' => $accountNumber,
                'balance' => 0,
                'daily_limit' => 50000,
                'spent_today' => 0,
                'last_spent_date' => now()->toDateString(),
                'pin' => Hash::make('123456'), // Default PIN awal
                'is_active' => true,
            ]);
        }

        return $wallet;
    }

    /**
     * Setor Tunai Tabungan Siswa (Top Up) di Loket Kasir / Koperasi.
     */
    public function topup(
        int $walletId,
        float $amount,
        int $cashierUserId,
        string $description = 'Setor Tunai Tabungan Siswa'
    ): WalletTransaction {
        if ($amount <= 0) {
            throw new Exception('Nominal setor tunai harus lebih besar dari 0.');
        }

        return DB::transaction(function () use ($walletId, $amount, $cashierUserId, $description) {
            // Pessimistic locking
            $wallet = StudentWallet::lockForUpdate()->findOrFail($walletId);

            if (! $wallet->is_active) {
                throw new Exception('Rekening dompet sedang dibekukan: ' . ($wallet->locked_reason ?? 'Nonaktif'));
            }

            $balanceBefore = (float) $wallet->balance;
            $balanceAfter = $balanceBefore + $amount;

            $wallet->update([
                'balance' => $balanceAfter,
            ]);

            $trxNumber = 'TRX/TOP/' . date('Ymd') . '/' . strtoupper(Str::random(6));

            return WalletTransaction::create([
                'student_wallet_id' => $wallet->id,
                'transaction_number' => $trxNumber,
                'type' => 'topup',
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'cashier_user_id' => $cashierUserId,
                'description' => $description,
                'status' => 'success',
            ]);
        });
    }

    /**
     * Tarik Tunai Tabungan Siswa di Kasir / Koperasi dengan verifikasi PIN.
     */
    public function withdraw(
        int $walletId,
        float $amount,
        int $cashierUserId,
        string $pin,
        string $description = 'Tarik Tunai Tabungan Siswa'
    ): WalletTransaction {
        if ($amount <= 0) {
            throw new Exception('Nominal tarik tunai harus lebih besar dari 0.');
        }

        return DB::transaction(function () use ($walletId, $amount, $cashierUserId, $pin, $description) {
            $wallet = StudentWallet::lockForUpdate()->findOrFail($walletId);

            if (! $wallet->is_active) {
                throw new Exception('Rekening dompet sedang dibekukan: ' . ($wallet->locked_reason ?? 'Nonaktif'));
            }

            if (! $wallet->verifyPin($pin)) {
                throw new Exception('PIN Dompet 6-digit salah. Transaksi dibatalkan.');
            }

            $balanceBefore = (float) $wallet->balance;

            if ($balanceBefore < $amount) {
                throw new Exception('Saldo tabungan tidak mencukupi (Tersedia: Rp ' . number_format($balanceBefore, 0, ',', '.') . ').');
            }

            $balanceAfter = $balanceBefore - $amount;

            $wallet->update([
                'balance' => $balanceAfter,
            ]);

            $trxNumber = 'TRX/WTH/' . date('Ymd') . '/' . strtoupper(Str::random(6));

            return WalletTransaction::create([
                'student_wallet_id' => $wallet->id,
                'transaction_number' => $trxNumber,
                'type' => 'withdrawal',
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'cashier_user_id' => $cashierUserId,
                'description' => $description,
                'status' => 'success',
            ]);
        });
    }

    /**
     * Potong Saldo untuk Pembayaran Jajan di E-Kantin dengan batas harian (Daily Limit).
     */
    public function deductForCanteen(
        int $walletId,
        float $amount,
        string $pin,
        string $description,
        ?int $orderId = null
    ): WalletTransaction {
        if ($amount <= 0) {
            throw new Exception('Nominal jajan harus lebih besar dari 0.');
        }

        return DB::transaction(function () use ($walletId, $amount, $pin, $description, $orderId) {
            $wallet = StudentWallet::lockForUpdate()->findOrFail($walletId);

            if (! $wallet->is_active) {
                throw new Exception('Rekening dompet siswa sedang dibekukan.');
            }

            if (! $wallet->verifyPin($pin)) {
                throw new Exception('PIN Dompet 6-digit salah.');
            }

            $today = now()->toDateString();
            // last_spent_date di-cast 'date' sehingga bertipe Carbon; bandingkan sebagai string tanggal.
            $spentToday = ($wallet->last_spent_date?->toDateString() === $today) ? (float) $wallet->spent_today : 0.0;

            // Pengecekan limit harian jajan
            if (($spentToday + $amount) > (float) $wallet->daily_limit) {
                $sisaLimit = max(0, (float) $wallet->daily_limit - $spentToday);
                throw new Exception(
                    'Batas belanja harian terlampaui. Sisa batas jajan hari ini: Rp ' . number_format($sisaLimit, 0, ',', '.')
                );
            }

            $balanceBefore = (float) $wallet->balance;
            if ($balanceBefore < $amount) {
                throw new Exception(
                    'Saldo dompet tidak mencukupi (Saldo: Rp ' . number_format($balanceBefore, 0, ',', '.') . '). Silakan lakukan top up tabungan.'
                );
            }

            $balanceAfter = $balanceBefore - $amount;

            $wallet->update([
                'balance' => $balanceAfter,
                'spent_today' => $spentToday + $amount,
                'last_spent_date' => $today,
            ]);

            $trxNumber = 'TRX/CAN/' . date('Ymd') . '/' . strtoupper(Str::random(6));

            return WalletTransaction::create([
                'student_wallet_id' => $wallet->id,
                'transaction_number' => $trxNumber,
                'type' => 'purchase',
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'description' => $description,
                'reference_type' => CanteenOrder::class,
                'reference_id' => $orderId,
                'status' => 'success',
            ]);
        });
    }

    /**
     * Potong Saldo untuk Pembayaran Tagihan Sekolah (SPP dsb.).
     */
    public function deductForBillPayment(
        int $walletId,
        float $amount,
        string $pin,
        string $description
    ): WalletTransaction {
        if ($amount <= 0) {
            throw new Exception('Nominal pembayaran tagihan harus lebih besar dari 0.');
        }

        return DB::transaction(function () use ($walletId, $amount, $pin, $description) {
            $wallet = StudentWallet::lockForUpdate()->findOrFail($walletId);

            if (! $wallet->is_active) {
                throw new Exception('Rekening dompet siswa sedang dinonaktifkan.');
            }

            if (! $wallet->verifyPin($pin)) {
                throw new Exception('PIN Dompet 6-digit salah.');
            }

            $balanceBefore = (float) $wallet->balance;
            if ($balanceBefore < $amount) {
                throw new Exception('Saldo tabungan siswa tidak mencukupi untuk melunasi tagihan.');
            }

            $balanceAfter = $balanceBefore - $amount;

            $wallet->update([
                'balance' => $balanceAfter,
            ]);

            $trxNumber = 'TRX/BIL/' . date('Ymd') . '/' . strtoupper(Str::random(6));

            return WalletTransaction::create([
                'student_wallet_id' => $wallet->id,
                'transaction_number' => $trxNumber,
                'type' => 'bill_payment',
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'description' => $description,
                'status' => 'success',
            ]);
        });
    }

    /**
     * Refund saldo jajan jika pesanan kantin dibatalkan.
     */
    public function refund(
        int $walletId,
        float $amount,
        string $description,
        ?int $orderId = null
    ): WalletTransaction {
        return DB::transaction(function () use ($walletId, $amount, $description, $orderId) {
            $wallet = StudentWallet::lockForUpdate()->findOrFail($walletId);

            $balanceBefore = (float) $wallet->balance;
            $balanceAfter = $balanceBefore + $amount;

            // Kurangi spent_today jika tanggal sama
            $today = now()->toDateString();
            $spentToday = ($wallet->last_spent_date?->toDateString() === $today)
                ? max(0, (float) $wallet->spent_today - $amount)
                : (float) $wallet->spent_today;

            $wallet->update([
                'balance' => $balanceAfter,
                'spent_today' => $spentToday,
            ]);

            $trxNumber = 'TRX/REF/' . date('Ymd') . '/' . strtoupper(Str::random(6));

            return WalletTransaction::create([
                'student_wallet_id' => $wallet->id,
                'transaction_number' => $trxNumber,
                'type' => 'refund',
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'description' => $description,
                'reference_type' => CanteenOrder::class,
                'reference_id' => $orderId,
                'status' => 'success',
            ]);
        });
    }
}
