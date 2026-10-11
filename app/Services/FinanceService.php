<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\FeeCategory;
use App\Models\Payment;
use App\Models\StudentBill;
use App\Models\StudentProfile;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class FinanceService
{
    public function __construct(
        protected WalletService $walletService
    ) {}

    /**
     * Generate tagihan massal untuk rombel siswa tertentu.
     */
    public function generateBatchBills(
        int $academicYearId,
        int $feeCategoryId,
        array $classroomIds,
        ?int $month,
        int $year,
        float $amount,
        ?string $dueDate = null
    ): int {
        $category = FeeCategory::findOrFail($feeCategoryId);
        $academicYear = AcademicYear::findOrFail($academicYearId);

        $students = StudentProfile::whereIn('classroom_id', $classroomIds)->get();
        $generatedCount = 0;

        DB::transaction(function () use ($students, $category, $academicYear, $month, $year, $amount, $dueDate, &$generatedCount) {
            foreach ($students as $student) {
                // Cek duplikasi tagihan untuk periode dan kategori yang sama
                $existsQuery = StudentBill::where('student_profile_id', $student->id)
                    ->where('fee_category_id', $category->id)
                    ->where('academic_year_id', $academicYear->id)
                    ->where('year', $year);

                if ($month !== null) {
                    $existsQuery->where('month', $month);
                } else {
                    $existsQuery->whereNull('month');
                }

                if ($existsQuery->exists()) {
                    continue;
                }

                $monthNames = [
                    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
                    5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
                    9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
                ];
                $periodText = $month ? ($monthNames[$month] ?? "Bulan $month") . " $year" : "Tahun $year";
                $title = "{$category->name} - {$periodText}";

                $billNumber = 'INV/' . date('Ym') . '/' . strtoupper(Str::random(6));

                StudentBill::create([
                    'student_profile_id' => $student->id,
                    'fee_category_id' => $category->id,
                    'academic_year_id' => $academicYear->id,
                    'bill_number' => $billNumber,
                    'title' => $title,
                    'month' => $month,
                    'year' => $year,
                    'amount' => $amount,
                    'paid_amount' => 0,
                    'status' => 'unpaid',
                    'due_date' => $dueDate ?? now()->addDays(15)->toDateString(),
                ]);

                $generatedCount++;
            }
        });

        return $generatedCount;
    }

    /**
     * Proses pembayaran tagihan siswa (Tunai, Transfer, QRIS, atau Dompet Digital).
     *
     * @param array $billAllocations [ ['bill_id' => 1, 'amount' => 350000], ... ]
     */
    public function processPayment(
        int $studentProfileId,
        array $billAllocations,
        string $paymentMethod,
        float $totalAmount,
        int $userId,
        ?string $referenceNumber = null,
        ?string $proofDocument = null,
        ?string $pin = null,
        ?string $notes = null
    ): Payment {
        return DB::transaction(function () use (
            $studentProfileId,
            $billAllocations,
            $paymentMethod,
            $totalAmount,
            $userId,
            $referenceNumber,
            $proofDocument,
            $pin,
            $notes
        ) {
            $student = StudentProfile::findOrFail($studentProfileId);
            $paymentNumber = 'PAY/' . date('Ymd') . '/' . strtoupper(Str::random(6));

            // Jika metode pembayaran menggunakan dompet digital sekolah
            if ($paymentMethod === 'wallet') {
                if (empty($pin)) {
                    throw new Exception('PIN Dompet Digital 6-digit wajib dimasukkan.');
                }
                $wallet = $this->walletService->getOrCreateWallet($student->id);
                $this->walletService->deductForBillPayment(
                    $wallet->id,
                    $totalAmount,
                    $pin,
                    "Pembayaran Tagihan Sekolah No. {$paymentNumber}"
                );
            }

            $status = ($paymentMethod === 'bank_transfer' && !empty($proofDocument)) ? 'pending' : 'verified';

            $payment = Payment::create([
                'payment_number' => $paymentNumber,
                'student_profile_id' => $student->id,
                'user_id' => $userId,
                'amount' => $totalAmount,
                'payment_method' => $paymentMethod,
                'payment_date' => now(),
                'reference_number' => $referenceNumber,
                'proof_document' => $proofDocument,
                'status' => $status,
                'verified_by_user_id' => $status === 'verified' ? $userId : null,
                'verified_at' => $status === 'verified' ? now() : null,
                'notes' => $notes,
            ]);

            // Alokasikan ke masing-masing tagihan
            foreach ($billAllocations as $alloc) {
                $bill = StudentBill::findOrFail($alloc['bill_id']);
                $allocatedAmount = (float) $alloc['amount'];

                $payment->bills()->attach($bill->id, [
                    'amount_allocated' => $allocatedAmount,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // Jika status langsung terverifikasi, update tagihan
                if ($status === 'verified') {
                    $newPaidAmount = (float) $bill->paid_amount + $allocatedAmount;
                    $billStatus = ($newPaidAmount >= (float) $bill->amount) ? 'paid' : 'partial';

                    $bill->update([
                        'paid_amount' => $newPaidAmount,
                        'status' => $billStatus,
                    ]);
                }
            }

            return $payment->load(['bills', 'studentProfile.user', 'studentProfile.classroom']);
        });
    }

    /**
     * Verifikasi bukti transfer bank manual (Approval/Rejection).
     */
    public function verifyBankTransfer(
        int $paymentId,
        bool $approved,
        int $verifierUserId,
        ?string $rejectionReason = null
    ): Payment {
        return DB::transaction(function () use ($paymentId, $approved, $verifierUserId, $rejectionReason) {
            $payment = Payment::with('billPayments.studentBill')->findOrFail($paymentId);

            if ($payment->status !== 'pending') {
                throw new Exception('Hanya pembayaran berstatus Menunggu Verifikasi yang dapat diverifikasi.');
            }

            if ($approved) {
                $payment->update([
                    'status' => 'verified',
                    'verified_by_user_id' => $verifierUserId,
                    'verified_at' => now(),
                ]);

                // Update tagihan siswa
                foreach ($payment->billPayments as $bp) {
                    $bill = $bp->studentBill;
                    $newPaidAmount = (float) $bill->paid_amount + (float) $bp->amount_allocated;
                    $billStatus = ($newPaidAmount >= (float) $bill->amount) ? 'paid' : 'partial';

                    $bill->update([
                        'paid_amount' => $newPaidAmount,
                        'status' => $billStatus,
                    ]);
                }
            } else {
                $payment->update([
                    'status' => 'rejected',
                    'verified_by_user_id' => $verifierUserId,
                    'verified_at' => now(),
                    'notes' => $rejectionReason ? "Ditolak: {$rejectionReason}" : 'Pembayaran ditolak verifikator.',
                ]);
            }

            return $payment;
        });
    }

    /**
     * Konversi angka nominal ke kata bahasa Indonesia (tanpa kata Rupiah).
     */
    public static function penyebut(float|int $angka): string
    {
        $angka = abs($angka);
        $baca = ['', 'Satu', 'Dua', 'Tiga', 'Empat', 'Lima', 'Enam', 'Tujuh', 'Delapan', 'Sembilan', 'Sepuluh', 'Sebelas'];
        $terbilang = '';

        if ($angka < 12) {
            $terbilang = ' ' . $baca[(int) $angka];
        } elseif ($angka < 20) {
            $terbilang = self::penyebut($angka - 10) . ' Belas';
        } elseif ($angka < 100) {
            $terbilang = self::penyebut((int) ($angka / 10)) . ' Puluh' . self::penyebut($angka % 10);
        } elseif ($angka < 200) {
            $terbilang = ' Seratus' . self::penyebut($angka - 100);
        } elseif ($angka < 1000) {
            $terbilang = self::penyebut((int) ($angka / 100)) . ' Ratus' . self::penyebut($angka % 100);
        } elseif ($angka < 2000) {
            $terbilang = ' Seribu' . self::penyebut($angka - 1000);
        } elseif ($angka < 1000000) {
            $terbilang = self::penyebut((int) ($angka / 1000)) . ' Ribu' . self::penyebut($angka % 1000);
        } elseif ($angka < 1000000000) {
            $terbilang = self::penyebut((int) ($angka / 1000000)) . ' Juta' . self::penyebut(fmod($angka, 1000000));
        } elseif ($angka < 1000000000000) {
            $terbilang = self::penyebut((int) ($angka / 1000000000)) . ' Miliar' . self::penyebut(fmod($angka, 1000000000));
        } else {
            $terbilang = self::penyebut((int) ($angka / 1000000000000)) . ' Triliun' . self::penyebut(fmod($angka, 1000000000000));
        }

        return $terbilang;
    }

    /**
     * Konversi angka nominal rupiah ke teks terbilang bahasa Indonesia.
     */
    public static function terbilang(float|int $angka): string
    {
        $hasil = trim(preg_replace('/\s+/', ' ', self::penyebut($angka)));
        return $hasil === '' ? 'Nol Rupiah' : $hasil . ' Rupiah';
    }
}
