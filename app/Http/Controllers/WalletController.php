<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\StudentProfile;
use App\Models\StudentWallet;
use App\Services\WalletService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WalletController extends Controller
{
    public function __construct(
        protected WalletService $walletService
    ) {}

    /**
     * Tampilan Rekening Tabungan & Dompet Siswa.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        // Cari profil siswa: apakah pengguna adalah siswa atau orang tua
        $student = StudentProfile::with('user', 'classroom.department')
            ->where(function ($query) use ($user) {
                $query->where('user_id', $user->id)
                    ->orWhere('parent_user_id', $user->id);
            })
            ->first();

        $wallet = null;
        $transactions = [];

        if ($student) {
            $wallet = $this->walletService->getOrCreateWallet($student->id);
            $transactions = $wallet->transactions()->take(25)->get()->map(function ($trx) {
                return [
                    'id' => $trx->id,
                    'transaction_number' => $trx->transaction_number,
                    'type' => $trx->type,
                    'amount' => (float) $trx->amount,
                    'balance_after' => (float) $trx->balance_after,
                    'description' => $trx->description,
                    'created_at' => $trx->created_at->format('d M Y, H:i'),
                ];
            });
        }

        $today = now()->toDateString();
        $spentToday = ($wallet && $wallet->last_spent_date?->toDateString() === $today) ? (float) $wallet->spent_today : 0.0;
        $dailyLimit = $wallet ? (float) $wallet->daily_limit : 50000.0;
        $remainingLimit = max(0, $dailyLimit - $spentToday);

        return Inertia::render('Wallet/Index', [
            'student' => $student ? [
                'name' => $student->full_name,
                'nisn' => $student->nisn,
                'classroom' => $student->classroom?->name ?? '-',
                'rfid_uid' => $student->rfid_uid,
            ] : null,
            'wallet' => $wallet ? [
                'id' => $wallet->id,
                'hash_id' => $wallet->hash_id,
                'account_number' => $wallet->account_number,
                'balance' => (float) $wallet->balance,
                'daily_limit' => $dailyLimit,
                'spent_today' => $spentToday,
                'remaining_limit' => $remainingLimit,
                'is_active' => (bool) $wallet->is_active,
            ] : null,
            'transactions' => $transactions,
        ]);
    }

    /**
     * Setor Tunai Tabungan Siswa (Top Up) oleh Kasir.
     */
    public function topup(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'wallet_id' => 'required|integer|exists:student_wallets,id',
            'amount' => 'required|numeric|min:5000',
            'description' => 'nullable|string|max:255',
        ]);

        try {
            $trx = $this->walletService->topup(
                (int) $validated['wallet_id'],
                (float) $validated['amount'],
                (int) $request->user()->id,
                $validated['description'] ?? 'Setor Tunai Tabungan Siswa'
            );

            return response()->json([
                'success' => true,
                'message' => 'Setor tunai berhasil dicatat.',
                'transaction' => $trx,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Tarik Tunai Tabungan Siswa dengan Verifikasi PIN oleh Kasir.
     */
    public function withdraw(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'wallet_id' => 'required|integer|exists:student_wallets,id',
            'amount' => 'required|numeric|min:5000',
            'pin' => 'required|string|size:6',
            'description' => 'nullable|string|max:255',
        ]);

        try {
            $trx = $this->walletService->withdraw(
                (int) $validated['wallet_id'],
                (float) $validated['amount'],
                (int) $request->user()->id,
                $validated['pin'],
                $validated['description'] ?? 'Tarik Tunai Tabungan Siswa'
            );

            return response()->json([
                'success' => true,
                'message' => 'Tarik tunai berhasil dicatat.',
                'transaction' => $trx,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Ubah 6-Digit PIN Dompet Siswa.
     */
    public function changePin(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'current_pin' => 'required|string|size:6',
            'new_pin' => 'required|string|size:6|different:current_pin',
        ]);

        // Dompet diturunkan dari pengguna yang login, bukan dari input `wallet_id`.
        // Sebelumnya siapa pun dapat mengirim wallet_id milik siswa lain.
        $user = $request->user();

        $student = StudentProfile::where(function ($query) use ($user) {
            $query->where('user_id', $user->id)
                ->orWhere('parent_user_id', $user->id);
        })->first();

        if (! $student) {
            return response()->json([
                'success' => false,
                'message' => 'Akun Anda tidak terhubung dengan rekening tabungan siswa mana pun.',
            ], 403);
        }

        $wallet = StudentWallet::where('student_profile_id', $student->id)->first();

        if (! $wallet) {
            return response()->json([
                'success' => false,
                'message' => 'Rekening tabungan siswa belum dibuat.',
            ], 404);
        }

        if (! $wallet->verifyPin($validated['current_pin'])) {
            return response()->json([
                'success' => false,
                'message' => 'PIN lama salah.',
            ], 422);
        }

        $wallet->setPin($validated['new_pin']);

        return response()->json([
            'success' => true,
            'message' => 'PIN 6-digit berhasil diperbarui dengan aman.',
        ]);
    }
}
